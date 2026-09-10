@extends('layouts.admin')

@section('title', 'Offices — '.config('app.name'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 fw-bold mb-1">Offices</h1>
        <p class="text-secondary mb-0">Fisheries offices by district</p>
    </div>
    @if (auth()->user()->hasModuleAction('offices', 'create'))
        <a href="{{ route('admin.offices.create') }}" class="btn btn-rflms">Add office</a>
    @endif
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <select name="district_id" class="form-select">
            <option value="">All districts</option>
            @foreach ($districts as $district)
                <option value="{{ $district->id }}" @selected(request('district_id') == $district->id)>{{ $district->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-5">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search office">
    </div>
    <div class="col-md-3">
        <button class="btn btn-outline-secondary w-100" type="submit">Filter</button>
    </div>
</form>

<div class="bg-white rounded-4 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Office</th>
                    <th>District</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($offices as $office)
                    <tr>
                        <td class="fw-semibold">{{ $office->name }}</td>
                        <td>{{ $office->district?->name }}</td>
                        <td>{{ $office->phone ?: '—' }}</td>
                        <td>{{ $office->email ?: '—' }}</td>
                        <td>
                            <span class="badge {{ $office->is_active ? 'text-bg-success' : 'text-bg-danger' }}">
                                {{ $office->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if (auth()->user()->hasModuleAction('offices', 'edit'))
                                <a href="{{ route('admin.offices.edit', $office) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary py-4">No offices found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $offices->links() }}</div>
@endsection
