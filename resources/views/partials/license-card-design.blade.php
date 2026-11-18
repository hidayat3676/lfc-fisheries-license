@php
    $holderName = $license->user?->citizenProfile?->full_name ?: ($license->user?->name ?: '—');
    $fatherName = $license->user?->citizenProfile?->father_name ?: '—';
    $cnic = $license->user?->citizenProfile?->cnic ?: '—';
    $photoUrl = $license->user?->citizenProfile?->photoUrl();
    $waterBodyName = $license->reservoir?->name ?: 'Designated Water Body';
    $districtName = $license->reservoir?->district?->name ?: 'KP';
    $categoryName = $license->category?->name ?: 'Angling Licence';
    $issueDateStr = $license->issue_date?->format('d M Y') ?: now()->format('d M Y');
    $startDateStr = ($license->fishing_start_date ?? $license->application?->fishing_start_date ?? $license->issue_date)?->format('d M Y') ?: $issueDateStr;
    $endDateStr = ($license->expiry_date ?? $license->application?->fishing_end_date)?->format('d M Y') ?: '—';
    $isValid = $license->isValidNow();
    $verifyUrl = route('license.verify', $license->qr_token);
@endphp

<style>
    .kpf-permit-sheet {
        max-width: 680px;
        margin: 0 auto;
        font-family: 'Source Sans 3', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: #0f172a;
    }

    .kpf-card {
        position: relative;
        background: #ffffff;
        border-radius: 22px;
        border: 1.5px solid #dbe3ec;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .kpf-card-watermark {
        position: absolute;
        top: 56%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 250px;
        height: 250px;
        object-fit: contain;
        opacity: 0.055;
        pointer-events: none;
        z-index: 0;
    }

    .kpf-card-header {
        background: linear-gradient(135deg, #075936 0%, #0b6e45 100%);
        color: #ffffff;
        padding: 14px 22px;
        border-bottom: 3.5px solid #d4a017;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        z-index: 1;
    }

    .kpf-emblem-circle {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: #ffffff;
        border: 2px solid #d4a017;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        padding: 3px;
    }

    .kpf-emblem-circle img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .kpf-header-title {
        font-size: 1.2rem;
        font-weight: 800;
        letter-spacing: 0.03em;
        line-height: 1.15;
        color: #ffffff;
        text-transform: uppercase;
    }

    .kpf-header-subtitle {
        font-size: 0.78rem;
        font-weight: 500;
        letter-spacing: 0.04em;
        color: rgba(255, 255, 255, 0.9);
        text-transform: uppercase;
    }

    .kpf-valid-pill {
        background: #16a34a;
        color: #ffffff;
        font-weight: 800;
        font-size: 0.82rem;
        letter-spacing: 0.06em;
        padding: 6px 18px;
        border-radius: 999px;
        text-transform: uppercase;
    }

    .kpf-expired-pill {
        background: #dc2626;
        color: #ffffff;
        font-weight: 800;
        font-size: 0.82rem;
        letter-spacing: 0.06em;
        padding: 6px 18px;
        border-radius: 999px;
        text-transform: uppercase;
    }

    .kpf-card-body {
        padding: 20px 22px 18px 22px;
        position: relative;
        z-index: 1;
    }

    .kpf-photo-frame {
        width: 118px;
        height: 146px;
        border-radius: 14px;
        border: 2px solid #cbd5e1;
        background: #f1f5f9;
        overflow: hidden;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
    }

    .kpf-photo-frame img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .kpf-photo-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #64748b;
        background: linear-gradient(180deg, #f8fafc, #e2e8f0);
    }

    .kpf-label {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 2px;
    }

    .kpf-ref-badge {
        background: #0f172a;
        color: #fbbf24;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 700;
        font-size: 0.96rem;
        letter-spacing: 0.06em;
        padding: 4px 12px;
        border-radius: 8px;
        display: inline-block;
    }

    .kpf-issued-top {
        font-size: 0.78rem;
        font-weight: 700;
        color: #0b6e45;
    }

    .kpf-holder-name {
        font-size: 1.18rem;
        font-weight: 800;
        color: #0b6e45;
        text-transform: uppercase;
        line-height: 1.2;
    }

    .kpf-value {
        font-size: 0.94rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
    }

    .kpf-subvalue {
        font-size: 0.78rem;
        font-weight: 600;
        color: #475569;
    }

    .kpf-qr-box {
        width: 112px;
        height: 112px;
        border-radius: 12px;
        border: 1.5px solid #cbd5e1;
        background: #ffffff;
        padding: 8px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
    }

    .kpf-qr-box img.qr-img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .kpf-qr-logo {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #ffffff;
        padding: 2px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.2);
    }

    .kpf-scan-caption {
        font-size: 0.66rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        color: #64748b;
        text-transform: uppercase;
        text-align: center;
        margin-top: 6px;
    }

    .kpf-dates-bar {
        margin-top: 14px;
        padding-top: 12px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
    }

    .kpf-cut-divider {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 18px 24px;
        color: #64748b;
    }

    .kpf-cut-line {
        flex-grow: 1;
        border-top: 2px dashed #cbd5e1;
    }

    .kpf-back-strip {
        height: 34px;
        background: linear-gradient(135deg, #075936 0%, #0b6e45 100%);
        border-bottom: 3.5px solid #d4a017;
    }

    .kpf-act-badge {
        background: #0f172a;
        color: #fbbf24;
        font-weight: 800;
        font-size: 0.72rem;
        letter-spacing: 0.06em;
        padding: 5px 12px;
        border-radius: 8px;
        text-transform: uppercase;
    }

    .kpf-terms-list {
        list-style: none;
        padding-left: 0;
        margin: 12px 0 18px 0;
    }

    .kpf-terms-list li {
        position: relative;
        padding-left: 18px;
        margin-bottom: 8px;
        font-size: 0.86rem;
        font-weight: 500;
        color: #334155;
        line-height: 1.4;
    }

    .kpf-terms-list li::before {
        content: '';
        position: absolute;
        left: 0;
        top: 7px;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #0b6e45;
    }

    .kpf-signed-pill {
        background: #075936;
        color: #ffffff;
        font-weight: 800;
        font-size: 0.72rem;
        letter-spacing: 0.06em;
        padding: 6px 14px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-transform: uppercase;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        *, *::before, *::after {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .no-print {
            display: none !important;
        }
        .kpf-permit-sheet {
            max-width: 650px !important;
            margin: 0 auto !important;
        }
        .kpf-card {
            box-shadow: none !important;
            border: 1.5px solid #94a3b8 !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
    }
</style>

<div class="kpf-permit-sheet">
    <!-- ================= FRONT CARD ================= -->
    <div class="kpf-card">
        <img src="{{ asset('images/logo-2.png') }}" alt="" class="kpf-card-watermark">

        <div class="kpf-card-header">
            <div class="d-flex align-items-center gap-3">
                <div class="kpf-emblem-circle">
                    <img src="{{ asset('images/logo-2.png') }}" alt="KP Fisheries">
                </div>
                <div>
                    <div class="kpf-header-title">KHYBER PAKHTUNKHWA</div>
                    <div class="kpf-header-subtitle">DIRECTORATE GENERAL OF FISHERIES</div>
                </div>
            </div>
            <div>
                <span class="{{ $isValid ? 'kpf-valid-pill' : 'kpf-expired-pill' }}">
                    {{ $isValid ? 'VALID' : 'EXPIRED' }}
                </span>
            </div>
        </div>

        <div class="kpf-card-body">
            <div class="d-flex flex-wrap flex-sm-nowrap gap-3 align-items-start">
                <!-- Left: Photo -->
                <div class="kpf-photo-frame">
                    @if ($photoUrl)
                        <img src="{{ $photoUrl }}" alt="{{ $holderName }}">
                    @else
                        <div class="kpf-photo-placeholder">
                            <i class="bi bi-person-fill fs-1"></i>
                            <span style="font-size: 0.6rem; font-weight: 700;">ANGLER PHOTO</span>
                        </div>
                    @endif
                </div>

                <!-- Center: Permit & Holder Details -->
                <div class="flex-grow-1" style="min-width: 220px;">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-1 mb-2">
                        <div>
                            <div class="kpf-label">LICENSE REFERENCE NO.</div>
                            <div class="kpf-ref-badge">{{ $license->license_no }}</div>
                        </div>
                        <div class="kpf-issued-top">
                            Issued: {{ $issueDateStr }}
                        </div>
                    </div>

                    <div class="mb-2">
                        <div class="kpf-label">LICENSEE NAME</div>
                        <div class="kpf-holder-name">{{ $holderName }}</div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <div class="kpf-label">FATHER / GUARDIAN</div>
                            <div class="kpf-value">{{ $fatherName }}</div>
                        </div>
                        <div class="col-6">
                            <div class="kpf-label">CNIC NO.</div>
                            <div class="kpf-value font-monospace">{{ $cnic }}</div>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <div class="kpf-label">DISTRICT</div>
                            <div class="kpf-value">{{ $districtName }}</div>
                        </div>
                        <div class="col-6">
                            <div class="kpf-label">CATEGORY</div>
                            <div class="kpf-value">{{ $categoryName }}</div>
                        </div>
                    </div>
                </div>

                <!-- Right: QR Code -->
                <div class="text-center flex-shrink-0">
                    <div class="kpf-qr-box">
                        <img
                            class="qr-img"
                            src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data={{ urlencode($verifyUrl) }}"
                            alt="QR Code"
                        >
                        <img src="{{ asset('images/logo-2.png') }}" alt="" class="kpf-qr-logo">
                    </div>
                    <div class="kpf-scan-caption">SCAN TO VERIFY</div>
                </div>
            </div>

            <!-- Dates Footer -->
            <div class="kpf-dates-bar">
                <div>
                    <span class="kpf-label me-1">FISHING START:</span>
                    <span class="kpf-value">{{ $startDateStr }}</span>
                </div>
                <div>
                    <span class="kpf-label me-1">FISHING END:</span>
                    <span class="kpf-value" style="color: #0b6e45;">{{ $endDateStr }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= SCISSORS CUT DIVIDER ================= -->
    <div class="kpf-cut-divider">
        <i class="bi bi-scissors fs-5"></i>
        <div class="kpf-cut-line"></div>
    </div>

    <!-- ================= BACK CARD ================= -->
    <div class="kpf-card">
        <img src="{{ asset('images/logo-2.png') }}" alt="" class="kpf-card-watermark">

        <div class="kpf-back-strip"></div>

        <div class="kpf-card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <h3 class="h5 fw-bold mb-0 text-dark">Permit Terms &amp; Angling Regulations</h3>
                <span class="kpf-act-badge">KP FISHERIES ACT</span>
            </div>

            <ul class="kpf-terms-list">
                <li>This digital permit is strictly non-transferable and valid only for the named licensee and designated district.</li>
                <li>Fishing allowed with Rod &amp; Line only. Netting, dynamite, poisons, and electric currents are severe illegal offences.</li>
                <li>Licensee must produce this card or PDF on demand to Fisheries Wardens &amp; Officers.</li>
                <li>Strictly adhere to species bag limits and minimum size regulations for District {{ $districtName }}.</li>
            </ul>

            <div class="pt-3 border-top d-flex justify-content-between align-items-end flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="kpf-emblem-circle" style="width: 40px; height: 40px;">
                        <img src="{{ asset('images/logo-2.png') }}" alt="KP Crest">
                    </div>
                    <div>
                        <div class="fw-bold small text-uppercase" style="color: #0b6e45; letter-spacing: 0.03em;">
                            DIRECTORATE GENERAL OF FISHERIES
                        </div>
                        <div class="text-secondary" style="font-size: 0.76rem;">Government of Khyber Pakhtunkhwa, Peshawar</div>
                        <div class="text-secondary" style="font-size: 0.76rem;">Helpline: (091) 921-0000 | fisheries.kp.gov.pk</div>
                    </div>
                </div>

                <div class="text-end">
                    <div class="fw-bold mb-1" style="font-size: 0.74rem; color: #0b6e45; letter-spacing: 0.04em;">
                        ISSUED: {{ $issueDateStr }}
                    </div>
                    <div class="text-secondary border-top border-secondary pt-1 mb-2" style="font-size: 0.68rem; letter-spacing: 0.06em;">
                        ISSUING AUTHORITY SEAL
                    </div>
                    <div class="kpf-signed-pill">
                        <i class="bi bi-patch-check-fill text-warning"></i> DIGITALLY SIGNED
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

