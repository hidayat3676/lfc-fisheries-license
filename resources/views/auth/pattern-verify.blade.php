@extends('layouts.app')

@section('title', 'Pattern Lock Verification — '.config('app.name'))

@section('content')
<section class="container py-5" style="max-width: 480px;">
    <div class="bg-white rounded-4 shadow-sm p-4 text-center">
        <div class="mb-3">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 64px; height: 64px;">
                <i class="bi bi-grid-3x3-gap-fill fs-2"></i>
            </div>
        </div>
        
        <h2 class="h4 fw-bold mb-1">Pattern Lock Security</h2>
        <p class="text-secondary small mb-4">Draw your security pattern to complete login for <strong>{{ $user->name }}</strong>.</p>

        @if ($errors->has('pattern'))
            <div class="alert alert-danger py-2 small mb-3">{{ $errors->first('pattern') }}</div>
        @endif

        <form method="POST" action="{{ route('pattern.verify.submit') }}" id="patternForm">
            @csrf
            
            <div class="pattern-lock-container d-flex flex-column align-items-center mb-4">
                <svg id="patternSvg" viewBox="0 0 300 300" style="width: 260px; height: 260px; touch-action: none; cursor: pointer; user-select: none;">
                    <!-- Connecting lines -->
                    <polyline id="patternPath" points="" fill="none" stroke="#0d6efd" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
                    <line id="activeLine" x1="0" y1="0" x2="0" y2="0" stroke="#0d6efd" stroke-width="4" stroke-dasharray="4,4" opacity="0"/>

                    <!-- 3x3 Dots -->
                    <!-- Row 1 -->
                    <circle class="pattern-node" data-id="1" cx="50" cy="50" r="14" fill="#f8f9fa" stroke="#6c757d" stroke-width="3"/>
                    <circle class="pattern-node" data-id="2" cx="150" cy="50" r="14" fill="#f8f9fa" stroke="#6c757d" stroke-width="3"/>
                    <circle class="pattern-node" data-id="3" cx="250" cy="50" r="14" fill="#f8f9fa" stroke="#6c757d" stroke-width="3"/>
                    
                    <!-- Row 2 -->
                    <circle class="pattern-node" data-id="4" cx="50" cy="150" r="14" fill="#f8f9fa" stroke="#6c757d" stroke-width="3"/>
                    <circle class="pattern-node" data-id="5" cx="150" cy="150" r="14" fill="#f8f9fa" stroke="#6c757d" stroke-width="3"/>
                    <circle class="pattern-node" data-id="6" cx="250" cy="150" r="14" fill="#f8f9fa" stroke="#6c757d" stroke-width="3"/>

                    <!-- Row 3 -->
                    <circle class="pattern-node" data-id="7" cx="50" cy="250" r="14" fill="#f8f9fa" stroke="#6c757d" stroke-width="3"/>
                    <circle class="pattern-node" data-id="8" cx="150" cy="250" r="14" fill="#f8f9fa" stroke="#6c757d" stroke-width="3"/>
                    <circle class="pattern-node" data-id="9" cx="250" cy="250" r="14" fill="#f8f9fa" stroke="#6c757d" stroke-width="3"/>
                </svg>

                <input type="hidden" name="pattern" id="patternInput" required>
                <div class="mt-2 text-muted small" id="patternHint">Draw pattern by dragging across dots</div>
                <button type="button" class="btn btn-sm btn-link text-decoration-none text-secondary mt-1" id="resetPatternBtn">Clear & redraw</button>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-rflms py-2" id="submitBtn" disabled>Verify & Unlock</button>
                <a href="{{ route('login') }}" class="btn btn-light text-secondary">Cancel</a>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const svg = document.getElementById('patternSvg');
    const path = document.getElementById('patternPath');
    const activeLine = document.getElementById('activeLine');
    const nodes = Array.from(document.querySelectorAll('.pattern-node'));
    const input = document.getElementById('patternInput');
    const submitBtn = document.getElementById('submitBtn');
    const resetBtn = document.getElementById('resetPatternBtn');
    const hint = document.getElementById('patternHint');

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
                submitBtn.disabled = false;
                hint.textContent = `Pattern recorded (${selectedNodes.length} nodes)`;
            } else {
                hint.textContent = `Connect at least 4 nodes (${selectedNodes.length}/4)`;
            }
        }
    }

    function resetPattern() {
        selectedNodes = [];
        input.value = '';
        submitBtn.disabled = true;
        hint.textContent = 'Draw pattern by dragging across dots';
        path.setAttribute('points', '');
        activeLine.setAttribute('opacity', '0');
        nodes.forEach(node => {
            node.setAttribute('fill', '#f8f9fa');
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
