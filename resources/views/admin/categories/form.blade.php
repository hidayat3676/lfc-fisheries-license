@extends('layouts.admin')

@section('title', ($mode === 'create' ? 'Add category' : 'Edit category').' — '.config('app.name'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 fw-bold mb-0">{{ $mode === 'create' ? 'Add category' : 'Edit category' }}</h1>
    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<form method="POST" action="{{ $mode === 'create' ? route('admin.categories.store') : route('admin.categories.update', $category) }}" class="bg-white rounded-4 shadow-sm p-4">
    @csrf
    @if ($mode === 'edit') @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Code</label>
            <input type="text" name="code" class="form-control" required value="{{ old('code', $category->code) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Name (English)</label>
            <input type="text" name="name" class="form-control" required value="{{ old('name', $category->name) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label d-flex justify-content-between align-items-center" for="urdu_name">
                <span>Urdu Name</span>
                <span dir="rtl" lang="ur" class="text-secondary small">اردو نام</span>
            </label>
            <input type="text" id="urdu_name" name="urdu_name" dir="rtl" lang="ur" class="form-control" placeholder="مثلاً یومیہ لائسنس" value="{{ old('urdu_name', $category->urdu_name) }}" autocomplete="off">
            <div id="urduKeyboardHint" class="form-text small text-success d-none">
                <i class="bi bi-keyboard me-1"></i> Urdu phonetic typing active (اردو کی بورڈ فعال ہے)
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2">{{ old('description', $category->description) }}</textarea>
        </div>
        <div class="col-md-3">
            <label class="form-label">Duration type</label>
            <select name="duration_type" class="form-select" required>
                @foreach (['daily','weekly','monthly','seasonal','custom'] as $type)
                    <option value="{{ $type }}" @selected(old('duration_type', $category->duration_type) === $type)>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Duration days</label>
            <input type="number" name="duration_days" class="form-control" min="1" value="{{ old('duration_days', $category->duration_days) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Fee amount</label>
            <input type="number" step="0.01" name="fee_amount" class="form-control" required value="{{ old('fee_amount', $category->fee_amount) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Currency</label>
            <input type="text" name="currency" class="form-control" required value="{{ old('currency', $category->currency ?: 'PKR') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Expiry rule</label>
            <select name="expiry_rule" class="form-select" required>
                <option value="from_start" @selected(old('expiry_rule', $category->expiry_rule) === 'from_start')>From start + days</option>
                <option value="fixed_date" @selected(old('expiry_rule', $category->expiry_rule) === 'fixed_date')>Fixed date (MM-DD)</option>
                <option value="season_end" @selected(old('expiry_rule', $category->expiry_rule) === 'season_end')>Season end</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Fixed expiry (MM-DD)</label>
            <input type="text" name="fixed_expiry_month_day" class="form-control" placeholder="06-30" value="{{ old('fixed_expiry_month_day', $category->fixed_expiry_month_day) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Max fish limit</label>
            <input type="number" name="max_fish_limit" class="form-control" value="{{ old('max_fish_limit', $category->max_fish_limit) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Instructions</label>
            <textarea name="instructions" class="form-control" rows="3">{{ old('instructions', $category->instructions) }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Terms</label>
            <textarea name="terms" class="form-control" rows="3">{{ old('terms', $category->terms) }}</textarea>
        </div>
        <div class="col-12">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $category->is_active ?? true))>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
        </div>
    </div>
    <button class="btn btn-rflms mt-3" type="submit">Save</button>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const urduInput = document.getElementById('urdu_name');
    const hint = document.getElementById('urduKeyboardHint');
    if (!urduInput) return;

    // Standard Urdu Phonetic Keyboard Mapping
    const urduKeyMap = {
        'a': '\u0627', 'A': '\u0622',
        'b': '\u0628', 'B': '\u0613',
        'c': '\u0686', 'C': '\u062B',
        'd': '\u062F', 'D': '\u0688',
        'e': '\u0639', 'E': '\u0611',
        'f': '\u0641', 'F': '\u0656',
        'g': '\u06AF', 'G': '\u063A',
        'h': '\u06C1', 'H': '\u062D',
        'i': '\u06CC', 'I': '\u0670',
        'j': '\u062C', 'J': '\u0636',
        'k': '\u06A9', 'K': '\u062E',
        'l': '\u0644', 'L': '\u0612',
        'm': '\u0645', 'M': '\u0652',
        'n': '\u0646', 'N': '\u06BA',
        'o': '\u06C1', 'O': '\u0629',
        'p': '\u067E', 'P': '\u064F',
        'q': '\u0642', 'Q': '\u0640',
        'r': '\u0631', 'R': '\u0691',
        's': '\u0633', 'S': '\u0635',
        't': '\u062A', 'T': '\u0679',
        'u': '\u0621', 'U': '\u0626',
        'v': '\u0637', 'V': '\u0638',
        'w': '\u0648', 'W': '\u0624',
        'x': '\u0634', 'X': '\u0698',
        'y': '\u06D2', 'Y': '\u0601',
        'z': '\u0632', 'Z': '\u0630',
        ',': '\u060C',
        '.': '\u06D4',
        '?': '\u061F',
        ';': '\u061B'
    };

    urduInput.addEventListener('focus', function () {
        if (hint) hint.classList.remove('d-none');
    });

    urduInput.addEventListener('blur', function () {
        if (hint) hint.classList.add('d-none');
    });

    urduInput.addEventListener('keydown', function (e) {
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        if (e.key.length !== 1) return;

        const mapped = urduKeyMap[e.key];
        if (!mapped) return;

        e.preventDefault();
        const start = this.selectionStart ?? this.value.length;
        const end = this.selectionEnd ?? this.value.length;
        this.value = this.value.slice(0, start) + mapped + this.value.slice(end);
        const cursor = start + mapped.length;
        this.setSelectionRange(cursor, cursor);
    });
});
</script>
@endpush
@endsection
