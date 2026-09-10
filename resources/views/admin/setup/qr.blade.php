@extends('layouts.admin')

@section('title', 'Setup — QR privacy — '.config('app.name'))

@section('content')
@include('admin.setup._steps', ['steps' => $steps, 'current' => 'qr'])

@php
    $previewMode = old('qr_privacy', $qrPrivacy);
    $previewShowCnic = old('show_cnic_on_public_qr', $showCnic);
@endphp

<form method="POST" action="{{ route('admin.setup.qr.save') }}"
      x-data="{
          mode: '{{ $previewMode }}',
          showCnic: {{ $previewShowCnic ? 'true' : 'false' }},
          maskName(name, mode) {
              if (mode === 'full') return name;
              return name.split(/\s+/).map(p => {
                  if (p.length <= 1) return p;
                  if (p.length === 2) return p[0] + '*';
                  return p[0] + '*'.repeat(p.length - 2) + p[p.length - 1];
              }).join(' ');
          },
          maskCnic(cnic, mode) {
              if (mode === 'full') return cnic;
              const d = cnic.replace(/\D+/g, '');
              const vis = d.slice(-4);
              const m = '*'.repeat(Math.max(0, d.length - 4)) + vis;
              if (m.length === 13) return m.slice(0,5)+'-'+m.slice(5,12)+'-'+m.slice(12);
              return m;
          }
      }">
    @csrf

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="bg-white rounded-4 shadow-sm p-4 h-100">
                <h2 class="h6 fw-bold mb-3">Public QR verify page</h2>
                <div class="mb-3">
                    <label class="form-label">Holder display</label>
                    <select name="qr_privacy" class="form-select" x-model="mode">
                        <option value="masked">Masked (recommended)</option>
                        <option value="full">Full name / CNIC</option>
                    </select>
                    <div class="form-text">Anyone scanning the QR sees this. Officers still see full details after login.</div>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="show_cnic"
                           name="show_cnic_on_public_qr" value="1" x-model="showCnic"
                           @checked($showCnic)>
                    <label class="form-check-label" for="show_cnic">Show CNIC on public verify page</label>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="bg-white rounded-4 shadow-sm p-4 h-100 text-center">
                <div class="small text-secondary mb-2">Live preview</div>
                <div class="border rounded-4 p-4">
                    <span class="badge text-bg-success mb-2">Valid</span>
                    <div class="fw-bold">RFL-2026-00001</div>
                    <div class="small text-secondary mb-3">Daily Licence</div>
                    <dl class="row text-start small mb-0">
                        <dt class="col-5">Holder</dt>
                        <dd class="col-7" x-text="maskName('{{ $sampleName }}', mode)"></dd>
                        <template x-if="showCnic">
                            <div class="contents">
                                <dt class="col-5">CNIC</dt>
                                <dd class="col-7" x-text="maskCnic('{{ $sampleCnic }}', mode)"></dd>
                            </div>
                        </template>
                        <dt class="col-5">Water body</dt><dd class="col-7">Kundal Dam</dd>
                    </dl>
                </div>
                <p class="small text-secondary mt-3 mb-0">
                    Server sample:
                    {{ \App\Support\PrivacyMask::name($sampleName, $qrPrivacy) }}
                    @if ($showCnic)
                        · {{ \App\Support\PrivacyMask::cnic($sampleCnic, $qrPrivacy) }}
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-4 shadow-sm p-4 mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="confirm" value="1" id="confirm" required>
            <label class="form-check-label" for="confirm">
                I confirm this public QR privacy mode for the pilot.
            </label>
        </div>
        @if ($confirmed)
            <p class="small text-success mb-0 mt-2">Previously confirmed — saving again updates the live setting.</p>
        @endif
    </div>

    <button type="submit" class="btn btn-rflms">Save &amp; finish setup</button>
</form>
@endsection
