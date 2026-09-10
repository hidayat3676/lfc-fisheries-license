@extends('layouts.app')

@section('title', $application->application_no.' — '.config('app.name'))

@section('content')
<section class="container py-4" style="max-width: 760px;">
    <a href="{{ route('citizen.applications.index') }}" class="small text-decoration-none">&larr; My applications</a>
    <h1 class="h3 fw-bold mt-2">{{ $application->application_no }}</h1>
    <p class="text-secondary">{{ $application->statusLabel() }}</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="bg-white rounded-4 shadow-sm p-4 mb-3">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
            <h2 class="h6 fw-bold mb-0">Application Summary</h2>
            <a href="{{ route('citizen.applications.success', $application) }}" class="btn btn-sm btn-outline-success rounded-pill">
                <i class="bi bi-patch-check-fill me-1"></i> View Success Card
            </a>
        </div>
        <dl class="row mb-0">
            <dt class="col-sm-4">Water body</dt><dd class="col-sm-8">{{ $application->reservoir?->name }}</dd>
            <dt class="col-sm-4">Category</dt><dd class="col-sm-8">{{ $application->category?->name }}</dd>
            <dt class="col-sm-4">Fee paid/submitted</dt><dd class="col-sm-8">{{ number_format((float) $application->fee_amount_snapshot, 0) }} {{ $application->currency }}</dd>
            <dt class="col-sm-4">Fishing dates</dt><dd class="col-sm-8">{{ $application->fishing_start_date?->format('d M Y') }} → {{ $application->fishing_end_date?->format('d M Y') }}</dd>
            <dt class="col-sm-4">Payment</dt><dd class="col-sm-8">{{ str_replace('_',' ', (string) $application->payment_method) }} · {{ $application->payment_reference ?: '—' }}</dd>
            @if ($application->psid_code)
                <dt class="col-sm-4">PSID</dt><dd class="col-sm-8"><code>{{ $application->psid_code }}</code></dd>
            @endif
            @if ($application->officer_remarks)
                <dt class="col-sm-4">Officer remarks</dt><dd class="col-sm-8">{{ $application->officer_remarks }}</dd>
            @endif
        </dl>
    </div>

    @if ($application->license)
        <div class="bg-white rounded-4 shadow-sm p-4">
            <h2 class="h6 fw-bold">Licence issued</h2>
            <p class="mb-2"><strong>{{ $application->license->license_no }}</strong></p>
            <a class="btn btn-rflms me-2" href="{{ route('citizen.licenses.card', $application->license) }}">Digital card / Print PDF</a>
            <a class="btn btn-outline-secondary" href="{{ route('license.verify', $application->license->qr_token) }}" target="_blank">Public verify</a>
        </div>
    @endif
</section>
@endsection
