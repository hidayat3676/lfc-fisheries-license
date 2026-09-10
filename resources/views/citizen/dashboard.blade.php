@extends('layouts.app')

@section('title', 'My account — '.config('app.name'))

@section('content')
<section class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div class="page-title-block mb-0">
            <div class="title-icon"><i class="bi bi-person-circle"></i></div>
            <div>
                <h1 class="h3 fw-bold">Citizen dashboard</h1>
                <p class="page-subtitle mb-0">Welcome, {{ auth()->user()->name }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-outline-secondary btn-sm btn-chip" type="submit"><i class="bi bi-box-arrow-right"></i> Logout</button>
        </form>
    </div>

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-check-circle"></i>{{ session('status') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="bg-white rounded-4 p-3 h-100 stat-card accent-orange">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
                    <span class="stat-label mb-0">Pending</span>
                </div>
                <div class="stat-value">{{ $stats['pending'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="bg-white rounded-4 p-3 h-100 stat-card accent-blue">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
                    <span class="stat-label mb-0">Approved</span>
                </div>
                <div class="stat-value">{{ $stats['approved'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="bg-white rounded-4 p-3 h-100 stat-card">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon"><i class="bi bi-card-checklist"></i></span>
                    <span class="stat-label mb-0">Active licences</span>
                </div>
                <div class="stat-value">{{ $stats['active_licences'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="bg-white rounded-4 p-3 h-100 stat-card {{ $stats['profile_complete'] ? '' : 'accent-orange' }}">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon"><i class="bi bi-person-vcard"></i></span>
                    <span class="stat-label mb-0">Profile</span>
                </div>
                <div class="fw-semibold">{{ $stats['profile_complete'] ? 'Complete' : 'Incomplete' }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <a href="{{ route('citizen.applications.index') }}" class="action-card bg-white rounded-4 p-4 shadow-sm h-100">
                <div class="d-flex align-items-start gap-3">
                    <span class="action-icon"><i class="bi bi-folder2-open"></i></span>
                    <div>
                        <div class="fw-semibold mb-1">Applications &amp; licences</div>
                        <p class="text-secondary small mb-0">Track requests and open issued digital licences.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('catalogue.index') }}" class="action-card bg-white rounded-4 p-4 shadow-sm h-100">
                <div class="d-flex align-items-start gap-3">
                    <span class="action-icon"><i class="bi bi-water"></i></span>
                    <div>
                        <div class="fw-semibold mb-1">Browse water bodies</div>
                        <p class="text-secondary small mb-0">Choose a reservoir and apply for a licence.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('citizen.profile.edit') }}" class="action-card bg-white rounded-4 p-4 shadow-sm h-100">
                <div class="d-flex align-items-start gap-3">
                    <span class="action-icon"><i class="bi bi-person-gear"></i></span>
                    <div>
                        <div class="fw-semibold mb-1">Profile</div>
                        <p class="text-secondary small mb-0">
                            {{ $stats['profile_complete'] ? 'Profile complete' : 'Complete CNIC details before apply' }}
                        </p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="content-shell">
        <div class="panel-header"><i class="bi bi-clock-history"></i> Recent applications</div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle table-hover">
                <tbody>
                    @forelse ($recentApplications as $app)
                        <tr class="soft-row">
                            <td>
                                <div class="fw-semibold">{{ $app->application_no }}</div>
                                <div class="small text-secondary"><i class="bi bi-geo me-1"></i>{{ $app->reservoir?->name }} · {{ $app->category?->name }}</div>
                            </td>
                            <td><span class="badge text-bg-secondary">{{ $app->statusLabel() }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('citizen.applications.show', $app) }}" class="btn btn-sm btn-outline-secondary row-action btn-chip"><i class="bi bi-eye"></i> View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty-state"><i class="bi bi-inbox"></i>No applications yet. Browse water bodies to apply.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
