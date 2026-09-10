@extends('layouts.app')

@section('title', 'Water bodies — '.config('app.name'))

@section('content')
<section class="container py-4 py-lg-5">
    <div class="page-title-block">
        <div class="title-icon"><i class="bi bi-water"></i></div>
        <div>
            <h1 class="h2 fw-bold mb-1">Water body catalogue</h1>
            <p class="page-subtitle mb-0">Browse natural water bodies by district, type, and trout category.</p>
        </div>
    </div>

    <form method="GET" class="content-shell mb-4">
        <div class="content-shell-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label"><i class="bi bi-geo-alt me-1"></i>District</label>
                    <select name="district_id" class="form-select">
                        <option value="">All districts</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->id }}" @selected(request('district_id') == $district->id)>{{ $district->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><i class="bi bi-bounding-box me-1"></i>Type</label>
                    <select name="type" class="form-select">
                        <option value="">All</option>
                        @foreach ($types as $key => $label)
                            <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><i class="bi bi-fish me-1"></i>Trout</label>
                    <select name="trout" class="form-select">
                        <option value="">All</option>
                        @foreach ($troutTypes as $key => $label)
                            <option value="{{ $key }}" @selected(request('trout') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="bi bi-search me-1"></i>Search</label>
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="e.g. Kundal Dam">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-rflms w-100 btn-chip" type="submit"><i class="bi bi-funnel"></i> Search</button>
                </div>
            </div>
        </div>
    </form>

    <div class="row g-3">
        @forelse ($reservoirs as $item)
            <div class="col-md-6 col-lg-4">
                <div class="bg-white rounded-4 shadow-sm overflow-hidden h-100 card-hover d-flex flex-column" style="border:1px solid var(--rflms-border)">
                    @if ($item->imageUrl())
                        <a href="{{ route('catalogue.show', $item) }}" class="text-decoration-none d-block">
                            <div style="background: linear-gradient(0deg, rgba(0,0,0,0.7) 0%, transparent 100%), url('{{ $item->imageUrl() }}') center/cover no-repeat; height: 140px;" class="position-relative">
                                <div class="position-absolute bottom-0 start-0 end-0 p-2 d-flex justify-content-between gap-2">
                                    <span class="badge text-bg-light border opacity-90"><i class="bi bi-bounding-box me-1"></i>{{ $item->typeLabel() }}</span>
                                    <span class="badge {{ $item->trout_type === 'trout' ? 'text-bg-info' : 'text-bg-secondary' }}"><i class="bi bi-fish me-1"></i>{{ $item->troutLabel() }}</span>
                                </div>
                            </div>
                        </a>
                        <div class="p-3 p-lg-4 flex-grow-1 d-flex flex-column justify-content-between">
                    @else
                        <div class="p-3 p-lg-4 flex-grow-1 d-flex flex-column justify-content-between">
                            <div class="d-flex justify-content-between gap-2 mb-2">
                                <span class="badge text-bg-light border"><i class="bi bi-bounding-box me-1"></i>{{ $item->typeLabel() }}</span>
                                <span class="badge {{ $item->trout_type === 'trout' ? 'text-bg-info' : 'text-bg-secondary' }}"><i class="bi bi-fish me-1"></i>{{ $item->troutLabel() }}</span>
                            </div>
                    @endif
                            <div>
                                <h2 class="h5 fw-bold mb-1">
                                    <a href="{{ route('catalogue.show', $item) }}" class="text-dark text-decoration-none hover-primary">
                                        {{ $item->name }}
                                    </a>
                                </h2>
                                <div class="small text-secondary mb-2">
                                    <i class="bi bi-geo-alt me-1 text-danger"></i>District {{ $item->district?->name ?? 'KP' }}
                                    @if ($item->office)
                                        <span class="text-muted">· {{ $item->office->name }}</span>
                                    @endif
                                </div>
                                <div class="small d-flex align-items-center gap-2 flex-wrap mb-3">
                                    @if ($item->hasCoordinates())
                                        <span><i class="bi bi-map text-rflms me-1"></i>Map available</span>
                                    @else
                                        <span><i class="bi bi-hourglass me-1 text-muted"></i>Coords pending</span>
                                    @endif
                                    @if ($item->allowsELicence())
                                        <span class="badge badge-status-approved ms-auto"><i class="bi bi-check2"></i> E-licence</span>
                                    @else
                                        <span class="badge text-bg-secondary ms-auto">Closed</span>
                                    @endif
                                </div>
                            </div>

                            <div class="pt-3 border-top d-flex align-items-center justify-content-between gap-2 mt-auto">
                                <a href="{{ route('catalogue.show', $item) }}" class="btn btn-sm btn-outline-secondary btn-chip">
                                    <i class="bi bi-info-circle me-1"></i>Details
                                </a>

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
                                    <button type="button" class="btn btn-sm btn-light border btn-chip text-muted" disabled title="Not open for e-licence">
                                        <i class="bi bi-slash-circle me-1"></i>Not Open
                                    </button>
                                @endif
                            </div>
                        </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="content-shell"><div class="empty-state"><i class="bi bi-water"></i>No water bodies match your filters.</div></div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $reservoirs->links() }}</div>
</section>
@endsection
