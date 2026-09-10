<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-2.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logo-2.png') }}">
    @include('partials.icons-cdn')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="aquatic-auth-bg text-dark">
    <!-- Ambient Aquatic Rays & Particle Layers -->
    <div class="aquatic-light-ray"></div>
    

    <!-- High-Tech Cyber Grid Overlay -->
    <div class="tech-grid-overlay"></div>

    <!-- Floating High-Tech Digital License Card Background -->
    <div class="tech-card-bg-canvas d-none d-md-block">
        <div class="tech-bg-permit-card">
            <!-- Holographic Security Strip -->
            <div class="tech-holo-strip"></div>
            
            <!-- Laser Scan Line Beam -->
            <div class="tech-laser-scanner"></div>

            <div class="p-3 text-white">
                <!-- Card Header -->
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-white-15 pb-2">
                    <div class="d-flex align-items-center gap-2">
                        <img src="{{ asset('images/logo-2.png') }}" alt="KP Crest" height="30" class="opacity-90">
                        <div>
                            <div class="fw-bold fs-xs text-warning tracking-wider uppercase">Government of KP</div>
                            <div class="fs-xxs text-white-75 fw-semibold">E-LICENCE PERMIT CARD</div>
                        </div>
                    </div>
                    <div class="badge bg-success-subtle text-success border border-success-subtle fs-xxs px-2 py-1 uppercase fw-bold">
                        <i class="bi bi-shield-check me-1"></i> VERIFIED
                    </div>
                </div>

                <!-- Card Body Details -->
                <div class="row g-2 mb-3">
                    <div class="col-8">
                        <div class="fs-xxs text-white-50 uppercase tracking-wider">PERMIT HOLDER</div>
                        <div class="fw-bold fs-sm text-white text-truncate">KHYBER PAKHTUNKHWA ANGLER</div>
                        
                        <div class="row g-1 mt-2">
                            <div class="col-6">
                                <div class="fs-xxs text-white-50">PERMIT NO.</div>
                                <div class="fw-mono fs-xs text-warning fw-bold">KP-FSH-2026-8891</div>
                            </div>
                            <div class="col-6">
                                <div class="fs-xxs text-white-50">CATEGORY</div>
                                <div class="fw-semibold fs-xs text-cyan">Trout Sport Angling</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="tech-qr-mini mx-auto ms-auto">
                            <svg viewBox="0 0 100 100" width="48" height="48" fill="#ffffff" opacity="0.9">
                                <path d="M0 0h30v30H0zM10 10h10v10H10zM70 0h30v30H70zM80 10h10v10H80zM0 70h30v30H0zM10 80h10v10H10zM40 10h10v10H40zM50 20h10v10H50zM40 40h20v20H40zM70 50h10v10H70zM90 40h10v20H90zM80 80h20v20H80zM50 70h20v10H50zM40 90h30v10H40z"/>
                            </svg>
                            <div class="qr-laser-line"></div>
                        </div>
                    </div>
                </div>

                <!-- Card Footer Tech Barcode & Security Chip -->
                <div class="d-flex align-items-center justify-content-between pt-2 border-top border-white-15 fs-xxs text-white-50">
                    <div class="d-flex align-items-center gap-2">
                        <span class="tech-sim-chip"></span>
                        <span class="fw-mono fs-xxs text-white-75">SECURE-ENCRYPTED-NFC</span>
                    </div>
                    <div class="fw-mono text-warning opacity-75">VAL: 2026-2027</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Header Navigation Bar -->
    <nav class="navbar navbar-dark bg-transparent border-bottom border-white-10 py-3 position-relative z-10">
        <div class="container d-flex align-items-center justify-content-between">
            <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="{{ route('home') }}">
                <img src="{{ asset('images/logo-2.png') }}" alt="KP Fisheries Emblem" height="42" class="brand-logo drop-shadow">
                <div>
                    <div class="fw-bold text-white fs-5 lh-1">KP Fisheries</div>
                    <div class="small text-white-50 fs-xs">E-Licensing Portal</div>
                </div>
            </a>
            
            <div class="d-none d-sm-flex align-items-center gap-2">
                <span class="govt-dept-badge">
                    <span class="badge-dot"></span>
                    Govt. of Khyber Pakhtunkhwa
                </span>
                <a href="{{ route('home') }}" class="btn btn-sm btn-outline-light rounded-pill px-3 py-1 text-white">
                    <i class="bi bi-house me-1"></i> Home
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content Shell -->
    <main class="flex-grow-1 d-flex align-items-center justify-content-center py-4 py-lg-5 position-relative z-10">
        <div class="container">
            @unless (request()->routeIs('register.verify', 'password.reset'))
                @if (session('status'))
                    <div class="alert alert-success d-flex align-items-center gap-2 shadow-sm border-0 mb-4 max-w-500 mx-auto rounded-3">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <div>{{ session('status') }}</div>
                    </div>
                @endif
                @if (session('debug_otp'))
                    <div class="alert alert-warning small border-0 shadow-sm mb-4 max-w-500 mx-auto rounded-3">
                        <i class="bi bi-info-circle me-1"></i> Local debug OTP: <strong>{{ session('debug_otp') }}</strong> (shown only when APP_DEBUG=true)
                    </div>
                @endif
            @endunless
            @yield('content')
        </div>
    </main>

    <!-- Animated Water Waves Divider Footer -->
    <div class="water-waves-container">
        <svg class="water-waves-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 24 150 28" preserveAspectRatio="none">
            <defs>
                <path id="gentle-wave" d="M-160 44c30 0 58-18 88-18s 58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z" />
            </defs>
            <g class="parallax">
                <use href="#gentle-wave" x="48" y="0" class="wave-path-1" />
                <use href="#gentle-wave" x="48" y="3" class="wave-path-2" />
                <use href="#gentle-wave" x="48" y="5" class="wave-path-3" />
            </g>
        </svg>
    </div>
    @stack('scripts')
</body>
</html>
