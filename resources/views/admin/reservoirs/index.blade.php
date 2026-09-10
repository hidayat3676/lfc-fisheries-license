@extends('layouts.admin')

@section('title', 'Water bodies — '.config('app.name'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 fw-bold mb-1">Water bodies</h1>
        <p class="text-secondary mb-0">Reservoir / river / stream catalogue</p>
    </div>
    @if (auth()->user()->hasModuleAction('reservoirs', 'create'))
        <a href="{{ route('admin.reservoirs.create') }}" class="btn btn-rflms">Add water body</a>
    @endif
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
        <select name="type" class="form-select">
            <option value="">All types</option>
            @foreach ($types as $key => $label)
                <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name">
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-secondary w-100" type="submit">Filter</button>
    </div>
</form>

<div class="bg-white rounded-4 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 60px;">Image</th>
                    <th>Name</th>
                    <th>District</th>
                    <th>Type</th>
                    <th>Trout</th>
                    <th>Coords</th>
                    <th>Flags</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reservoirs as $item)
                    <tr>
                        <td>
                            @if ($item->imageUrl())
                                <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" class="rounded-2" style="width: 44px; height: 34px; object-fit: cover;">
                            @else
                                <div class="bg-light rounded-2 d-flex align-items-center justify-content-center text-muted" style="width: 44px; height: 34px; font-size: 0.75rem;">
                                    <i class="bi bi-image"></i>
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $item->name }}</div>
                            <div class="small text-secondary">{{ $item->office?->name }}</div>
                        </td>
                        <td>{{ $item->district?->name }}</td>
                        <td>{{ $item->typeLabel() }}</td>
                        <td>{{ $item->troutLabel() }}</td>
                        <td class="small">
                            @if ($item->hasCoordinates())
                                {{ number_format($item->latitude, 5) }}, {{ number_format($item->longitude, 5) }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if ($item->needs_review)
                                <span class="badge text-bg-warning">Review</span>
                            @endif
                            @if ($item->is_open_for_licensing === true)
                                <span class="badge text-bg-success">E-licence</span>
                            @elseif ($item->is_open_for_licensing === false)
                                <span class="badge text-bg-secondary">Not eligible</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if (auth()->user()->hasModuleAction('reservoirs', 'edit'))
                                <a href="{{ route('admin.reservoirs.edit', $item) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-secondary py-4">No water bodies found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $reservoirs->links() }}</div>
@endsection
