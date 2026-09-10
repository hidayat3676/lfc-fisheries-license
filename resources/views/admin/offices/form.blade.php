@extends('layouts.admin')

@section('title', ($mode === 'create' ? 'Add office' : 'Edit office').' — '.config('app.name'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 fw-bold mb-0">{{ $mode === 'create' ? 'Add office' : 'Edit office' }}</h1>
    <a href="{{ route('admin.offices.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<form method="POST" action="{{ $mode === 'create' ? route('admin.offices.store') : route('admin.offices.update', $office) }}" class="bg-white rounded-4 shadow-sm p-4" style="max-width:720px;">
    @csrf
    @if ($mode === 'edit') @method('PUT') @endif

    <div class="mb-3">
        <label class="form-label">District</label>
        <select name="district_id" class="form-select" required>
            @foreach ($districts as $district)
                <option value="{{ $district->id }}" @selected(old('district_id', $office->district_id) == $district->id)>{{ $district->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Office name</label>
        <input type="text" name="name" class="form-control" required value="{{ old('name', $office->name) }}">
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $office->phone) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $office->email) }}">
        </div>
    </div>
    <div class="mb-3 mt-3">
        <label class="form-label">Address</label>
        <input type="text" name="address" class="form-control" value="{{ old('address', $office->address) }}">
    </div>
    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $office->is_active ?? true))>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
    <button class="btn btn-rflms" type="submit">Save</button>
</form>
@endsection
