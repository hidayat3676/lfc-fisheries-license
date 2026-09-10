@extends('layouts.admin')

@section('title', 'Setup — E-licence waters — '.config('app.name'))

@section('content')
@include('admin.setup._steps', ['steps' => $steps, 'current' => 'eligibility'])

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="bg-white rounded-4 shadow-sm p-3"><div class="small text-secondary">Eligible</div><div class="fs-4 fw-bold text-success">{{ $counts['open'] }}</div></div></div>
    <div class="col-6 col-md-3"><div class="bg-white rounded-4 shadow-sm p-3"><div class="small text-secondary">Not eligible</div><div class="fs-4 fw-bold">{{ $counts['closed'] }}</div></div></div>
    <div class="col-6 col-md-3"><div class="bg-white rounded-4 shadow-sm p-3"><div class="small text-secondary">Undecided</div><div class="fs-4 fw-bold text-warning">{{ $counts['undecided'] }}</div></div></div>
    <div class="col-6 col-md-3"><div class="bg-white rounded-4 shadow-sm p-3"><div class="small text-secondary">Active waters</div><div class="fs-4 fw-bold">{{ $counts['total'] }}</div></div></div>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3">
        <select name="district_id" class="form-select">
            <option value="">All districts</option>
            @foreach ($districts as $district)
                <option value="{{ $district->id }}" @selected(request('district_id') == $district->id)>{{ $district->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="eligibility" class="form-select">
            <option value="">All eligibility</option>
            <option value="open" @selected(request('eligibility') === 'open')>Eligible</option>
            <option value="closed" @selected(request('eligibility') === 'closed')>Not eligible</option>
            <option value="undecided" @selected(request('eligibility') === 'undecided')>Undecided</option>
        </select>
    </div>
    <div class="col-md-4">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search water body">
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-secondary w-100" type="submit">Filter</button>
    </div>
</form>

<form method="POST" action="{{ route('admin.setup.eligibility.save') }}">
    @csrf
    <div class="bg-white rounded-4 shadow-sm p-4 mb-3">
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="require_explicit"
                   name="require_explicit_e_licence" value="1" @checked(old('require_explicit_e_licence', $requireExplicit))>
            <label class="form-check-label" for="require_explicit">
                Strict mode: only waters marked <strong>Eligible</strong> accept applications (undecided = blocked)
            </label>
        </div>

        <div class="table-responsive" style="max-height: 480px; overflow:auto;">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light sticky-top">
                    <tr>
                        <th>Water body</th>
                        <th>District</th>
                        <th style="width:220px">E-licence</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reservoirs as $item)
                        @php
                            $current = $item->is_open_for_licensing === true ? '1' : ($item->is_open_for_licensing === false ? '0' : '');
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $item->name }}</div>
                                <div class="small text-secondary">{{ $item->typeLabel() }}</div>
                            </td>
                            <td>{{ $item->district?->name }}</td>
                            <td>
                                <select name="eligibility[{{ $item->id }}]" class="form-select form-select-sm">
                                    <option value="" @selected($current === '')>Undecided</option>
                                    <option value="1" @selected($current === '1')>Eligible</option>
                                    <option value="0" @selected($current === '0')>Not eligible</option>
                                </select>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-secondary py-4">No water bodies match filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $reservoirs->links() }}</div>
        <p class="small text-secondary mb-0">Tip: filter <em>Undecided</em>, set Eligible for pilot waters (e.g. Kundal, Tanda, Azakhel), mark the rest Not eligible.</p>
    </div>

    <div class="bg-white rounded-4 shadow-sm p-4 mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="confirm" value="1" id="confirm" required>
            <label class="form-check-label" for="confirm">
                I confirm e-licence eligibility for the waters shown (at least one must be Eligible).
            </label>
        </div>
        @if ($confirmed)
            <p class="small text-success mb-0 mt-2">Previously confirmed — saving again updates live eligibility.</p>
        @endif
    </div>

    <button type="submit" class="btn btn-rflms">Save &amp; continue to permission templates</button>
</form>
@endsection
