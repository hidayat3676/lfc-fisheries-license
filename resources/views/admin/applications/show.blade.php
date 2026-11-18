@extends('layouts.admin')

@section('title', $application->application_no.' — '.config('app.name'))

@push('styles')
<style>
    @media print {
        nav.admin-topbar,
        aside,
        .admin-sidebar,
        .no-print {
            display: none !important;
        }
        body {
            background: #ffffff !important;
        }
        .container-fluid, .row, .col-lg-9, .col-xl-10 {
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            flex: 0 0 100% !important;
        }
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
    <div>
        <h1 class="h3 fw-bold mb-1">{{ $application->application_no }}</h1>
        <p class="text-secondary mb-0">{{ $application->statusLabel() }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if ($application->license)
            <a href="{{ route('admin.applications.card', ['application' => $application, 'print' => 1]) }}" target="_blank" class="btn btn-rflms btn-sm">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Printable PDF
            </a>
            <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-printer me-1"></i> Print Licence
            </button>
        @endif
        <a href="{{ route('admin.applications.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>
</div>

<div class="row g-3 no-print">
    <div class="col-lg-7">
        <div class="bg-white rounded-4 shadow-sm p-4 mb-3">
            <h2 class="h6 fw-bold mb-3">Application details</h2>
            <dl class="row mb-0 small">
                <dt class="col-sm-4">Citizen</dt><dd class="col-sm-8">{{ $application->user?->name }} ({{ $application->user?->email }})</dd>
                <dt class="col-sm-4">District</dt><dd class="col-sm-8">{{ $application->reservoir?->district?->name ?: '—' }}</dd>
                <dt class="col-sm-4">Category</dt><dd class="col-sm-8">{{ $application->category?->name }}</dd>
                <dt class="col-sm-4">Fishing dates</dt><dd class="col-sm-8">{{ $application->fishing_start_date?->format('d M Y') }} → {{ $application->fishing_end_date?->format('d M Y') }}</dd>
                <dt class="col-sm-4">Fee snapshot</dt><dd class="col-sm-8">{{ number_format((float) $application->fee_amount_snapshot, 0) }} {{ $application->currency }}</dd>
                <dt class="col-sm-4">Payment</dt><dd class="col-sm-8">{{ str_replace('_',' ', (string) $application->payment_method) }} · {{ $application->payment_reference ?: '—' }}</dd>
                @if ($application->psid_code)
                    <dt class="col-sm-4">PSID</dt><dd class="col-sm-8">{{ $application->psid_code }}</dd>
                @endif
            </dl>
            @if ($application->payment_receipt_path)
                <a class="btn btn-sm btn-outline-secondary mt-3" href="{{ asset('storage/'.$application->payment_receipt_path) }}" target="_blank">View payment receipt</a>
            @endif
        </div>

        @if ($application->license)
            <div class="bg-white rounded-4 shadow-sm p-4 mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h2 class="h6 fw-bold mb-1">Issued licence</h2>
                        <p class="mb-1"><strong>{{ $application->license->license_no }}</strong></p>
                        <p class="small text-secondary mb-0">{{ $application->license->issue_date?->format('d M Y') }} → {{ $application->license->expiry_date?->format('d M Y') }}</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.applications.card', ['application' => $application, 'print' => 1]) }}" target="_blank" class="btn btn-sm btn-rflms">
                            <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download / Print PDF
                        </a>
                        <a href="{{ route('license.verify', $application->license->qr_token) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-qr-code-scan me-1"></i> Verify QR
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-5">
        @if ($application->isPendingReview() && auth()->user()->hasModuleAction('applications', 'approve'))
            <div class="bg-white rounded-4 shadow-sm p-4 mb-3">
                <h2 class="h6 fw-bold mb-3">Decision</h2>
                <form method="POST" action="{{ route('admin.applications.approve', $application) }}" class="mb-3">
                    @csrf
                    <label class="form-label">Remarks (optional)</label>
                    <textarea name="officer_remarks" class="form-control mb-2" rows="2"></textarea>
                    <button class="btn btn-rflms w-100" type="submit">Approve &amp; issue licence</button>
                </form>
                <form method="POST" action="{{ route('admin.applications.request-info', $application) }}" class="mb-3">
                    @csrf
                    <label class="form-label">Request info (required)</label>
                    <textarea name="officer_remarks" class="form-control mb-2" rows="2" required></textarea>
                    <button class="btn btn-outline-secondary w-100" type="submit">Request more info</button>
                </form>
                <form method="POST" action="{{ route('admin.applications.reject', $application) }}">
                    @csrf
                    <label class="form-label">Reject reason (required)</label>
                    <textarea name="officer_remarks" class="form-control mb-2" rows="2" required></textarea>
                    <button class="btn btn-outline-danger w-100" type="submit">Reject</button>
                </form>
            </div>
        @else
            <div class="bg-white rounded-4 shadow-sm p-4">
                <h2 class="h6 fw-bold mb-2">Review notes</h2>
                <p class="small mb-0">{{ $application->officer_remarks ?: '—' }}</p>
            </div>
        @endif
    </div>
</div>

@if ($application->license)
    <div class="mt-4 pt-2">
        <div class="d-flex justify-content-between align-items-center mb-3 no-print" style="max-width: 680px; margin: 0 auto;">
            <h2 class="h5 fw-bold mb-0"><i class="bi bi-card-heading text-rflms me-2"></i>Official E-Licence Card (Front &amp; Back)</h2>
            <a href="{{ route('admin.applications.card', ['application' => $application, 'print' => 1]) }}" target="_blank" class="btn btn-sm btn-rflms rounded-pill px-3">
                <i class="bi bi-printer-fill me-1"></i> Print / Save as PDF
            </a>
        </div>

        @include('partials.license-card-design', ['license' => $application->license])
    </div>
@endif
@endsection
