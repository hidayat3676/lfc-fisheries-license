@extends('layouts.app')

@section('title', $reservoir->name.' — '.config('app.name'))

@section('content')
<section class="container py-4 py-lg-5">
    <a href="{{ route('catalogue.index') }}" class="small text-decoration-none">&larr; Back to catalogue</a>

    <div class="row g-4 mt-1">
        <div class="col-lg-8">
            <div class="bg-white rounded-4 shadow-sm overflow-hidden p-4 p-lg-5">
                @if ($reservoir->imageUrl())
                    <div class="mb-4 rounded-3 overflow-hidden" style="max-height: 320px;">
                        <img src="{{ $reservoir->imageUrl() }}" alt="{{ $reservoir->name }}" class="w-100 h-100" style="object-fit: cover;">
                    </div>
                @endif
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge text-bg-light border">{{ $reservoir->typeLabel() }}</span>
                    <span class="badge text-bg-secondary">{{ $reservoir->troutLabel() }}</span>
                    @if ($reservoir->allowsELicence())
                        <span class="badge text-bg-success">Open for e-licence</span>
                    @elseif ($reservoir->is_open_for_licensing === false)
                        <span class="badge text-bg-dark">Not open for e-licence</span>
                    @else
                        <span class="badge text-bg-warning text-dark">Eligibility undecided</span>
                    @endif
                </div>

                <h1 class="h2 fw-bold mb-2">{{ $reservoir->name }}</h1>
                <p class="text-secondary">{{ $reservoir->district?->name }}@if($reservoir->office) · {{ $reservoir->office->name }}@endif</p>

                @if ($reservoir->description)
                    <p>{{ $reservoir->description }}</p>
                @endif

                @if ($reservoir->species_notes)
                    <h2 class="h6 fw-bold mt-4">Species notes</h2>
                    <p class="mb-0">{{ $reservoir->species_notes }}</p>
                @endif

                @if ($reservoir->trout_stretch_notes)
                    <h2 class="h6 fw-bold mt-4">Trout stretch</h2>
                    <p class="mb-0">{{ $reservoir->trout_stretch_notes }}</p>
                @endif

                @if ($reservoir->reserve_area_notes)
                    <h2 class="h6 fw-bold mt-4">Reserve area</h2>
                    <p class="mb-0">{{ $reservoir->reserve_area_notes }}</p>
                @endif

                @if ($reservoir->lease_notes)
                    <h2 class="h6 fw-bold mt-4">Lease notes</h2>
                    <p class="mb-0">{{ $reservoir->lease_notes }}</p>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="bg-white rounded-4 shadow-sm p-4 mb-3">
                <h2 class="h6 fw-bold mb-3">Location</h2>
                @if ($reservoir->hasCoordinates())
                    <p class="small mb-2">{{ number_format($reservoir->latitude, 6) }}, {{ number_format($reservoir->longitude, 6) }}</p>
                    <a class="btn btn-rflms w-100" href="{{ $reservoir->mapsUrl() }}" target="_blank" rel="noopener">Open in Google Maps</a>
                @else
                    <p class="small text-secondary mb-0">Coordinates not yet confirmed.</p>
                @endif
            </div>

            <div class="bg-white rounded-4 shadow-sm p-4 mb-3">
                <h2 class="h6 fw-bold mb-3">Office contact</h2>
                <p class="mb-1 fw-semibold">{{ $reservoir->office?->name ?: 'District fisheries office' }}</p>
                <p class="small mb-1">{{ $reservoir->office?->phone ?: 'Phone not listed' }}</p>
                <p class="small mb-0">{{ $reservoir->office?->email ?: 'Email not listed' }}</p>
            </div>

            <div class="bg-white rounded-4 shadow-sm p-4">
                <h2 class="h6 fw-bold mb-2">Apply for licence</h2>
                @if ($errors->has('licensing'))
                    <div class="alert alert-warning small">{{ $errors->first('licensing') }}</div>
                @endif
                <p class="small text-secondary">Choose a category, fishing date, and upload payment proof.</p>
                @if (! $reservoir->allowsELicence())
                    <div class="alert alert-warning small mb-0">This water body is not currently open for e-licence applications.</div>
                @elseif ($activeLicense)
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle-fill me-1"></i> You currently hold an active licence for this water body (Licence #{{ $activeLicense->license_no }}, valid until {{ $activeLicense->expiry_date->format('M d, Y') }}). Re-applying is allowed once your current licence expires.
                    </div>
                @elseif (auth()->check() && auth()->user()->isCitizen())
                    <a href="{{ route('citizen.applications.create', $reservoir) }}" class="btn btn-rflms w-100">Start application</a>
                @elseif (auth()->check())
                    <p class="small text-secondary mb-0">Use a citizen account to apply.</p>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-dark w-100">Login to apply</a>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
