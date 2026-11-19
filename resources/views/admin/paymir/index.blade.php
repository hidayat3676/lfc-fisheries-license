@extends('layouts.admin')

@section('title', 'PAYMIR Vouchers & API Console — '.config('app.name'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="page-title-block mb-0">
        <div class="title-icon"><i class="bi bi-credit-card-2-front"></i></div>
        <div>
            <h1 class="h3 fw-bold mb-1">PAYMIR Vouchers &amp; API Console</h1>
            <p class="page-subtitle mb-0">
                Track real-time voucher payment status and cancel unpaid KP PAYMIR DPG vouchers.
                <span class="badge text-bg-light border ms-1">Service: {{ config('rflms.paymir.service_code', 'SRV-0070') }}</span>
            </p>
        </div>
    </div>
</div>

{{-- Summary KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="bg-white rounded-4 p-3 h-100 stat-card">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="stat-icon"><i class="bi bi-receipt"></i></span>
                <span class="stat-label mb-0">Total Vouchers</span>
            </div>
            <div class="stat-value">{{ number_format($stats['total']) }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="bg-white rounded-4 p-3 h-100 stat-card accent-orange">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
                <span class="stat-label mb-0">Pending Payment</span>
            </div>
            <div class="stat-value">{{ number_format($stats['pending']) }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="bg-white rounded-4 p-3 h-100 stat-card accent-blue">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
                <span class="stat-label mb-0">Paid / Verified</span>
            </div>
            <div class="stat-value">{{ number_format($stats['paid']) }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="bg-white rounded-4 p-3 h-100 stat-card accent-red">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="stat-icon"><i class="bi bi-x-octagon"></i></span>
                <span class="stat-label mb-0">Cancelled</span>
            </div>
            <div class="stat-value">{{ number_format($stats['cancelled']) }}</div>
        </div>
    </div>
</div>

{{-- Interactive PAYMIR API Console (1. Voucher Tracking & 2. Cancel Voucher) --}}
<div class="row g-4 mb-4">
    {{-- 1. Voucher Tracking --}}
    <div class="col-lg-6">
        <div class="content-shell h-100">
            <div class="panel-header">
                <span><i class="bi bi-search me-1"></i> 1. Voucher Tracking</span>
            </div>
            <div class="p-3 p-lg-4">
                <p class="small text-secondary mb-3">
                    Query KP PAYMIR DPG by <code>DPTPaymentID</code> to inspect real-time payment status, <code>PSID</code>, <code>TrxNo</code>, and <code>TrxDate</code>. If PAYMIR reports <strong>Paid</strong>, the local voucher and application payment status are synced automatically.
                </p>
                <form method="POST" action="{{ route('admin.paymir.trace') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-sm-8">
                        <label for="trace_dpt_payment_id" class="form-label small fw-semibold mb-1">DPTPaymentID</label>
                        <input
                            type="text"
                            id="trace_dpt_payment_id"
                            name="dpt_payment_id"
                            value="{{ session('traced_dpt_id', old('dpt_payment_id')) }}"
                            class="form-control form-control-sm font-monospace"
                            placeholder="e.g. ABCD-3009-26-000001"
                            required
                        >
                    </div>
                    <div class="col-sm-4">
                        <button type="submit" class="btn btn-rflms btn-sm w-100">
                            <i class="bi bi-arrow-repeat me-1"></i> Trace Voucher
                        </button>
                    </div>
                </form>

                @if (session('trace_result'))
                    @php
                        $traceResult = (array) session('trace_result');
                        $traceData = (array) ($traceResult['Data'] ?? $traceResult['data'] ?? []);
                        $traceOk = ($traceResult['ResponseStatus'] ?? $traceResult['responseStatus'] ?? false);
                        $traceOkBool = $traceOk === true || strtolower((string) $traceOk) === 'true' || ($traceResult['ResponseCode'] ?? '') === '200';
                    @endphp
                    <div class="mt-3 p-3 rounded-3 border {{ $traceOkBool ? 'bg-success-subtle border-success-subtle' : 'bg-light' }}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold small">
                                <i class="bi {{ $traceOkBool ? 'bi-check-circle-fill text-success' : 'bi-info-circle-fill text-secondary' }} me-1"></i>
                                Trace Result for <code>{{ session('traced_dpt_id') }}</code>
                            </span>
                            <span class="badge {{ $traceOkBool ? 'text-bg-success' : 'text-bg-secondary' }}">
                                Code: {{ $traceResult['ResponseCode'] ?? $traceResult['responseCode'] ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="small mb-2 text-secondary">
                            <strong>Message:</strong> {{ $traceResult['ResponseMessage'] ?? $traceResult['responseMessage'] ?? '—' }}
                        </div>

                        @if (! empty($traceData))
                            <div class="table-responsive bg-white rounded border mb-2">
                                <table class="table table-sm mb-0 small">
                                    <tbody>
                                        <tr>
                                            <th class="text-secondary" style="width: 38%;">DPTPaymentID</th>
                                            <td class="font-monospace">{{ $traceData['DPTPaymentID'] ?? $traceData['dptPaymentID'] ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-secondary">PSID</th>
                                            <td class="font-monospace fw-semibold">{{ $traceData['PSID'] ?? $traceData['psid'] ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-secondary">Payment Status</th>
                                            <td>
                                                @php $pStatus = (string) ($traceData['Status'] ?? $traceData['status'] ?? '—'); @endphp
                                                <span class="badge {{ strcasecmp($pStatus, 'Paid') === 0 ? 'text-bg-success' : 'text-bg-warning' }}">
                                                    {{ $pStatus }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="text-secondary">Synching Status</th>
                                            <td>{{ $traceData['SynchingStatus'] ?? $traceData['synchingStatus'] ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-secondary">Transaction No (TrxNo)</th>
                                            <td class="font-monospace">{{ $traceData['TrxNo'] ?? $traceData['trxNo'] ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-secondary">Transaction Date (TrxDate)</th>
                                            <td>{{ $traceData['TrxDate'] ?? $traceData['trxDate'] ?? '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <details class="small">
                            <summary class="text-secondary" style="cursor: pointer;">View raw JSON response</summary>
                            <pre class="bg-dark text-light p-2 rounded mt-2 mb-0 small" style="max-height: 180px; overflow: auto;">{{ json_encode($traceResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- 2. Cancel Voucher --}}
    <div class="col-lg-6">
        <div class="content-shell h-100">
            <div class="panel-header">
                <span><i class="bi bi-x-circle me-1"></i> 2. Cancel Voucher</span>
            </div>
            <div class="p-3 p-lg-4">
                <p class="small text-secondary mb-3">
                    Cancel an unpaid voucher in KP PAYMIR DPG by <code>DPTPaymentID</code>. Paid vouchers cannot be cancelled. PSID generation happens automatically during online licence application submission.
                </p>
                <form
                    method="POST"
                    action="{{ route('admin.paymir.cancel') }}"
                    class="row g-2 align-items-end"
                    onsubmit="return confirm('Are you sure you want to cancel this PAYMIR voucher?');"
                >
                    @csrf
                    <div class="col-sm-8">
                        <label for="cancel_dpt_payment_id" class="form-label small fw-semibold mb-1">DPTPaymentID</label>
                        <input
                            type="text"
                            id="cancel_dpt_payment_id"
                            name="dpt_payment_id"
                            value="{{ session('cancelled_dpt_id') }}"
                            class="form-control form-control-sm font-monospace"
                            placeholder="e.g. ABCD-3009-26-000001"
                            required
                        >
                    </div>
                    <div class="col-sm-4">
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                            <i class="bi bi-slash-circle me-1"></i> Cancel Voucher
                        </button>
                    </div>
                </form>

                @if (session('cancel_result'))
                    @php
                        $cancelResult = (array) session('cancel_result');
                        $cancelOk = ($cancelResult['ResponseStatus'] ?? $cancelResult['responseStatus'] ?? false);
                        $cancelOkBool = $cancelOk === true || strtolower((string) $cancelOk) === 'true' || ($cancelResult['ResponseCode'] ?? '') === '200';
                    @endphp
                    <div class="mt-3 p-3 rounded-3 border {{ $cancelOkBool ? 'bg-success-subtle border-success-subtle' : 'bg-danger-subtle border-danger-subtle' }}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold small">
                                <i class="bi {{ $cancelOkBool ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger' }} me-1"></i>
                                Cancellation Result for <code>{{ session('cancelled_dpt_id') }}</code>
                            </span>
                            <span class="badge {{ $cancelOkBool ? 'text-bg-success' : 'text-bg-danger' }}">
                                Code: {{ $cancelResult['ResponseCode'] ?? $cancelResult['responseCode'] ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="small mb-2">
                            <strong>Response Status:</strong>
                            <code>{{ is_bool($cancelOk) ? ($cancelOk ? 'true' : 'false') : $cancelOk }}</code>
                            <br>
                            <strong>Message:</strong> {{ $cancelResult['ResponseMessage'] ?? $cancelResult['responseMessage'] ?? '—' }}
                        </div>
                        <details class="small">
                            <summary class="text-secondary" style="cursor: pointer;">View raw JSON response</summary>
                            <pre class="bg-dark text-light p-2 rounded mt-2 mb-0 small" style="max-height: 180px; overflow: auto;">{{ json_encode($cancelResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Vouchers Table & Filter Bar --}}
<div class="content-shell">
    <div class="panel-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-table me-1"></i> PAYMIR Vouchers Ledger</span>
        <form method="GET" action="{{ route('admin.paymir.index') }}" class="d-flex flex-wrap align-items-center gap-2">
            <input
                type="text"
                name="q"
                value="{{ $search }}"
                class="form-control form-control-sm"
                style="min-width: 230px;"
                placeholder="Search DPT ID, PSID, App #, CNIC..."
            >
            <select name="status" class="form-select form-select-sm" style="width: auto;">
                <option value="">All statuses</option>
                <option value="pending" @selected($statusFilter === 'pending')>Pending</option>
                <option value="paid" @selected($statusFilter === 'paid')>Paid</option>
                <option value="cancelled" @selected($statusFilter === 'cancelled')>Cancelled</option>
            </select>
            <button type="submit" class="btn btn-sm btn-rflms">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
            @if ($search !== '' || $statusFilter !== '')
                <a href="{{ route('admin.paymir.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table mb-0 align-middle table-hover">
            <thead class="table-light small text-uppercase text-secondary">
                <tr>
                    <th>DPTPaymentID / PSID</th>
                    <th>Application</th>
                    <th>Citizen</th>
                    <th>Fee (PKR)</th>
                    <th>Status</th>
                    <th>Transaction Info</th>
                    <th class="text-end">PAYMIR API Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vouchers as $voucher)
                    <tr>
                        <td>
                            <div class="fw-semibold font-monospace small">{{ $voucher->dpt_payment_id }}</div>
                            <div class="small text-secondary">
                                PSID: <code class="fw-semibold">{{ $voucher->psid ?: '—' }}</code>
                            </div>
                        </td>
                        <td>
                            @if ($voucher->application)
                                <a href="{{ route('admin.applications.show', $voucher->application) }}" class="fw-semibold text-decoration-none">
                                    {{ $voucher->tracking_number ?: $voucher->application->application_no }}
                                </a>
                                <div class="small text-secondary">
                                    {{ $voucher->application->category?->name ?? $voucher->service_type_name }}
                                    @if ($voucher->application->reservoir?->district)
                                        · {{ $voucher->application->reservoir->district->name }}
                                    @endif
                                </div>
                            @else
                                <span class="font-monospace small">{{ $voucher->tracking_number ?: '—' }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="small fw-semibold">{{ $voucher->user?->name ?? 'Citizen' }}</div>
                            <div class="small text-secondary font-monospace">{{ $voucher->citizen_cnic ?: '—' }}</div>
                        </td>
                        <td>
                            <span class="fw-semibold">{{ number_format((float) $voucher->fee_amount, 0) }}</span>
                        </td>
                        <td>
                            @if ($voucher->status === \App\Models\Voucher::STATUS_PAID)
                                <span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Paid</span>
                            @elseif ($voucher->status === \App\Models\Voucher::STATUS_CANCELLED)
                                <span class="badge text-bg-danger"><i class="bi bi-x-circle me-1"></i>Cancelled</span>
                            @else
                                <span class="badge text-bg-warning"><i class="bi bi-clock-history me-1"></i>Pending</span>
                            @endif
                            @if ($voucher->status_msg)
                                <div class="small text-secondary mt-1">{{ $voucher->status_msg }}</div>
                            @endif
                        </td>
                        <td class="small">
                            @if ($voucher->transaction_id)
                                <div>Trx: <code class="font-monospace">{{ $voucher->transaction_id }}</code></div>
                            @endif
                            @if ($voucher->transaction_date || $voucher->paid_at)
                                <div class="text-secondary">{{ $voucher->transaction_date ?: $voucher->paid_at?->format('d M Y H:i') }}</div>
                            @elseif ($voucher->epay_expire_date)
                                <div class="text-secondary">Expires: {{ $voucher->epay_expire_date->format('d M Y') }}</div>
                            @else
                                <span class="text-secondary">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <form method="POST" action="{{ route('admin.paymir.trace', ['q' => $search, 'status' => $statusFilter]) }}">
                                    @csrf
                                    <input type="hidden" name="dpt_payment_id" value="{{ $voucher->dpt_payment_id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-primary btn-chip" title="Trace Voucher Status in PAYMIR">
                                        <i class="bi bi-search"></i> Trace
                                    </button>
                                </form>

                                @if ($voucher->status !== \App\Models\Voucher::STATUS_PAID && $voucher->status !== \App\Models\Voucher::STATUS_CANCELLED)
                                    <form
                                        method="POST"
                                        action="{{ route('admin.paymir.cancel', ['q' => $search, 'status' => $statusFilter]) }}"
                                        onsubmit="return confirm('Cancel PAYMIR voucher {{ $voucher->dpt_payment_id }}?');"
                                    >
                                        @csrf
                                        <input type="hidden" name="dpt_payment_id" value="{{ $voucher->dpt_payment_id }}">
                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-chip" title="Cancel Unpaid Voucher in PAYMIR">
                                            <i class="bi bi-x-circle"></i> Cancel
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state py-4 text-center text-secondary">
                            <i class="bi bi-receipt-cutoff fs-4 d-block mb-1"></i>
                            No PAYMIR vouchers found matching your filter. You can still use the <strong>Voucher Tracking</strong> and <strong>Cancel Voucher</strong> forms above with any <code>DPTPaymentID</code>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($vouchers->hasPages())
        <div class="p-3 border-top">
            {{ $vouchers->links() }}
        </div>
    @endif
</div>
@endsection

