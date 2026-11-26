@extends('layouts.app')

@section('title', 'KP Fisheries E-Licensing Portal — Government of Khyber Pakhtunkhwa')
@section('meta_description', 'Official portal for obtaining angling and commercial fishing licences for rivers, dams, lakes and trout waters in Khyber Pakhtunkhwa.')

@section('content')
<!-- Hero Section -->
<section class="container py-4 py-lg-5">
    <div class="hero-panel p-4 p-lg-5 position-relative overflow-hidden" style="background: linear-gradient(135deg, #0b6e4f, #064a35);">
        <!-- Digital Security Grid Animated Background -->
        <div class="license-security-bg"></div>

        <div class="row align-items-center g-4 position-relative" style="z-index: 1;">
            <div class="col-lg-7 animate-in">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill bg-black bg-opacity-35 text-white border border-white border-opacity-25 mb-3 small fw-semibold shadow-sm">
                    <i class="bi bi-shield-check text-warning"></i>
                    <span class="text-white">Directorate General of Fisheries · Khyber Pakhtunkhwa</span>
                </div>
                <h1 class="display-5 fw-bold mb-3 text-white">
                    Fish Responsibly. <br class="d-none d-sm-inline">Get Your E-Licence Online.
                </h1>
                <p class="lead mb-4 text-white opacity-90">
                    Apply for angling and commercial permits for rivers, reservoirs, and coldwater trout streams across KP. Digital issuance with QR verification for instant validation.
                </p>

                <!-- Animated E-Licence Digital Permit Badge Preview -->
                <div class="e-licence-preview-card p-3 mb-4 max-w-md shadow-lg" style="max-width: 480px;">
                    <div class="holo-strip"></div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-patch-check-fill text-warning fs-5"></i>
                            <div>
                                <div class="fw-bold small text-white lh-1">OFFICIAL DIGITAL E-PERMIT</div>
                                <div class="text-white-50" style="font-size:0.7rem">Government of KP · Fisheries Dept</div>
                            </div>
                        </div>
                        <span class="official-verified-stamp">
                            <i class="bi bi-check-lg"></i> VERIFIED
                        </span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-2 border-top border-white border-opacity-10">
                        <div>
                            <div class="text-white-50" style="font-size:0.68rem;text-transform:uppercase;letter-spacing:0.05em">PERMIT TYPE</div>
                            <div class="fw-semibold small text-white">Trout &amp; Warmwater Angling</div>
                        </div>
                        <div>
                            <div class="text-white-50" style="font-size:0.68rem;text-transform:uppercase;letter-spacing:0.05em">ISSUANCE</div>
                            <div class="fw-semibold small text-warning">Instant QR PDF</div>
                        </div>
                        <div class="qr-scan-mini ms-2 flex-shrink-0">
                            <svg viewBox="0 0 32 32" fill="#1a2332" style="width:100%;height:100%">
                                <path d="M0,0h12v12H0V0zm4,4v4h4V4H4zm16-4h12v12H20V0zm4,4v4h4V4h-4zM0,20h12v12H0V20zm4,4v4h4v-4H4zm22-4h6v2h-6v-2zm0,4h2v2h-2v-2zm4,0h2v6h-2v-6zm-6,4h4v2h-4v-2zm-4-8h2v2h-2v-2zm2,4h2v4h-2v-4zm-4,0h2v2h-2v-2zm0,4h2v4h-2v-4zm8,0h2v2h-2v-2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('reservoirs.index') }}" class="btn btn-warning btn-lg text-dark fw-bold btn-chip px-4">
                        <i class="bi bi-water"></i> Explore Water Bodies
                    </a>
                    <a href="#how-it-works" class="btn btn-outline-light btn-lg btn-chip px-4">
                        <i class="bi bi-signpost-2"></i> How It Works
                    </a>
                </div>
            </div>

            <!-- Quick Search Form Card -->
            <div class="col-lg-5 animate-in-delay">
                <div class="hero-search-card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <div class="fw-bold d-flex align-items-center gap-2 text-dark">
                            <span class="feature-icon" style="width:2rem;height:2rem;font-size:.9rem"><i class="bi bi-search"></i></span>
                            <span>Quick Water Body Search</span>
                        </div>
                        <span class="badge bg-rflms rounded-pill px-2 py-1" style="font-size: 0.7rem;">KP E-Catalog</span>
                    </div>

                    <form action="{{ route('reservoirs.index') }}" method="GET">
                        <div class="mb-3">
                            <label class="form-label small text-muted">Keywords / Water Body Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" name="q" class="form-control" placeholder="e.g. Tarbela, Kunhar, Khanpur...">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small text-muted">District</label>
                                <select name="district_id" class="form-select">
                                    <option value="">All Districts</option>
                                    @foreach($districts as $district)
                                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">Water Type</label>
                                <select name="trout" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="trout">Trout Stream</option>
                                    <option value="non_trout">Non-Trout Waters</option>
                                    <option value="mixed">Mixed</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-rflms w-100 btn-chip justify-content-center py-2">
                            <i class="bi bi-funnel-fill"></i> Search &amp; Apply
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Core E-Services Grid -->
<section class="container pb-5">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-2">
            <span class="feature-icon rounded-circle bg-rflms bg-opacity-10 text-rflms d-inline-flex align-items-center justify-content-center" style="width:2.2rem;height:2.2rem"><i class="bi bi-grid-fill"></i></span>
            <div>
                <h2 class="h5 fw-bold mb-0 text-dark">Quick Portal Services</h2>
                <div class="text-secondary small">Direct access to online licensing, instant verification, and reporting</div>
            </div>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-md-3 col-6">
            <a href="{{ route('citizen.applications.apply') }}" class="action-card p-3 rounded-4 bg-white h-100 shadow-sm border">
                <div class="d-flex align-items-center gap-3">
                    <div class="action-icon" style="width:2.75rem;height:2.75rem;font-size:1.2rem"><i class="bi bi-card-checklist"></i></div>
                    <div>
                        <div class="fw-bold text-dark">Apply License</div>
                        <div class="text-secondary small" style="font-size:0.78rem">Select district &amp; category</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="#verify-section" class="action-card p-3 rounded-4 bg-white h-100 shadow-sm border">
                <div class="d-flex align-items-center gap-3">
                    <div class="action-icon" style="width:2.75rem;height:2.75rem;font-size:1.2rem;background:#e8f4fd;color:#2980b9"><i class="bi bi-qr-code-scan"></i></div>
                    <div>
                        <div class="fw-bold text-dark">Verify QR</div>
                        <div class="text-secondary small" style="font-size:0.78rem">Check license authenticity</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="{{ route('reservoirs.index') }}" class="action-card p-3 rounded-4 bg-white h-100 shadow-sm border">
                <div class="d-flex align-items-center gap-3">
                    <div class="action-icon" style="width:2.75rem;height:2.75rem;font-size:1.2rem;background:#e8f8ef;color:#27ae60"><i class="bi bi-compass"></i></div>
                    <div>
                        <div class="fw-bold text-dark">Water Directory</div>
                        <div class="text-secondary small" style="font-size:0.78rem">Maps, species &amp; rules</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="{{ route('violations.create') }}" class="action-card p-3 rounded-4 bg-white h-100 shadow-sm border" style="border-left-color:var(--status-pending) !important">
                <div class="d-flex align-items-center gap-3">
                    <div class="action-icon" style="width:2.75rem;height:2.75rem;font-size:1.2rem;background:#fef5e7;color:var(--status-pending)"><i class="bi bi-exclamation-triangle"></i></div>
                    <div>
                        <div class="fw-bold text-dark">Report Violation</div>
                        <div class="text-secondary small" style="font-size:0.78rem">Anti-poaching helpline</div>
                    </div>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- License Categories Section -->
@if(isset($categories) && $categories->count() > 0)
<section id="categories" class="container pb-5">
    <div class="section-header mb-4">
        <div class="section-icon"><i class="bi bi-tags-fill"></i></div>
        <div>
            <h2 class="h3 fw-bold mb-0">Licence Categories &amp; Fees</h2>
            <p class="text-secondary mb-0">Choose the appropriate fishing permit category based on species and location.</p>
        </div>
    </div>

    <div class="row g-4">
        @foreach($categories as $index => $category)
        <div class="col-lg-3 col-md-6">
            <div class="category-card p-4 {{ $index === 0 ? 'featured-category' : '' }}">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="badge bg-rflms-light text-rflms border border-success border-opacity-25 uppercase">{{ $category->code }}</span>
                </div>

                <h3 class="h5 fw-bold text-dark mb-2">{{ $category->name }}</h3>
                <p class="text-secondary small mb-3" style="min-height: 2.6rem;">
                    {{ Str::limit($category->description ?? 'Official fishing licence issued by KP Fisheries Dept.', 90) }}
                </p>

                <div class="my-3 py-2 border-top border-bottom">
                    <div class="d-flex align-items-baseline gap-1">
                        <span class="price-tag">PKR {{ number_format($category->fee_amount, 0) }}</span>
                        <span class="text-muted small">/ permit</span>
                    </div>
                </div>

                <ul class="list-unstyled small text-secondary mb-4 gap-2 d-flex flex-column">
                    @if($category->max_fish_limit)
                    <li class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span>Bag Limit: <strong>{{ $category->max_fish_limit }} fish / day</strong></span>
                    </li>
                    @endif
                    <li class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span>QR Encrypted Permit Card</span>
                    </li>
                    <li class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span>District Wide Validity</span>
                    </li>
                </ul>

                <a href="{{ route('citizen.applications.apply') }}" class="btn btn-rflms w-100 btn-chip justify-content-center">
                    <i class="bi bi-check2-circle"></i> Apply Category
                </a>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif

<!-- Featured Water Bodies Section -->
@if(isset($featuredReservoirs) && $featuredReservoirs->count() > 0)
<section id="water-bodies-featured" class="container pb-5">
    <div class="d-flex flex-wrap align-items-end justify-content-between mb-4 gap-3">
        <div class="section-header mb-0">
            <div class="section-icon"><i class="bi bi-water"></i></div>
            <div>
                <h2 class="h3 fw-bold mb-0">Natural Water Bodies &amp; Dams</h2>
                <p class="text-secondary mb-0">Explore key fishing grounds, trout streams, and reservoirs in KP.</p>
            </div>
        </div>
        <a href="{{ route('reservoirs.index') }}" class="btn btn-rflms btn-chip">
            <i class="bi bi-grid-fill"></i> View All {{ $totalReservoirs ?? '' }} Water Bodies
        </a>
    </div>

    <div class="row g-4">
        @foreach($featuredReservoirs as $reservoir)
        <div class="col-lg-4 col-md-6">
            <div class="reservoir-card h-100">
                <div class="reservoir-header-pattern p-3 d-flex flex-column justify-content-between" style="{{ $reservoir->imageUrl() ? 'background: linear-gradient(180deg, rgba(0,0,0,0.35) 0%, rgba(0,0,0,0.7) 100%), url('.$reservoir->imageUrl().') center/cover no-repeat; min-height: 140px;' : '' }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge {{ $reservoir->trout_type === 'trout' ? 'badge-trout' : ($reservoir->trout_type === 'mixed' ? 'badge-mixed' : 'badge-non-trout') }} rounded-pill">
                            <i class="bi bi-water me-1"></i>{{ $reservoir->troutLabel() }}
                        </span>
                        <span class="badge bg-black bg-opacity-40 text-white fw-normal">
                            {{ $reservoir->typeLabel() }}
                        </span>
                    </div>
                </div>

                <div class="p-4 flex-grow-1 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-1 text-primary small fw-semibold mb-1">
                            <i class="bi bi-geo-alt-fill text-danger"></i>
                            <span>District {{ $reservoir->district->name ?? 'KP' }}</span>
                        </div>
                        <h3 class="h5 fw-bold text-dark mb-2">{{ $reservoir->name }}</h3>
                        <div class="small text-secondary mb-2">
                            <span class="fw-semibold text-dark"><i class="bi bi-card-checklist text-rflms me-1"></i>Permits:</span>
                            {{ $reservoir->licensePermitsLabel() }}
                        </div>
                        <p class="text-secondary small mb-3">
                            {{ Str::limit($reservoir->description ?? 'Natural water body under management of Directorate General of Fisheries KP.', 100) }}
                            <a href="#" class="text-primary text-decoration-none fw-semibold ms-1" data-bs-toggle="modal" data-bs-target="#reservoirModal-{{ $reservoir->id }}">
                                Read More <i class="bi bi-arrows-angle-expand" style="font-size:0.75rem"></i>
                            </a>
                        </p>

                        @if($reservoir->species_notes)
                        <div class="mb-3">
                            <span class="text-muted small d-block mb-1 font-monospace" style="font-size:0.75rem">SPECIES PRESENT:</span>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach(explode(',', $reservoir->species_notes) as $species)
                                <span class="species-chip"><i class="bi bi-fish"></i> {{ trim($species) }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="pt-3 border-top d-flex align-items-center justify-content-between mt-auto">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-chip" data-bs-toggle="modal" data-bs-target="#reservoirModal-{{ $reservoir->id }}">
                            <i class="bi bi-info-circle"></i> Details
                        </button>
                        @auth
                        <a href="{{ route('citizen.applications.create', $reservoir) }}" class="btn btn-sm btn-rflms btn-chip">
                            <i class="bi bi-pen"></i> Apply Licence
                        </a>
                        @else
                        <a href="{{ route('catalogue.show', $reservoir) }}" class="btn btn-sm btn-rflms btn-chip">
                            <i class="bi bi-arrow-right-short"></i> Apply
                        </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>

        <!-- Water Body Description & Specs Modal -->
        <div class="modal fade" id="reservoirModal-{{ $reservoir->id }}" tabindex="-1" aria-labelledby="reservoirModalLabel-{{ $reservoir->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    @if ($reservoir->imageUrl())
                    <div style="background: linear-gradient(180deg, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.75) 100%), url('{{ $reservoir->imageUrl() }}') center/cover no-repeat; min-height: 180px;" class="p-4 d-flex flex-column justify-content-between text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge {{ $reservoir->trout_type === 'trout' ? 'badge-trout' : ($reservoir->trout_type === 'mixed' ? 'badge-mixed' : 'badge-non-trout') }} rounded-pill">
                                <i class="bi bi-water me-1"></i>{{ $reservoir->troutLabel() }}
                            </span>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div>
                            <div class="small text-warning fw-semibold mb-1"><i class="bi bi-geo-alt-fill text-danger me-1"></i>District {{ $reservoir->district->name ?? 'KP' }}</div>
                            <h3 class="h4 fw-bold mb-0 text-white">{{ $reservoir->name }}</h3>
                        </div>
                    </div>
                    @else
                    <div class="modal-header bg-rflms text-white p-4">
                        <div>
                            <div class="small text-warning fw-semibold mb-1"><i class="bi bi-geo-alt-fill text-danger me-1"></i>District {{ $reservoir->district->name ?? 'KP' }}</div>
                            <h3 class="h5 modal-title fw-bold" id="reservoirModalLabel-{{ $reservoir->id }}">{{ $reservoir->name }}</h3>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    @endif

                    <div class="modal-body p-4 text-start">
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-file-text me-1 text-primary"></i> Full Description &amp; Details</h6>
                        <p class="text-secondary mb-4" style="white-space: pre-line; line-height: 1.6;">
                            {{ $reservoir->description ?: 'Natural water body under active jurisdiction and management of Directorate General of Fisheries, Government of Khyber Pakhtunkhwa.' }}
                        </p>

                        @if($reservoir->species_notes)
                        <div class="mb-3 p-3 bg-light rounded-3">
                            <h6 class="fw-bold text-dark mb-2 small text-uppercase tracking-wide"><i class="bi bi-fish text-success me-1"></i> Species Present</h6>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach(explode(',', $reservoir->species_notes) as $species)
                                <span class="species-chip bg-white border"><i class="bi bi-fish"></i> {{ trim($species) }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($reservoir->trout_stretch_notes)
                        <div class="mb-3 p-3 bg-light rounded-3">
                            <h6 class="fw-bold text-dark mb-1 small text-uppercase"><i class="bi bi-water text-info me-1"></i> Trout Stretch Information</h6>
                            <p class="small text-secondary mb-0">{{ $reservoir->trout_stretch_notes }}</p>
                        </div>
                        @endif

                        @if($reservoir->reserve_area_notes)
                        <div class="mb-3 p-3 bg-light rounded-3">
                            <h6 class="fw-bold text-dark mb-1 small text-uppercase"><i class="bi bi-shield-check text-warning me-1"></i> Reserve Area Information</h6>
                            <p class="small text-secondary mb-0">{{ $reservoir->reserve_area_notes }}</p>
                        </div>
                        @endif

                        @if($reservoir->lease_notes)
                        <div class="mb-3 p-3 bg-light rounded-3">
                            <h6 class="fw-bold text-dark mb-1 small text-uppercase"><i class="bi bi-journal-text text-primary me-1"></i> Licensing &amp; Lease Notes</h6>
                            <p class="small text-secondary mb-0">{{ $reservoir->lease_notes }}</p>
                        </div>
                        @endif
                    </div>

                    <div class="modal-footer bg-light p-3 d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary btn-chip" data-bs-dismiss="modal">Close</button>
                        <div class="d-flex gap-2">
                            <a href="{{ route('catalogue.show', $reservoir) }}" class="btn btn-outline-dark btn-chip">
                                Details <i class="bi bi-arrow-right"></i>
                            </a>
                            @if ($reservoir->allowsELicence())
                                <a href="{{ route('citizen.applications.create', $reservoir) }}" class="btn btn-rflms btn-chip">
                                    <i class="bi bi-send-fill me-1"></i>Apply Licence
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="text-center mt-4 pt-2">
        <a href="{{ route('reservoirs.index') }}" class="btn btn-rflms btn-lg btn-chip px-5">
            <i class="bi bi-compass me-1"></i> Explore All {{ $totalReservoirs ?? '' }} Reservoirs &amp; Water Bodies Directory
        </a>
    </div>
</section>
@endif

<!-- How It Works / E-Licensing Journey Section -->
<section id="how-it-works" class="container pb-5">
    <style>
        .journey-showcase {
            position: relative;
            background: linear-gradient(180deg, #ffffff 0%, #f4faf7 100%);
            border: 1px solid rgba(11, 110, 79, 0.12);
            border-radius: 1.5rem;
            padding: 2.5rem 1.75rem;
            box-shadow: 0 12px 36px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }
        .journey-showcase::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(11, 110, 79, 0.08), transparent 70%);
            pointer-events: none;
        }
        .journey-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
            position: relative;
            z-index: 1;
        }
        @media (min-width: 768px) {
            .journey-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (min-width: 992px) {
            .journey-showcase {
                padding: 3rem 2.5rem;
            }
            .journey-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 2rem;
            }
            .journey-card:not(:last-child)::after {
                content: '';
                position: absolute;
                top: 2.35rem;
                right: -1.65rem;
                width: 1.35rem;
                height: 1.35rem;
                background-color: #0b6e4f;
                -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='3' y1='12' x2='20' y2='12'/%3E%3Cpolyline points='14 6 20 12 14 18'/%3E%3C/svg%3E") center / contain no-repeat;
                mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='3' y1='12' x2='20' y2='12'/%3E%3Cpolyline points='14 6 20 12 14 18'/%3E%3C/svg%3E") center / contain no-repeat;
                opacity: 0.75;
                z-index: 2;
                pointer-events: none;
                transition: transform 0.25s ease, opacity 0.25s ease;
            }
            .journey-card:not(:last-child):hover::after {
                transform: translateX(3px);
                opacity: 1;
            }
        }
        .journey-card {
            position: relative;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.15rem;
            padding: 1.5rem 1.25rem 1.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.28s cubic-bezier(.4,0,.2,1), box-shadow 0.28s ease, border-color 0.28s ease;
            z-index: 1;
        }
        .journey-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 38px rgba(11, 110, 79, 0.12);
            border-color: rgba(11, 110, 79, 0.35);
        }
        .journey-step-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.1rem;
        }
        .journey-num-pill {
            width: 2.4rem;
            height: 2.4rem;
            border-radius: 50%;
            background: linear-gradient(135deg, #0b6e4f, #064a35);
            color: #ffffff;
            font-weight: 800;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(11, 110, 79, 0.28);
            border: 3px solid #ffffff;
            transition: transform 0.25s ease, background 0.25s ease;
        }
        .journey-card:hover .journey-num-pill {
            transform: scale(1.1);
            background: linear-gradient(135deg, #d4a017, #b8860b);
            color: #0f172a;
        }
        .journey-icon-box {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            transition: transform 0.25s ease;
        }
        .journey-card:hover .journey-icon-box {
            transform: rotate(-4deg) scale(1.06);
        }
        .journey-meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            padding: 0.28rem 0.65rem;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            margin-top: 1rem;
            width: fit-content;
        }
    </style>

    <div class="journey-showcase">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4 pb-2">
            <div>
                <span class="badge rounded-pill px-3 py-1 mb-2 fw-semibold" style="background: rgba(11,110,79,0.1); color: #0b6e4f; letter-spacing: 0.05em;">
                    <i class="bi bi-stars me-1"></i> HOW IT WORKS · 4 SIMPLE STEPS
                </span>
                <h2 class="h3 fw-bold mb-1 text-dark">E-Licensing Journey</h2>
                <p class="text-secondary mb-0">From CNIC sign-up to an instant, QR-verified digital fishing permit.</p>
            </div>
            <a href="{{ route('citizen.applications.apply') }}" class="btn btn-rflms btn-chip px-4">
                <i class="bi bi-lightning-charge-fill"></i> Start Application
            </a>
        </div>

        <div class="journey-grid">
            <div class="journey-card">
                <div>
                    <div class="journey-step-top">
                        <span class="journey-num-pill">01</span>
                        <span class="journey-icon-box" style="background: #e8f5ef; color: #0b6e4f;">
                            <i class="bi bi-person-vcard-fill"></i>
                        </span>
                    </div>
                    <h3 class="h6 fw-bold text-dark mb-2">Citizen Registration</h3>
                    <p class="text-secondary small mb-0">Sign up with your CNIC and password (email &amp; OTP optional) and complete your profile once.</p>
                </div>
                <span class="journey-meta-pill">
                    <i class="bi bi-check2-circle text-success"></i> Quick CNIC Setup
                </span>
            </div>

            <div class="journey-card">
                <div>
                    <div class="journey-step-top">
                        <span class="journey-num-pill">02</span>
                        <span class="journey-icon-box" style="background: #e0f2fe; color: #0284c7;">
                            <i class="bi bi-geo-alt-fill"></i>
                        </span>
                    </div>
                    <h3 class="h6 fw-bold text-dark mb-2">District &amp; Category</h3>
                    <p class="text-secondary small mb-0">Select your district to view available water bodies and pick a Trout or Non-Trout licence category.</p>
                </div>
                <span class="journey-meta-pill">
                    <i class="bi bi-water text-primary"></i> District-Wide Permit
                </span>
            </div>

            <div class="journey-card">
                <div>
                    <div class="journey-step-top">
                        <span class="journey-num-pill">03</span>
                        <span class="journey-icon-box" style="background: #fef3c7; color: #b45309;">
                            <i class="bi bi-credit-card-2-front-fill"></i>
                        </span>
                    </div>
                    <h3 class="h6 fw-bold text-dark mb-2">1Bill / PAYMIR PSID</h3>
                    <p class="text-secondary small mb-0">Receive an official PAYMIR PSID voucher instantly and pay via 1Bill, banking apps, or mobile wallets.</p>
                </div>
                <span class="journey-meta-pill">
                    <i class="bi bi-shield-lock text-warning"></i> Online PSID Payment
                </span>
            </div>

            <div class="journey-card">
                <div>
                    <div class="journey-step-top">
                        <span class="journey-num-pill">04</span>
                        <span class="journey-icon-box" style="background: #e0f2fe; color: #0369a1;">
                            <i class="bi bi-qr-code-scan"></i>
                        </span>
                    </div>
                    <h3 class="h6 fw-bold text-dark mb-2">Instant QR E-Licence</h3>
                    <p class="text-secondary small mb-0">Once payment confirms, your licence is auto-verified in real time with a printable QR PDF card.</p>
                </div>
                <span class="journey-meta-pill">
                    <i class="bi bi-patch-check-fill text-success"></i> Auto-Issued PDF
                </span>
            </div>
        </div>
    </div>
</section>

<!-- Instant QR Verification Widget Section -->
<section id="verify-section" class="container pb-5">
    <div class="content-shell" style="border-top:3px solid var(--rflms-primary)">
        <div class="content-shell-body p-4 p-lg-5">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <div class="d-flex align-items-center gap-2 text-rflms small fw-bold mb-2">
                        <i class="bi bi-shield-check"></i>
                        <span>FIELD OFFICER &amp; PUBLIC VERIFICATION</span>
                    </div>
                    <h2 class="h3 fw-bold text-dark mb-2">Verify License Authenticity</h2>
                    <p class="text-secondary mb-0">
                        Enter the unique license token or scan the QR code printed on the E-License card to verify validity, holder identity, and expiry date.
                    </p>
                </div>
                <div class="col-lg-6">
                    <form action="#" method="GET" onsubmit="event.preventDefault(); var t = document.getElementById('tokenInput').value.trim(); if(t) window.location.href = '/verify/licence/' + encodeURIComponent(t);">
                        <div class="bg-light p-3 rounded-4 border qr-scan-box">
                            <label class="form-label small fw-bold text-muted">License Reference / QR Token</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-qr-code"></i></span>
                                <input type="text" id="tokenInput" class="form-control form-control-lg" placeholder="e.g. RFLMS-2026-ABC123XYZ" required>
                                <button type="submit" class="btn btn-rflms btn-lg px-4">
                                    <i class="bi bi-check-circle"></i> Verify
                                </button>
                            </div>
                            <div class="form-text small mt-2">
                                <i class="bi bi-info-circle me-1"></i> Fisheries wardens can scan the QR directly using any camera.
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Conservation & Anti-Poaching Notice -->
<section id="report" class="container pb-5">
    <div class="conservation-banner p-4 p-lg-5">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-2 text-warning small fw-bold mb-2">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                    <span>AQUATIC CONSERVATION &amp; PROTECTION LAWS</span>
                </div>
                <h2 class="h3 fw-bold text-white mb-2">Help Protect Khyber Pakhtunkhwa's Fisheries</h2>
                <p class="text-white opacity-80 mb-0">
                    Use of nets, poison, explosives, or electric current in natural waters is strictly prohibited under the KP Fisheries Act. Report illegal netting or out-of-season poaching anonymously.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="{{ route('violations.create') }}" class="btn btn-warning btn-lg text-dark fw-bold btn-chip px-4">
                    <i class="bi bi-flag-fill"></i> Report Illegal Fishing
                </a>
            </div>
        </div>
    </div>
</section>
@endsection