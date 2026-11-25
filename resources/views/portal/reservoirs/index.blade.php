@extends('layouts.app')

@section('title', 'All Reservoirs & Water Bodies — '.config('app.name'))

@section('content')
<section class="container py-4 py-lg-5">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="page-title-block mb-0">
            <div class="title-icon"><i class="bi bi-water"></i></div>
            <div>
                <h1 class="h2 fw-bold mb-1">All Reservoirs &amp; Water Bodies</h1>
                <p class="page-subtitle mb-0">Search and filter dams, rivers, trout streams, and canals across Khyber Pakhtunkhwa.</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-rflms px-3 py-2 rounded-pill">
                <i class="bi bi-collection me-1"></i> {{ $totalMatching ?? $reservoirs->total() }} Water Bodies Found
            </span>
            <a href="{{ route('home') }}#water-bodies-featured" class="btn btn-outline-secondary btn-sm btn-chip">
                <i class="bi bi-arrow-left me-1"></i> Home
            </a>
        </div>
    </div>

    {{-- Search & Filters --}}
    <form method="GET" action="{{ route('reservoirs.index') }}" class="content-shell mb-4">
        <div class="content-shell-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold"><i class="bi bi-search me-1 text-rflms"></i>Search Name / Keyword</label>
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="e.g. Tanda Dam, River Kunhar, Swat...">
                </div>

                <div class="col-md-4 col-lg-2">
                    <label class="form-label small fw-semibold"><i class="bi bi-geo-alt me-1 text-danger"></i>District</label>
                    <select name="district_id" class="form-select">
                        <option value="">All Districts</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->id }}" @selected(request('district_id') == $district->id)>{{ $district->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-lg-2">
                    <label class="form-label small fw-semibold"><i class="bi bi-bounding-box me-1 text-primary"></i>Water Body Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        @foreach ($types as $key => $label)
                            <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-lg-2">
                    <label class="form-label small fw-semibold"><i class="bi bi-fish me-1 text-info"></i>Trout Classification</label>
                    <select name="trout" class="form-select">
                        <option value="">All Waters</option>
                        @foreach ($troutTypes as $key => $label)
                            <option value="{{ $key }}" @selected(request('trout') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold"><i class="bi bi-card-checklist me-1 text-success"></i>License / Permit Type</label>
                    <select name="permit" class="form-select">
                        <option value="">All Permits</option>
                        @foreach (($permitOptions ?? []) as $val => $label)
                            <option value="{{ $val }}" @selected(request('permit') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold"><i class="bi bi-check2-circle me-1 text-rflms"></i>E-Licence Status</label>
                    <select name="eligibility" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="open" @selected(request('eligibility') === 'open')>Open for E-Licence</option>
                        <option value="closed" @selected(request('eligibility') === 'closed')>Closed</option>
                    </select>
                </div>

                <div class="col-md-8 col-lg-9 d-flex justify-content-end gap-2">
                    @if (request()->hasAny(['q', 'district_id', 'type', 'trout', 'permit', 'eligibility']))
                        <a href="{{ route('reservoirs.index') }}" class="btn btn-outline-secondary btn-chip px-3">
                            <i class="bi bi-x-circle me-1"></i> Reset Filters
                        </a>
                    @endif
                    <button class="btn btn-rflms btn-chip px-4" type="submit">
                        <i class="bi bi-funnel-fill me-1"></i> Apply Filters
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- Reservoir Cards Grid --}}
    <div class="row g-4">
        @forelse ($reservoirs as $item)
            @php
                $lat = $item->latitude ?? $item->start_lat;
                $lng = $item->longitude ?? $item->start_lng;
                $mapUrl = ($lat !== null && $lng !== null)
                    ? 'https://www.google.com/maps?q=' . $lat . ',' . $lng
                    : $item->mapsUrl();
            @endphp
            <div class="col-md-6 col-lg-4">
                <div class="bg-white rounded-4 shadow-sm overflow-hidden h-100 card-hover d-flex flex-column" style="border:1px solid var(--rflms-border)">
                    @if ($item->imageUrl())
                        <a href="{{ route('catalogue.show', $item) }}" class="text-decoration-none d-block">
                            <div style="background: linear-gradient(0deg, rgba(0,0,0,0.72) 0%, transparent 100%), url('{{ $item->imageUrl() }}') center/cover no-repeat; height: 155px;" class="position-relative">
                                <div class="position-absolute bottom-0 start-0 end-0 p-2 px-3 d-flex justify-content-between align-items-center gap-2">
                                    <span class="badge text-bg-light border opacity-95"><i class="bi bi-bounding-box me-1"></i>{{ $item->typeLabel() }}</span>
                                    <span class="badge {{ $item->trout_type === 'trout' ? 'text-bg-info text-dark' : ($item->trout_type === 'mixed' ? 'text-bg-warning text-dark' : 'text-bg-secondary') }}">
                                        <i class="bi bi-fish me-1"></i>{{ $item->troutLabel() }}
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endif

                    <div class="p-3 p-lg-4 flex-grow-1 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                <span class="small fw-semibold text-primary">
                                    <i class="bi bi-geo-alt-fill me-1 text-danger"></i>District {{ $item->district?->name ?? 'KP' }}
                                </span>
                                @if ($item->allowsELicence())
                                    <span class="badge badge-status-approved"><i class="bi bi-check2"></i> E-licence</span>
                                @else
                                    <span class="badge text-bg-secondary">Closed</span>
                                @endif
                            </div>

                            <h2 class="h5 fw-bold mb-2">
                                <a href="{{ route('catalogue.show', $item) }}" class="text-dark text-decoration-none hover-primary">
                                    {{ $item->name }}
                                </a>
                            </h2>

                            @if ($item->office)
                                <div class="small text-muted mb-2">
                                    <i class="bi bi-building me-1"></i>{{ $item->office->name }}
                                </div>
                            @endif

                            <div class="small text-secondary mb-3 p-2 bg-light rounded-3 border">
                                <span class="fw-semibold text-dark d-block mb-1">
                                    <i class="bi bi-card-checklist text-rflms me-1"></i>License / Permits:
                                </span>
                                <span>{{ $item->licensePermitsLabel() }}</span>
                            </div>
                        </div>

                        <div class="pt-3 border-top d-flex align-items-center justify-content-between gap-2 mt-auto">
                            <div class="d-flex align-items-center gap-1">
                                <a href="{{ route('catalogue.show', $item) }}" class="btn btn-sm btn-outline-secondary btn-chip">
                                    <i class="bi bi-info-circle me-1"></i>Details
                                </a>
                            </div>

                            @if ($item->allowsELicence())
                                @auth
                                    @if (auth()->user()->isCitizen())
                                        <a href="{{ route('citizen.applications.create', $item) }}" class="btn btn-sm btn-rflms btn-chip">
                                            <i class="bi bi-send-fill me-1"></i>Apply
                                        </a>
                                    @else
                                        <a href="{{ route('catalogue.show', $item) }}" class="btn btn-sm btn-outline-primary btn-chip">
                                            <i class="bi bi-eye me-1"></i>View
                                        </a>
                                    @endif
                                @else
                                    <a href="{{ route('citizen.applications.create', $item) }}" class="btn btn-sm btn-rflms btn-chip">
                                        <i class="bi bi-send-fill me-1"></i>Apply
                                    </a>
                                @endauth
                            @else
                                <button type="button" class="btn btn-sm btn-light border btn-chip text-muted" disabled>
                                    <i class="bi bi-slash-circle me-1"></i>Not Open
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="content-shell">
                    <div class="empty-state">
                        <i class="bi bi-water"></i>
                        No reservoirs or water bodies match your selected filters.
                        <div class="mt-2">
                            <a href="{{ route('reservoirs.index') }}" class="btn btn-sm btn-outline-secondary">Reset Filters</a>
                        </div>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $reservoirs->links() }}</div>
</section>
@endsection

