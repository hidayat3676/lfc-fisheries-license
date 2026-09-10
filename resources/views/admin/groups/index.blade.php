@extends('layouts.admin')

@section('title', 'Groups & Roles — ' . config('app.name'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h1 class="h3 fw-bold mb-1">Groups &amp; Roles</h1>
        <p class="text-secondary mb-0">Manage permission groups and assign them to staff members</p>
    </div>
    @if (auth()->user()->hasModuleAction('groups', 'create') || auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.groups.create') }}" class="btn btn-rflms btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Add new group
        </a>
    @endif
</div>

<div class="bg-white rounded-4 shadow-sm p-3 mb-3">
    <form method="GET" action="{{ route('admin.groups.index') }}" class="row g-2 align-items-center">
        <div class="col-md-5 col-lg-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm border-start-0" placeholder="Search by name or description...">
            </div>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
            @if (request()->filled('q'))
                <a href="{{ route('admin.groups.index') }}" class="btn btn-sm btn-link text-decoration-none">Clear</a>
            @endif
        </div>
    </form>
</div>

<div class="bg-white rounded-4 shadow-sm p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 30%;">Group Name</th>
                    <th>Description</th>
                    <th class="text-center" style="width: 15%;">Assigned Users</th>
                    <th class="text-center" style="width: 12%;">Status</th>
                    <th class="text-end" style="width: 15%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($groups as $group)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark fs-6">{{ $group->name }}</div>
                            <div class="small text-secondary">ID: #{{ $group->id }}</div>
                        </td>
                        <td>
                            <div class="small text-muted">{{ $group->description ?: 'No description provided.' }}</div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary-subtle text-dark border px-2.5 py-1.5 rounded-pill">
                                <i class="bi bi-people-fill me-1 text-primary"></i>{{ $group->users_count }} {{ Str::plural('user', $group->users_count) }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if ($group->is_active)
                                <span class="badge text-bg-success px-2 py-1">Active</span>
                            @else
                                <span class="badge text-bg-danger px-2 py-1">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                @if (auth()->user()->hasModuleAction('groups', 'edit') || auth()->user()->isSuperAdmin())
                                    <a href="{{ route('admin.groups.edit', $group) }}" class="btn btn-sm btn-outline-secondary" title="Edit Group">
                                        Edit
                                    </a>
                                @endif
                                @if (auth()->user()->hasModuleAction('groups', 'delete') || auth()->user()->isSuperAdmin())
                                    <form method="POST" action="{{ route('admin.groups.destroy', $group) }}" onsubmit="return confirm('Are you sure you want to delete this group?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Group">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">No groups found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($groups->hasPages())
        <div class="mt-3">
            {{ $groups->links() }}
        </div>
    @endif
</div>
@endsection
