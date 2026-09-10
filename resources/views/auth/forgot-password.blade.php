@extends('layouts.auth')
@section('title', 'Forgot Password — '.config('app.name'))

@section('content')
<div class="row align-items-center justify-content-center g-4 my-2">
    <!-- Left Hero Side (Visible on desktop & large tablets) -->
    <div class="col-lg-6 text-white d-none d-lg-block pe-lg-5">
        <div class="mb-4">
            <span class="govt-dept-badge mb-3">
                <span class="badge-dot"></span> Directorate of Fisheries, KP
            </span>
            <h1 class="display-6 fw-bold text-white mb-3">Account Security &amp; Recovery</h1>
            <p class="text-white-50 lead fs-6 mb-4">Reset your KP Fisheries account credentials safely using single-use OTP verification.</p>
        </div>

        <!-- Domain Feature Chips -->
        <div class="d-flex flex-column gap-3 max-w-450">
            <div class="auth-domain-chip d-flex align-items-center gap-3">
                <div class="p-2 rounded-3 bg-white-10 text-warning fs-4">
                    <i class="bi bi-key-fill"></i>
                </div>
                <div>
                    <div class="fw-semibold text-white">Secure Password Reset</div>
                    <div class="small text-white-50 fs-xs">Receive a 6-digit verification code directly to your registered email.</div>
                </div>
            </div>

            <div class="auth-domain-chip d-flex align-items-center gap-3">
                <div class="p-2 rounded-3 bg-white-10 text-info fs-4">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div>
                    <div class="fw-semibold text-white">Encrypted Verification</div>
                    <div class="small text-white-50 fs-xs">Time-limited security codes protect your angling licence profile.</div>
                </div>
            </div>

            <div class="auth-domain-chip d-flex align-items-center gap-3">
                <div class="p-2 rounded-3 bg-white-10 text-success fs-4">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="fw-semibold text-white">Instant Account Access</div>
                    <div class="small text-white-50 fs-xs">Update your credentials and resume your e-permit applications immediately.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Glass Card -->
    <div class="col-12 col-md-9 col-lg-6 max-w-500">
        <div class="card auth-glass-card border-0">
            <div class="card-body p-4 p-sm-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex p-3 rounded-circle bg-warning-subtle text-warning mb-3 shadow-sm">
                        <i class="bi bi-key-fill fs-3 text-warning"></i>
                    </div>
                    <h2 class="h4 fw-bold mb-1 text-dark">Forgot Password?</h2>
                    <p class="text-muted small mb-0">Enter your registered email address to receive a password reset verification code.</p>
                </div>

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <!-- Email Input -->
                    <div class="mb-4">
                        <label class="form-label text-dark fw-semibold small" for="email">Registered Email Address</label>
                        <div class="input-group input-aquatic-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="name@example.com" required autofocus>
                        </div>
                        @error('email') <div class="invalid-feedback d-block small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <!-- Submit Button -->
                    <button class="btn btn-aquatic-submit w-100" type="submit">
                        <i class="bi bi-send-fill me-1"></i> Send Reset Code
                    </button>
                </form>

                <!-- Footer Links -->
                <div class="text-center mt-4 pt-3 border-top border-light-subtle">
                    <p class="small text-secondary mb-0">
                        Remember your password? <a href="{{ route('login') }}" class="fw-bold text-rflms text-decoration-none">Back to Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
