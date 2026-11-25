<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Voucher;
use App\Services\ApplicationWorkflowService;
use App\Services\PaymirService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PaymirController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $statusFilter = trim((string) $request->query('status', ''));

        $stats = [
            'total' => 0,
            'pending' => 0,
            'paid' => 0,
            'cancelled' => 0,
        ];

        if (Schema::hasTable('vouchers')) {
            $stats = [
                'total' => Voucher::query()->count(),
                'pending' => Voucher::query()->where('status', Voucher::STATUS_PENDING)->count(),
                'paid' => Voucher::query()->where('status', Voucher::STATUS_PAID)->count(),
                'cancelled' => Voucher::query()->where('status', Voucher::STATUS_CANCELLED)->count(),
            ];

            $query = Voucher::query()
                ->with(['application.reservoir.district', 'application.category', 'user'])
                ->latest();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('dpt_payment_id', 'like', "%{$search}%")
                        ->orWhere('psid', 'like', "%{$search}%")
                        ->orWhere('tracking_number', 'like', "%{$search}%")
                        ->orWhere('citizen_cnic', 'like', "%{$search}%")
                        ->orWhere('transaction_id', 'like', "%{$search}%");
                });
            }

            if ($statusFilter !== '') {
                $query->where('status', $statusFilter);
            }

            $vouchers = $query->paginate(15)->withQueryString();
        } else {
            $vouchers = new LengthAwarePaginator([], 0, 15, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
        }

        return view('admin.paymir.index', [
            'vouchers' => $vouchers,
            'stats' => $stats,
            'search' => $search,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * PAYMIR API 1: Voucher Tracking (/api/Voucher/VoucherTrace)
     */
    public function trace(
        Request $request,
        PaymirService $paymir,
        ApplicationWorkflowService $workflow
    ): RedirectResponse {
        $validated = $request->validate([
            'dpt_payment_id' => ['required', 'string', 'max:120'],
        ]);

        $dptPaymentId = trim($validated['dpt_payment_id']);
        $result = $paymir->traceVoucher($dptPaymentId);

        $data = (array) ($result['Data'] ?? $result['data'] ?? []);
        $tracedStatus = trim((string) ($data['Status'] ?? $data['status'] ?? ''));

        if (Schema::hasTable('vouchers')) {
            $voucher = Voucher::query()
                ->where('dpt_payment_id', $dptPaymentId)
                ->orWhere('psid', $dptPaymentId)
                ->latest()
                ->first();

            if ($voucher) {
                $voucherUpdates = [];

                if (! empty($data['PSID'])) {
                    $voucherUpdates['psid'] = (string) $data['PSID'];
                }
                if (isset($data['SynchingStatus'])) {
                    $voucherUpdates['synching_status'] = (string) $data['SynchingStatus'];
                }
                if (! empty($data['TrxNo'])) {
                    $voucherUpdates['transaction_id'] = (string) $data['TrxNo'];
                }
                if (! empty($data['TrxDate'])) {
                    $voucherUpdates['transaction_date'] = (string) $data['TrxDate'];
                }

                if (strcasecmp($tracedStatus, 'Paid') === 0) {
                    $voucherUpdates['status'] = Voucher::STATUS_PAID;
                    $voucherUpdates['status_code'] = 1;
                    $voucherUpdates['status_msg'] = 'Paid';
                    $voucherUpdates['paid_at'] = $voucher->paid_at ?? now();

                    if ($voucher->application) {
                        $voucher->application->update([
                            'payment_reference' => $voucherUpdates['transaction_id'] ?? $voucher->dpt_payment_id,
                        ]);

                        $voucher->application->payments()->update([
                            'status' => 'verified',
                            'reference' => $voucherUpdates['transaction_id'] ?? $voucher->dpt_payment_id,
                            'paid_at' => now(),
                            'verified_at' => now(),
                        ]);

                        $workflow->approve(
                            $voucher->application,
                            $request->user(),
                            'Auto-verified via PAYMIR voucher tracking.'
                        );
                    }
                } elseif (strcasecmp($tracedStatus, 'Cancelled') === 0 || strcasecmp($tracedStatus, 'Canceled') === 0) {
                    $voucherUpdates['status'] = Voucher::STATUS_CANCELLED;
                    $voucherUpdates['status_msg'] = $tracedStatus;
                }

                if (! empty($voucherUpdates)) {
                    $voucher->update($voucherUpdates);
                }
            }
        }

        $responseMsg = (string) ($result['ResponseMessage'] ?? $result['responseMessage'] ?? 'Voucher trace request completed.');

        return redirect()
            ->route('admin.paymir.index', $request->only(['q', 'status']))
            ->with('trace_result', $result)
            ->with('traced_dpt_id', $dptPaymentId)
            ->with('status', "PAYMIR Trace ({$dptPaymentId}): {$responseMsg}");
    }

    /**
     * PAYMIR API 2: Cancel Voucher (/api/Voucher/CancelVoucher)
     */
    public function cancel(Request $request, PaymirService $paymir): RedirectResponse
    {
        $validated = $request->validate([
            'dpt_payment_id' => ['required', 'string', 'max:120'],
        ]);

        $dptPaymentId = trim($validated['dpt_payment_id']);
        $result = $paymir->cancelVoucher($dptPaymentId);

        $responseStatus = $result['ResponseStatus'] ?? $result['responseStatus'] ?? false;
        $responseCode = (string) ($result['ResponseCode'] ?? $result['responseCode'] ?? '');
        $responseMsg = (string) ($result['ResponseMessage'] ?? $result['responseMessage'] ?? 'Voucher cancellation request completed.');

        $isSuccess = $responseStatus === true
            || strtolower((string) $responseStatus) === 'true'
            || $responseCode === '200';

        if ($isSuccess && Schema::hasTable('vouchers')) {
            $voucher = Voucher::query()
                ->where('dpt_payment_id', $dptPaymentId)
                ->latest()
                ->first();

            if ($voucher && $voucher->status !== Voucher::STATUS_PAID) {
                $voucher->update([
                    'status' => Voucher::STATUS_CANCELLED,
                    'status_msg' => $responseMsg ?: 'Voucher cancelled via Admin PAYMIR console',
                ]);
            }
        }

        return redirect()
            ->route('admin.paymir.index', $request->only(['q', 'status']))
            ->with('cancel_result', $result)
            ->with('cancelled_dpt_id', $dptPaymentId)
            ->with('status', "PAYMIR Cancel Voucher ({$dptPaymentId}): {$responseMsg}");
    }

    public function events(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'latest_event' => \Illuminate\Support\Facades\Cache::get('paymir_latest_paid_event'),
        ]);
    }
}

