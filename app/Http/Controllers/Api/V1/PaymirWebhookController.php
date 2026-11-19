<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Voucher;
use App\Services\ApplicationWorkflowService;
use App\Services\PaymirService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PaymirWebhookController extends Controller
{
    /**
     * Section 4.3: Payment Confirmation API (PUT /UpdatePaymentDetail)
     */
    public function updatePaymentDetail(
        Request $request,
        PaymirService $paymir,
        ApplicationWorkflowService $workflow
    ): JsonResponse {
        $payload = $request->all();

        Log::info('PAYMIR updatePaymentDetail: request received.', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'has_payment_key_header' => $request->hasHeader('PaymentKey'),
            'payload' => $payload,
        ]);

        if (empty($payload)) {
            Log::warning('PAYMIR updatePaymentDetail: empty payload received.', [
                'ip' => $request->ip(),
                'raw_body' => $request->getContent(),
            ]);

            return response()->json([
                'responseStatus' => false,
                'responseMessage' => 'No data found.',
            ]);
        }

        $headerKey = $request->header('PaymentKey');
        if ($headerKey !== null && $headerKey !== $paymir->paymentKey()) {
            Log::warning('PAYMIR updatePaymentDetail: invalid PaymentKey header.', [
                'ip' => $request->ip(),
                'received_payment_key' => $headerKey,
            ]);

            return response()->json([
                'responseStatus' => false,
                'responseMessage' => 'Invalid credentials.',
            ], 401);
        }

        $arcId = $payload['ArcId'] ?? $payload['arcid'] ?? $payload['arc_id'] ?? null;
        $dptPaymentId = trim((string) ($payload['DptPaymentID'] ?? $payload['dptPaymentID'] ?? $payload['DPTPaymentID'] ?? $payload['dpt_payment_id'] ?? ''));
        $status = trim((string) ($payload['Status'] ?? $payload['status'] ?? ''));
        $transactionId = trim((string) ($payload['TransactionId'] ?? $payload['transactionId'] ?? $payload['TrxNo'] ?? $payload['trxNo'] ?? ''));
        $transactionAmount = $payload['TransactionAmount'] ?? $payload['transactionAmount'] ?? null;
        $transactionDate = trim((string) ($payload['TransactionDate'] ?? $payload['transactionDate'] ?? $payload['TrxDate'] ?? $payload['trxDate'] ?? ''));
        $transactionRemarks = trim((string) ($payload['TransactionRemarks'] ?? $payload['transactionRemarks'] ?? ''));

        if ($dptPaymentId === '' && $arcId === null) {
            Log::warning('PAYMIR updatePaymentDetail: missing both DptPaymentID and ArcId.', [
                'payload' => $payload,
            ]);

            return response()->json([
                'responseStatus' => false,
                'responseMessage' => 'No data found.',
            ]);
        }

        $voucher = null;
        if (Schema::hasTable('vouchers')) {
            $voucher = Voucher::query()
                ->where(function ($q) use ($dptPaymentId, $arcId) {
                    if ($dptPaymentId !== '') {
                        $q->where('dpt_payment_id', $dptPaymentId)
                            ->orWhere('psid', $dptPaymentId)
                            ->orWhere('tracking_number', $dptPaymentId);
                    }
                    if ($arcId !== null && $arcId !== '') {
                        $q->orWhere('arc_id', (int) $arcId);
                    }
                })
                ->latest()
                ->first();

            if ($voucher) {
                Log::info('PAYMIR updatePaymentDetail: voucher found.', [
                    'voucher_id' => $voucher->id,
                    'dpt_payment_id' => $voucher->dpt_payment_id,
                    'psid' => $voucher->psid,
                    'application_id' => $voucher->application_id,
                    'current_status' => $voucher->status,
                ]);
            } else {
                Log::warning('PAYMIR updatePaymentDetail: no voucher matched initial lookup.', [
                    'dpt_payment_id' => $dptPaymentId,
                    'arc_id' => $arcId,
                ]);
            }
        } else {
            Log::warning('PAYMIR updatePaymentDetail: vouchers table does not exist.');
        }

        $application = $voucher?->application;
        if ($application) {
            Log::info('PAYMIR updatePaymentDetail: application resolved via voucher.', [
                'application_id' => $application->id,
                'application_no' => $application->application_no,
                'current_status' => $application->status,
            ]);
        } elseif ($dptPaymentId !== '') {
            $application = Application::query()
                ->where('payment_reference', $dptPaymentId)
                ->orWhere('psid_code', $dptPaymentId)
                ->orWhere('application_no', $dptPaymentId)
                ->latest()
                ->first();

            if ($application) {
                Log::info('PAYMIR updatePaymentDetail: application resolved via direct lookup.', [
                    'application_id' => $application->id,
                    'application_no' => $application->application_no,
                    'current_status' => $application->status,
                    'dpt_payment_id' => $dptPaymentId,
                ]);
            } else {
                Log::warning('PAYMIR updatePaymentDetail: no application matched DptPaymentID.', [
                    'dpt_payment_id' => $dptPaymentId,
                    'arc_id' => $arcId,
                ]);
            }
        }

        if (! $voucher && $application && Schema::hasTable('vouchers')) {
            $voucher = $application->vouchers()->latest()->first();
            if ($voucher) {
                Log::info('PAYMIR updatePaymentDetail: voucher resolved from matched application.', [
                    'voucher_id' => $voucher->id,
                    'application_id' => $application->id,
                ]);
            }
        }

        $isPaid = in_array(strtolower($status), ['200', 'paid', 'success', 'successful', '1', 'true', 'verified'], true);

        if ($isPaid) {
            Log::info('PAYMIR updatePaymentDetail: paid status confirmed, processing updates.', [
                'status' => $status,
                'dpt_payment_id' => $dptPaymentId,
                'arc_id' => $arcId,
                'transaction_id' => $transactionId,
                'transaction_amount' => $transactionAmount,
                'transaction_date' => $transactionDate,
            ]);

            $voucherAttributes = [
                'status_code' => 1,
                'status' => Voucher::STATUS_PAID,
                'status_msg' => 'Paid',
                'synching_status' => 'Synched',
                'paid_at' => now(),
            ];

            if ($arcId !== null && $arcId !== '') {
                $voucherAttributes['arc_id'] = (int) $arcId;
            }
            if ($transactionId !== '') {
                $voucherAttributes['transaction_id'] = $transactionId;
            }
            if (is_numeric($transactionAmount)) {
                $voucherAttributes['transaction_amount'] = (float) $transactionAmount;
            }
            if ($transactionDate !== '') {
                $voucherAttributes['transaction_date'] = $transactionDate;
            }
            if ($transactionRemarks !== '') {
                $voucherAttributes['remarks'] = $transactionRemarks;
            }

            if ($voucher) {
                $voucher->update($voucherAttributes);
                Log::info('PAYMIR updatePaymentDetail: voucher marked as paid.', [
                    'voucher_id' => $voucher->id,
                    'dpt_payment_id' => $voucher->dpt_payment_id,
                    'status' => $voucher->status,
                ]);
            }

            if ($application) {
                if (Schema::hasTable('vouchers')) {
                    if ($application->vouchers()->exists()) {
                        $updatedCount = $application->vouchers()->update($voucherAttributes);
                        Log::info('PAYMIR updatePaymentDetail: updated application vouchers to paid.', [
                            'application_id' => $application->id,
                            'updated_vouchers_count' => $updatedCount,
                        ]);
                    } elseif (! $voucher) {
                        $application->loadMissing(['user.citizenProfile', 'category']);
                        $createdVoucher = Voucher::query()->create(array_merge([
                            'application_id' => $application->id,
                            'user_id' => $application->user_id,
                            'dpt_payment_id' => $dptPaymentId ?: $paymir->buildDptPaymentId($application),
                            'tracking_number' => $application->application_no,
                            'psid' => $application->psid_code,
                            'citizen_cnic' => $application->user?->citizenProfile?->cnic,
                            'department_name' => (string) config('rflms.paymir.department_name', 'Directorate of Fisheries'),
                            'service_name' => (string) config('rflms.paymir.service_name', 'Fishing E-License'),
                            'service_type_name' => $application->category?->name ?? 'Fishing E-License',
                            'service_key' => $paymir->serviceCode(),
                            'fee_amount' => $application->fee_amount_snapshot ?? 0,
                        ], $voucherAttributes));

                        Log::info('PAYMIR updatePaymentDetail: created missing paid voucher record for application.', [
                            'voucher_id' => $createdVoucher->id,
                            'application_id' => $application->id,
                            'dpt_payment_id' => $createdVoucher->dpt_payment_id,
                        ]);
                    }
                }

                $application->update([
                    'payment_reference' => $transactionId ?: ($dptPaymentId ?: $application->payment_reference),
                ]);

                $updatedPaymentsCount = $application->payments()->update([
                    'status' => 'verified',
                    'reference' => $transactionId ?: ($dptPaymentId ?: $application->payment_reference),
                    'paid_at' => now(),
                    'verified_at' => now(),
                ]);

                Log::info('PAYMIR updatePaymentDetail: application payment ledger marked as verified.', [
                    'application_id' => $application->id,
                    'updated_payments_count' => $updatedPaymentsCount,
                ]);

                $remarks = $transactionRemarks !== ''
                    ? 'Auto-verified via PAYMIR payment confirmation ('.$transactionRemarks.').'
                    : 'Auto-verified via PAYMIR payment confirmation.';

                $license = $workflow->approve($application, null, $remarks);

                Log::info('PAYMIR updatePaymentDetail: application auto-approved and license issued.', [
                    'application_id' => $application->id,
                    'application_no' => $application->application_no,
                    'license_id' => $license->id,
                    'license_no' => $license->license_no,
                ]);
            } elseif (! $voucher) {
                Log::warning('PAYMIR updatePaymentDetail: paid status received but neither voucher nor application was found in database.', [
                    'dpt_payment_id' => $dptPaymentId,
                    'arc_id' => $arcId,
                    'transaction_id' => $transactionId,
                ]);
            }
        } else {
            Log::warning('PAYMIR updatePaymentDetail: non-paid status received, skipping auto-verification.', [
                'status' => $status,
                'dpt_payment_id' => $dptPaymentId,
                'arc_id' => $arcId,
                'voucher_id' => $voucher?->id,
                'application_id' => $application?->id,
            ]);
        }

        Log::info('PAYMIR updatePaymentDetail: returning success response.', [
            'ArcId' => (string) $arcId,
            'DptPaymentID' => $dptPaymentId,
            'is_paid' => $isPaid,
        ]);

        return response()->json([
            'responseStatus' => true,
            'responseMessage' => 'Received successfully',
            'ArcId' => (string) $arcId,
            'DptPaymentID' => $dptPaymentId,
        ]);
    }

    public function trace(Request $request, PaymirService $paymir): JsonResponse
    {
        $data = $request->validate([
            'DPTPaymentID' => ['required', 'string'],
        ]);

        return response()->json($paymir->traceVoucher($data['DPTPaymentID']));
    }

    public function cancel(Request $request, PaymirService $paymir): JsonResponse
    {
        $data = $request->validate([
            'DPTPaymentID' => ['required', 'string'],
        ]);

        return response()->json($paymir->cancelVoucher($data['DPTPaymentID']));
    }
}
