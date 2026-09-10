@extends('layouts.auth')
@section('title', ($portal === 'staff' ? 'Staff Portal' : 'Citizen Portal').' Login — '.config('app.name'))

@section('content')
<div class="row align-items-center justify-content-center g-4 my-2">
    <!-- Left Hero Side (Visible on desktop & large tablets) -->
    <div class="col-lg-6 text-white d-none d-lg-block pe-lg-5">
        <div class="mb-4">
            <span class="govt-dept-badge mb-3">
                <span class="badge-dot"></span> Directorate of Fisheries, KP
            </span>
            <h1 class="display-6 fw-bold text-white mb-3">Official E-Licensing Portal for KP Fisheries</h1>
            <p class="text-white-50 lead fs-6 mb-4">Apply, manage, and verify fishing permits & trout licences across Khyber Pakhtunkhwa water bodies online.</p>
        </div>

        <!-- Domain Feature Chips -->
        <div class="d-flex flex-column gap-3 max-w-450">
            <div class="auth-domain-chip d-flex align-items-center gap-3">
                <div class="p-2 rounded-3 bg-white-10 text-warning fs-4">
                    <i class="bi bi-award"></i>
                </div>
                <div>
                    <div class="fw-semibold text-white">Instant E-Licence Generation</div>
                    <div class="small text-white-50 fs-xs">Apply online and get instant digital permit cards with QR verification.</div>
                </div>
            </div>

            <div class="auth-domain-chip d-flex align-items-center gap-3">
                <div class="p-2 rounded-3 bg-white-10 text-info fs-4">
                    <i class="bi bi-water"></i>
                </div>
                <div>
                    <div class="fw-semibold text-white">Public Water Bodies Directory</div>
                    <div class="small text-white-50 fs-xs">Explore Trout & Non-Trout natural reservoirs across 36 districts.</div>
                </div>
            </div>

            <div class="auth-domain-chip d-flex align-items-center gap-3">
                <div class="p-2 rounded-3 bg-white-10 text-success fs-4">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <div class="fw-semibold text-white">Official Government Compliance</div>
                    <div class="small text-white-50 fs-xs">Secure digital signatures and real-time inspector verification.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Login Form Glass Card -->
    <div class="col-12 col-md-9 col-lg-6 max-w-500">
        <!-- Dual Portal Switcher Pills -->
        <div class="portal-switcher mb-3 shadow-sm">
            <a href="{{ route('login') }}" class="portal-tab-btn {{ $portal === 'citizen' ? 'active' : '' }}">
                <i class="bi bi-person-circle"></i> Citizen Login
            </a>
            <a href="{{ route('staff.login') }}" class="portal-tab-btn {{ $portal === 'staff' ? 'active' : '' }}">
                <i class="bi bi-shield-lock"></i> Staff Portal
            </a>
        </div>

        <div class="card auth-glass-card border-0">
            <div class="card-body p-4 p-sm-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex p-3 rounded-circle bg-success-subtle text-success mb-3 shadow-sm">
                        @if($portal === 'staff')
                            <i class="bi bi-shield-lock-fill fs-3 text-rflms"></i>
                        @else
                            <i class="bi bi-person-badge-fill fs-3 text-rflms"></i>
                        @endif
                    </div>
                    <h2 class="h4 fw-bold mb-1 text-dark">
                        {{ $portal === 'staff' ? 'Staff Portal Login' : 'Citizen Portal Login' }}
                    </h2>
                    <p class="text-muted small mb-0">
                        {{ $portal === 'staff' ? 'Authorized Super Admin & Departmental Officers' : 'Sign in to access your permits, trout passes & applications' }}
                    </p>
                </div>

                <form method="POST" action="{{ $portal === 'staff' ? route('staff.login.submit') : route('login.submit') }}">
                    @csrf
                    <!-- Email Input -->
                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold small" for="email">Email Address</label>
                        <div class="input-group input-aquatic-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="name@example.com" required autofocus>
                        </div>
                        @error('email') <div class="invalid-feedback d-block small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <!-- Password Input -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label text-dark fw-semibold small mb-0" for="password">Password</label>
                            <a href="{{ route('password.request') }}" class="small text-rflms text-decoration-none fw-medium">Forgot password?</a>
                        </div>
                        <div class="input-group input-aquatic-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••" required>
                            <button type="button" class="btn btn-password-toggle" id="togglePasswordBtn" title="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password') <div class="invalid-feedback d-block small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label small text-secondary" for="remember">Keep me signed in on this device</label>
                    </div>

                    <!-- Submit Button -->
                    <button class="btn btn-aquatic-submit w-100" type="submit">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In to {{ $portal === 'staff' ? 'Staff Portal' : 'Account' }}
                    </button>
                </form>

                <!-- Footer Links -->
                <div class="text-center mt-4 pt-3 border-top border-light-subtle">
                    @if ($portal === 'citizen')
                        <p class="small text-secondary mb-2">
                            Don't have an account yet? <a href="{{ route('register') }}" class="fw-bold text-rflms text-decoration-none">Register New Account</a>
                        </p>
                    @else
                        <p class="small text-secondary mb-2">
                            Are you a citizen applying for a license? <a href="{{ route('login') }}" class="fw-bold text-rflms text-decoration-none">Citizen Login</a>
                        </p>
                    @endif

                    <div class="d-flex align-items-center justify-content-center gap-3 small mt-3">
                        <a href="{{ route('catalogue.index') }}" class="text-secondary text-decoration-none">
                            <i class="bi bi-water me-1 text-primary"></i> Water Bodies
                        </a>
                        <span class="text-muted">•</span>
                        <a href="{{ route('violations.create') }}" class="text-secondary text-decoration-none">
                            <i class="bi bi-exclamation-triangle me-1 text-warning"></i> Report Violation
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('togglePasswordBtn');
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

