@extends('layouts.admin')

@section('title', 'Policy setup — '.config('app.name'))

@section('content')
@include('admin.setup._steps', ['steps' => $steps, 'current' => ''])

@php
    $doneCount = collect($steps)->where('done', true)->count();
    $stepIcons = [
        'fees' => 'bi-cash-coin',
        'eligibility' => 'bi-water',
        'templates' => 'bi-people',
        'qr' => 'bi-qr-code-scan',
    ];
@endphp

<div class="content-shell mb-4">
    <div class="content-shell-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 fw-bold mb-0 d-flex align-items-center gap-2"><i class="bi bi-activity text-rflms"></i> Setup progress</h2>
            <span class="badge text-bg-secondary">{{ $doneCount }} / {{ count($steps) }} confirmed</span>
        </div>
        <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-rflms" style="width: {{ ($doneCount / max(1, count($steps))) * 100 }}%"></div>
        </div>
        <p class="small text-secondary mt-3 mb-0">
            Work through each step, tick <strong>Confirm</strong>, then save. The system enforces these rules on citizen apply and public QR verify.
        </p>
    </div>
</div>

<div class="row g-3">
    @foreach ($steps as $step)
        <div class="col-md-6">
            <div class="bg-white rounded-4 shadow-sm p-4 h-100 card-hover" style="border:1px solid var(--rflms-border); border-left:3px solid var(--rflms-primary)">
                <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="feature-icon" style="width:2.25rem;height:2.25rem"><i class="bi {{ $stepIcons[$step['key']] ?? 'bi-circle' }}"></i></span>
                        <h3 class="h6 fw-bold mb-0">{{ $step['label'] }}</h3>
                    </div>
                    @if ($step['done'])
                        <span class="badge badge-status-approved"><i class="bi bi-check-lg"></i> Confirmed</span>
                    @elseif ($step['ready'])
                        <span class="badge badge-status-pending">Ready</span>
                    @else
                        <span class="badge text-bg-light text-secondary border">Needs data</span>
                    @endif
                </div>
                <p class="small text-secondary mb-3">{{ $step['hint'] }}</p>
                <a href="{{ route($step['route']) }}" class="btn btn-sm btn-chip {{ $step['done'] ? 'btn-outline-secondary' : 'btn-rflms' }}">
                    <i class="bi {{ $step['done'] ? 'bi-eye' : 'bi-arrow-right-circle' }}"></i>
                    {{ $step['done'] ? 'Review' : 'Open step' }}
                </a>
            </div>
        </div>
    @endforeach
</div>
@endsection
