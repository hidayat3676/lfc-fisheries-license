@extends('layouts.app')

@section('title', 'My applications — '.config('app.name'))

@section('content')
<section class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 fw-bold mb-1">My applications &amp; licences</h1>
            <p class="text-secondary mb-0">Track status and open issued licences</p>
        </div>
        <a href="{{ route('catalogue.index') }}" class="btn btn-rflms btn-sm">Browse water bodies</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="bg-white rounded-4 shadow-sm overflow-hidden mb-4">
        <div class="p-3 border-bottom fw-semibold">Applications</div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No.</th>
                        <th>Water body</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $app)
                        <tr>
                            <td>{{ $app->application_no }}</td>
                            <td>{{ $app->reservoir?->name }}</td>
                            <td>{{ $app->category?->name }}</td>
                            <td><span class="badge text-bg-secondary">{{ $app->statusLabel() }}</span></td>
                            <td class="text-end"><a href="{{ route('citizen.applications.show', $app) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-4">No applications yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $applications->links() }}

    <div class="bg-white rounded-4 shadow-sm overflow-hidden mt-4">
        <div class="p-3 border-bottom fw-semibold">Issued licences</div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Licence</th>
                        <th>Water body</th>
                        <th>Valid</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($licenses as $license)
                        <tr>
                            <td>{{ $license->license_no }}</td>
                            <td>{{ $license->reservoir?->name }}</td>
                            <td class="small">{{ $license->issue_date?->format('d M Y') }} → {{ $license->expiry_date?->format('d M Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('citizen.licenses.card', $license) }}" class="btn btn-sm btn-rflms">Digital card</a>
                                <a href="{{ route('license.verify', $license->qr_token) }}" class="btn btn-sm btn-outline-secondary" target="_blank">Verify</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-secondary py-4">No licences issued yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
