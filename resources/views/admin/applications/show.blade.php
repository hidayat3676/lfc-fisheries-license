@extends('layouts.admin')

@section('title', $application->application_no.' — '.config('app.name'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 fw-bold mb-1">{{ $application->application_no }}</h1>
        <p class="text-secondary mb-0">{{ $application->statusLabel() }}</p>
    </div>
    <a href="{{ route('admin.applications.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="bg-white rounded-4 shadow-sm p-4 mb-3">
            <h2 class="h6 fw-bold mb-3">Application details</h2>
            <dl class="row mb-0 small">
                <dt class="col-sm-4">Citizen</dt><dd class="col-sm-8">{{ $application->user?->name }} ({{ $application->user?->email }})</dd>
                <dt class="col-sm-4">Water body</dt><dd class="col-sm-8">{{ $application->reservoir?->name }} · {{ $application->reservoir?->district?->name }}</dd>
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
            <div class="bg-white rounded-4 shadow-sm p-4">
                <h2 class="h6 fw-bold mb-2">Issued licence</h2>
                <p class="mb-1"><strong>{{ $application->license->license_no }}</strong></p>
                <p class="small mb-2">{{ $application->license->issue_date?->format('d M Y') }} → {{ $application->license->expiry_date?->format('d M Y') }}</p>
                <a href="{{ route('license.verify', $application->license->qr_token) }}" target="_blank">Open QR verify page</a>
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
@endsection
