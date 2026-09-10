@extends('layouts.admin')

@section('title', 'Setup — Fees & seasons — '.config('app.name'))

@section('content')
@include('admin.setup._steps', ['steps' => $steps, 'current' => 'fees'])

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.setup.fees.save') }}">
    @csrf

    <div class="bg-white rounded-4 shadow-sm p-4 mb-3">
        <h2 class="h6 fw-bold mb-3">Licence fees</h2>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Rule</th>
                        <th style="width:160px">Fee (PKR)</th>
                        <th style="width:90px">Active</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $i => $category)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $category->name }}</div>
                                <div class="small text-secondary">{{ $category->code }}</div>
                                <input type="hidden" name="fees[{{ $i }}][id]" value="{{ $category->id }}">
                            </td>
                            <td class="small">
                                @if ($category->duration_type === 'seasonal' || in_array($category->expiry_rule, ['fixed_date','season_end'], true))
                                    Expires on seasonal date below
                                @else
                                    {{ $category->duration_days }} day(s) from start
                                @endif
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control"
                                       name="fees[{{ $i }}][fee_amount]"
                                       value="{{ old('fees.'.$i.'.fee_amount', $category->fee_amount) }}" required>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input" name="fees[{{ $i }}][is_active]" value="1"
                                       @checked(old('fees.'.$i.'.is_active', $category->is_active))>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-4 shadow-sm p-4 mb-3">
        <h2 class="h6 fw-bold mb-2">Seasonal expiry (30 June rule)</h2>
        <p class="small text-secondary mb-3">Seasonal / fixed-date licences expire on this month-day each year (MM-DD).</p>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Expiry month-day</label>
                <input type="text" name="seasonal_expiry_month_day" class="form-control"
                       value="{{ old('seasonal_expiry_month_day', $seasonalExpiry) }}"
                       pattern="(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])" placeholder="06-30" required>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-4 shadow-sm p-4 mb-3">
        <h2 class="h6 fw-bold mb-2">Closed / breeding season</h2>
        <p class="small text-secondary mb-3">Applications are blocked when the fishing start date falls in this window.</p>
        <div class="row g-3">
            <div class="col-6 col-md-2">
                <label class="form-label">Start month</label>
                <input type="number" min="1" max="12" name="closed_start_month" class="form-control"
                       value="{{ old('closed_start_month', $closedSeason?->start_month ?? 6) }}" required>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Start day</label>
                <input type="number" min="1" max="31" name="closed_start_day" class="form-control"
                       value="{{ old('closed_start_day', $closedSeason?->start_day ?? 1) }}" required>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">End month</label>
                <input type="number" min="1" max="12" name="closed_end_month" class="form-control"
                       value="{{ old('closed_end_month', $closedSeason?->end_month ?? 7) }}" required>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">End day</label>
                <input type="number" min="1" max="31" name="closed_end_day" class="form-control"
                       value="{{ old('closed_end_day', $closedSeason?->end_day ?? 31) }}" required>
            </div>
            <div class="col-md-8">
                <label class="form-label">Reason shown to citizens</label>
                <input type="text" name="closed_reason" class="form-control"
                       value="{{ old('closed_reason', $closedSeason?->reason ?? 'Breeding / closed season') }}">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="closed_is_active"
                           name="closed_is_active" value="1" @checked(old('closed_is_active', $closedSeason?->is_active ?? true))>
                    <label class="form-check-label" for="closed_is_active">Closed season active</label>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-4 shadow-sm p-4 mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="confirm" value="1" id="confirm" required>
            <label class="form-check-label" for="confirm">
                I confirm these fees, the seasonal expiry date, and the closed-season window.
            </label>
        </div>
        @if ($confirmed)
            <p class="small text-success mb-0 mt-2">Previously confirmed — saving again updates the live policy.</p>
        @endif
    </div>

    <button type="submit" class="btn btn-rflms">Save &amp; continue to e-licence waters</button>
</form>
@endsection
