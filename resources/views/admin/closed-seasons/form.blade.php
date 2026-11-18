@extends('layouts.admin')

@section('title', ($mode === 'create' ? 'Add closed season' : 'Edit closed season').' — '.config('app.name'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 fw-bold mb-1">
            {{ $mode === 'create' ? 'Add closed season' : 'Edit closed season #'.$season->id }}
        </h1>
        <p class="text-secondary mb-0">Configure breeding closure window, scope, and enforcement dates</p>
    </div>
    <a href="{{ route('admin.closed-seasons.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to closed seasons
    </a>
</div>

@if ($errors->any())
    <div class="alert alert-danger mb-3">
        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please correct the errors below:</div>
        <ul class="mb-0 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST"
      action="{{ $mode === 'create' ? route('admin.closed-seasons.store') : route('admin.closed-seasons.update', $season) }}"
      class="bg-white rounded-4 shadow-sm p-4">
    @csrf
    @if ($mode === 'edit')
        @method('PUT')
    @endif

    {{-- Scope selection --}}
    <div class="mb-4">
        <h2 class="h6 fw-bold mb-2">1. Scope of closure</h2>
        <p class="small text-secondary mb-3">Select whether this closure applies to all waters in KP or a specific water body / category.</p>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100 scope-box" id="box-global">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="scope_type" id="scope_global"
                               value="global" @checked(old('scope_type', $season->scope_type) === 'global')
                               onchange="toggleScope(this.value)">
                        <label class="form-check-label fw-bold d-block" for="scope_global">
                            <i class="bi bi-globe text-primary me-1"></i> Province-wide (Global)
                        </label>
                        <span class="small text-secondary">Applies to all water bodies and all licence categories across KP.</span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100 scope-box" id="box-reservoir">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="scope_type" id="scope_reservoir"
                               value="reservoir" @checked(old('scope_type', $season->scope_type) === 'reservoir')
                               onchange="toggleScope(this.value)">
                        <label class="form-check-label fw-bold d-block" for="scope_reservoir">
                            <i class="bi bi-water text-info me-1"></i> Specific water body
                        </label>
                        <span class="small text-secondary">Applies only to a designated dam, river reach, or stream.</span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100 scope-box" id="box-category">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="scope_type" id="scope_category"
                               value="category" @checked(old('scope_type', $season->scope_type) === 'category')
                               onchange="toggleScope(this.value)">
                        <label class="form-check-label fw-bold d-block" for="scope_category">
                            <i class="bi bi-tag text-warning me-1"></i> Specific licence category
                        </label>
                        <span class="small text-secondary">Applies only to an individual licence type (e.g. Trout seasonal).</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Conditional Water Body dropdown --}}
        <div id="reservoir-selector" class="mt-3 {{ old('scope_type', $season->scope_type) === 'reservoir' ? '' : 'd-none' }}">
            <label class="form-label fw-semibold">Select water body <span class="text-danger">*</span></label>
            <select name="scope_id_reservoir" id="scope_id_reservoir" class="form-select @error('scope_id') is-invalid @enderror">
                <option value="">— Choose a water body —</option>
                @foreach ($reservoirs as $res)
                    <option value="{{ $res->id }}"
                        @selected((old('scope_type', $season->scope_type) === 'reservoir') && (old('scope_id', $season->scope_id) == $res->id))>
                        {{ $res->name }} @if ($res->district) ({{ $res->district->name }}) @endif
                    </option>
                @endforeach
            </select>
            <div class="form-text small">Only licences issued for this water body will enforce this closed season.</div>
        </div>

        {{-- Conditional Category dropdown --}}
        <div id="category-selector" class="mt-3 {{ old('scope_type', $season->scope_type) === 'category' ? '' : 'd-none' }}">
            <label class="form-label fw-semibold">Select licence category <span class="text-danger">*</span></label>
            <select name="scope_id_category" id="scope_id_category" class="form-select @error('scope_id') is-invalid @enderror">
                <option value="">— Choose a licence category —</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}"
                        @selected((old('scope_type', $season->scope_type) === 'category') && (old('scope_id', $season->scope_id) == $cat->id))>
                        {{ $cat->name }} ({{ $cat->code }}) — {{ $cat->feeLabel() }}
                    </option>
                @endforeach
            </select>
            <div class="form-text small">Only licences for this category will enforce this closed season.</div>
        </div>

        <input type="hidden" name="scope_id" id="hidden_scope_id" value="{{ old('scope_id', $season->scope_id) }}">
    </div>

    <hr class="my-4">

    {{-- Closure Period --}}
    <div class="mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h2 class="h6 fw-bold mb-0">2. Closure window (Annual recurring dates)</h2>
            <div class="d-flex flex-wrap gap-1">
                <span class="small text-secondary align-self-center me-1">Quick presets:</span>
                <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill"
                        onclick="setDates(6, 1, 7, 31, 'Statutory KP breeding closed season (Monsoon spawning)')">
                    01 Jun – 31 Jul
                </button>
                <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill"
                        onclick="setDates(10, 1, 3, 31, 'Cold-water trout winter spawning season')">
                    01 Oct – 31 Mar
                </button>
                <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill"
                        onclick="setDates(3, 15, 5, 15, 'Spring fish conservation period')">
                    15 Mar – 15 May
                </button>
            </div>
        </div>
        <p class="small text-secondary mb-3">
            Licence applications with a fishing start date falling within this window will be automatically rejected.
        </p>

        <div class="row g-3">
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Start month <span class="text-danger">*</span></label>
                <select name="start_month" id="start_month" class="form-select" required>
                    @foreach ($months as $num => $label)
                        <option value="{{ $num }}" @selected(old('start_month', $season->start_month) == $num)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Start day <span class="text-danger">*</span></label>
                <input type="number" name="start_day" id="start_day" min="1" max="31" class="form-control"
                       value="{{ old('start_day', $season->start_day) }}" required>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">End month <span class="text-danger">*</span></label>
                <select name="end_month" id="end_month" class="form-select" required>
                    @foreach ($months as $num => $label)
                        <option value="{{ $num }}" @selected(old('end_month', $season->end_month) == $num)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">End day <span class="text-danger">*</span></label>
                <input type="number" name="end_day" id="end_day" min="1" max="31" class="form-control"
                       value="{{ old('end_day', $season->end_day) }}" required>
            </div>
        </div>
    </div>

    <hr class="my-4">

    {{-- Reason and Status --}}
    <div class="mb-4">
        <h2 class="h6 fw-bold mb-2">3. Legal basis &amp; Enforcement state</h2>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label fw-semibold">Reason shown to citizens</label>
                <input type="text" name="reason" id="reason" class="form-control"
                       placeholder="e.g. Statutory breeding / closed season under KP Fisheries Act"
                       value="{{ old('reason', $season->reason) }}">
                <div class="form-text small">This message will appear in validation alerts when an applicant picks a date inside the closed season.</div>
            </div>
            <div class="col-md-4 d-flex align-items-center">
                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active"
                           name="is_active" value="1" @checked(old('is_active', $season->is_active ?? true))>
                    <label class="form-check-label fw-bold" for="is_active">
                        Enforce this closed season
                    </label>
                    <div class="small text-secondary">Uncheck to temporarily suspend without deleting.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
        <a href="{{ route('admin.closed-seasons.index') }}" class="btn btn-light border">Cancel</a>
        <button type="submit" class="btn btn-rflms px-4">
            <i class="bi bi-check-lg me-1"></i> {{ $mode === 'create' ? 'Save closed season' : 'Update closed season' }}
        </button>
    </div>
</form>

@push('scripts')
<script>
function toggleScope(scope) {
    const resDiv = document.getElementById('reservoir-selector');
    const catDiv = document.getElementById('category-selector');
    const hiddenScopeId = document.getElementById('hidden_scope_id');

    if (scope === 'reservoir') {
        resDiv.classList.remove('d-none');
        catDiv.classList.add('d-none');
        const resSelect = document.getElementById('scope_id_reservoir');
        hiddenScopeId.value = resSelect.value;
    } else if (scope === 'category') {
        catDiv.classList.remove('d-none');
        resDiv.classList.add('d-none');
        const catSelect = document.getElementById('scope_id_category');
        hiddenScopeId.value = catSelect.value;
    } else {
        resDiv.classList.add('d-none');
        catDiv.classList.add('d-none');
        hiddenScopeId.value = '';
    }
}

document.getElementById('scope_id_reservoir')?.addEventListener('change', function() {
    document.getElementById('hidden_scope_id').value = this.value;
});

document.getElementById('scope_id_category')?.addEventListener('change', function() {
    document.getElementById('hidden_scope_id').value = this.value;
});

function setDates(sMonth, sDay, eMonth, eDay, reasonText) {
    document.getElementById('start_month').value = sMonth;
    document.getElementById('start_day').value = sDay;
    document.getElementById('end_month').value = eMonth;
    document.getElementById('end_day').value = eDay;
    if (reasonText) {
        document.getElementById('reason').value = reasonText;
    }
}

// Initial sync
document.addEventListener('DOMContentLoaded', function() {
    const checkedScope = document.querySelector('input[name="scope_type"]:checked')?.value || 'global';
    toggleScope(checkedScope);
});
</script>
@endpush
@endsection

