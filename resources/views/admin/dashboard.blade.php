@extends('layouts.admin')

@section('title', 'Admin — '.config('app.name'))

@section('content')
<div class="page-title-block">
    <div class="title-icon"><i class="bi bi-speedometer2"></i></div>
    <div>
        <h1 class="h3 fw-bold">Staff dashboard</h1>
        <p class="page-subtitle mb-0">
            <i class="bi bi-person-badge me-1"></i>{{ auth()->user()->name }}
            · {{ str_replace('_', ' ', auth()->user()->user_type) }}
            @if (auth()->user()->all_districts || auth()->user()->isSuperAdmin())
                · <i class="bi bi-geo-alt me-1"></i>All districts
            @endif
        </p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-2">
        <div class="bg-white rounded-4 p-3 h-100 stat-card accent-orange">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
                <span class="stat-label mb-0">Pending</span>
            </div>
            <div class="stat-value">{{ $stats['pending_applications'] }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="bg-white rounded-4 p-3 h-100 stat-card accent-blue">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
                <span class="stat-label mb-0">Approved today</span>
            </div>
            <div class="stat-value">{{ $stats['approved_today'] }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="bg-white rounded-4 p-3 h-100 stat-card">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="stat-icon"><i class="bi bi-card-checklist"></i></span>
                <span class="stat-label mb-0">Active licences</span>
            </div>
            <div class="stat-value">{{ $stats['issued_licences'] }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="bg-white rounded-4 p-3 h-100 stat-card accent-red">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="stat-icon"><i class="bi bi-exclamation-triangle"></i></span>
                <span class="stat-label mb-0">Violations</span>
            </div>
            <div class="stat-value">{{ $stats['open_violations'] }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="bg-white rounded-4 p-3 h-100 stat-card">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="stat-icon"><i class="bi bi-water"></i></span>
                <span class="stat-label mb-0">Water bodies</span>
            </div>
            <div class="stat-value">{{ $stats['active_water_bodies'] }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="bg-white rounded-4 p-3 h-100 stat-card accent-gold">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="stat-icon"><i class="bi bi-cash-stack"></i></span>
                <span class="stat-label mb-0">Revenue</span>
            </div>
            <div class="stat-value" style="font-size:1.25rem">{{ number_format($stats['revenue_verified'], 0) }}</div>
            <div class="small text-secondary">PKR verified</div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
    @if (auth()->user()->hasModuleAction('applications', 'view'))
        <a href="{{ route('admin.applications.index') }}" class="btn btn-rflms btn-sm btn-chip"><i class="bi bi-inbox"></i> Applications</a>
    @endif
    @if (auth()->user()->hasModuleAction('reports', 'view'))
        <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm btn-chip"><i class="bi bi-bar-chart"></i> Reports</a>
    @endif
    @if (auth()->user()->hasModuleAction('settings', 'view'))
        <a href="{{ route('admin.setup.index') }}" class="btn btn-outline-secondary btn-sm btn-chip"><i class="bi bi-sliders"></i> Policy setup</a>
    @endif
    @if (auth()->user()->hasModuleAction('violations', 'view'))
        <a href="{{ route('admin.violations.index') }}" class="btn btn-outline-secondary btn-sm btn-chip"><i class="bi bi-exclamation-octagon"></i> Violations</a>
    @endif
    @if (auth()->user()->hasModuleAction('license_categories', 'view'))
        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary btn-sm btn-chip"><i class="bi bi-tags"></i> Categories</a>
    @endif
    @if (auth()->user()->hasModuleAction('reservoirs', 'view'))
        <a href="{{ route('admin.reservoirs.index') }}" class="btn btn-outline-secondary btn-sm btn-chip"><i class="bi bi-water"></i> Water bodies</a>
    @endif
    @if (auth()->user()->hasModuleAction('users', 'view'))
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm btn-chip"><i class="bi bi-people"></i> Staff users</a>
    @endif
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="content-shell">
            <div class="panel-header"><i class="bi bi-file-earmark-text"></i> Recent applications</div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle table-hover">
                    <tbody>
                        @forelse ($recentApplications as $app)
                            <tr class="soft-row">
                                <td>
                                    <div class="fw-semibold">{{ $app->application_no }}</div>
                                    <div class="small text-secondary"><i class="bi bi-person me-1"></i>{{ $app->user?->name }} · <i class="bi bi-geo me-1"></i>{{ $app->reservoir?->name }}</div>
                                </td>
                                <td><span class="badge text-bg-secondary">{{ $app->statusLabel() }}</span></td>
                                <td class="text-end">
                                    @if (auth()->user()->hasModuleAction('applications', 'view'))
                                        <a href="{{ route('admin.applications.show', $app) }}" class="btn btn-sm btn-outline-secondary row-action btn-chip"><i class="bi bi-box-arrow-up-right"></i> Open</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td class="empty-state"><i class="bi bi-inbox"></i>No applications yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="content-shell">
            <div class="panel-header"><i class="bi bi-exclamation-triangle"></i> Recent violations</div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle table-hover">
                    <tbody>
                        @forelse ($recentViolations as $report)
                            <tr class="soft-row">
                                <td>
                                    <div class="fw-semibold">{{ $report->report_no }}</div>
                                    <div class="small text-secondary">{{ $report->district?->name }} · {{ $report->typeLabel() }}</div>
                                </td>
                                <td class="text-end"><span class="badge text-bg-secondary">{{ $report->statusLabel() }}</span></td>
                            </tr>
                        @empty
                            <tr><td class="empty-state"><i class="bi bi-shield-check"></i>No violation reports yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
