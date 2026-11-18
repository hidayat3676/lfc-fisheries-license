<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $license->license_no }} — Printable E-Licence PDF</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-2.png') }}">
    @include('partials.icons-cdn')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background: #f1f5f9;
            min-height: 100vh;
            padding: 24px 16px 48px 16px;
        }
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="container no-print mb-4" style="max-width: 680px;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 bg-white p-3 rounded-4 shadow-sm border">
            <a href="{{ route('admin.applications.show', $application) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to Application
            </a>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('license.verify', $license->qr_token) }}" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                    <i class="bi bi-qr-code-scan me-1"></i> Verify Online
                </a>
                <button type="button" onclick="window.print()" class="btn btn-rflms btn-sm rounded-pill px-4 fw-bold">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> Print / Save PDF
                </button>
            </div>
        </div>
    </div>

    @include('partials.license-card-design', ['license' => $license])

    @if (request()->boolean('print'))
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () {
                    window.print();
                }, 350);
            });
        </script>
    @endif
</body>
</html>

