@extends('layouts.app')

@section('title', 'My profile — '.config('app.name'))

@section('content')
<section class="container py-4" style="max-width: 720px;">
    <h1 class="h3 fw-bold mb-1">Citizen profile</h1>
    <p class="text-secondary mb-4">Required before submitting a licence application.</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('citizen.profile.update') }}" enctype="multipart/form-data" class="bg-white rounded-4 shadow-sm p-4">
        @csrf
        @method('PUT')
        
        <!-- Profile Picture Preview & Upload Row -->
        <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
            @if($profile?->photo_path)
                <img src="{{ Storage::url($profile->photo_path) }}" alt="Profile Photo" class="rounded-3 border" style="width: 72px; height: 72px; object-fit: cover;">
            @else
                <div class="rounded-3 border bg-light d-flex align-items-center justify-content-center text-secondary fs-2" style="width: 72px; height: 72px;">
                    <i class="bi bi-person-fill"></i>
                </div>
            @endif
            <div class="flex-grow-1">
                <label class="form-label mb-1 fw-bold">Profile Picture <span class="text-muted small fw-normal">(Shown on Licence Card)</span></label>
                <input type="file" name="photo" accept="image/jpeg,image/png,image/jpg,image/webp" class="form-control @error('photo') is-invalid @enderror">
                <div class="form-text small mb-0">Upload clear passport size or face photo (JPG, PNG, max 2MB).</div>
                @error('photo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Full name</label>
                <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" required
                       value="{{ old('full_name', $profile?->full_name ?? $user->name) }}">
                @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Father name</label>
                <input type="text" name="father_name" class="form-control @error('father_name') is-invalid @enderror" required
                       value="{{ old('father_name', $profile?->father_name) }}">
                @error('father_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">CNIC</label>
                <input type="text" name="cnic" class="form-control @error('cnic') is-invalid @enderror" required
                       placeholder="12345-1234567-1" value="{{ old('cnic', $profile?->cnic) }}">
                @error('cnic') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Mobile</label>
                <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror" required
                       value="{{ old('mobile', $user->mobile) }}">
                @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Date of birth</label>
                <input type="date" name="dob" class="form-control @error('dob') is-invalid @enderror" required
                       value="{{ old('dob', optional($profile?->dob)->toDateString()) }}">
                @error('dob') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                    <option value="">Select</option>
                    @foreach (['male','female','other'] as $g)
                        <option value="{{ $g }}" @selected(old('gender', $profile?->gender) === $g)>{{ ucfirst($g) }}</option>
                    @endforeach
                </select>
                @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Province</label>
                <input type="text" name="province" class="form-control @error('province') is-invalid @enderror" required
                       value="{{ old('province', $profile?->province ?? 'Khyber Pakhtunkhwa') }}">
                @error('province') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Residence district</label>
                <select name="residence_district_id" class="form-select @error('residence_district_id') is-invalid @enderror" required>
                    <option value="">Select district</option>
                    @foreach ($districts as $district)
                        <option value="{{ $district->id }}" @selected(old('residence_district_id', $profile?->residence_district_id) == $district->id)>{{ $district->name }}</option>
                    @endforeach
                </select>
                @error('residence_district_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Emergency contact</label>
                <input type="text" name="emergency_contact" class="form-control @error('emergency_contact') is-invalid @enderror" required
                       value="{{ old('emergency_contact', $profile?->emergency_contact) }}">
                @error('emergency_contact') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" required>{{ old('address', $profile?->address) }}</textarea>
                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <button class="btn btn-rflms mt-3" type="submit">Save profile</button>
    </form>

    <!-- Pattern Lock Security Settings Card -->
    <div class="bg-white rounded-4 shadow-sm p-4 mt-4">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
            <div>
                <h5 class="fw-bold mb-1"><i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Pattern Lock Security</h5>
                <p class="text-secondary small mb-0">Require drawing a 9-dot pattern lock after logging in for additional account security.</p>
            </div>
            <span class="badge {{ $user->hasPatternLock() ? 'bg-success' : 'bg-secondary' }}">
                {{ $user->hasPatternLock() ? 'Enabled' : 'Disabled' }}
            </span>
        </div>

        <form method="POST" action="{{ route('citizen.profile.pattern-lock') }}" id="settingPatternForm">
            @csrf
            
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="pattern_lock_enabled" value="1" id="enablePatternSwitch" @checked($user->pattern_lock_enabled)>
                <label class="form-check-label fw-semibold" for="enablePatternSwitch">
                    Enable Pattern Lock for Login
                </label>
            </div>

            <div id="patternDrawerArea" class="{{ $user->pattern_lock_enabled ? '' : 'd-none' }} bg-light rounded-3 p-3 text-center mb-3">
                <p class="small text-secondary mb-2">Draw your new pattern sequence below (min 4 dots):</p>
                <div class="d-flex flex-column align-items-center">
                    <svg id="settingPatternSvg" viewBox="0 0 300 300" style="width: 220px; height: 220px; touch-action: none; cursor: pointer; user-select: none;">
                        <polyline id="settingPatternPath" points="" fill="none" stroke="#0d6efd" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
                        <line id="settingActiveLine" x1="0" y1="0" x2="0" y2="0" stroke="#0d6efd" stroke-width="4" stroke-dasharray="4,4" opacity="0"/>
                        <!-- Row 1 -->
                        <circle class="setting-node" data-id="1" cx="50" cy="50" r="14" fill="#ffffff" stroke="#6c757d" stroke-width="3"/>
                        <circle class="setting-node" data-id="2" cx="150" cy="50" r="14" fill="#ffffff" stroke="#6c757d" stroke-width="3"/>
                        <circle class="setting-node" data-id="3" cx="250" cy="50" r="14" fill="#ffffff" stroke="#6c757d" stroke-width="3"/>
                        <!-- Row 2 -->
                        <circle class="setting-node" data-id="4" cx="50" cy="150" r="14" fill="#ffffff" stroke="#6c757d" stroke-width="3"/>
                        <circle class="setting-node" data-id="5" cx="150" cy="150" r="14" fill="#ffffff" stroke="#6c757d" stroke-width="3"/>
                        <circle class="setting-node" data-id="6" cx="250" cy="150" r="14" fill="#ffffff" stroke="#6c757d" stroke-width="3"/>
                        <!-- Row 3 -->
                        <circle class="setting-node" data-id="7" cx="50" cy="250" r="14" fill="#ffffff" stroke="#6c757d" stroke-width="3"/>
                        <circle class="setting-node" data-id="8" cx="150" cy="250" r="14" fill="#ffffff" stroke="#6c757d" stroke-width="3"/>
                        <circle class="setting-node" data-id="9" cx="250" cy="250" r="14" fill="#ffffff" stroke="#6c757d" stroke-width="3"/>
                    </svg>

                    <input type="hidden" name="pattern" id="settingPatternInput">
                    <div class="mt-2 text-muted small" id="settingPatternHint">
                        {{ $user->hasPatternLock() ? 'Pattern already set. Draw a new pattern to update.' : 'Draw pattern by dragging across dots' }}
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-decoration-none text-secondary mt-1" id="settingResetBtn">Redraw pattern</button>
                </div>
            </div>

            <button class="btn btn-outline-primary" type="submit">Save Pattern Lock Settings</button>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const switchEl = document.getElementById('enablePatternSwitch');
    const drawerArea = document.getElementById('patternDrawerArea');
    const svg = document.getElementById('settingPatternSvg');
    const path = document.getElementById('settingPatternPath');
    const activeLine = document.getElementById('settingActiveLine');
    const nodes = Array.from(document.querySelectorAll('.setting-node'));
    const input = document.getElementById('settingPatternInput');
    const resetBtn = document.getElementById('settingResetBtn');
    const hint = document.getElementById('settingPatternHint');

    switchEl.addEventListener('change', function() {
        if (this.checked) {
            drawerArea.classList.remove('d-none');
        } else {
            drawerArea.classList.add('d-none');
            input.value = '';
        }
    });

    let isDrawing = false;
    let selectedNodes = [];
    const nodeCoords = {
        '1': {x: 50, y: 50},   '2': {x: 150, y: 50},  '3': {x: 250, y: 50},
        '4': {x: 50, y: 150},  '5': {x: 150, y: 150}, '6': {x: 250, y: 150},
        '7': {x: 50, y: 250},  '8': {x: 150, y: 250}, '9': {x: 250, y: 250}
    };

    function getSvgCoords(e) {
        const rect = svg.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        return {
            x: (clientX - rect.left) * (300 / rect.width),
            y: (clientY - rect.top) * (300 / rect.height)
        };
    }

    function getNodeAt(x, y) {
        return nodes.find(node => {
            const id = node.getAttribute('data-id');
            const coord = nodeCoords[id];
            const dx = x - coord.x;
            const dy = y - coord.y;
            return Math.sqrt(dx * dx + dy * dy) <= 30;
        });
    }

    function updateDrawing(currentPos) {
        const pointsStr = selectedNodes.map(id => `${nodeCoords[id].x},${nodeCoords[id].y}`).join(' ');
        path.setAttribute('points', pointsStr);

        if (currentPos && selectedNodes.length > 0) {
            const lastId = selectedNodes[selectedNodes.length - 1];
            const lastCoord = nodeCoords[lastId];
            activeLine.setAttribute('x1', lastCoord.x);
            activeLine.setAttribute('y1', lastCoord.y);
            activeLine.setAttribute('x2', currentPos.x);
            activeLine.setAttribute('y2', currentPos.y);
            activeLine.setAttribute('opacity', '1');
        } else {
            activeLine.setAttribute('opacity', '0');
        }
    }

    function addNode(node) {
        const id = node.getAttribute('data-id');
        if (!selectedNodes.includes(id)) {
            selectedNodes.push(id);
            node.setAttribute('fill', '#0d6efd');
            node.setAttribute('stroke', '#0a58ca');
            node.setAttribute('r', '18');
            input.value = selectedNodes.join('');
            if (selectedNodes.length >= 4) {
                hint.textContent = `Pattern sequence recorded (${selectedNodes.length} nodes)`;
            } else {
                hint.textContent = `Connect at least 4 nodes (${selectedNodes.length}/4)`;
            }
        }
    }

    function resetPattern() {
        selectedNodes = [];
        input.value = '';
        hint.textContent = 'Draw pattern by dragging across dots';
        path.setAttribute('points', '');
        activeLine.setAttribute('opacity', '0');
        nodes.forEach(node => {
            node.setAttribute('fill', '#ffffff');
            node.setAttribute('stroke', '#6c757d');
            node.setAttribute('r', '14');
        });
    }

    function startDraw(e) {
        e.preventDefault();
        isDrawing = true;
        resetPattern();
        const pos = getSvgCoords(e);
        const node = getNodeAt(pos.x, pos.y);
        if (node) addNode(node);
    }

    function moveDraw(e) {
        if (!isDrawing) return;
        e.preventDefault();
        const pos = getSvgCoords(e);
        const node = getNodeAt(pos.x, pos.y);
        if (node) addNode(node);
        updateDrawing(pos);
    }

    function endDraw(e) {
        if (!isDrawing) return;
        isDrawing = false;
        updateDrawing(null);
    }

    svg.addEventListener('mousedown', startDraw);
    svg.addEventListener('mousemove', moveDraw);
    window.addEventListener('mouseup', endDraw);

    svg.addEventListener('touchstart', startDraw, {passive: false});
    svg.addEventListener('touchmove', moveDraw, {passive: false});
    window.addEventListener('touchend', endDraw);

    resetBtn.addEventListener('click', resetPattern);
});
</script>
@endsection
