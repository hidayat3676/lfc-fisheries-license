@php
    $current = $current ?? '';
    $stepIcons = [
        'fees' => 'bi-cash-coin',
        'eligibility' => 'bi-water',
        'templates' => 'bi-people',
        'qr' => 'bi-qr-code-scan',
    ];
@endphp
<div class="mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="page-title-block mb-0">
            <div class="title-icon"><i class="bi bi-sliders"></i></div>
            <div>
                <h1 class="h3 fw-bold mb-0">Policy setup</h1>
                <p class="page-subtitle mb-0">Confirm fees, waters, staff templates, and QR privacy in order</p>
            </div>
        </div>
        <a href="{{ route('admin.setup.index') }}" class="btn btn-outline-secondary btn-sm btn-chip">
            <i class="bi bi-list-check"></i> Checklist
        </a>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @foreach ($steps as $step)
            @php
                $isCurrent = $step['key'] === $current;
                $chipClass = $isCurrent ? 'active' : ($step['done'] ? 'done' : '');
            @endphp
            <a href="{{ route($step['route']) }}" class="setup-step-chip {{ $chipClass }}">
                <i class="bi {{ $stepIcons[$step['key']] ?? 'bi-circle' }}"></i>
                @if ($step['done']) <i class="bi bi-check-lg"></i> @endif
                {{ $step['label'] }}
            </a>
        @endforeach
    </div>
</div>
