@extends('layouts.admin')

@section('title', ($mode === 'create' ? 'Add water body' : 'Edit water body').' — '.config('app.name'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 fw-bold mb-0">{{ $mode === 'create' ? 'Add water body' : 'Edit water body' }}</h1>
    <a href="{{ route('admin.reservoirs.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<form method="POST"
      action="{{ $mode === 'create' ? route('admin.reservoirs.store') : route('admin.reservoirs.update', $reservoir) }}"
      enctype="multipart/form-data"
      class="bg-white rounded-4 shadow-sm p-4">
    @csrf
    @if ($mode === 'edit') @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">District</label>
            <select name="district_id" class="form-select" required>
                @foreach ($districts as $district)
                    <option value="{{ $district->id }}" @selected(old('district_id', $reservoir->district_id) == $district->id)>{{ $district->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Office</label>
            <select name="office_id" class="form-select">
                <option value="">—</option>
                @foreach ($offices as $office)
                    <option value="{{ $office->id }}" @selected(old('office_id', $reservoir->office_id) == $office->id)>
                        {{ $office->name }} ({{ $office->district?->name }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-12">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" required value="{{ old('name', $reservoir->name) }}">
        </div>

        <div class="col-12">
            <label class="form-label">Water Body Feature Cover Image</label>
            @if ($reservoir->imageUrl())
                <div class="mb-2">
                    <img src="{{ $reservoir->imageUrl() }}" alt="{{ $reservoir->name }}" class="img-thumbnail rounded-3" style="max-height: 140px; object-fit: cover;">
                </div>
            @endif
            <input type="file" name="image" accept="image/jpeg,image/png,image/jpg,image/webp" class="form-control @error('image') is-invalid @enderror">
            <div class="form-text small">Upload scenic/landscape photo of this river, dam, or stream (Max 4MB). Shown on homepage and catalogue cards.</div>
            @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Type</label>
            <select name="water_body_type" class="form-select" required>
                @foreach ($types as $key => $label)
                    <option value="{{ $key }}" @selected(old('water_body_type', $reservoir->water_body_type) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Trout type</label>
            <select name="trout_type" class="form-select" required>
                @foreach ($troutTypes as $key => $label)
                    <option value="{{ $key }}" @selected(old('trout_type', $reservoir->trout_type) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">E-licence eligible</label>
            <select name="is_open_for_licensing" class="form-select">
                <option value="" @selected(old('is_open_for_licensing', $reservoir->is_open_for_licensing) === null)>Undecided</option>
                <option value="1" @selected(old('is_open_for_licensing', $reservoir->is_open_for_licensing) === true)>Yes</option>
                <option value="0" @selected(old('is_open_for_licensing', $reservoir->is_open_for_licensing) === false)>No</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Latitude</label>
            <input type="text" name="latitude" class="form-control" value="{{ old('latitude', $reservoir->latitude) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Longitude</label>
            <input type="text" name="longitude" class="form-control" value="{{ old('longitude', $reservoir->longitude) }}">
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $reservoir->description) }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Species notes</label>
            <textarea name="species_notes" class="form-control" rows="2">{{ old('species_notes', $reservoir->species_notes) }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Lease notes</label>
            <textarea name="lease_notes" class="form-control" rows="2">{{ old('lease_notes', $reservoir->lease_notes) }}</textarea>
        </div>
        <div class="col-md-6">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $reservoir->is_active ?? true))>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="needs_review" value="1" id="needs_review" @checked(old('needs_review', $reservoir->needs_review ?? false))>
                <label class="form-check-label" for="needs_review">Needs review</label>
            </div>
        </div>
    </div>

    <button class="btn btn-rflms mt-3" type="submit">Save</button>
</form>
@endsection
