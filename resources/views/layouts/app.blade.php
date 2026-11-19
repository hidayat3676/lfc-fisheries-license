<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'KP Fisheries E-License'))</title>
    <meta name="description" content="@yield('meta_description', 'Apply for reservoir fishing licences online — Directorate General of Fisheries, Khyber Pakhtunkhwa.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-2.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logo-2.png') }}">
    @include('partials.icons-cdn')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media (min-width: 992px) {
            html { font-size: 14.5px; }
        }
        @media (min-width: 1200px) {
            .container, .container-lg, .container-md, .container-sm, .container-xl { max-width: 1140px; }
        }
        @media (min-width: 1400px) {
            .container, .container-lg, .container-md, .container-sm, .container-xl, .container-xxl { max-width: 1220px; }
        }
    </style>
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">
    @include('partials.navbar')
    <main class="flex-grow-1 page-content">
        @yield('content')
    </main>
    @include('partials.footer')
    @stack('scripts')
</body>
</html>
