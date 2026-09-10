@extends('layouts.admin')

@section('title', 'Applications — '.config('app.name'))

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="page-title-block mb-0">
        <div class="title-icon"><i class="bi bi-inbox"></i></div>
        <div>
            <h1 class="h3 fw-bold mb-1">Licence applications</h1>
            <p class="page-subtitle mb-0">Review payment proof and issue licences</p>
        </div>
    </div>
    @if(auth()->user()->hasModuleAction('applications', 'create'))
        <a href="{{ route('admin.applications.create') }}" class="btn btn-rflms btn-chip">
            <i class="bi bi-plus-circle-fill me-1"></i> Apply Walk-in Licence
        </a>
    @endif
</div>

<form method="GET" class="content-shell mb-3">
    <div class="content-shell-body">
    <div class="row g-2">
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach (['submitted','under_review','info_required','approved','rejected'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_',' ', $status)) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Application no / citizen">
        </div>
    </div>
    <div class="col-md-3">
        <button class="btn btn-outline-secondary w-100 btn-chip" type="submit"><i class="bi bi-funnel"></i> Filter</button>
    </div>
    </div>
    </div>
</form>

<div class="content-shell">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Application</th>
                    <th>Citizen</th>
                    <th>Water body</th>
                    <th>Category / Fee</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($applications as $app)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $app->application_no }}</div>
                            <div class="small text-secondary">{{ $app->created_at?->format('d M Y H:i') }}</div>
                        </td>
                        <td>{{ $app->user?->name }}</td>
                        <td>
                            <div>{{ $app->reservoir?->name }}</div>
                            <div class="small text-secondary">{{ $app->reservoir?->district?->name }}</div>
                        </td>
                        <td>
                            <div>{{ $app->category?->name }}</div>
                            <div class="small">{{ number_format((float) $app->fee_amount_snapshot, 0) }} {{ $app->currency }}</div>
                        </td>
                        <td><span class="badge text-bg-secondary">{{ $app->statusLabel() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('admin.applications.show', $app) }}" class="btn btn-sm btn-outline-secondary btn-chip row-action"><i class="bi bi-box-arrow-up-right"></i> Open</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state"><i class="bi bi-inbox"></i>No applications found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $applications->links() }}</div>
@endsection
