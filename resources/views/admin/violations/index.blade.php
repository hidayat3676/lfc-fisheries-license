@extends('layouts.admin')

@section('title', 'Violations — '.config('app.name'))

@section('content')
<div class="mb-3">
    <h1 class="h3 fw-bold mb-1">Violation reports</h1>
    <p class="text-secondary mb-0">Illegal fishing triage queue</p>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach (['pending','under_investigation','resolved'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_',' ', $status)) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <button class="btn btn-outline-secondary w-100" type="submit">Filter</button>
    </div>
</form>

<div class="bg-white rounded-4 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Report</th>
                    <th>District</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $report->report_no }}</div>
                            <div class="small text-secondary">{{ $report->created_at?->format('d M Y H:i') }}</div>
                        </td>
                        <td>{{ $report->district?->name }}</td>
                        <td>{{ $report->typeLabel() }}</td>
                        <td><span class="badge text-bg-secondary">{{ $report->statusLabel() }}</span></td>
                        <td class="text-end"><a href="{{ route('admin.violations.show', $report) }}" class="btn btn-sm btn-outline-secondary">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-4">No reports found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $reports->links() }}</div>
@endsection
