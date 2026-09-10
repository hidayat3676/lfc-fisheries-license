@extends('layouts.app')

@section('title', 'Licence Verification — ' . $license->license_no . ' — ' . config('app.name'))
@section('meta_description', 'Official licence verification result for licence number ' . $license->license_no . ' issued by Directorate General of Fisheries, KP.')

@section('content')
<section class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">

            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb" class="mb-3 no-print">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-muted">Home</a></li>
                    <li class="breadcrumb-item text-muted">E-Licence Verification</li>
                    <li class="breadcrumb-item active fw-semibold text-rflms" aria-current="page">{{ $license->license_no }}</li>
                </ol>
            </nav>

            <!-- Verification Main Card -->
            <div class="verify-status-card overflow-hidden position-relative animate-in">
                
                <!-- Status Header Banner -->
                <div class="p-4 p-md-5 text-center position-relative {{ $valid ? 'verify-badge-valid' : ($status === 'Expired' ? 'verify-badge-expired' : 'verify-badge-cancelled') }}">
                    <div class="license-security-bg opacity-20"></div>

                    <!-- Seal Header -->
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill bg-black bg-opacity-35 text-white border border-white border-opacity-25 mb-3 small fw-semibold shadow-sm">
                        <i class="bi bi-shield-lock-fill text-warning"></i>
                        <span class="text-white">Directorate General of Fisheries · Khyber Pakhtunkhwa</span>
                    </div>

                    <!-- Status Icon & Title -->
                    <div class="mb-3">
                        @if($valid)
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white text-success p-3 shadow-lg mb-2 verify-valid" style="width: 72px; height: 72px;">
                                <i class="bi bi-patch-check-fill fs-1 text-success"></i>
                            </div>
                            <h1 class="h3 fw-bold mb-1 text-white">Authentic E-Licence Verified</h1>
                            <p class="text-white-50 small mb-0">This angling permit is active and officially recognized by KP Fisheries Department.</p>
                        @elseif($status === 'Expired')
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white text-danger p-3 shadow-lg mb-2" style="width: 72px; height: 72px;">
                                <i class="bi bi-exclamation-triangle-fill fs-1 text-danger"></i>
                            </div>
                            <h1 class="h3 fw-bold mb-1 text-white">Licence Expired</h1>
                            <p class="text-white-50 small mb-0">This licence has passed its validity period and requires renewal.</p>
                        @else
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white text-secondary p-3 shadow-lg mb-2" style="width: 72px; height: 72px;">
                                <i class="bi bi-shield-x fs-1 text-danger"></i>
                            </div>
                            <h1 class="h3 fw-bold mb-1 text-white">Licence {{ $status }}</h1>
                            <p class="text-white-50 small mb-0">This permit status is currently designated as {{ strtolower($status) }}.</p>
                        @endif
                    </div>

                    <!-- License Number Pill -->
                    <div class="d-inline-flex align-items-center gap-2 bg-black bg-opacity-30 rounded-3 px-3 py-2 border border-white border-opacity-20 mt-2">
                        <span class="text-white-50 small font-monospace">REF:</span>
                        <span class="font-monospace fw-bold text-warning fs-5" id="licenseNo">{{ $license->license_no }}</span>
                        <button type="button" class="btn btn-sm btn-link text-white p-0 ms-1 no-print text-decoration-none" onclick="copyLicenseRef('{{ $license->license_no }}', this)" title="Copy Reference">
                            <i class="bi bi-copy"></i>
                        </button>
                    </div>
                </div>

                <!-- Main Details Section -->
                <div class="p-4 p-md-5 position-relative">
                    <i class="bi bi-shield-check verify-watermark"></i>

                    <!-- Holder & Category Header Row -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between pb-4 mb-4 border-bottom gap-3">
                        <div class="d-flex align-items-center gap-3">
                            @if($license->user?->citizenProfile?->photo_path)
                                <img src="{{ Storage::url($license->user->citizenProfile->photo_path) }}" alt="{{ $holderDisplay }}" class="verify-avatar">
                            @else
                                <div class="verify-avatar d-flex align-items-center justify-content-center text-secondary fs-3">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            @endif
                            <div>
                                <div class="verify-label">Permit Holder / Licensee</div>
                                <h2 class="h4 fw-bold text-dark mb-0">{{ $holderDisplay }}</h2>
                                @if($license->user?->citizenProfile?->father_name)
                                    <div class="text-muted small fw-medium">S/O {{ $license->user->citizenProfile->father_name }}</div>
                                @endif
                                @if ($cnicDisplay)
                                    <div class="text-muted small font-monospace text-nowrap"><i class="bi bi-card-heading me-1"></i>CNIC: {{ $cnicDisplay }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="text-md-end">
                            <span class="badge bg-rflms text-white px-3 py-2 fs-6 rounded-pill">
                                <i class="bi bi-tag-fill me-1"></i>{{ $license->category?->name ?? 'Angling Licence' }}
                            </span>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="verify-detail-item h-100">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="feature-icon" style="width:2.2rem;height:2.2rem;font-size:0.95rem"><i class="bi bi-water"></i></div>
                                    <div>
                                        <div class="verify-label">Designated Water Body</div>
                                        <div class="verify-value">{{ $license->reservoir?->name ?? 'All Approved Waters' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="verify-detail-item h-100">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="feature-icon" style="width:2.2rem;height:2.2rem;font-size:0.95rem"><i class="bi bi-geo-alt-fill text-danger"></i></div>
                                    <div>
                                        <div class="verify-label">District Jurisdiction</div>
                                        <div class="verify-value">District {{ $license->reservoir?->district?->name ?? 'KP Province' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="verify-detail-item h-100">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="feature-icon" style="width:2.2rem;height:2.2rem;font-size:0.95rem;background:#e8f8ef;color:#27ae60"><i class="bi bi-calendar-check"></i></div>
                                    <div>
                                        <div class="verify-label">Issue Date</div>
                                        <div class="verify-value">{{ $license->issue_date?->format('d M Y') ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="verify-detail-item h-100">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="feature-icon" style="width:2.2rem;height:2.2rem;font-size:0.95rem;background:{{ $valid ? '#e8f8ef' : '#fdecea' }};color:{{ $valid ? '#27ae60' : '#c0392b' }}"><i class="bi bi-calendar-x"></i></div>
                                    <div>
                                        <div class="verify-label">Expiry Date</div>
                                        <div class="verify-value {{ $valid ? 'text-success' : 'text-danger' }}">{{ $license->expiry_date?->format('d M Y') ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Privacy & Verification Footer Note -->
                    <div class="mt-4 pt-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2 text-muted small">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-shield-check text-rflms fs-5"></i>
                            <span>Cryptographically signed &amp; verified on {{ now()->format('d M Y, h:i A') }}</span>
                        </div>
                        @if ($privacyMode === 'masked')
                            <div class="text-secondary"><i class="bi bi-eye-slash me-1"></i>Personal details partially masked for public privacy.</div>
                        @endif
                    </div>
                </div>

                <!-- Field Officer & User Action Buttons -->
                <div class="p-4 bg-light border-top no-print">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex flex-wrap gap-2">
                            <button onclick="window.print()" class="btn btn-rflms btn-chip">
                                <i class="bi bi-printer me-1"></i> Print Verification Record
                            </button>
                            @if($license->reservoir_id)
                                <a href="{{ route('catalogue.show', $license->reservoir_id) }}" class="btn btn-outline-secondary btn-chip">
                                    <i class="bi bi-info-circle me-1"></i> Water Body Rules
                                </a>
                            @endif
                        </div>
                        
                        <a href="{{ route('home') }}#verify-section" class="btn btn-link text-decoration-none text-rflms fw-medium p-0">
                            <i class="bi bi-search me-1"></i> Verify Another Permit
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Search Form for Warden / Officer -->
            <div class="mt-4 p-4 rounded-4 bg-white border shadow-sm no-print">
                <div class="d-flex align-items-center gap-2 mb-2 fw-bold text-dark">
                    <i class="bi bi-qr-code-scan text-rflms"></i>
                    <span>Quick Verification Lookup</span>
                </div>
                <form action="#" method="GET" onsubmit="event.preventDefault(); var t = document.getElementById('newVerifyToken').value.trim(); if(t) window.location.href = '/verify/licence/' + encodeURIComponent(t);">
                    <div class="input-group">
                        <input type="text" id="newVerifyToken" class="form-control" placeholder="Enter QR token or reference number..." required>
                        <button type="submit" class="btn btn-rflms">
                            <i class="bi bi-check-circle me-1"></i> Verify Token
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</section>

<script>
function copyLicenseRef(text, btnElement) {
    function showFeedback() {
        var icon = btnElement.querySelector('i');
        if (icon) {
            var origClass = icon.className;
            icon.className = 'bi bi-check-lg text-warning fs-5';
            setTimeout(function() { icon.className = origClass; }, 2000);
        }
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(showFeedback).catch(function() { fallbackCopy(text); });
    } else {
        fallbackCopy(text);
    }

    function fallbackCopy(val) {
        var input = document.createElement('textarea');
        input.value = val;
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.focus();
        input.select();
        try {
            document.execCommand('copy');
            showFeedback();
        } catch (err) {
            console.error('Copy failed', err);
        }
        document.body.removeChild(input);
    }
}
</script>
@endsection

