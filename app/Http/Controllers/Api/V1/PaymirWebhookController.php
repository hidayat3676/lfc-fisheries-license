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
        if (empty($payload)) {
            return response()->json([
                'responseStatus' => false,
                'responseMessage' => 'No data found.',
            ]);
        }

        $headerKey = $request->header('PaymentKey');
        if ($headerKey !== null && $headerKey !== $paymir->paymentKey()) {
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
        }

        $application = $voucher?->application;
        if (! $application && $dptPaymentId !== '') {
            $application = Application::query()
                ->where('payment_reference', $dptPaymentId)
                ->orWhere('psid_code', $dptPaymentId)
                ->orWhere('application_no', $dptPaymentId)
                ->latest()
                ->first();
        }

        if (! $voucher && $application && Schema::hasTable('vouchers')) {
            $voucher = $application->vouchers()->latest()->first();
        }

        $isPaid = in_array(strtolower($status), ['200', 'paid', 'success', 'successful', '1', 'true', 'verified'], true);

        if ($isPaid) {
            $voucherAttributes = [
                'status_code' => 1,
                'status' => Voucher::STATUS_PAID,
                'status_msg' => 'Paid',
                'synching_status' => 'Synched',
                'paid_at' => now(),
            ];

            Log::info('Payment successfull!', ['voucher' => $voucher]);

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
            }

            if ($application) {
                if (Schema::hasTable('vouchers')) {
                    if ($application->vouchers()->exists()) {
                        $application->vouchers()->update($voucherAttributes);
                    } elseif (! $voucher) {
                        $application->loadMissing(['user.citizenProfile', 'category']);
                        Voucher::query()->create(array_merge([
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
                    }
                }

                $application->update([
                    'payment_reference' => $transactionId ?: ($dptPaymentId ?: $application->payment_reference),
                ]);

                $application->payments()->update([
                    'status' => 'verified',
                    'reference' => $transactionId ?: ($dptPaymentId ?: $application->payment_reference),
                    'paid_at' => now(),
                    'verified_at' => now(),
                ]);

                $remarks = $transactionRemarks !== ''
                    ? 'Auto-verified via PAYMIR payment confirmation ('.$transactionRemarks.').'
                    : 'Auto-verified via PAYMIR payment confirmation.';

                $workflow->approve($application, null, $remarks);
            }
        }

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
