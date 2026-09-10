@extends('layouts.app')

@section('title', 'Report illegal fishing — '.config('app.name'))

@section('content')
<section class="container py-4" style="max-width: 760px;">
    <h1 class="h3 fw-bold mb-1">Report illegal fishing</h1>
    <p class="text-secondary mb-4">Submit a geo-tagged report. It is routed to the district fisheries office.</p>

    <form method="POST" action="{{ route('violations.store') }}" enctype="multipart/form-data" class="bg-white rounded-4 shadow-sm p-4"
          x-data="{ districtId: '{{ old('district_id') }}' }">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">District</label>
                <select name="district_id" class="form-select" x-model="districtId" required>
                    <option value="">Select district</option>
                    @foreach ($districts as $district)
                        <option value="{{ $district->id }}">{{ $district->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Water body (optional)</label>
                <select name="reservoir_id" class="form-select">
                    <option value="">—</option>
                    @foreach ($reservoirs as $item)
                        <option value="{{ $item->id }}"
                            data-district="{{ $item->district_id }}"
                            @selected(old('reservoir_id', $selectedReservoirId) == $item->id)
                            x-show="!districtId || districtId == '{{ $item->district_id }}'">
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Violation type</label>
                <select name="violation_type" class="form-select" required>
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}" @selected(old('violation_type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">When occurred</label>
                <input type="datetime-local" name="occurred_at" class="form-control" value="{{ old('occurred_at') }}">
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4" required>{{ old('description') }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Latitude</label>
                <input id="latitude" type="text" name="latitude" class="form-control" value="{{ old('latitude') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Longitude</label>
                <input id="longitude" type="text" name="longitude" class="form-control" value="{{ old('longitude') }}">
            </div>
            @guest
                <div class="col-md-6">
                    <label class="form-label">Your name</label>
                    <input type="text" name="reporter_name" class="form-control" value="{{ old('reporter_name') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="reporter_phone" class="form-control" value="{{ old('reporter_phone') }}">
                </div>
            @endguest
            <div class="col-12">
                <label class="form-label">Photos (up to 3)</label>
                <input type="file" name="photos[]" class="form-control" accept=".jpg,.jpeg,.png" multiple>
            </div>
        </div>
        <button class="btn btn-rflms mt-3" type="submit">Submit report</button>
    </form>
</section>
@endsection
