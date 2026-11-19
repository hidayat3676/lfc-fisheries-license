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
                                        <option value="{{ $d->id }}" @selected(old('residence_district_id') == $d->id)>{{ $d->name }}{{ $d->urdu_name ? ' ('.$d->urdu_name.')' : '' }}</option>
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
                        <label class="form-label" for="district_id">District <span class="text-danger">*</span></label>
                        <select name="district_id" id="district_id" class="form-select @error('district_id') is-invalid @enderror" required onchange="renderDistrictWaterBodies()">
                            <option value="">-- Select District --</option>
                            @foreach (($licensingDistricts ?? $districts) as $d)
                                <option value="{{ $d->id }}" @selected(old('district_id') == $d->id)>
                                    {{ $d->name }}{{ $d->urdu_name ? ' ('.$d->urdu_name.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('district_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between align-items-center mb-1">
                            <span>Available Water Bodies in District</span>
                            <span class="badge text-bg-light border text-secondary fw-normal" id="waterBodiesCountBadge">0 available</span>
                        </label>
                        <div id="districtWaterBodiesBox" class="p-3 bg-light rounded-3 border small" style="max-height: 240px; overflow-y: auto;">
                            <div class="text-secondary" id="waterBodiesPlaceholder">
                                <i class="bi bi-info-circle me-1"></i> Select a district above to view available water bodies.
                            </div>
                            <ul class="list-unstyled mb-0 d-none" id="waterBodiesList"></ul>
                        </div>
                        <div class="form-text small">Available water bodies in the selected district (no selection required).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="category_id">Licence Category <span class="text-danger">*</span></label>
                        <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required onchange="handleCategoryChange()">
                            <option value="">-- Select Category --</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>
                                    {{ $cat->name }}{{ $cat->urdu_name ? ' ('.$cat->urdu_name.')' : '' }} — {{ number_format((float)$cat->fee_amount, 0) }} {{ $cat->currency }} ({{ $cat->duration_days ? $cat->duration_days.' days' : ucfirst($cat->duration_type) }})
                                </option>
                            @endforeach
                        </select>
                        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3" id="dailyStartDateBlock">
                        <label class="form-label" for="fishing_start_date">Fishing Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="fishing_start_date" id="fishing_start_date" value="{{ old('fishing_start_date', now()->toDateString()) }}" class="form-control @error('fishing_start_date') is-invalid @enderror" required>
                        @error('fishing_start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3 d-none" id="seasonalDatesBlock">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label" for="seasonal_from_date">From Date (Current Date)</label>
                                <input type="date" id="seasonal_from_date" value="{{ now()->toDateString() }}" class="form-control bg-light" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="seasonal_end_date">End Date (Closed Season)</label>
                                <input type="date" id="seasonal_end_date" value="" class="form-control bg-light" readonly>
                            </div>
                        </div>
                        <div class="form-text small mt-1">
                            <i class="bi bi-calendar-check text-rflms me-1"></i>
                            Seasonal licence starts from the current date and expires on the closed season end date defined for this licence (<span id="seasonalEndDateLabel">—</span>).
                        </div>
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
                        <input type="text" name="payment_reference" id="payment_reference" value="{{ old('payment_reference') }}" class="form-control @error('payment_reference') is-invalid @enderror" placeholder="Counter cash receipt no. (optional)">
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

@php
    $waterBodiesByDistrict = ($licensingDistricts ?? $districts)->mapWithKeys(fn ($d) => [
        $d->id => ($d->reservoirs ?? collect())->map(function ($r) {
            $lat = $r->latitude ?? $r->start_lat;
            $lng = $r->longitude ?? $r->start_lng;
            $mapUrl = ($lat !== null && $lng !== null)
                ? 'https://www.google.com/maps?q=' . $lat . ',' . $lng
                : $r->directions_url;

            return [
                'id' => $r->id,
                'name' => $r->name,
                'type' => $r->typeLabel(),
                'trout_type' => $r->trout_type,
                'trout_label' => $r->troutLabel(),
                'license_type' => $r->licensePermitsLabel(),
                'lat' => $lat,
                'lng' => $lng,
                'map_url' => $mapUrl,
            ];
        })->values(),
    ]);
    $policyService = app(\App\Services\LicensingPolicyService::class);
    $todayStr = now()->toDateString();
    $categoryMeta = $categories->mapWithKeys(function ($cat) use ($policyService, $todayStr) {
        $isSeasonal = $cat->duration_type === 'seasonal'
            || in_array($cat->expiry_rule, ['fixed_date', 'season_end'], true)
            || str_contains(strtolower((string) $cat->name), 'seasonal');
        $endDate = $policyService->calculateEndDate($cat, $todayStr);

        return [
            $cat->id => [
                'id' => $cat->id,
                'duration_type' => $cat->duration_type,
                'is_seasonal' => $isSeasonal,
                'from_date' => $todayStr,
                'end_date' => $endDate->toDateString(),
                'end_date_formatted' => $endDate->format('d M Y'),
            ],
        ];
    });
@endphp

@push('scripts')
<script>
    const districtWaterBodies = @json($waterBodiesByDistrict);
    const categoriesMeta = @json($categoryMeta);
    const currentDateStr = @json($todayStr);

    function toggleCitizenMode() {
        const isExisting = document.getElementById('citizenExisting').checked;
        document.getElementById('existingCitizenBlock').style.display = isExisting ? 'block' : 'none';
        document.getElementById('newCitizenBlock').style.display = isExisting ? 'none' : 'block';
    }

    function handleCategoryChange() {
        const categoryId = document.getElementById('category_id')?.value;
        const meta = categoryId ? categoriesMeta[categoryId] : null;
        const dailyBlock = document.getElementById('dailyStartDateBlock');
        const seasonalBlock = document.getElementById('seasonalDatesBlock');
        const startInput = document.getElementById('fishing_start_date');
        const seasonalFromInput = document.getElementById('seasonal_from_date');
        const seasonalEndInput = document.getElementById('seasonal_end_date');
        const seasonalEndLabel = document.getElementById('seasonalEndDateLabel');

        if (meta && meta.is_seasonal) {
            dailyBlock.classList.add('d-none');
            seasonalBlock.classList.remove('d-none');
            if (startInput) {
                startInput.value = meta.from_date || currentDateStr;
            }
            if (seasonalFromInput) {
                seasonalFromInput.value = meta.from_date || currentDateStr;
            }
            if (seasonalEndInput) {
                seasonalEndInput.value = meta.end_date || '';
            }
            if (seasonalEndLabel) {
                seasonalEndLabel.textContent = meta.end_date_formatted || meta.end_date || '';
            }
        } else {
            dailyBlock.classList.remove('d-none');
            seasonalBlock.classList.add('d-none');
        }
    }

    function renderDistrictWaterBodies() {
        const districtId = document.getElementById('district_id')?.value;
        const placeholder = document.getElementById('waterBodiesPlaceholder');
        const listEl = document.getElementById('waterBodiesList');
        const countBadge = document.getElementById('waterBodiesCountBadge');

        if (!districtId) {
            placeholder.innerHTML = '<i class="bi bi-info-circle me-1"></i> Select a district above to view available water bodies.';
            placeholder.classList.remove('d-none');
            listEl.classList.add('d-none');
            listEl.innerHTML = '';
            countBadge.textContent = '0 available';
            return;
        }

        const items = districtWaterBodies[districtId] || [];
        countBadge.textContent = items.length + ' available';

        if (items.length === 0) {
            placeholder.innerHTML = '<i class="bi bi-exclamation-circle text-warning me-1"></i> No active water bodies found in this district.';
            placeholder.classList.remove('d-none');
            listEl.classList.add('d-none');
            listEl.innerHTML = '';
            return;
        }

        placeholder.classList.add('d-none');
        listEl.classList.remove('d-none');
        listEl.innerHTML = items.map(item => {
            const troutBadgeClass = item.trout_type === 'trout'
                ? 'text-bg-info text-dark'
                : (item.trout_type === 'mixed' ? 'text-bg-warning text-dark' : 'text-bg-secondary');

            const mapBtn = item.map_url
                ? `<a href="${item.map_url}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;" title="Open in Google Maps (${item.lat ?? ''}, ${item.lng ?? ''})">
                       <i class="bi bi-geo-alt-fill me-1"></i>Google Map
                   </a>`
                : `<span class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-geo-alt me-1"></i>No coordinates</span>`;

            const licensePermitsRow = item.license_type
                ? `<div class="ps-3 mt-1 text-secondary" style="font-size: 0.78rem;">
                       <span class="fw-semibold text-dark"><i class="bi bi-card-checklist text-rflms me-1"></i>License / Permits:</span>
                       <span>${item.license_type}</span>
                   </div>`
                : '';

            return `
                <li class="py-2 border-bottom border-light-subtle">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <span class="fw-semibold text-dark"><i class="bi bi-water text-rflms me-1"></i>${item.name}</span>
                        ${mapBtn}
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-1 ps-3">
                        <span class="badge text-bg-white border text-secondary fw-normal">${item.type}</span>
                        <span class="badge ${troutBadgeClass} fw-normal">${item.trout_label}</span>
                    </div>
                    ${licensePermitsRow}
                </li>
            `;
        }).join('');
    }

    document.addEventListener('DOMContentLoaded', function () {
        toggleCitizenMode();
        renderDistrictWaterBodies();
        handleCategoryChange();
    });
</script>
@endpush
@endsection
