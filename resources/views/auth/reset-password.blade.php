@extends('layouts.auth')
@section('title', 'Reset Password — '.config('app.name'))

@section('content')
@php
    $expiresTs = \Illuminate\Support\Carbon::parse($expiresAt)->getTimestamp();
@endphp
<div class="row align-items-center justify-content-center g-4 my-2"
     x-data="{
        expiresTs: {{ $expiresTs }},
        resendLeft: {{ (int) $resendIn }},
        expiryLabel: '--',
        init() {
            this.tick();
            setInterval(() => this.tick(), 1000);
        },
        tick() {
            const left = Math.max(0, this.expiresTs - Math.floor(Date.now() / 1000));
            const m = Math.floor(left / 60);
            const s = left % 60;
            this.expiryLabel = m + ':' + String(s).padStart(2, '0');
            if (this.resendLeft > 0) this.resendLeft--;
        }
     }">
    <!-- Left Hero Side (Visible on desktop & large tablets) -->
    <div class="col-lg-6 text-white d-none d-lg-block pe-lg-5">
        <div class="mb-4">
            <span class="govt-dept-badge mb-3">
                <span class="badge-dot"></span> Directorate of Fisheries, KP
            </span>
            <h1 class="display-6 fw-bold text-white mb-3">Set New Account Password</h1>
            <p class="text-white-50 lead fs-6 mb-4">Enter the 6-digit verification code sent to your email along with your new secure password.</p>
        </div>

        <!-- Domain Feature Chips -->
        <div class="d-flex flex-column gap-3 max-w-450">
            <div class="auth-domain-chip d-flex align-items-center gap-3">
                <div class="p-2 rounded-3 bg-white-10 text-warning fs-4">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <div class="fw-semibold text-white">Strong Password Security</div>
                    <div class="small text-white-50 fs-xs">Must be at least 8 characters long for complete account protection.</div>
                </div>
            </div>

            <div class="auth-domain-chip d-flex align-items-center gap-3">
                <div class="p-2 rounded-3 bg-white-10 text-info fs-4">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="fw-semibold text-white">Expiring Security Code</div>
                    <div class="small text-white-50 fs-xs">OTP remains active for a limited duration to prevent unauthorized access.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Glass Card -->
    <div class="col-12 col-md-9 col-lg-6 max-w-500">
        <div class="card auth-glass-card border-0">
            <div class="card-body p-4 p-sm-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex p-3 rounded-circle bg-success-subtle text-success mb-3 shadow-sm">
                        <i class="bi bi-lock-fill fs-3 text-rflms"></i>
                    </div>
                    <h2 class="h4 fw-bold mb-1 text-dark">Reset Password</h2>
                    <p class="text-muted small mb-0">Code sent to <strong class="text-dark">{{ $email }}</strong></p>
                </div>

                <div class="d-flex justify-content-between align-items-center p-2 rounded-3 bg-light border mb-3 small">
                    <span class="text-secondary"><i class="bi bi-clock me-1 text-warning"></i>Expires in: <strong class="text-dark" x-text="expiryLabel"></strong></span>
                    <span x-show="resendLeft > 0" class="text-muted">Resend in: <strong class="text-dark" x-text="resendLeft + 's'"></strong></span>
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

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <!-- 6-digit OTP -->
                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold small" for="otp">6-Digit Verification Code</label>
                        <div class="input-group input-aquatic-group">
                            <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                            <input id="otp" type="text" name="otp" inputmode="numeric" maxlength="6" class="form-control font-monospace tracking-widest text-center fs-5 @error('otp') is-invalid @enderror" placeholder="123456" required autofocus>
                        </div>
                        @error('otp') <div class="invalid-feedback d-block small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <!-- New Password -->
                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold small" for="password">New Password</label>
                        <div class="input-group input-aquatic-group">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Minimum 8 characters" required minlength="8">
                            <button type="button" class="btn btn-password-toggle" id="togglePassBtn" title="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password') <div class="invalid-feedback d-block small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label class="form-label text-dark fw-semibold small" for="password_confirmation">Confirm New Password</label>
                        <div class="input-group input-aquatic-group">
                            <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" placeholder="Re-enter new password" required minlength="8">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button class="btn btn-aquatic-submit w-100 mb-3" type="submit">
                        <i class="bi bi-check-circle-fill me-1"></i> Update Password
                    </button>
                </form>

                <!-- Resend Form -->
                <form method="POST" action="{{ route('password.resend') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-chip w-100 justify-content-center" :disabled="resendLeft > 0">
                        <i class="bi bi-arrow-repeat me-1"></i> Resend Verification Code
                    </button>
                </form>

                <!-- Footer Links -->
                <div class="text-center mt-4 pt-3 border-top border-light-subtle">
                    <p class="small text-secondary mb-0">
                        <a href="{{ route('login') }}" class="fw-bold text-rflms text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back to Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('togglePassBtn');
    const passwordInput = document.getElementById('password');
    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function() {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            }
        });
    }
});
</script>
@endsection
