@extends('layouts.auth')
@section('title', 'Create Citizen Account — '.config('app.name'))

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

    <!-- Right Register Form Glass Card -->
    <div class="col-12 col-md-9 col-lg-6 max-w-500">
        <div class="card auth-glass-card border-0">
            <div class="card-body p-4 p-sm-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex p-3 rounded-circle bg-success-subtle text-success mb-3 shadow-sm">
                        <i class="bi bi-person-plus-fill fs-3 text-rflms"></i>
                    </div>
                    <h2 class="h4 fw-bold mb-1 text-dark">Create Citizen Account</h2>
                    <p class="text-muted small mb-0">We will email a one-time code to verify your address.</p>
                </div>

                <form method="POST" action="{{ route('register.otp') }}" enctype="multipart/form-data">
                    @csrf
                    <!-- Full Name -->
                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold small" for="name">Full Name</label>
                        <div class="input-group input-aquatic-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input id="name" type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Muhammad Ali" required autofocus>
                        </div>
                        @error('name') <div class="invalid-feedback d-block small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <!-- Email Address -->
                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold small" for="email">Email Address</label>
                        <div class="input-group input-aquatic-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="name@example.com" required>
                        </div>
                        @error('email') <div class="invalid-feedback d-block small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <!-- CNIC Number -->
                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold small" for="cnic">CNIC Number</label>
                        <div class="input-group input-aquatic-group">
                            <span class="input-group-text"><i class="bi bi-card-heading"></i></span>
                            <input id="cnic" type="text" name="cnic" value="{{ old('cnic') }}" class="form-control @error('cnic') is-invalid @enderror" placeholder="12345-1234567-1" required maxlength="15" autocomplete="off" inputmode="numeric">
                        </div>
                        <div class="form-text small text-secondary mt-1">Format: 12345-1234567-1</div>
                        @error('cnic') <div class="invalid-feedback d-block small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <!-- Profile Picture (For Digital Licence Card) -->
                    <div class="mb-4">
                        <label class="form-label text-dark fw-semibold small mb-1" for="photo">
                            Profile Picture <span class="text-muted fw-normal">(For Licence Card)</span>
                        </label>
                        <div class="input-group input-aquatic-group">
                            <span class="input-group-text"><i class="bi bi-camera"></i></span>
                            <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/jpg,image/webp" class="form-control @error('photo') is-invalid @enderror">
                        </div>
                        <div class="form-text small text-secondary mt-1">Clear face photo for your digital licence card (Max 2MB).</div>
                        @error('photo') <div class="invalid-feedback d-block small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <!-- Submit Button -->
                    <button class="btn btn-aquatic-submit w-100" type="submit">
                        <i class="bi bi-send-check me-1"></i> Send Verification Code
                    </button>
                </form>

                <!-- Footer Links -->
                <div class="text-center mt-4 pt-3 border-top border-light-subtle">
                    <p class="small text-secondary mb-2">
                        Already registered? <a href="{{ route('login') }}" class="fw-bold text-rflms text-decoration-none">Sign In to Account</a>
                    </p>

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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cnicInput = document.getElementById('cnic');
    if (!cnicInput) return;

    function formatCNIC(val) {
        let digits = (val || '').replace(/\D/g, '');
        if (digits.length > 13) {
            digits = digits.substring(0, 13);
        }
        let formatted = '';
        if (digits.length > 0) {
            formatted += digits.substring(0, Math.min(5, digits.length));
        }
        if (digits.length > 5) {
            formatted += '-' + digits.substring(5, Math.min(12, digits.length));
        }
        if (digits.length > 12) {
            formatted += '-' + digits.substring(12, 13);
        }
        return formatted;
    }

    cnicInput.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace') {
            const start = this.selectionStart;
            const end = this.selectionEnd;
            if (start === end && (start === 6 || start === 14)) {
                e.preventDefault();
                const val = this.value;
                const newVal = val.substring(0, start - 2) + val.substring(start);
                this.value = formatCNIC(newVal);
                const newPos = Math.max(0, start - 2);
                this.setSelectionRange(newPos, newPos);
            }
        }
    });

    cnicInput.addEventListener('input', function () {
        const start = this.selectionStart;
        const oldVal = this.value;
        const formatted = formatCNIC(oldVal);

        if (oldVal !== formatted) {
            let digitsBefore = oldVal.substring(0, start).replace(/\D/g, '').length;
            if (digitsBefore > 13) digitsBefore = 13;

            this.value = formatted;

            let newPos = 0;
            let count = 0;
            for (let i = 0; i < formatted.length; i++) {
                if (/\d/.test(formatted[i])) {
                    count++;
                }
                if (count >= digitsBefore) {
                    newPos = i + 1;
                    break;
                }
            }
            if (digitsBefore === 0) newPos = 0;
            this.setSelectionRange(newPos, newPos);
        }
    });

    cnicInput.addEventListener('paste', function (e) {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text');
        this.value = formatCNIC(text);
    });

    if (cnicInput.value) {
        cnicInput.value = formatCNIC(cnicInput.value);
    }
});
</script>
@endpush

