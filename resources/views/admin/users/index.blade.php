@extends('layouts.admin')

@section('title', 'Users — '.config('app.name'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 fw-bold mb-1">Staff users</h1>
        <p class="text-secondary mb-0">Districts × modules × actions</p>
    </div>
    @if (auth()->user()->hasModuleAction('users', 'create'))
        <a href="{{ route('admin.users.create') }}" class="btn btn-rflms">Add staff user</a>
    @endif
</div>

<form method="GET" class="mb-3">
    <div class="input-group" style="max-width: 420px;">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name or email">
        <button class="btn btn-outline-secondary" type="submit">Search</button>
    </div>
</form>

<div class="bg-white rounded-4 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Districts</th>
                    <th>Offices</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $user->name }}</div>
                            @if ($user->adminProfile?->cnic)
                                <div class="small text-muted font-monospace"><i class="bi bi-card-text me-1"></i>{{ $user->adminProfile->cnic }}</div>
                            @endif
                            <div class="small text-secondary">{{ $user->adminProfile?->designation_label }}</div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if ($user->isSuperAdmin())
                                <span class="badge text-bg-primary">Super Admin</span>
                            @elseif ($user->isExecutive())
                                <span class="badge text-bg-info text-dark">Executive</span>
                            @else
                                <span class="badge text-bg-secondary">Admin</span>
                            @endif
                            @if ($user->groups->isNotEmpty())
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    @foreach ($user->groups as $grp)
                                        <span class="badge bg-secondary-subtle text-dark border" style="font-size: 0.68rem;">{{ $grp->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="small">
                            @if ($user->isSuperAdmin() || $user->all_districts)
                                All districts
                            @elseif ($user->districts->isEmpty())
                                —
                            @else
                                {{ $user->districts->pluck('name')->take(3)->join(', ') }}
                                @if ($user->districts->count() > 3)
                                    +{{ $user->districts->count() - 3 }}
                                @endif
                            @endif
                        </td>
                        <td class="small">
                            @if ($user->isSuperAdmin())
                                All offices
                            @elseif ($user->offices->isEmpty())
                                —
                            @else
                                {{ $user->offices->pluck('name')->take(2)->join(', ') }}
                                @if ($user->offices->count() > 2)
                                    +{{ $user->offices->count() - 2 }}
                                @endif
                            @endif
                        </td>
                        <td>
                            @if ($user->is_active)
                                <span class="badge text-bg-success">Active</span>
                            @else
                                <span class="badge text-bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if (auth()->user()->hasModuleAction('users', 'edit'))
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">No staff users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $users->links() }}</div>
@endsection
