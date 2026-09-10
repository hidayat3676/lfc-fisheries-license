@extends('layouts.admin')

@section('title', ($mode === 'create' ? 'Add category' : 'Edit category').' — '.config('app.name'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 fw-bold mb-0">{{ $mode === 'create' ? 'Add category' : 'Edit category' }}</h1>
    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<form method="POST" action="{{ $mode === 'create' ? route('admin.categories.store') : route('admin.categories.update', $category) }}" class="bg-white rounded-4 shadow-sm p-4">
    @csrf
    @if ($mode === 'edit') @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Code</label>
            <input type="text" name="code" class="form-control" required value="{{ old('code', $category->code) }}">
        </div>
        <div class="col-md-8">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" required value="{{ old('name', $category->name) }}">
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2">{{ old('description', $category->description) }}</textarea>
        </div>
        <div class="col-md-3">
            <label class="form-label">Duration type</label>
            <select name="duration_type" class="form-select" required>
                @foreach (['daily','weekly','monthly','seasonal','custom'] as $type)
                    <option value="{{ $type }}" @selected(old('duration_type', $category->duration_type) === $type)>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Duration days</label>
            <input type="number" name="duration_days" class="form-control" min="1" value="{{ old('duration_days', $category->duration_days) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Fee amount</label>
            <input type="number" step="0.01" name="fee_amount" class="form-control" required value="{{ old('fee_amount', $category->fee_amount) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Currency</label>
            <input type="text" name="currency" class="form-control" required value="{{ old('currency', $category->currency ?: 'PKR') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Expiry rule</label>
            <select name="expiry_rule" class="form-select" required>
                <option value="from_start" @selected(old('expiry_rule', $category->expiry_rule) === 'from_start')>From start + days</option>
                <option value="fixed_date" @selected(old('expiry_rule', $category->expiry_rule) === 'fixed_date')>Fixed date (MM-DD)</option>
                <option value="season_end" @selected(old('expiry_rule', $category->expiry_rule) === 'season_end')>Season end</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Fixed expiry (MM-DD)</label>
            <input type="text" name="fixed_expiry_month_day" class="form-control" placeholder="06-30" value="{{ old('fixed_expiry_month_day', $category->fixed_expiry_month_day) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Max fish limit</label>
            <input type="number" name="max_fish_limit" class="form-control" value="{{ old('max_fish_limit', $category->max_fish_limit) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Instructions</label>
            <textarea name="instructions" class="form-control" rows="3">{{ old('instructions', $category->instructions) }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Terms</label>
            <textarea name="terms" class="form-control" rows="3">{{ old('terms', $category->terms) }}</textarea>
        </div>
        <div class="col-12">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $category->is_active ?? true))>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
        </div>
    </div>
    <button class="btn btn-rflms mt-3" type="submit">Save</button>
</form>
@endsection
