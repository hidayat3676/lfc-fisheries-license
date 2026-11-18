<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Voucher;
use App\Services\PaymirService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PaymirWebhookController extends Controller
{
    /**
     * Section 4.3: Payment Confirmation API (PUT /UpdatePaymentDetail)
     */
    public function updatePaymentDetail(Request $request, PaymirService $paymir): JsonResponse
    {
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

        $arcId = $payload['ArcId'] ?? $payload['arcid'] ?? null;
        $dptPaymentId = (string) ($payload['DptPaymentID'] ?? $payload['dptPaymentID'] ?? '');
        $status = (string) ($payload['Status'] ?? '');
        $transactionId = (string) ($payload['TransactionId'] ?? '');
        $transactionAmount = $payload['TransactionAmount'] ?? null;
        $transactionDate = (string) ($payload['TransactionDate'] ?? '');
        $transactionRemarks = (string) ($payload['TransactionRemarks'] ?? '');

        if ($dptPaymentId === '') {
            return response()->json([
                'responseStatus' => false,
                'responseMessage' => 'No data found.',
            ]);
        }

        $voucher = null;
        if (Schema::hasTable('vouchers')) {
            $voucher = Voucher::query()
                ->where('dpt_payment_id', $dptPaymentId)
                ->orWhere(fn ($q) => $arcId !== null ? $q->where('arc_id', (int) $arcId) : $q)
                ->first();
        }

        $application = $voucher?->application ?? Application::query()
            ->where('payment_reference', $dptPaymentId)
            ->orWhere('psid_code', $dptPaymentId)
            ->first();

        if ($status === '200') {
            if ($voucher) {
                $voucher->update([
                    'arc_id' => $arcId !== null ? (int) $arcId : $voucher->arc_id,
                    'status_code' => 1,
                    'status' => Voucher::STATUS_PAID,
                    'status_msg' => 'Paid',
                    'transaction_id' => $transactionId ?: $voucher->transaction_id,
                    'transaction_amount' => is_numeric($transactionAmount) ? (float) $transactionAmount : $voucher->transaction_amount,
                    'transaction_date' => $transactionDate ?: $voucher->transaction_date,
                    'remarks' => $transactionRemarks ?: $voucher->remarks,
                    'synching_status' => 'Synched',
                    'paid_at' => now(),
                ]);
            }

            if ($application) {
                $application->payments()
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'verified',
                        'reference' => $transactionId ?: $dptPaymentId,
                        'verified_at' => now(),
                    ]);
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

