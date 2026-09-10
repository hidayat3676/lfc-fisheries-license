@extends('layouts.admin')

@section('title', 'Reports — '.config('app.name'))

@section('content')
<div class="mb-3">
    <h1 class="h3 fw-bold mb-1">Reports</h1>
    <p class="text-secondary mb-0">Export district-scoped data as Excel-compatible CSV or printable PDF</p>
</div>

<div class="bg-white rounded-4 shadow-sm p-4">
    <form method="GET" action="{{ route('admin.reports.export') }}" class="row g-3" target="_blank">
        <div class="col-md-4">
            <label class="form-label">Report</label>
            <select name="type" class="form-select" required>
                @foreach ($types as $key => $label)
                    <option value="{{ $key }}" @selected($filters['type'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">From</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">To</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Status (optional)</label>
            <input type="text" name="status" value="{{ $filters['status'] }}" class="form-control"
                   placeholder="e.g. submitted, active, pending">
            <div class="form-text">Leave blank for all statuses.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Format</label>
            <select name="format" class="form-select" required>
                <option value="csv" @selected($filters['format'] === 'csv')>CSV / Excel</option>
                <option value="print" @selected($filters['format'] === 'print')>Printable PDF</option>
            </select>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-rflms w-100">Generate report</button>
        </div>
    </form>
</div>

<div class="mt-4 small text-secondary">
    <p class="mb-1">Tips:</p>
    <ul class="mb-0">
        <li>CSV opens in Excel / LibreOffice (UTF-8 with BOM).</li>
        <li>Printable PDF: use the browser Print dialog → Save as PDF.</li>
        <li>Results are limited to 5,000 rows and respect your district scope.</li>
    </ul>
</div>
@endsection
