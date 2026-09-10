@extends('layouts.app')

@section('title', 'Application Submitted — '.$application->application_no)

@push('styles')
<style>
    /* ── Success Hero & Particle Styles ── */
    .success-hero {
        position: relative;
        background: radial-gradient(circle at 50% 20%, #064a35 0%, #042c1f 60%, #021a12 100%);
        color: #ffffff;
        border-radius: 24px;
        padding: 3rem 1.5rem;
        overflow: hidden;
        box-shadow: 0 20px 50px rgba(4, 44, 31, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.2);
    }

    .success-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image: 
            radial-gradient(circle at 50% 50%, rgba(212, 160, 23, 0.15) 1px, transparent 2px),
            linear-gradient(135deg, rgba(11, 110, 79, 0.2) 25%, transparent 25%);
        background-size: 24px 24px, 48px 48px;
        pointer-events: none;
        opacity: 0.6;
    }

    /* Floating Canvas for Confetti */
    #confettiCanvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 2;
    }

    /* Animated Checkmark Badge */
    .checkmark-wrapper {
        width: 90px;
        height: 90px;
        margin: 0 auto 1.5rem auto;
        position: relative;
        z-index: 3;
    }

    .checkmark-circle {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: linear-gradient(135deg, #10b981, #059669);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulseRing 2s infinite cubic-bezier(0.66, 0, 0, 1), bounceIn 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    @keyframes pulseRing {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { box-shadow: 0 0 0 24px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    @keyframes bounceIn {
        0% { transform: scale(0); opacity: 0; }
        60% { transform: scale(1.15); opacity: 1; }
        100% { transform: scale(1); }
    }

    .checkmark-icon {
        font-size: 3rem;
        color: #ffffff;
        animation: checkPop 0.6s 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) backwards;
    }

    @keyframes checkPop {
        0% { transform: scale(0) rotate(-45deg); opacity: 0; }
        100% { transform: scale(1) rotate(0deg); opacity: 1; }
    }

    /* ── License Animated Showcase Card ── */
    .license-card-wrapper {
        perspective: 1000px;
        margin: 2rem auto 1rem auto;
        max-width: 520px;
        z-index: 3;
        position: relative;
    }

    .license-preview-card {
        width: 100%;
        background: #ffffff;
        color: #1e293b;
        border-radius: 20px;
        overflow: hidden;
        position: relative;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35), 0 0 0 2px rgba(212, 160, 23, 0.4);
        transform-style: preserve-3d;
        transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1), box-shadow 0.6s ease;
        animation: cardFloatEntrance 1.2s 0.2s cubic-bezier(0.16, 1, 0.3, 1) backwards;
    }

    .license-preview-card:hover {
        transform: translateY(-8px) rotateX(4deg) rotateY(-2deg);
        box-shadow: 0 35px 75px rgba(0, 0, 0, 0.45), 0 0 25px rgba(212, 160, 23, 0.5);
    }

    @keyframes cardFloatEntrance {
        0% { transform: translateY(50px) scale(0.9) rotateX(-15deg); opacity: 0; }
        100% { transform: translateY(0) scale(1) rotateX(0); opacity: 1; }
    }

    /* Shimmer Light Sweep Effect */
    .shimmer-overlay {
        position: absolute;
        top: 0;
        left: -150%;
        width: 100%;
        height: 100%;
        background: linear-gradient(
            90deg,
            transparent,
            rgba(255, 255, 255, 0.35),
            transparent
        );
        transform: skewX(-25deg);
        animation: shineSweep 4s infinite 1.5s ease-in-out;
        pointer-events: none;
        z-index: 5;
    }

    @keyframes shineSweep {
        0% { left: -150%; }
        30% { left: 150%; }
        100% { left: 150%; }
    }

    /* Guilloche Pattern Overlay */
    .card-guilloche-bg {
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: 0.07;
        background-image: 
            radial-gradient(circle at 50% 50%, #0b6e4f 2px, transparent 2.5px),
            linear-gradient(135deg, rgba(11,110,79,0.3) 25%, transparent 25%),
            linear-gradient(225deg, rgba(212,160,23,0.3) 25%, transparent 25%);
        background-size: 16px 16px, 32px 32px, 32px 32px;
    }

    /* Card Government Header */
    .card-gov-header {
        background: linear-gradient(135deg, #064a35, #0b6e4f);
        color: #ffffff;
        padding: 0.85rem 1.25rem;
        border-bottom: 3px solid #d4a017;
        position: relative;
    }

    .kp-badge-circle {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(212, 160, 23, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Holographic Seal */
    .card-holo-seal {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #d4a017, #fef08a, #0b6e4f, #38bdf8, #d4a017);
        background-size: 250% 250%;
        animation: holoRotate 4s ease infinite;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        border: 1.5px solid rgba(255, 255, 255, 0.8);
        flex-shrink: 0;
    }

    @keyframes holoRotate {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    /* Pulsing Status Tag */
    .pulse-status-tag {
        font-weight: 700;
        font-size: 0.7rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        padding: 0.3rem 0.75rem;
        border-radius: 50px;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .pulse-status-tag.status-approved {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .pulse-status-tag.status-rejected {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }

    .pulse-status-tag.status-info {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }

    .pulse-status-tag.status-review {
        background: #e0f2fe;
        color: #075985;
        border: 1px solid #bae6fd;
    }

    .pulse-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        animation: statusDotPulse 1.5s infinite ease-in-out;
    }

    .pulse-dot.dot-approved { background-color: #10b981; }
    .pulse-dot.dot-rejected { background-color: #ef4444; }
    .pulse-dot.dot-info { background-color: #f59e0b; }
    .pulse-dot.dot-review { background-color: #0284c7; }

    .timeline-badge.rejected {
        background: #ef4444;
        color: #ffffff;
        box-shadow: 0 0 0 3px #fee2e2;
    }

    .timeline-badge.info-req {
        background: #f59e0b;
        color: #ffffff;
        box-shadow: 0 0 0 3px #fef3c7;
    }

    .field-title {
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 2px;
    }

    .field-data {
        font-size: 0.88rem;
        font-weight: 600;
        color: #0f172a;
    }

    /* ── Next Steps Timeline ── */
    .timeline-steps {
        position: relative;
        padding-left: 2rem;
    }

    .timeline-steps::before {
        content: '';
        position: absolute;
        top: 8px;
        bottom: 8px;
        left: 11px;
        width: 2px;
        background: #e2e8f0;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .timeline-item:last-child {
        margin-bottom: 0;
    }

    .timeline-badge {
        position: absolute;
        left: -2rem;
        top: 0;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .timeline-badge.done {
        background: #10b981;
        color: #ffffff;
        box-shadow: 0 0 0 3px #d1fae5;
    }

    .timeline-badge.active {
        background: #3b82f6;
        color: #ffffff;
        box-shadow: 0 0 0 3px #dbeafe;
        animation: pulseBadge 1.8s infinite;
    }

    @keyframes pulseBadge {
        0%, 100% { box-shadow: 0 0 0 3px #dbeafe; }
        50% { box-shadow: 0 0 0 6px rgba(59, 130, 246, 0.3); }
    }

    .timeline-badge.pending {
        background: #cbd5e1;
        color: #64748b;
    }

    /* ── High-Definition Vibrant Print Styles ── */
    @media print {
        @page {
            size: A4 portrait;
            margin: 10mm 12mm;
        }

        *, *::before, *::after {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        nav, navbar, footer, .no-print, #confettiCanvas {
            display: none !important;
        }

        body {
            background-color: #ffffff !important;
            color: #0f172a !important;
            font-size: 10.5pt !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .container {
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .success-hero {
            background: linear-gradient(135deg, #064a35 0%, #0b6e4f 100%) !important;
            color: #ffffff !important;
            border-radius: 20px !important;
            padding: 2rem 1.5rem !important;
            box-shadow: none !important;
            margin-bottom: 1.5rem !important;
            page-break-inside: avoid;
        }

        .checkmark-wrapper {
            margin-bottom: 1rem !important;
        }

        .checkmark-circle {
            width: 70px !important;
            height: 70px !important;
            background: linear-gradient(135deg, #10b981, #059669) !important;
            box-shadow: none !important;
        }

        .checkmark-icon {
            font-size: 2.4rem !important;
        }

        .license-card-wrapper {
            margin: 1.2rem auto !important;
            max-width: 480px !important;
        }

        .license-preview-card {
            border: 2px solid #d4a017 !important;
            box-shadow: none !important;
            background: #ffffff !important;
        }

        .card-gov-header {
            background: linear-gradient(135deg, #064a35, #0b6e4f) !important;
            color: #ffffff !important;
            border-bottom: 3px solid #d4a017 !important;
        }

        .card-holo-seal {
            background: linear-gradient(135deg, #d4a017, #fef08a, #0b6e4f, #38bdf8, #d4a017) !important;
        }

        .shimmer-overlay {
            display: none !important;
        }

        .bg-white {
            background-color: #ffffff !important;
        }

        .border {
            border-color: #cbd5e1 !important;
        }

        .shadow-sm, .shadow-lg, .shadow {
            box-shadow: none !important;
        }

        .timeline-steps::before {
            background: #cbd5e1 !important;
        }

        .timeline-badge.done {
            background: #10b981 !important;
            color: #ffffff !important;
        }

        .timeline-badge.active {
            background: #3b82f6 !important;
            color: #ffffff !important;
        }

        .table-success {
            background-color: #ecfdf5 !important;
        }

        .badge.bg-rflms {
            background-color: #0b6e4f !important;
            color: #ffffff !important;
        }
    }
</style>
@endpush

@section('content')
<section class="container py-4" style="max-width: 860px;">

    <!-- Print-Only Official Header Banner -->
    <div class="d-none d-print-block mb-3 border-bottom pb-3">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <img src="{{ asset('images/logo-2.png') }}" alt="KP Crest" height="50">
                <div>
                    <h2 class="h5 fw-bold mb-0 text-success" style="color: #064a35 !important;">DIRECTORATE GENERAL OF FISHERIES</h2>
                    <div class="small fw-semibold text-dark">GOVERNMENT OF KHYBER PAKHTUNKHWA</div>
                    <div class="text-muted" style="font-size: 0.75rem;">Official E-Licensing Portal · Application Submission Summary</div>
                </div>
            </div>
            <div class="text-end">
                <div class="small text-muted">Date Printed:</div>
                <div class="fw-bold font-monospace text-dark">{{ now()->format('d M Y, h:i A') }}</div>
            </div>
        </div>
    </div>
    
    <!-- Breadcrumb -->
    <nav class="no-print mb-3">
        <a href="{{ route('citizen.applications.index') }}" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left me-1"></i> Back to My Applications
        </a>
    </nav>

    <!-- Main Success Banner Hero -->
    <div class="success-hero text-center mb-4 position-relative">
        <canvas id="confettiCanvas"></canvas>

        <div class="checkmark-wrapper">
            <div class="checkmark-circle {{ $application->status === \App\Models\Application::STATUS_REJECTED ? 'bg-danger' : ($application->status === \App\Models\Application::STATUS_INFO_REQUIRED ? 'bg-warning' : '') }}">
                @if ($application->status === \App\Models\Application::STATUS_REJECTED)
                    <i class="bi bi-x-lg checkmark-icon"></i>
                @elseif ($application->status === \App\Models\Application::STATUS_INFO_REQUIRED)
                    <i class="bi bi-exclamation-lg checkmark-icon"></i>
                @else
                    <i class="bi bi-check-lg checkmark-icon"></i>
                @endif
            </div>
        </div>

        @if ($application->status === \App\Models\Application::STATUS_APPROVED || $application->license)
            <span class="badge bg-success text-white px-3 py-1 rounded-pill fw-bold text-uppercase mb-2" style="font-size:0.72rem; letter-spacing:0.08em">
                <i class="bi bi-patch-check-fill me-1"></i> E-Licence Approved &amp; Issued
            </span>
            <h1 class="h2 fw-bold text-white mb-2">Licence Approved!</h1>
            <p class="text-light opacity-90 mx-auto" style="max-width: 580px; font-size: 0.98rem;">
                Your fishing licence request has been approved by the Directorate General of Fisheries, Khyber Pakhtunkhwa.
            </p>
        @elseif ($application->status === \App\Models\Application::STATUS_REJECTED)
            <span class="badge bg-danger text-white px-3 py-1 rounded-pill fw-bold text-uppercase mb-2" style="font-size:0.72rem; letter-spacing:0.08em">
                <i class="bi bi-x-circle-fill me-1"></i> Application Rejected
            </span>
            <h1 class="h2 fw-bold text-white mb-2">Licence Application Rejected</h1>
            <p class="text-light opacity-90 mx-auto" style="max-width: 580px; font-size: 0.98rem;">
                Your application was reviewed and rejected. Reason: {{ $application->officer_remarks ?: 'Not specified' }}
            </p>
        @elseif ($application->status === \App\Models\Application::STATUS_INFO_REQUIRED)
            <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold text-uppercase mb-2" style="font-size:0.72rem; letter-spacing:0.08em">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> Action Required
            </span>
            <h1 class="h2 fw-bold text-white mb-2">Additional Information Requested</h1>
            <p class="text-light opacity-90 mx-auto" style="max-width: 580px; font-size: 0.98rem;">
                The reviewing officer requested further info: {{ $application->officer_remarks }}
            </p>
        @else
            <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold text-uppercase mb-2" style="font-size:0.72rem; letter-spacing:0.08em">
                E-Licence Application Registered
            </span>
            <h1 class="h2 fw-bold text-white mb-2">Licence Application Submitted!</h1>
            <p class="text-light opacity-90 mx-auto" style="max-width: 580px; font-size: 0.98rem;">
                Your fishing licence request has been successfully submitted to the Directorate General of Fisheries, Khyber Pakhtunkhwa.
            </p>
        @endif

        <!-- Dynamic License Pass Preview Animation Card -->
        <div class="license-card-wrapper" id="cardContainer">
            <div class="license-preview-card text-start">
                <div class="shimmer-overlay"></div>
                <div class="card-guilloche-bg"></div>

                <!-- Header -->
                <div class="card-gov-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="kp-badge-circle">
                            <img src="{{ asset('images/logo-2.png') }}" alt="KP Crest" height="26" width="26" style="object-fit:contain">
                        </div>
                        <div>
                            <div class="fw-bold text-white small lh-1" style="letter-spacing:0.03em">KHYBER PAKHTUNKHWA</div>
                            <div class="opacity-75" style="font-size:0.65rem">DIRECTORATE GENERAL OF FISHERIES</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-holo-seal" title="KP E-Licensing Official Security Hologram">
                            <i class="bi bi-shield-lock-fill text-dark fs-6 opacity-85"></i>
                        </div>
                        @if ($application->status === \App\Models\Application::STATUS_APPROVED || $application->license)
                            <span class="pulse-status-tag status-approved">
                                <span class="pulse-dot dot-approved"></span> Approved
                            </span>
                        @elseif ($application->status === \App\Models\Application::STATUS_REJECTED)
                            <span class="pulse-status-tag status-rejected">
                                <span class="pulse-dot dot-rejected"></span> Rejected
                            </span>
                        @elseif ($application->status === \App\Models\Application::STATUS_INFO_REQUIRED)
                            <span class="pulse-status-tag status-info">
                                <span class="pulse-dot dot-info"></span> Info Required
                            </span>
                        @else
                            <span class="pulse-status-tag status-review">
                                <span class="pulse-dot dot-review"></span> Under Review
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Body -->
                <div class="p-3 position-relative" style="z-index:3;">
                    <div class="row g-2 align-items-start mb-2">
                        <div class="col-8">
                            @if ($application->license)
                                <div class="field-title">Official Licence No.</div>
                                <div class="font-monospace fw-bold fs-5 text-success">{{ $application->license->license_no }}</div>
                                <div class="text-muted small font-monospace">App Ref: {{ $application->application_no }}</div>
                            @else
                                <div class="field-title">Application Reference No.</div>
                                <div class="font-monospace fw-bold fs-5 text-success">{{ $application->application_no }}</div>
                            @endif
                        </div>
                        <div class="col-4 text-end">
                            <div class="bg-white p-1 rounded border shadow-sm d-inline-block">
                                @if ($application->license)
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data={{ urlencode(route('license.verify', $application->license->qr_token)) }}" alt="Licence QR" width="54" height="54">
                                @else
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data={{ urlencode(route('citizen.applications.show', $application)) }}" alt="Application QR" width="54" height="54">
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <div class="field-title">Applicant Name</div>
                            <div class="field-data text-uppercase text-truncate">
                                {{ $application->user?->citizenProfile?->full_name ?: $application->user?->name }}
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="field-title">CNIC Number</div>
                            <div class="field-data font-monospace">
                                {{ $application->user?->citizenProfile?->cnic ?: '—' }}
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <div class="field-title">Designated Water Body</div>
                            <div class="field-data text-primary fw-bold text-truncate" title="{{ $application->reservoir?->name }}">
                                {{ $application->reservoir?->name }}
                            </div>
                            <div class="text-muted" style="font-size:0.7rem">District: {{ $application->reservoir?->district?->name ?: 'KP' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="field-title">Licence Category &amp; Rate</div>
                            <div class="field-data text-truncate fw-bold">
                                {{ $application->category?->name }}
                            </div>
                            <div class="text-success fw-bold font-monospace small">
                                Fee: {{ number_format((float) $application->fee_amount_snapshot, 0) }} {{ $application->currency }}
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 pt-2 border-top align-items-center">
                        <div class="col-6">
                            <div class="field-title">Fishing Window</div>
                            <div class="field-data small font-monospace">
                                {{ $application->fishing_start_date?->format('d M Y') }} → {{ $application->fishing_end_date?->format('d M Y') }}
                            </div>
                        </div>
                        <div class="col-6 text-end">
                            <div class="field-title">Fee Submitted</div>
                            <div class="field-data">
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1.5 font-monospace fw-bold fs-6 shadow-sm">
                                    <i class="bi bi-cash-stack me-1"></i>{{ number_format((float) $application->fee_amount_snapshot, 0) }} {{ $application->currency }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-white-50 small mt-2 no-print">
            <i class="bi bi-mouse me-1"></i> Hover over or tap the card to inspect your digital application pass.
        </p>

        <!-- Quick Action Buttons -->
        <div class="d-flex flex-wrap justify-content-center gap-2 mt-4 no-print position-relative" style="z-index:4;">
            @if ($application->license)
                <a href="{{ route('citizen.licenses.card', $application->license) }}" class="btn btn-success text-white fw-bold px-4 py-2 rounded-pill shadow-sm">
                    <i class="bi bi-card-heading me-1"></i> Open Digital Licence Card
                </a>
            @endif
            <a href="{{ route('citizen.applications.show', $application) }}" class="btn btn-warning text-dark fw-bold px-4 py-2 rounded-pill shadow-sm">
                <i class="bi bi-eye-fill me-1"></i> Application Details
            </a>
            <a href="{{ route('citizen.dashboard') }}" class="btn btn-outline-light px-4 py-2 rounded-pill">
                <i class="bi bi-speedometer2 me-1"></i> Return to Dashboard
            </a>
            <button onclick="window.print()" class="btn btn-link text-white text-decoration-none px-3 py-2">
                <i class="bi bi-printer me-1"></i> Print Summary
            </button>
        </div>
    </div>

    <!-- Application Next Steps & Information Grid -->
    <div class="row g-4">
        <!-- Left: Workflow Progress Timeline -->
        <div class="col-md-6">
            <div class="bg-white rounded-4 p-4 shadow-sm h-100 border">
                <h2 class="h5 fw-bold mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-list-check text-success"></i> Application Process Timeline
                </h2>

                <div class="timeline-steps">
                    <!-- Step 1: Application Submitted -->
                    <div class="timeline-item">
                        <div class="timeline-badge done">
                            <i class="bi bi-check"></i>
                        </div>
                        <h3 class="h6 fw-bold mb-1 text-dark">1. Application Submitted</h3>
                        <p class="small text-muted mb-0">Submitted on {{ $application->created_at?->format('d M Y, h:i A') ?: now()->format('d M Y, h:i A') }}. Reference: <code>{{ $application->application_no }}</code></p>
                    </div>

                    <!-- Step 2: Payment Verification -->
                    <div class="timeline-item">
                        <div class="timeline-badge {{ $application->payment_method === '1bill' && !$application->isPaid() && $application->status !== \App\Models\Application::STATUS_APPROVED ? 'active' : 'done' }}">
                            <i class="bi bi-credit-card"></i>
                        </div>
                        <h3 class="h6 fw-bold mb-1 text-dark">2. Payment Verification</h3>
                        @if ($application->psid_code)
                            <p class="small text-muted mb-1">PSID Generated: <strong>{{ $application->psid_code }}</strong></p>
                            <span class="badge bg-info text-dark" style="font-size:0.7rem">1Bill / PSID Ledger</span>
                        @else
                            <p class="small text-muted mb-0">Payment receipt submitted via {{ str_replace('_', ' ', $application->payment_method) }}.</p>
                        @endif
                    </div>

                    <!-- Step 3: District Officer Review / Approval -->
                    <div class="timeline-item">
                        @if ($application->status === \App\Models\Application::STATUS_APPROVED || $application->license)
                            <div class="timeline-badge done">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <h3 class="h6 fw-bold mb-1 text-dark">3. District Officer Approved</h3>
                            <p class="small text-muted mb-0">
                                Approved on {{ $application->reviewed_at?->format('d M Y, h:i A') ?: ($application->license?->created_at?->format('d M Y, h:i A') ?: 'Verified') }}.
                            </p>
                            @if ($application->officer_remarks)
                                <div class="mt-1 small text-success">
                                    <i class="bi bi-chat-left-text me-1"></i><strong>Remarks:</strong> {{ $application->officer_remarks }}
                                </div>
                            @endif
                        @elseif ($application->status === \App\Models\Application::STATUS_REJECTED)
                            <div class="timeline-badge rejected">
                                <i class="bi bi-x-lg"></i>
                            </div>
                            <h3 class="h6 fw-bold mb-1 text-danger">3. Application Rejected</h3>
                            <p class="small text-muted mb-0">
                                Rejected on {{ $application->reviewed_at?->format('d M Y, h:i A') ?: 'Reviewed' }}.
                            </p>
                            @if ($application->officer_remarks)
                                <div class="mt-1 small text-danger">
                                    <i class="bi bi-exclamation-circle me-1"></i><strong>Reason:</strong> {{ $application->officer_remarks }}
                                </div>
                            @endif
                        @elseif ($application->status === \App\Models\Application::STATUS_INFO_REQUIRED)
                            <div class="timeline-badge info-req">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <h3 class="h6 fw-bold mb-1 text-warning">3. Action Required</h3>
                            <p class="small text-muted mb-0">
                                Additional info requested on {{ $application->reviewed_at?->format('d M Y, h:i A') }}.
                            </p>
                            @if ($application->officer_remarks)
                                <div class="mt-1 small text-dark fw-semibold">
                                    <i class="bi bi-chat-left-dots me-1 text-warning"></i><strong>Details Needed:</strong> {{ $application->officer_remarks }}
                                </div>
                            @endif
                        @else
                            <div class="timeline-badge active">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <h3 class="h6 fw-bold mb-1 text-dark">3. District Officer Review</h3>
                            <p class="small text-muted mb-0">Assigned to {{ $application->reservoir?->district?->name ?: 'KP' }} District Office for verification.</p>
                        @endif
                    </div>

                    <!-- Step 4: Digital E-Licence Issuance -->
                    <div class="timeline-item">
                        @if ($application->status === \App\Models\Application::STATUS_APPROVED || $application->license)
                            <div class="timeline-badge done">
                                <i class="bi bi-qr-code"></i>
                            </div>
                            <h3 class="h6 fw-bold mb-1 text-dark">4. Digital E-Licence Issued</h3>
                            <p class="small text-muted mb-0">
                                Licence No: <code>{{ $application->license?->license_no ?: 'Active' }}</code>. Official digital permit is unlocked and ready.
                            </p>
                        @elseif ($application->status === \App\Models\Application::STATUS_REJECTED)
                            <div class="timeline-badge pending">
                                <i class="bi bi-slash-circle"></i>
                            </div>
                            <h3 class="h6 fw-bold mb-1 text-secondary">4. Digital E-Licence Cancelled</h3>
                            <p class="small text-muted mb-0">Licence issuance cancelled due to application rejection.</p>
                        @else
                            <div class="timeline-badge pending">
                                <i class="bi bi-qr-code"></i>
                            </div>
                            <h3 class="h6 fw-bold mb-1 text-secondary">4. Digital E-Licence Issuance</h3>
                            <p class="small text-muted mb-0">Once approved, your official digital permit with QR verification will be unlocked.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Application & Payment Summary Card -->
        <div class="col-md-6">
            <div class="bg-white rounded-4 p-4 shadow-sm h-100 border">
                <h2 class="h5 fw-bold mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-text text-primary"></i> Application Summary
                </h2>

                <table class="table table-borderless table-sm small mb-3">
                    <tbody>
                        <tr>
                            <td class="text-muted">Application Status:</td>
                            <td class="text-end">
                                <span class="badge bg-{{ $application->status === \App\Models\Application::STATUS_APPROVED ? 'success' : ($application->status === \App\Models\Application::STATUS_REJECTED ? 'danger' : ($application->status === \App\Models\Application::STATUS_INFO_REQUIRED ? 'warning' : 'primary')) }} px-2 py-1">
                                    {{ $application->statusLabel() }}
                                </span>
                            </td>
                        </tr>
                        @if ($application->license)
                            <tr>
                                <td class="text-muted">Licence No:</td>
                                <td class="fw-bold font-monospace text-success text-end">{{ $application->license->license_no }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="text-muted">Application Ref:</td>
                            <td class="fw-bold font-monospace text-end">{{ $application->application_no }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Water Body:</td>
                            <td class="fw-bold text-end">{{ $application->reservoir?->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Category:</td>
                            <td class="fw-bold text-end">{{ $application->category?->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Payment Method:</td>
                            <td class="text-uppercase text-end">{{ str_replace('_', ' ', $application->payment_method) }}</td>
                        </tr>
                        @if ($application->payment_reference)
                            <tr>
                                <td class="text-muted">Transaction Ref:</td>
                                <td class="font-monospace text-end">{{ $application->payment_reference }}</td>
                            </tr>
                        @endif
                        @if ($application->psid_code)
                            <tr>
                                <td class="text-muted">PSID Code:</td>
                                <td class="font-monospace fw-bold text-primary text-end">{{ $application->psid_code }}</td>
                            </tr>
                        @endif
                        @if ($application->officer_remarks)
                            <tr>
                                <td class="text-muted">Officer Remarks:</td>
                                <td class="small text-end fw-semibold">{{ $application->officer_remarks }}</td>
                            </tr>
                        @endif
                        <tr class="border-top">
                            <td class="fw-bold text-dark pt-2">Fee Amount:</td>
                            <td class="fw-bold fs-6 text-success text-end pt-2">
                                {{ number_format((float) $application->fee_amount_snapshot, 0) }} {{ $application->currency }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="alert alert-light border small text-muted mb-0">
                    <i class="bi bi-info-circle me-1 text-primary"></i>
                    You can track your application status anytime from your <a href="{{ route('citizen.applications.index') }}" class="fw-bold text-decoration-none">Citizen Dashboard</a>.
                </div>
            </div>
        </div>
    </div>

    <!-- Print-Only Official Verification Footer -->
    <div class="d-none d-print-block text-center mt-4 pt-3 border-top text-muted small">
        <div class="fw-bold text-dark mb-1">
            <i class="bi bi-shield-check text-success me-1"></i> DIRECTORATE GENERAL OF FISHERIES — GOVERNMENT OF KHYBER PAKHTUNKHWA
        </div>
        <div class="text-secondary" style="font-size: 0.78rem;">
            This is an official computer-generated e-application summary document. Authenticity can be verified online at {{ config('app.url') }} or via QR scan.
        </div>
    </div>
</section>

@push('scripts')
<script>
    // ── Confetti Particle Animation Script ──
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('confettiCanvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        let width = canvas.width = canvas.parentElement.offsetWidth;
        let height = canvas.height = canvas.parentElement.offsetHeight;

        window.addEventListener('resize', () => {
            if (canvas && canvas.parentElement) {
                width = canvas.width = canvas.parentElement.offsetWidth;
                height = canvas.height = canvas.parentElement.offsetHeight;
            }
        });

        const colors = ['#10b981', '#f59e0b', '#3b82f6', '#ec4899', '#6366f1', '#fef08a'];
        const particles = [];
        const particleCount = 45;

        for (let i = 0; i < particleCount; i++) {
            particles.push({
                x: Math.random() * width,
                y: Math.random() * height - height,
                size: Math.random() * 7 + 4,
                color: colors[Math.floor(Math.random() * colors.length)],
                speedY: Math.random() * 2.5 + 1.2,
                speedX: (Math.random() - 0.5) * 1.5,
                rotation: Math.random() * 360,
                rotSpeed: (Math.random() - 0.5) * 4
            });
        }

        let animationFrame;
        let opacity = 1;

        function animateConfetti() {
            ctx.clearRect(0, 0, width, height);

            particles.forEach((p) => {
                p.y += p.speedY;
                p.x += p.speedX;
                p.rotation += p.rotSpeed;

                ctx.save();
                ctx.translate(p.x, p.y);
                ctx.rotate((p.rotation * Math.PI) / 180);
                ctx.fillStyle = p.color;
                ctx.globalAlpha = opacity;
                ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.7);
                ctx.restore();

                if (p.y > height) {
                    p.y = -20;
                    p.x = Math.random() * width;
                }
            });

            if (opacity > 0) {
                animationFrame = requestAnimationFrame(animateConfetti);
            }
        }

        animateConfetti();

        // Fade out confetti after 6 seconds
        setTimeout(() => {
            let fadeInterval = setInterval(() => {
                opacity -= 0.05;
                if (opacity <= 0) {
                    clearInterval(fadeInterval);
                    cancelAnimationFrame(animationFrame);
                    ctx.clearRect(0, 0, width, height);
                }
            }, 100);
        }, 5000);

        // 3D Interactive Card Tilt Effect
        const cardContainer = document.getElementById('cardContainer');
        const card = cardContainer?.querySelector('.license-preview-card');

        if (cardContainer && card) {
            cardContainer.addEventListener('mousemove', (e) => {
                const rect = cardContainer.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;
                
                const rotateX = (-y / rect.height) * 16;
                const rotateY = (x / rect.width) * 16;

                card.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-6px)`;
            });

            cardContainer.addEventListener('mouseleave', () => {
                card.style.transform = 'rotateX(0deg) rotateY(0deg) translateY(0px)';
            });
        }
    });
</script>
@endpush
@endsection
