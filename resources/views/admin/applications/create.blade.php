@extends('layouts.admin')

@section('title', 'Walk-in Citizen Application — '.config('app.name'))

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="page-title-block mb-0">
        <div class="title-icon"><i class="bi bi-person-plus-fill"></i></div>
        <div>
            <h1 class="h3 fw-bold mb-1">Walk-in Licence Application</h1>
            <p class="page-subtitle mb-0">Apply and issue e-licences on behalf of walk-in citizens at the counter</p>
        </div>
    </div>
    <a href="{{ route('admin.applications.index') }}" class="btn btn-outline-secondary btn-chip">
        <i class="bi bi-arrow-left me-1"></i> Back to Applications
    </a>
</div>

@if ($errors->any())
    <div class="alert alert-danger mb-4">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.applications.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="row g-4">
        <!-- Left Column: Citizen Selection / Registration -->
        <div class="col-lg-7">
            <div class="content-shell mb-4">
                <div class="content-shell-header px-4 py-3 border-bottom">
                    <h5 class="fw-bold mb-0"><i class="bi bi-person-badge me-2 text-rflms"></i>1. Citizen Information</h5>
                </div>
                <div class="content-shell-body p-4">
                    
                    <!-- Citizen Mode Selection -->
                    <div class="mb-4 p-3 bg-light rounded-3 border">
                        <label class="form-label fw-semibold d-block mb-2">Registration Mode</label>
                        <div class="d-flex gap-4">
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="citizen_type" id="citizenExisting" value="existing" @checked(old('citizen_type', 'new') === 'existing') onchange="toggleCitizenMode()">
                                <label class="form-check-label fw-medium" for="citizenExisting">Select Existing Citizen</label>
                            </div>
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="citizen_type" id="citizenNew" value="new" @checked(old('citizen_type', 'new') === 'new') onchange="toggleCitizenMode()">
                                <label class="form-check-label fw-medium" for="citizenNew">Register New Walk-in Citizen</label>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Citizen Dropdown -->
                    <div id="existingCitizenBlock" class="mb-3 py-2" style="display: none;">
                        <label class="form-label fw-medium" for="user_id">Select Registered Citizen</label>
                        <select name="user_id" id="user_id" class="form-select form-select-lg @error('user_id') is-invalid @enderror">
                            <option value="">-- Choose Citizen --</option>
                            @foreach ($citizens as $c)
                                <option value="{{ $c->id }}" @selected(old('user_id') == $c->id)>
                                    {{ $c->name }} (CNIC: {{ $c->citizenProfile?->cnic ?? 'N/A' }} | {{ $c->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- New Citizen Form Fields -->
                    <div id="newCitizenBlock">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="full_name">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" id="full_name" value="{{ old('full_name') }}" class="form-control @error('full_name') is-invalid @enderror" placeholder="As per CNIC">
                                @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="cnic">CNIC No. <span class="text-danger">*</span></label>
                                <input type="text" name="cnic" id="cnic" value="{{ old('cnic') }}" class="form-control font-monospace @error('cnic') is-invalid @enderror" placeholder="12345-1234567-1">
                                @error('cnic') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="father_name">Father / Guardian Name <span class="text-danger">*</span></label>
                                <input type="text" name="father_name" id="father_name" value="{{ old('father_name') }}" class="form-control @error('father_name') is-invalid @enderror">
                                @error('father_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="mobile">Mobile Number <span class="text-danger">*</span></label>
                                <input type="text" name="mobile" id="mobile" value="{{ old('mobile') }}" class="form-control @error('mobile') is-invalid @enderror" placeholder="03xx-xxxxxxx">
                                @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="email">Email Address <span class="text-muted small">(Optional)</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="Auto-generated if empty">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="password">Account Password <span class="text-muted small">(Optional)</span></label>
                                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="Auto-generated if left empty" minlength="8">
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="dob">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" name="dob" id="dob" value="{{ old('dob') }}" class="form-control @error('dob') is-invalid @enderror">
                                @error('dob') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="gender">Gender <span class="text-danger">*</span></label>
                                <select name="gender" id="gender" class="form-select @error('gender') is-invalid @enderror">
                                    <option value="">Select Gender</option>
                                    <option value="male" @selected(old('gender') === 'male')>Male</option>
                                    <option value="female" @selected(old('gender') === 'female')>Female</option>
                                    <option value="other" @selected(old('gender') === 'other')>Other</option>
                                </select>
                                @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="residence_district_id">Residence District <span class="text-danger">*</span></label>
                                <select name="residence_district_id" id="residence_district_id" class="form-select @error('residence_district_id') is-invalid @enderror">
                                    <option value="">Select District</option>
                                    @foreach ($districts as $d)
                                        <option value="{{ $d->id }}" @selected(old('residence_district_id') == $d->id)>{{ $d->name }}</option>
                                    @endforeach
                                </select>
                                @error('residence_district_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="province">Province <span class="text-danger">*</span></label>
                                <input type="text" name="province" id="province" value="{{ old('province', 'Khyber Pakhtunkhwa') }}" class="form-control @error('province') is-invalid @enderror">
                                @error('province') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="emergency_contact">Emergency Contact <span class="text-danger">*</span></label>
                                <input type="text" name="emergency_contact" id="emergency_contact" value="{{ old('emergency_contact') }}" class="form-control @error('emergency_contact') is-invalid @enderror" placeholder="Phone or relationship">
                                @error('emergency_contact') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="address">Postal Address <span class="text-danger">*</span></label>
                                <textarea name="address" id="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="photo">Angler Profile Photo <span class="text-muted small">(For Licence Card)</span></label>
                                <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/jpg,image/webp" class="form-control @error('photo') is-invalid @enderror">
                                <div class="form-text small">Upload clear face photo (Max 2MB).</div>
                                @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Right Column: Permit & Payment Details -->
        <div class="col-lg-5">
            
            <!-- Permit Selection Shell -->
            <div class="content-shell mb-4">
                <div class="content-shell-header px-4 py-3 border-bottom">
                    <h5 class="fw-bold mb-0"><i class="bi bi-water me-2 text-rflms"></i>2. Licence Details</h5>
                </div>
                <div class="content-shell-body p-4">
                    <div class="mb-3">
                        <label class="form-label" for="reservoir_id">Water Body / Reservoir <span class="text-danger">*</span></label>
                        <select name="reservoir_id" id="reservoir_id" class="form-select @error('reservoir_id') is-invalid @enderror" required>
                            <option value="">-- Select Water Body --</option>
                            @foreach ($reservoirs as $res)
                                <option value="{{ $res->id }}" @selected(old('reservoir_id') == $res->id)>
                                    {{ $res->name }} (Dist: {{ $res->district?->name }})
                                </option>
                            @endforeach
                        </select>
                        @error('reservoir_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="category_id">Licence Category <span class="text-danger">*</span></label>
                        <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                            <option value="">-- Select Category --</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>
                                    {{ $cat->name }} — {{ number_format((float)$cat->fee_amount, 0) }} {{ $cat->currency }} ({{ $cat->validity_duration_days }} days)
                                </option>
                            @endforeach
                        </select>
                        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="fishing_start_date">Fishing Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="fishing_start_date" id="fishing_start_date" value="{{ old('fishing_start_date', now()->toDateString()) }}" class="form-control @error('fishing_start_date') is-invalid @enderror" required>
                        @error('fishing_start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <!-- Payment & Counter Approval Shell -->
            <div class="content-shell mb-4">
                <div class="content-shell-header px-4 py-3 border-bottom">
                    <h5 class="fw-bold mb-0"><i class="bi bi-cash-stack me-2 text-rflms"></i>3. Payment &amp; Counter Approval</h5>
                </div>
                <div class="content-shell-body p-4">
                    <div class="mb-3">
                        <label class="form-label" for="payment_method">Payment Collection Method <span class="text-danger">*</span></label>
                        <select name="payment_method" id="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                            @foreach ($paymentMethods as $key => $label)
                                <option value="{{ $key }}" @selected(old('payment_method', 'counter_cash') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="payment_reference">Payment Reference / Cash Receipt No.</label>
                        <input type="text" name="payment_reference" id="payment_reference" value="{{ old('payment_reference') }}" class="form-control @error('payment_reference') is-invalid @enderror" placeholder="Counter receipt / Bank reference">
                        @error('payment_reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="payment_receipt">Attach Payment Slip <span class="text-muted small">(Optional)</span></label>
                        <input type="file" name="payment_receipt" id="payment_receipt" accept="image/*,application/pdf" class="form-control @error('payment_receipt') is-invalid @enderror">
                        @error('payment_receipt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="officer_remarks">Officer Processing Remarks</label>
                        <textarea name="officer_remarks" id="officer_remarks" rows="2" class="form-control @error('officer_remarks') is-invalid @enderror" placeholder="Remarks for verification audit...">Walk-in application registered and processed at counter.</textarea>
                        @error('officer_remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-check p-3 bg-light rounded-3 border mb-4">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="auto_approve" id="auto_approve" value="1" @checked(old('auto_approve', 1) == 1)>
                        <label class="form-check-label fw-bold text-success" for="auto_approve">
                            <i class="bi bi-patch-check-fill me-1"></i> Auto-Approve &amp; Issue E-Licence Card On-the-spot
                        </label>
                        <div class="form-text small mt-1 ms-4">Generates active licence permit and QR code immediately.</div>
                    </div>

                    <button type="submit" class="btn btn-rflms btn-lg w-100 py-3 fw-bold">
                        <i class="bi bi-check-circle-fill me-2"></i> Submit &amp; Process Walk-in Application
                    </button>
                </div>
            </div>

        </div>
    </div>
</form>

@push('scripts')
<script>
    function toggleCitizenMode() {
        const isExisting = document.getElementById('citizenExisting').checked;
        document.getElementById('existingCitizenBlock').style.display = isExisting ? 'block' : 'none';
        document.getElementById('newCitizenBlock').style.display = isExisting ? 'none' : 'block';
    }
    document.addEventListener('DOMContentLoaded', toggleCitizenMode);
</script>
@endpush
@endsection
