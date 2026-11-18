@extends('layouts.app')

@section('title', 'Apply for Fishing Licence — '.config('app.name'))

@section('content')
<section class="container py-4">
    @if ($reservoir)
        <a href="{{ route('catalogue.show', $reservoir) }}" class="small text-decoration-none">&larr; Back to Water Body Details</a>
    @else
        <a href="{{ route('reservoirs.index') }}" class="small text-decoration-none">&larr; Back to Reservoirs &amp; Water Bodies</a>
    @endif

    <div class="d-flex align-items-center justify-content-between mt-2 mb-3">
        <div>
            <h1 class="h3 fw-bold mb-1">Apply for Fishing Licence</h1>
            <p class="text-secondary mb-0">
                Select your district to view available water bodies, choose a licence category, and submit your application.
            </p>
        </div>
        <span class="badge bg-success px-3 py-2 rounded-pill">
            <i class="bi bi-shield-check me-1"></i> Open for E-Licence
        </span>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <!-- Application Form -->
        <div class="col-lg-7">
            <form method="POST"
                  action="{{ $reservoir ? route('citizen.applications.store', $reservoir) : route('citizen.applications.submit') }}"
                  enctype="multipart/form-data"
                  class="bg-white rounded-4 shadow-sm p-4 border"
                  x-data="{ method: '{{ old('payment_method', '1bill') }}' }">
                @csrf

                <h2 class="h6 fw-bold mb-3 text-dark border-bottom pb-2">
                    <i class="bi bi-pen-fill text-primary me-1"></i> Application Details
                </h2>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="district_id">District <span class="text-danger">*</span></label>
                    <select name="district_id" id="district_id" class="form-select form-select-lg @error('district_id') is-invalid @enderror" required onchange="renderDistrictWaterBodies()">
                        <option value="">-- Select District --</option>
                        @foreach ($districts as $d)
                            <option value="{{ $d->id }}" @selected(old('district_id', $selectedDistrictId) == $d->id)>
                                {{ $d->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('district_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold d-flex justify-content-between align-items-center mb-1">
                        <span>Available Water Bodies in District</span>
                        <span class="badge text-bg-light border text-secondary fw-normal" id="waterBodiesCountBadge">0 available</span>
                    </label>
                    <div id="districtWaterBodiesBox" class="p-3 bg-light rounded-3 border small" style="max-height: 260px; overflow-y: auto;">
                        <div class="text-secondary" id="waterBodiesPlaceholder">
                            <i class="bi bi-info-circle me-1"></i> Select a district above to view available water bodies.
                        </div>
                        <ul class="list-unstyled mb-0 d-none" id="waterBodiesList"></ul>
                    </div>
                    <div class="form-text small">Available water bodies in the selected district (no individual selection required).</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="category_id">Licence Category &amp; Duration <span class="text-danger">*</span></label>
                    <select name="category_id" id="category_id" class="form-select form-select-lg @error('category_id') is-invalid @enderror" required onchange="handleCategoryChange()">
                        <option value="">-- Select Licence Category --</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                {{ $category->name }} — {{ $category->feeLabel() }} ({{ $category->duration_days ? $category->duration_days.' Days' : ucfirst($category->duration_type) }})
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text small">Select the permit category that matches your planned fishing period.</div>
                    @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3" id="dailyStartDateBlock">
                    <label class="form-label fw-semibold" for="fishing_start_date">Fishing Start Date <span class="text-danger">*</span></label>
                    <input type="date" name="fishing_start_date" id="fishing_start_date" class="form-control form-control-lg" required
                           min="{{ now()->toDateString() }}" value="{{ old('fishing_start_date', now()->toDateString()) }}">
                    <div class="form-text small">Expiry date will be calculated automatically based on your selected category duration.</div>
                </div>

                <div class="mb-3 d-none" id="seasonalDatesBlock">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="seasonal_from_date">From Date (Current Date)</label>
                            <input type="date" id="seasonal_from_date" value="{{ now()->toDateString() }}" class="form-control form-control-lg bg-light" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="seasonal_end_date">End Date (Closed Season)</label>
                            <input type="date" id="seasonal_end_date" value="" class="form-control form-control-lg bg-light" readonly>
                        </div>
                    </div>
                    <div class="form-text small mt-1">
                        <i class="bi bi-calendar-check text-rflms me-1"></i>
                        Seasonal licence starts from the current date and expires on the closed season end date defined for this licence (<span id="seasonalEndDateLabel">—</span>).
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Payment Method</label>
                    <select name="payment_method" class="form-select form-select-lg" x-model="method" required>
                        @foreach ($paymentMethods as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3" x-show="method === '1bill' || method === 'bank_transfer'">
                    <div class="alert alert-info small mb-0 rounded-3">
                        <i class="bi bi-info-circle-fill me-1"></i> An official <strong>PAYMIR PSID</strong> voucher will be generated automatically upon submission so you can complete your online payment via PAYMIR / 1Bill.
                    </div>
                </div>

                <div class="mb-3" x-show="method === 'bank_deposit'">
                    <label class="form-label fw-semibold">Transaction / Deposit Reference No.</label>
                    <input type="text" name="payment_reference" class="form-control" placeholder="e.g. TRX-99882211" value="{{ old('payment_reference') }}">
                </div>

                <div class="mb-3" x-show="method === 'bank_deposit'">
                    <label class="form-label fw-semibold">Upload Payment Receipt Proof (JPG, PNG, PDF)</label>
                    <input type="file" name="payment_receipt" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    <div class="form-text small">Maximum file size: 4MB. Upload clear image or PDF of bank deposit slip.</div>
                </div>

                <div class="form-check mb-4 pt-2 border-top">
                    <input class="form-check-input" type="checkbox" name="accept_terms" value="1" id="accept_terms" required>
                    <label class="form-check-label small" for="accept_terms">
                        I hereby declare that the details provided are correct and I agree to abide by the KP Fisheries Conservation Laws and angling regulations for <strong id="selectedDistrictTermsLabel">{{ $reservoir?->name ?? 'the selected district water bodies' }}</strong>.
                    </label>
                </div>

                <button class="btn btn-rflms btn-lg w-100 py-2.5 fw-bold" type="submit">
                    <i class="bi bi-send-fill me-1"></i> Submit Licence Application
                </button>
            </form>
        </div>

        <!-- Official Fee Structure & Guidelines Sidebar -->
        <div class="col-lg-5">
            <!-- Official Fee Structure Card -->
            <div class="bg-white rounded-4 shadow-sm p-4 border mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <h2 class="h6 fw-bold mb-0 text-dark">
                        <i class="bi bi-tags-fill text-success me-1"></i> Official Fee Structure
                    </h2>
                    <span class="badge bg-light text-dark border" style="font-size:0.7rem">Approved Rates</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Category</th>
                                <th>Duration</th>
                                <th class="text-end">Fee (PKR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $cat)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $cat->name }}</div>
                                        @if ($cat->max_fish_limit)
                                            <div class="text-muted" style="font-size: 0.7rem;">Limit: {{ $cat->max_fish_limit }} fish/day</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-dark border" style="font-size:0.7rem">
                                            {{ $cat->duration_days ? $cat->duration_days.' Days' : 'Fixed' }}
                                        </span>
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-success">
                                        {{ number_format((float) $cat->fee_amount, 0) }} {{ $cat->currency }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="form-text small mt-2">
                    <i class="bi bi-shield-check text-success me-1"></i> Fee snapshots are securely locked at the time of submission.
                </div>
            </div>

            <!-- Guidelines Card -->
            <div class="bg-white rounded-4 shadow-sm p-4 border">
                <h2 class="h6 fw-bold mb-3 text-dark">
                    <i class="bi bi-info-circle-fill text-primary me-1"></i> Important Regulations
                </h2>
                <ul class="small text-secondary ps-3 mb-0">
                    <li class="mb-2">Licence is strictly valid for the designated water bodies within your selected district.</li>
                    <li class="mb-2">Only permitted fishing gear for your licence category is allowed. Explosives, poison, and electric current are illegal offences.</li>
                    <li class="mb-2">Your digital licence PDF will be generated immediately after district officer verification.</li>
                    <li>Always present your QR E-Licence when requested by Fisheries Officers.</li>
                </ul>
            </div>
        </div>
    </div>
</section>

@php
    $waterBodiesByDistrict = $districts->mapWithKeys(fn ($d) => [
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
        const selectEl = document.getElementById('district_id');
        const districtId = selectEl?.value;
        const placeholder = document.getElementById('waterBodiesPlaceholder');
        const listEl = document.getElementById('waterBodiesList');
        const countBadge = document.getElementById('waterBodiesCountBadge');
        const termsLabel = document.getElementById('selectedDistrictTermsLabel');

        if (!districtId) {
            placeholder.innerHTML = '<i class="bi bi-info-circle me-1"></i> Select a district above to view available water bodies.';
            placeholder.classList.remove('d-none');
            listEl.classList.add('d-none');
            listEl.innerHTML = '';
            countBadge.textContent = '0 available';
            if (termsLabel) {
                termsLabel.textContent = 'the selected district water bodies';
            }
            return;
        }

        const districtName = selectEl.options[selectEl.selectedIndex]?.text?.trim() || 'selected district';
        if (termsLabel) {
            termsLabel.textContent = 'District ' + districtName;
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
        renderDistrictWaterBodies();
        handleCategoryChange();
    });
</script>
@endpush
@endsection
