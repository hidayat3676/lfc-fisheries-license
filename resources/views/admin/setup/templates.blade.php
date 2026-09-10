@extends('layouts.admin')

@section('title', 'Setup — Permission templates — '.config('app.name'))

@section('content')
@include('admin.setup._steps', ['steps' => $steps, 'current' => 'templates'])

<div class="alert alert-light border">
    These presets appear on <a href="{{ route('admin.users.create') }}">Add staff user</a>.
    Pick a template → districts + module checkboxes fill automatically. Active staff admins: <strong>{{ $staffCount }}</strong>.
</div>

<div class="row g-3 mb-4">
    @foreach ($templates as $key => $template)
        <div class="col-md-6">
            <div class="bg-white rounded-4 shadow-sm p-4 h-100">
                <h2 class="h6 fw-bold mb-1">{{ $template['label'] }}</h2>
                <p class="small text-secondary mb-3">{{ $template['description'] }}</p>
                <div class="small mb-2">
                    <span class="badge text-bg-secondary">{{ ! empty($template['all_districts']) ? 'All districts' : 'Selected districts' }}</span>
                </div>
                <ul class="small mb-0 ps-3">
                    @foreach ($template['modules'] as $moduleKey => $actions)
                        @php $mod = $modules->get($moduleKey); @endphp
                        <li>
                            <strong>{{ $mod?->name ?? $moduleKey }}</strong>:
                            {{ implode(', ', $actions) }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endforeach
</div>

<form method="POST" action="{{ route('admin.setup.templates.save') }}">
    @csrf
    <div class="bg-white rounded-4 shadow-sm p-4 mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="confirm" value="1" id="confirm" required>
            <label class="form-check-label" for="confirm">
                I understand DG / District / Office / Field templates and will use them when creating staff.
            </label>
        </div>
        @if ($confirmed)
            <p class="small text-success mb-0 mt-2">Already confirmed.</p>
        @endif
    </div>
    <button type="submit" class="btn btn-rflms">Confirm &amp; continue to QR privacy</button>
</form>
@endsection
