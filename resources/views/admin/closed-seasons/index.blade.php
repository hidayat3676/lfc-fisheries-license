@extends('layouts.admin')

@section('title', 'Closed seasons — '.config('app.name'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h1 class="h3 fw-bold mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-calendar-x text-danger"></i> Closed seasons
        </h1>
        <p class="text-secondary mb-0">Statutory fish breeding periods, spawning closures, and water body restrictions</p>
    </div>
    @if (auth()->user()->hasModuleAction('license_categories', 'edit') || auth()->user()->hasModuleAction('license_categories', 'create') || auth()->user()->hasModuleAction('settings', 'edit'))
        <a href="{{ route('admin.closed-seasons.create') }}" class="btn btn-rflms">
            <i class="bi bi-plus-lg me-1"></i> Add closed season
        </a>
    @endif
</div>

{{-- Sub-navigation tabs --}}
<ul class="nav nav-pills mb-3 gap-2">
    @if (auth()->user()->hasModuleAction('license_categories', 'view'))
        <li class="nav-item">
            <a class="nav-link bg-white border text-dark" href="{{ route('admin.categories.index') }}">
                <i class="bi bi-tags me-1"></i> Licence categories
            </a>
        </li>
    @endif
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('admin.closed-seasons.index') }}">
            <i class="bi bi-calendar-x me-1"></i> Closed seasons
        </a>
    </li>
    @if (auth()->user()->hasModuleAction('settings', 'view'))
        <li class="nav-item">
            <a class="nav-link bg-white border text-dark" href="{{ route('admin.setup.fees') }}">
                <i class="bi bi-sliders me-1"></i> Policy setup wizard (Fees &amp; seasons)
            </a>
        </li>
    @endif
</ul>

{{-- Stat cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 shadow-sm p-3 h-100">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="small text-secondary fw-semibold">Total rules</span>
                <i class="bi bi-calendar-week text-secondary fs-5"></i>
            </div>
            <div class="fs-3 fw-bold">{{ $totalCount }}</div>
            <div class="small text-secondary">Configured closure rules</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 shadow-sm p-3 h-100">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="small text-secondary fw-semibold">Active rules</span>
                <i class="bi bi-check-circle text-success fs-5"></i>
            </div>
            <div class="fs-3 fw-bold text-success">{{ $activeCount }}</div>
            <div class="small text-secondary">Enforced by licensing engine</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 shadow-sm p-3 h-100">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="small text-secondary fw-semibold">Today's state</span>
                @if ($activeTodaySeasons->isNotEmpty())
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                @else
                    <i class="bi bi-shield-check text-success fs-5"></i>
                @endif
            </div>
            @if ($activeTodaySeasons->isNotEmpty())
                <div class="fs-5 fw-bold text-danger">Closure in effect</div>
                <div class="small text-danger">{{ $activeTodaySeasons->count() }} active rule(s) right now</div>
            @else
                <div class="fs-5 fw-bold text-success">All waters open</div>
                <div class="small text-secondary">No statutory closure today</div>
            @endif
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 shadow-sm p-3 h-100">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="small text-secondary fw-semibold">Quick test</span>
                <i class="bi bi-shield-lock text-primary fs-5"></i>
            </div>
            <div class="small text-secondary">Date protection active</div>
            <div class="small text-muted mt-1">Applications in closed windows are blocked automatically</div>
        </div>
    </div>
</div>

@if ($activeTodaySeasons->isNotEmpty())
    <div class="alert alert-warning border-warning d-flex align-items-start gap-2 mb-4">
        <i class="bi bi-exclamation-octagon-fill text-danger fs-5 mt-1"></i>
        <div>
            <strong>Spawning closure is currently active today:</strong>
            <ul class="mb-0 mt-1 ps-3">
                @foreach ($activeTodaySeasons as $activeSeason)
                    <li>
                        <strong>{{ $activeSeason->scopeLabel() }}:</strong>
                        {{ $activeSeason->formattedPeriod() }} — <em>{{ $activeSeason->reason }}</em>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

{{-- Filters --}}
<div class="bg-white rounded-4 shadow-sm p-3 mb-3">
    <form method="GET" action="{{ route('admin.closed-seasons.index') }}" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small text-secondary mb-1">Search</label>
            <input type="text" name="q" class="form-control form-control-sm"
                   placeholder="Search reason, water body, category..."
                   value="{{ request('q') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label small text-secondary mb-1">Scope</label>
            <select name="scope" class="form-select form-select-sm">
                <option value="">All scopes</option>
                <option value="global" @selected(request('scope') === 'global')>Global (Province-wide)</option>
                <option value="reservoir" @selected(request('scope') === 'reservoir')>Specific water body</option>
                <option value="category" @selected(request('scope') === 'category')>Specific category</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small text-secondary mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                <i class="bi bi-filter"></i> Filter
            </button>
            @if (request()->hasAny(['q', 'scope', 'status']))
                <a href="{{ route('admin.closed-seasons.index') }}" class="btn btn-sm btn-light border" title="Reset">
                    <i class="bi bi-x-lg"></i>
                </a>
            @endif
        </div>
    </form>
</div>

{{-- Data Table --}}
<div class="bg-white rounded-4 shadow-sm overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px;">#</th>
                    <th>Scope</th>
                    <th>Closure period</th>
                    <th>Duration</th>
                    <th>Today's status</th>
                    <th>Reason / Justification</th>
                    <th class="text-center" style="width: 110px;">Active</th>
                    <th class="text-end" style="width: 160px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($seasons as $season)
                    @php
                        $inSeasonNow = $season->isCurrentlyActive();
                    @endphp
                    <tr>
                        <td class="text-secondary small fw-semibold">#{{ $season->id }}</td>
                        <td>
                            @if ($season->scope_type === 'global')
                                <span class="badge text-bg-primary d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-globe"></i> Global (All waters)
                                </span>
                            @elseif ($season->scope_type === 'reservoir')
                                <span class="badge text-bg-info text-dark d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-water"></i> {{ $season->reservoir?->name ?? 'Water body #'.$season->scope_id }}
                                </span>
                                @if ($season->reservoir?->district)
                                    <span class="small text-secondary ms-1">({{ $season->reservoir->district->name }})</span>
                                @endif
                            @elseif ($season->scope_type === 'category')
                                <span class="badge text-bg-warning text-dark d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-tag"></i> {{ $season->category?->name ?? 'Category #'.$season->scope_id }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $season->formattedPeriod() }}</div>
                            <div class="small text-secondary">Annual recurring closure</div>
                        </td>
                        <td>
                            <span class="badge text-bg-light border text-dark">
                                {{ $season->durationDays() }} days
                            </span>
                        </td>
                        <td>
                            @if ($inSeasonNow)
                                <span class="badge text-bg-danger d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-exclamation-circle"></i> In effect now
                                </span>
                            @else
                                <span class="badge text-bg-light text-secondary border">Off-season</span>
                            @endif
                        </td>
                        <td>
                            <span class="text-secondary small">{{ $season->reason ?: '—' }}</span>
                        </td>
                        <td class="text-center">
                            @if (auth()->user()->hasModuleAction('license_categories', 'edit') || auth()->user()->hasModuleAction('settings', 'edit'))
                                <form method="POST" action="{{ route('admin.closed-seasons.toggle', $season) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent"
                                            title="Click to toggle active status"
                                            onclick="return confirm('Toggle status for closed season #{{ $season->id }}?')">
                                        @if ($season->is_active)
                                            <span class="badge text-bg-success"><i class="bi bi-check-lg"></i> Active</span>
                                        @else
                                            <span class="badge text-bg-secondary">Inactive</span>
                                        @endif
                                    </button>
                                </form>
                            @else
                                <span class="badge {{ $season->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $season->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                @if (auth()->user()->hasModuleAction('license_categories', 'edit') || auth()->user()->hasModuleAction('settings', 'edit'))
                                    <a href="{{ route('admin.closed-seasons.edit', $season) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Edit closed season">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.closed-seasons.destroy', $season) }}"
                                          onsubmit="return confirm('Are you sure you want to permanently delete this closed season?');"
                                          class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-secondary">
                            <i class="bi bi-calendar-x display-6 text-muted mb-2 d-block"></i>
                            <div class="fw-semibold">No closed seasons found</div>
                            <div class="small text-muted mb-3">No closed season records match your search criteria.</div>
                            @if (auth()->user()->hasModuleAction('license_categories', 'edit') || auth()->user()->hasModuleAction('settings', 'edit'))
                                <a href="{{ route('admin.closed-seasons.create') }}" class="btn btn-sm btn-rflms">
                                    <i class="bi bi-plus-lg me-1"></i> Add new closed season
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $seasons->links() }}</div>

{{-- Explanatory Policy Card --}}
<div class="bg-white rounded-4 shadow-sm p-4 mt-4 border-start border-4 border-primary">
    <h3 class="h6 fw-bold mb-2 d-flex align-items-center gap-2">
        <i class="bi bi-info-circle text-primary"></i> How closed breeding seasons are enforced
    </h3>
    <p class="small text-secondary mb-2">
        Under the KP Fisheries Act and RFLMS licensing rules, fishing is strictly prohibited during statutory breeding windows to preserve fish biodiversity:
    </p>
    <ul class="small text-secondary mb-0">
        <li><strong>Global Closures:</strong> Apply across all 36 districts and all water bodies in Khyber Pakhtunkhwa.</li>
        <li><strong>Water Body Closures:</strong> Apply only to designated rivers, dams, or streams (e.g. cold-water trout reaches during winter spawning).</li>
        <li><strong>Licence Category Closures:</strong> Apply only to specific licence categories.</li>
        <li><strong>Automated Protection:</strong> The licensing engine automatically validates application fishing start dates. Any attempt by a citizen or walk-in operator to apply within an active closed season window is instantly rejected.</li>
    </ul>
</div>
@endsection

