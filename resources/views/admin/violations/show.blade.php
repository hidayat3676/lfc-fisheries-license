@extends('layouts.admin')

@section('title', $report->report_no.' — '.config('app.name'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 fw-bold mb-1">{{ $report->report_no }}</h1>
        <p class="text-secondary mb-0">{{ $report->statusLabel() }} · {{ $report->typeLabel() }}</p>
    </div>
    <a href="{{ route('admin.violations.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="bg-white rounded-4 shadow-sm p-4 mb-3">
            <dl class="row small mb-0">
                <dt class="col-sm-4">District</dt><dd class="col-sm-8">{{ $report->district?->name }}</dd>
                <dt class="col-sm-4">Water body</dt><dd class="col-sm-8">{{ $report->reservoir?->name ?: '—' }}</dd>
                <dt class="col-sm-4">Occurred</dt><dd class="col-sm-8">{{ $report->occurred_at?->format('d M Y H:i') ?: '—' }}</dd>
                <dt class="col-sm-4">Reporter</dt><dd class="col-sm-8">{{ $report->reporter_name ?: $report->reporter?->name ?: 'Anonymous' }} · {{ $report->reporter_phone ?: '—' }}</dd>
                <dt class="col-sm-4">Location</dt>
                <dd class="col-sm-8">
                    @if ($report->latitude && $report->longitude)
                        {{ $report->latitude }}, {{ $report->longitude }}
                        <a href="https://www.google.com/maps?q={{ $report->latitude }},{{ $report->longitude }}" target="_blank" rel="noopener">Map</a>
                    @else
                        —
                    @endif
                </dd>
                <dt class="col-sm-4">Description</dt><dd class="col-sm-8">{{ $report->description }}</dd>
            </dl>
        </div>

        @if ($report->media->isNotEmpty())
            <div class="bg-white rounded-4 shadow-sm p-4">
                <h2 class="h6 fw-bold mb-3">Photos</h2>
                <div class="row g-2">
                    @foreach ($report->media as $media)
                        <div class="col-md-4">
                            <a href="{{ asset('storage/'.$media->path) }}" target="_blank">
                                <img src="{{ asset('storage/'.$media->path) }}" alt="Evidence" class="img-fluid rounded-3 border">
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-5">
        @if (auth()->user()->hasModuleAction('violations', 'status'))
            <div class="bg-white rounded-4 shadow-sm p-4">
                <h2 class="h6 fw-bold mb-3">Update status</h2>
                <form method="POST" action="{{ route('admin.violations.status', $report) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="pending" @selected($report->status === 'pending')>Pending</option>
                            <option value="under_investigation" @selected($report->status === 'under_investigation')>Under investigation</option>
                            <option value="resolved" @selected($report->status === 'resolved')>Resolved</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Resolution notes</label>
                        <textarea name="resolution_notes" class="form-control" rows="4">{{ old('resolution_notes', $report->resolution_notes) }}</textarea>
                    </div>
                    <button class="btn btn-rflms w-100" type="submit">Save</button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
