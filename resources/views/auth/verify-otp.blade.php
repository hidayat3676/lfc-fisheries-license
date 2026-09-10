@extends('layouts.auth')
@section('title', 'Verify Email — '.config('app.name'))

@section('content')
@php $expiresTs = \Illuminate\Support\Carbon::parse($expiresAt)->getTimestamp(); @endphp
<div class="row align-items-center justify-content-center g-4 my-2"
     x-data="{ expiresTs: {{ $expiresTs }}, resendLeft: {{ (int) $resendIn }}, expiryLabel: '--',
         init() { this.tick(); setInterval(() => this.tick(), 1000); },
         tick() { const left = Math.max(0, this.expiresTs - Math.floor(Date.now() / 1000)); const m = Math.floor(left / 60); const s = left % 60; this.expiryLabel = m + ':' + String(s).padStart(2, '0'); if (this.resendLeft > 0) this.resendLeft--; }
     }">
    <!-- Left Hero Side -->
    <div class="col-lg-6 text-white d-none d-lg-block pe-lg-5">
        <div class="mb-4">
            <span class="govt-dept-badge mb-3">
                <span class="badge-dot"></span> Directorate of Fisheries, KP
            </span>
            <h1 class="display-6 fw-bold text-white mb-3">Official E-Licensing Portal for KP Fisheries</h1>
            <p class="text-white-50 lead fs-6 mb-4">Apply, manage, and verify fishing permits & trout licences across Khyber Pakhtunkhwa water bodies online.</p>
        </div>

        <div class="d-flex flex-column gap-3 max-w-450">
            <div class="auth-domain-chip d-flex align-items-center gap-3">
                <div class="p-2 rounded-3 bg-white-10 text-warning fs-4">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <div class="fw-semibold text-white">Security Verification</div>
                    <div class="small text-white-50 fs-xs">We sent a 6-digit OTP to verify your ownership of this email address.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Form Glass Card -->
    <div class="col-12 col-md-9 col-lg-6 max-w-500">
        <div class="card auth-glass-card border-0">
            <div class="card-body p-4 p-sm-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex p-3 rounded-circle bg-success-subtle text-success mb-3 shadow-sm">
                        <i class="bi bi-shield-lock-fill fs-3 text-rflms"></i>
                    </div>
                    <h2 class="h4 fw-bold mb-1 text-dark">Verify Email Address</h2>
                    <p class="text-muted small mb-3">Code sent to <strong>{{ $email }}</strong></p>
                    
                    <div class="d-flex justify-content-between align-items-center small bg-light p-2 rounded-3 mb-3">
                        <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">Expires in <strong x-text="expiryLabel"></strong></span>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1" x-show="resendLeft > 0">Resend in <strong x-text="resendLeft + 's'"></strong></span>
                    </div>
                </div>

                @if (session('status'))
                    <div class="alert alert-success d-flex align-items-center gap-2 small border-0 mb-3 rounded-3 shadow-sm">
                        <i class="bi bi-check-circle-fill fs-6 text-success"></i>
                        <div>{{ session('status') }}</div>
                    </div>
                @endif

                @if (session('debug_otp'))
                    <div class="alert alert-warning small border-0 mb-3 rounded-3 shadow-sm text-start">
                        <i class="bi bi-info-circle me-1"></i> Local debug OTP: <strong>{{ session('debug_otp') }}</strong> (shown only when APP_DEBUG=true)
                    </div>
                @endif

                <form method="POST" action="{{ route('register.verify.submit') }}">
                    @csrf
                    <!-- 6-digit OTP -->
                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold small" for="otp">6-Digit Verification Code</label>
                        <div class="input-group input-aquatic-group">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input id="otp" type="text" name="otp" inputmode="numeric" maxlength="6" class="form-control text-center fw-bold fs-5 @error('otp') is-invalid @enderror" placeholder="••••••" required autofocus style="letter-spacing:.3em">
                        </div>
                        @error('otp') <div class="invalid-feedback d-block small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <!-- Create Password -->
                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold small" for="password">Create Password</label>
                        <div class="input-group input-aquatic-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••" required minlength="8">
                        </div>
                        @error('password') <div class="invalid-feedback d-block small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label class="form-label text-dark fw-semibold small" for="password_confirmation">Confirm Password</label>
                        <div class="input-group input-aquatic-group">
                            <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" placeholder="••••••••" required minlength="8">
                        </div>
                    </div>

                    <button class="btn btn-aquatic-submit w-100" type="submit">
                        <i class="bi bi-check-circle me-1"></i> Verify & Complete Registration
                    </button>
                </form>

                <form method="POST" action="{{ route('register.resend') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary w-100 rounded-pill" :disabled="resendLeft > 0">
                        <i class="bi bi-arrow-clockwise me-1"></i> Resend Verification Code
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

