<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $license->license_no }} — Digital Licence Card</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --card-width: 520px;
            --card-height: 330px;
        }

        body {
            background-color: #0f172a;
            font-family: 'Source Sans 3', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            overflow-anchor: none;
        }

        /* ── Dynamic Ambient Glow Aura ── */
        .card-glow-aura {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: var(--card-width);
            height: var(--card-height);
            border-radius: 50%;
            background: radial-gradient(ellipse at center, rgba(16, 185, 129, 0.25), rgba(14, 165, 233, 0.2), rgba(212, 160, 23, 0.15), transparent 70%);
            filter: blur(40px);
            opacity: 0.5;
            transition: all 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
            pointer-events: none;
            z-index: 0;
        }

        .card-scene.dual-view .card-glow-aura {
            width: 90%;
            height: 380px;
            opacity: 0.85;
            filter: blur(50px);
            background: radial-gradient(ellipse at center, rgba(16, 185, 129, 0.4), rgba(14, 165, 233, 0.35), rgba(212, 160, 23, 0.25), transparent 75%);
        }

        /* ── Holographic Light Sweep Streak ── */
        .card-holo-sweep {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                115deg, 
                transparent 30%, 
                rgba(255, 255, 255, 0.45) 45%, 
                rgba(212, 160, 23, 0.5) 50%, 
                rgba(16, 185, 129, 0.45) 55%, 
                transparent 70%
            );
            transform: translateX(-100%) translateY(-100%) rotate(25deg);
            pointer-events: none;
            z-index: 10;
            opacity: 0;
        }

        .card-scene.dual-view .card-holo-sweep {
            animation: holoSweep 1.1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes holoSweep {
            0% {
                transform: translateX(-100%) translateY(-100%) rotate(25deg);
                opacity: 0.9;
            }
            100% {
                transform: translateX(100%) translateY(100%) rotate(25deg);
                opacity: 0;
            }
        }

        /* ── 3D Card Scene & Perspective Container ── */
        .card-scene {
            width: var(--card-width);
            height: var(--card-height);
            perspective: 1400px;
            margin: 2.8rem auto 1.5rem auto;
            transition: width 0.8s cubic-bezier(0.34, 1.56, 0.64, 1), height 0.8s cubic-bezier(0.34, 1.56, 0.64, 1), margin 0.8s ease;
            position: relative;
        }

        .card-scene.dual-view {
            width: 100%;
            max-width: 1140px;
            height: auto;
            perspective: 1600px;
            margin-top: 3.5rem;
        }

        .license-card-3d {
            width: 100%;
            height: 100%;
            position: relative;
            transform-style: preserve-3d;
            -webkit-transform-style: preserve-3d;
            transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1;
        }

        .license-card-3d.flipped {
            transform: rotateY(180deg);
        }

        .card-scene.dual-view .license-card-3d {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 3.8rem 2.8rem;
            height: auto;
            transform: none !important;
        }

        /* ── Floating Side Labels ── */
        .card-side-label {
            position: absolute;
            bottom: calc(100% + 14px);
            left: 50%;
            transform: translateX(-50%) translateY(-14px) scale(0.85);
            opacity: 0;
            pointer-events: none;
            display: none;
            transition: opacity 0.5s ease 0.2s, transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s;
            white-space: nowrap;
            z-index: 30;
            font-size: 0.73rem;
            font-weight: 700;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            color: #f8fafc;
            background: rgba(15, 23, 42, 0.88);
            padding: 0.38rem 1.15rem;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4), 0 0 15px rgba(16, 185, 129, 0.2);
            backdrop-filter: blur(12px);
            align-items: center;
        }

        .status-dot-pulse {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 8px currentColor;
            animation: dotPulse 1.8s ease-in-out infinite alternate;
        }

        @keyframes dotPulse {
            0% { opacity: 0.5; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1.3); }
        }

        .card-scene.dual-view .card-side-label {
            display: flex;
            opacity: 1;
            transform: translateX(-50%) translateY(0) scale(1);
            pointer-events: auto;
        }

        /* ── Card Faces (Direct Children of #licenseCard) ── */
        .card-face {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
            border-radius: 18px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.15);
            background: #fff;
            display: flex;
            flex-direction: column;
            transition: transform 0.5s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.4s ease, filter 0.4s ease;
        }

        .card-face-inner {
            position: absolute;
            inset: 0;
            border-radius: 18px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            width: 100%;
            height: 100%;
        }

        .card-face-front {
            transform: rotateY(0deg);
            z-index: 2;
        }

        .card-face-back {
            transform: rotateY(180deg);
            background: #f8fafc;
            z-index: 1;
        }

        .card-scene.dual-view .card-face {
            position: relative;
            inset: auto;
            width: var(--card-width);
            height: var(--card-height);
            backface-visibility: visible !important;
            -webkit-backface-visibility: visible !important;
            box-shadow: 0 25px 55px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.2);
            cursor: pointer;
            will-change: transform;
        }

        .card-scene.dual-view .card-face-front {
            transform: rotateY(0deg) !important;
            animation: ultraUnfoldFront 0.85s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        .card-scene.dual-view .card-face-back {
            transform: rotateY(0deg) !important;
            animation: ultraUnfoldBack 0.85s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        /* ── Dynamic Hyper-Modern Keyframes ── */
        @keyframes ultraUnfoldFront {
            0% {
                transform: translate3d(0, 0, 0) rotateY(0deg) scale(0.92);
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
                filter: brightness(0.85);
            }
            35% {
                transform: translate3d(-45px, -25px, 100px) rotateY(-24deg) rotateZ(-5deg) scale(1.08);
                box-shadow: 0 45px 95px rgba(16, 185, 129, 0.45), 0 0 40px rgba(212, 160, 23, 0.5);
                filter: brightness(1.25);
            }
            70% {
                transform: translate3d(0, 8px, 20px) rotateY(6deg) rotateZ(1.5deg) scale(1.02);
                filter: brightness(1.05);
            }
            100% {
                transform: translate3d(0, 0, 0) rotateY(0deg) rotateZ(0deg) scale(1);
                box-shadow: 0 25px 55px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.2);
                filter: brightness(1);
            }
        }

        @keyframes ultraUnfoldBack {
            0% {
                transform: translate3d(-140px, 45px, -80px) rotateY(180deg) scale(0.55);
                opacity: 0;
                filter: brightness(0.4);
            }
            40% {
                transform: translate3d(65px, -30px, 130px) rotateY(90deg) rotateZ(8deg) scale(1.12);
                opacity: 0.85;
                filter: brightness(1.3);
                box-shadow: 0 45px 95px rgba(14, 165, 233, 0.5), 0 0 40px rgba(212, 160, 23, 0.5);
            }
            75% {
                transform: translate3d(0, 8px, 25px) rotateY(-8deg) scale(1.03);
                opacity: 1;
            }
            100% {
                transform: translate3d(0, 0, 0) rotateY(0deg) scale(1);
                opacity: 1;
                filter: brightness(1);
                box-shadow: 0 25px 55px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.2);
            }
        }

        .btn-chip {
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 0.5rem 1.25rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-chip:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        /* ── Guilloche & Security Pattern Background ── */
        .guilloche-bg {
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: 0.08;
            background-image: 
                radial-gradient(circle at 50% 50%, #0b6e4f 2px, transparent 2.5px),
                linear-gradient(135deg, rgba(11,110,79,0.3) 25%, transparent 25%),
                linear-gradient(225deg, rgba(212,160,23,0.3) 25%, transparent 25%);
            background-size: 16px 16px, 32px 32px, 32px 32px;
        }

        /* ── Card Header ── */
        .card-header-gov {
            background: linear-gradient(135deg, #064a35, #0b6e4f);
            color: #fff;
            padding: 0.75rem 1.15rem;
            border-bottom: 3px solid #d4a017;
            position: relative;
        }

        .card-header-gov::after {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 100px; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(212,160,23,0.25));
            pointer-events: none;
        }

        .kp-badge-emblem {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(212,160,23,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #d4a017;
            flex-shrink: 0;
        }

        /* ── Photo Box ── */
        .citizen-photo-box {
            width: 96px;
            height: 114px;
            border-radius: 10px;
            border: 2px solid #cbd5e1;
            background: #e2e8f0;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            position: relative;
            flex-shrink: 0;
        }

        .citizen-photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .citizen-photo-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #64748b;
            background: linear-gradient(180deg, #f1f5f9, #cbd5e1);
        }

        /* ── Holographic Foil Strip ── */
        .holo-foil-seal {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #d4a017, #fef08a, #0b6e4f, #38bdf8, #d4a017);
            background-size: 200% 200%;
            animation: holoFoil 4s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.6);
            opacity: 0.9;
            flex-shrink: 0;
        }

        @keyframes holoFoil {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* ── Typography & Field Rows ── */
        .field-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 1px;
        }

        .field-value {
            font-size: 0.85rem;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.15;
        }

        .permit-no-badge {
            background: #0f172a;
            color: #d4a017;
            font-family: monospace;
            font-weight: 700;
            font-size: 0.78rem;
            padding: 0.25rem 0.5rem;
            border-radius: 5px;
            letter-spacing: 0.05em;
            display: inline-block;
        }

        /* ── Back Side Styles ── */
        .mag-stripe {
            height: 36px;
            background: #1e293b;
            margin-top: 1rem;
            width: 100%;
        }

        .terms-list {
            font-size: 0.7rem;
            color: #475569;
            padding-left: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .terms-list li {
            margin-bottom: 0.2rem;
        }

        /* ── Print & Save PDF Overrides ── */
        .print-card-label {
            display: none;
        }

        @media print {
            *, *::before, *::after {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
            }

            .print-card-label {
                display: block !important;
                text-align: center;
                font-weight: 700;
                font-size: 0.75rem;
                letter-spacing: 0.08em;
                color: #334155;
                margin-bottom: 0.35rem;
                text-transform: uppercase;
            }

            body {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .card-scene {
                width: 100% !important;
                max-width: 520px !important;
                height: auto !important;
                perspective: none !important;
                margin: 0 auto !important;
                display: block !important;
            }

            .license-card-3d {
                width: 100% !important;
                height: auto !important;
                transform: none !important;
                transform-style: flat !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                gap: 1.5rem !important;
            }

            .card-face {
                position: relative !important;
                inset: auto !important;
                width: 520px !important;
                height: 330px !important;
                backface-visibility: visible !important;
                -webkit-backface-visibility: visible !important;
                transform: none !important;
                box-shadow: none !important;
                border: 1.5px solid #64748b !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                margin: 0 auto !important;
                border-radius: 14px !important;
                overflow: hidden !important;
                background-color: #ffffff !important;
            }

            .card-face-back {
                transform: none !important;
                display: flex !important;
                flex-direction: column !important;
                background-color: #f8fafc !important;
            }

            .holo-foil-seal {
                animation: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- Action Toolbar (Hidden in Print) -->
    <div class="container no-print py-4">
        <div class="d-flex flex-wrap justify-content-center align-items-center gap-3">
            <button class="btn btn-warning text-dark fw-bold btn-chip px-4 shadow-sm" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Print / Save PDF Card
            </button>
            <button class="btn btn-outline-light btn-chip px-4" id="toggleViewBtn" onclick="toggleDualView()">
                <i class="bi bi-layout-split me-1"></i> Show Both Sides
            </button>
            <button class="btn btn-outline-light btn-chip px-3" id="flipBtn" onclick="flipCard()">
                <i class="bi bi-arrow-repeat me-1"></i> Flip Card
            </button>
            <a class="btn btn-outline-light btn-chip" href="{{ route('license.verify', $license->qr_token) }}" target="_blank">
                <i class="bi bi-shield-check me-1"></i> Verify Online
            </a>
            @auth
                <a class="btn btn-outline-secondary text-white btn-chip" href="{{ route('citizen.dashboard') }}">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
            @endauth
        </div>
    </div>

    <!-- Interactive / Printable Card Container -->
    <div class="card-scene" id="cardScene">
        <div class="card-glow-aura"></div>
        <div class="license-card-3d" id="licenseCard">
            
            <!-- ================= FRONT SIDE ================= -->
            <div class="card-face card-face-front">
                <div class="card-side-label no-print">
                    <span class="status-dot-pulse bg-success me-2"></span> FRONT SIDE — ANGLER &amp; PERMIT DETAILS
                </div>
                <div class="card-face-inner">
                    <div class="print-card-label">FRONT SIDE — E-LICENCE PERMIT</div>
                    <div class="card-holo-sweep"></div>
                    <div class="guilloche-bg"></div>

                    <!-- Header Banner -->
                    <div class="card-header-gov d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <div class="kp-badge-emblem p-1">
                                <img src="{{ asset('images/logo-2.png') }}" alt="KP Fisheries Emblem" height="28" width="28" style="object-fit:contain">
                            </div>
                            <div>
                                <div class="fw-bold text-white small lh-1" style="letter-spacing:0.03em">KHYBER PAKHTUNKHWA</div>
                                <div class="opacity-75" style="font-size:0.68rem">DIRECTORATE GENERAL OF FISHERIES</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="holo-foil-seal" title="Official Security Hologram">
                                <i class="bi bi-award-fill text-dark fs-6 opacity-75"></i>
                            </div>
                            <span class="badge {{ $license->isValidNow() ? 'bg-success' : 'bg-danger' }} rounded-pill px-2 py-1" style="font-size:0.65rem;letter-spacing:0.05em">
                                {{ $license->isValidNow() ? 'OFFICIAL E-PERMIT' : 'EXPIRED' }}
                            </span>
                        </div>
                    </div>

                    <!-- Main Card Body -->
                    <div class="p-3 flex-grow-1 d-flex flex-column justify-content-between position-relative" style="z-index:1;">
                        <div class="row g-2 align-items-start">
                            
                            <!-- Left: Citizen Photo -->
                            <div class="col-auto">
                                <div class="citizen-photo-box">
                                    @if($license->user?->citizenProfile?->photo_path)
                                        <img src="{{ Storage::url($license->user->citizenProfile->photo_path) }}" alt="{{ $license->user?->name }}">
                                    @else
                                        <div class="citizen-photo-fallback">
                                            <i class="bi bi-person-fill fs-1"></i>
                                            <span style="font-size:0.55rem;font-weight:700">ANGLER PHOTO</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Center: Profile & License Details -->
                            <div class="col">
                                <div class="mb-1">
                                    <div class="field-label">License Reference No.</div>
                                    <div class="permit-no-badge">{{ $license->license_no }}</div>
                                </div>

                                <div class="mb-1">
                                    <div class="field-label">Licensee Name</div>
                                    <div class="field-value text-uppercase text-rflms" style="font-size:0.95rem">
                                        {{ $license->user?->citizenProfile?->full_name ?: $license->user?->name }}
                                    </div>
                                </div>

                                <div class="row g-1">
                                    <div class="col-6">
                                        <div class="field-label">Father / Guardian</div>
                                        <div class="field-value small text-truncate">
                                            {{ $license->user?->citizenProfile?->father_name ?: '—' }}
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="field-label">CNIC No.</div>
                                        <div class="field-value font-monospace text-nowrap" style="font-size:0.78rem">
                                            {{ $license->user?->citizenProfile?->cnic ?: '—' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-1 mt-1">
                                    <div class="col-6">
                                        <div class="field-label">Water Body &amp; District</div>
                                        <div class="field-value small text-truncate" title="{{ $license->reservoir?->name }}">
                                            {{ $license->reservoir?->name }}
                                        </div>
                                        <div class="text-muted" style="font-size:0.7rem">Dist. {{ $license->reservoir?->district?->name }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="field-label">Category</div>
                                        <div class="field-value small text-truncate">
                                            {{ $license->category?->name }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: QR Code -->
                            <div class="col-auto text-end">
                                <div class="bg-white p-1 rounded-2 border shadow-sm d-inline-block">
                                    <img
                                        src="https://api.qrserver.com/v1/create-qr-code/?size=110x110&data={{ urlencode(route('license.verify', $license->qr_token)) }}"
                                        alt="QR Code"
                                        width="70"
                                        height="70"
                                    >
                                </div>
                                <div class="text-muted" style="font-size:0.58rem;font-weight:700">SCAN TO VERIFY</div>
                            </div>
                        </div>

                        <!-- Bottom Validity Bar -->
                        <div class="pt-2 border-top d-flex align-items-center justify-content-between mt-auto">
                            <div>
                                <span class="field-label d-inline me-1">Issue Date:</span>
                                <span class="fw-bold small text-dark">{{ $license->issue_date?->format('d M Y') }}</span>
                            </div>
                            <div>
                                <span class="field-label d-inline me-1">Expiry Date:</span>
                                <span class="fw-bold small text-danger">{{ $license->expiry_date?->format('d M Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= BACK SIDE ================= -->
            <div class="card-face card-face-back">
                <div class="card-side-label no-print">
                    <span class="status-dot-pulse bg-warning me-2"></span> BACK SIDE — TERMS &amp; REGULATIONS
                </div>
                <div class="card-face-inner">
                    <div class="print-card-label">BACK SIDE — TERMS &amp; REGULATIONS</div>
                    <div class="card-holo-sweep"></div>
                    <div class="guilloche-bg"></div>

                    <!-- Magnetic Stripe Mockup -->
                    <div class="mag-stripe"></div>

                    <div class="p-3 flex-grow-1 d-flex flex-column justify-content-between position-relative" style="z-index:1;">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                                <div class="fw-bold small text-dark uppercase">Permit Terms &amp; Angling Regulations</div>
                                <span class="badge bg-dark text-warning" style="font-size:0.65rem">KP FISHERIES ACT</span>
                            </div>

                            <ul class="terms-list">
                                <li>This digital permit is strictly non-transferable and valid only for the named licensee and designated water body.</li>
                                <li>Fishing allowed with Rod &amp; Line only. Netting, dynamite, poisons, and electric currents are severe illegal offences.</li>
                                <li>Licensee must produce this card or PDF on demand to Fisheries Wardens &amp; Officers.</li>
                                <li>Strictly adhere to species bag limits and minimum size regulations for {{ $license->reservoir?->name }}.</li>
                            </ul>
                        </div>

                        <div class="pt-2 border-top">
                            <div class="row align-items-end">
                                <div class="col-7">
                                    <div class="d-flex align-items-center gap-1 mb-1">
                                        <img src="{{ asset('images/logo-2.png') }}" alt="KP Crest" height="18" width="18" style="object-fit:contain">
                                        <div class="field-label mb-0">Directorate General of Fisheries</div>
                                    </div>
                                    <div class="text-secondary" style="font-size:0.68rem">Government of Khyber Pakhtunkhwa, Peshawar</div>
                                    <div class="text-secondary" style="font-size:0.68rem">Helpline: (091) 921-0000 | fisheries.kp.gov.pk</div>
                                </div>
                                <div class="col-5 text-end">
                                    <div class="d-inline-block text-center">
                                        <div class="font-monospace text-muted" style="font-size:0.6rem;text-decoration:overline">ISSUING AUTHORITY SEAL</div>
                                        <div class="badge bg-rflms text-white px-2 py-1 mt-1" style="font-size:0.6rem">DIGITALLY SIGNED</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Instruction Footer -->
    <div class="container no-print text-center text-white-50 small mb-4">
        <p><i class="bi bi-info-circle me-1"></i> Click <strong>Show Both Sides</strong> to view front and back together with 3D unfolding effect, or click <strong>Flip Card</strong> to inspect each side interactively.</p>
    </div>

    <script>
        const cardScene = document.getElementById('cardScene');
        const licenseCard = document.getElementById('licenseCard');
        const toggleViewBtn = document.getElementById('toggleViewBtn');

        function flipCard() {
            if (cardScene.classList.contains('dual-view')) {
                toggleDualView();
                setTimeout(() => {
                    licenseCard.classList.toggle('flipped');
                }, 300);
            } else {
                licenseCard.classList.toggle('flipped');
            }
        }

        function toggleDualView() {
            const isDual = cardScene.classList.contains('dual-view');
            
            if (!isDual) {
                licenseCard.classList.remove('flipped');
                cardScene.classList.add('dual-view');
                toggleViewBtn.innerHTML = '<i class="bi bi-box-seam me-1"></i> 3D Single View';
                toggleViewBtn.classList.remove('btn-outline-light');
                toggleViewBtn.classList.add('btn-success', 'shadow-sm');
            } else {
                cardScene.classList.remove('dual-view');
                toggleViewBtn.innerHTML = '<i class="bi bi-layout-split me-1"></i> Show Both Sides';
                toggleViewBtn.classList.remove('btn-success', 'shadow-sm');
                toggleViewBtn.classList.add('btn-outline-light');
            }
        }

        // 3D Gyroscope Interactive Mouse Tilt effect for Dual View cards
        document.querySelectorAll('.card-face').forEach(cardFace => {
            cardFace.addEventListener('mousemove', function(e) {
                if (cardScene.classList.contains('dual-view')) {
                    const rect = cardFace.getBoundingClientRect();
                    const x = e.clientX - rect.left - rect.width / 2;
                    const y = e.clientY - rect.top - rect.height / 2;
                    const rotateX = (-y / rect.height) * 18;
                    const rotateY = (x / rect.width) * 18;
                    cardFace.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-8px) scale(1.03)`;
                    cardFace.style.boxShadow = `0 35px 75px rgba(0, 0, 0, 0.5), 0 0 35px rgba(16, 185, 129, 0.4), 0 0 0 2px rgba(212, 160, 23, 0.6)`;
                }
            });

            cardFace.addEventListener('mouseleave', function() {
                if (cardScene.classList.contains('dual-view')) {
                    cardFace.style.transform = 'rotateX(0deg) rotateY(0deg) translateY(0px) scale(1)';
                    cardFace.style.boxShadow = '0 25px 55px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.2)';
                }
            });

            cardFace.addEventListener('click', function(e) {
                if (!cardScene.classList.contains('dual-view')) {
                    licenseCard.classList.toggle('flipped');
                }
            });
        });
    </script>
</body>
</html>

