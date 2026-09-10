@extends('layouts.app')

@section('title', 'Apply — '.$reservoir->name)

@section('content')
<section class="container py-4">
    <a href="{{ route('catalogue.show', $reservoir) }}" class="small text-decoration-none">&larr; Back to Water Body Details</a>
    
    <div class="d-flex align-items-center justify-content-between mt-2 mb-3">
        <div>
            <h1 class="h3 fw-bold mb-1">Apply for Fishing Licence</h1>
            <p class="text-secondary mb-0">{{ $reservoir->name }} · District {{ $reservoir->district?->name }}</p>
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
            <form method="POST" action="{{ route('citizen.applications.store', $reservoir) }}" enctype="multipart/form-data" class="bg-white rounded-4 shadow-sm p-4 border"
                  x-data="{ method: '{{ old('payment_method', 'bank_transfer') }}' }">
                @csrf

                <h2 class="h6 fw-bold mb-3 text-dark border-bottom pb-2">
                    <i class="bi bi-pen-fill text-primary me-1"></i> Application Details
                </h2>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Licence Category &amp; Duration</label>
                    <select name="category_id" class="form-select form-select-lg" required>
                        <option value="">-- Select Licence Category --</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                {{ $category->name }} — {{ $category->feeLabel() }} ({{ $category->duration_days ? $category->duration_days.' Days' : 'Standard' }})
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text small">Select the permit category that matches your planned fishing period.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Fishing Start Date</label>
                    <input type="date" name="fishing_start_date" class="form-control form-control-lg" required
                           min="{{ now()->toDateString() }}" value="{{ old('fishing_start_date', now()->toDateString()) }}">
                    <div class="form-text small">Expiry date will be calculated automatically based on your selected category duration.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Payment Method</label>
                    <select name="payment_method" class="form-select form-select-lg" x-model="method" required>
                        @foreach ($paymentMethods as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3" x-show="method === '1bill'">
                    <div class="alert alert-info small mb-0 rounded-3">
                        <i class="bi bi-info-circle-fill me-1"></i> A demo PSID code will be generated upon submission. You can process payment using 1Bill / PSID reconciliation.
                    </div>
                </div>

                <div class="mb-3" x-show="method !== '1bill'">
                    <label class="form-label fw-semibold">Transaction / Deposit Reference No.</label>
                    <input type="text" name="payment_reference" class="form-control" placeholder="e.g. TRX-99882211" value="{{ old('payment_reference') }}">
                </div>

                <div class="mb-3" x-show="method !== '1bill'">
                    <label class="form-label fw-semibold">Upload Payment Receipt Proof (JPG, PNG, PDF)</label>
                    <input type="file" name="payment_receipt" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    <div class="form-text small">Maximum file size: 4MB. Upload clear image or PDF of bank deposit slip.</div>
                </div>

                <div class="form-check mb-4 pt-2 border-top">
                    <input class="form-check-input" type="checkbox" name="accept_terms" value="1" id="accept_terms" required>
                    <label class="form-check-label small" for="accept_terms">
                        I hereby declare that the details provided are correct and I agree to abide by the KP Fisheries Conservation Laws and angling regulations for <strong>{{ $reservoir->name }}</strong>.
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
                    <li class="mb-2">Licence is strictly valid only for <strong>{{ $reservoir->name }}</strong> within District {{ $reservoir->district?->name }}.</li>
                    <li class="mb-2">Only Rod &amp; Line fishing is permitted. Netting and explosives are illegal offences.</li>
                    <li class="mb-2">Your digital licence PDF will be generated immediately after district officer verification.</li>
                    <li>Always present your QR E-Licence when requested by Fisheries Officers.</li>
                </ul>
            </div>
        </div>
    </div>
</section>
@endsection
