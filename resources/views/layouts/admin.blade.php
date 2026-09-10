<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin — '.config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-2.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logo-2.png') }}">
    @include('partials.icons-cdn')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-light min-vh-100">
    <nav class="admin-topbar navbar navbar-dark">
        <div class="container-fluid px-3 px-lg-4">
            <a class="brand-text d-inline-flex align-items-center gap-2" href="{{ route('admin.dashboard') }}">
                <img src="{{ asset('images/logo-2.png') }}" alt="KP Fisheries Logo" height="30" class="me-1">
                <span>RFLMS Admin</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <span class="user-badge d-none d-md-inline">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/></svg>{{ auth()->user()->name }}
                </span>
                <a class="btn btn-sm btn-outline-light" href="{{ route('home') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4.5a.5.5 0 0 0 .5-.5v-4h2v4a.5.5 0 0 0 .5.5H14a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM2.5 14V7.707l5.5-5.5 5.5 5.5V14H11v-4a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0-.5.5v4z"/></svg>Public site
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-sm btn-warning text-dark" type="submit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/><path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/></svg>Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="row g-4">
            <aside class="col-lg-3 col-xl-2">
                <div class="admin-sidebar">
                    <div class="sidebar-heading">Navigation</div>
                    <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h2A1.5 1.5 0 0 1 5 1.5v2A1.5 1.5 0 0 1 3.5 5h-2A1.5 1.5 0 0 1 0 3.5zM1.5 1a.5.5 0 0 0-.5.5v2a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5v-2a.5.5 0 0 0-.5-.5zM0 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm1 3v2a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2zm14-1V8a1 1 0 0 0-1-1H2a1 1 0 0 0-1 1v2zM2 8.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m0 4a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5"/></svg>
                        Dashboard
                    </a>
                    @if (auth()->user()->hasModuleAction('violations', 'view'))
                        <a href="{{ route('admin.violations.index') }}" class="sidebar-link {{ request()->routeIs('admin.violations.*') ? 'active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/></svg>
                            Violations
                        </a>
                    @endif
                    @if (auth()->user()->hasModuleAction('applications', 'view'))
                        <a href="{{ route('admin.applications.index') }}" class="sidebar-link {{ request()->routeIs('admin.applications.*') ? 'active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M5.523 12.424q.21-.124.459-.238a7.878 7.878 0 0 1-.45.606C5.06 13.3 4.546 13.89 4 14.153V14.5a.5.5 0 0 1-1 0v-.347c-.546-.263-1.06-.853-1.532-1.361a7.878 7.878 0 0 1-.45-.606c.248.114.496.21.459.238a.5.5 0 0 1 .255.932.5.5 0 0 1-.255-.068 7.878 7.878 0 0 1-.378-.23 8.57 8.57 0 0 1-.866-.674C.504 11.748 0 10.948 0 10V3a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v7c0 .948-.504 1.748-1.233 2.384a8.57 8.57 0 0 1-.866.674"/><path d="M14 3H2v7c0 .524.382 1.128.968 1.636A7.57 7.57 0 0 0 3.5 12.12V10.5a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v1.62a7.57 7.57 0 0 0 .532-.484C13.618 11.128 14 10.524 14 10zm-4.5 8H6.5v-1h3z"/></svg>
                            Applications
                        </a>
                    @endif
                    @if (auth()->user()->hasModuleAction('reports', 'view'))
                        <a href="{{ route('admin.reports.index') }}" class="sidebar-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M1 11a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1zm5-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1zm5-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1z"/></svg>
                            Reports
                        </a>
                    @endif
                    @if (auth()->user()->hasModuleAction('settings', 'view'))
                        <a href="{{ route('admin.setup.index') }}" class="sidebar-link {{ request()->routeIs('admin.setup.*') ? 'active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.658.06a1.873 1.873 0 0 1 3.724 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16a1.873 1.873 0 0 1 2.693 2.693l-.16.292a1.873 1.873 0 0 0 1.116 2.692l.318.094a1.873 1.873 0 0 1 0 3.724l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291a1.873 1.873 0 0 1-2.693 2.693l-.292-.16a1.873 1.873 0 0 0-2.692 1.116l-.094.318a1.873 1.873 0 0 1-3.724 0l-.094-.319a1.873 1.873 0 0 0-2.693-1.115l-.291.16a1.873 1.873 0 0 1-2.693-2.693l.16-.292a1.873 1.873 0 0 0-1.116-2.692l-.318-.094a1.873 1.873 0 0 1 0-3.724l.319-.094a1.873 1.873 0 0 0 1.115-2.693l-.16-.291A1.873 1.873 0 0 1 4.4 1.96l.292.16a1.873 1.873 0 0 0 2.692-1.116z"/></svg>
                            Policy setup
                        </a>
                    @endif
                    @if (auth()->user()->hasModuleAction('license_categories', 'view'))
                        <a href="{{ route('admin.categories.index') }}" class="sidebar-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/></svg>
                            Licence categories
                        </a>
                    @endif
                    @if (auth()->user()->hasModuleAction('offices', 'view'))
                        <a href="{{ route('admin.offices.index') }}" class="sidebar-link {{ request()->routeIs('admin.offices.*') ? 'active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M14.763.075A.5.5 0 0 1 15 .5v15a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V14h-1v1.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V10a.5.5 0 0 1 .342-.474L6 7.64V4.5a.5.5 0 0 1 .276-.447l8-4a.5.5 0 0 1 .487.022M6 8.694 1 10.36V15h5zM7 15h2v-1.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5V15h2V1.309l-7 3.5z"/><path d="M2 11h1v1H2zm2 0h1v1H4zm-2 2h1v1H2zm2 0h1v1H4zm4-4h1v1H8zm2 0h1v1h-1zm-2 2h1v1H8zm2 0h1v1h-1zm2-2h1v1h-1zm0 2h1v1h-1zM8 7h1v1H8zm2 0h1v1h-1zm2 0h1v1h-1zM8 5h1v1H8zm2 0h1v1h-1zm2 0h1v1h-1zm0-2h1v1h-1z"/></svg>
                            Offices
                        </a>
                    @endif
                    @if (auth()->user()->hasModuleAction('reservoirs', 'view'))
                        <a href="{{ route('admin.reservoirs.index') }}" class="sidebar-link {{ request()->routeIs('admin.reservoirs.*') ? 'active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M.036 3.314a.5.5 0 0 1 .65-.278l1.757.703a1.5 1.5 0 0 0 1.114 0l1.014-.406a2.5 2.5 0 0 1 1.857 0l1.015.406a1.5 1.5 0 0 0 1.114 0l1.014-.406a2.5 2.5 0 0 1 1.857 0l1.015.406a1.5 1.5 0 0 0 1.114 0l1.757-.703a.5.5 0 1 1 .372.928l-1.758.703a2.5 2.5 0 0 1-1.857 0l-1.014-.406a1.5 1.5 0 0 0-1.114 0l-1.015.406a2.5 2.5 0 0 1-1.857 0l-1.014-.406a1.5 1.5 0 0 0-1.114 0L2.443 4.74a2.5 2.5 0 0 1-1.857 0L.328 4.04a.5.5 0 0 1-.292-.726m0 3a.5.5 0 0 1 .65-.278l1.757.703a1.5 1.5 0 0 0 1.114 0l1.014-.406a2.5 2.5 0 0 1 1.857 0l1.015.406a1.5 1.5 0 0 0 1.114 0l1.014-.406a2.5 2.5 0 0 1 1.857 0l1.015.406a1.5 1.5 0 0 0 1.114 0l1.757-.703a.5.5 0 1 1 .372.928l-1.758.703a2.5 2.5 0 0 1-1.857 0l-1.014-.406a1.5 1.5 0 0 0-1.114 0l-1.015.406a2.5 2.5 0 0 1-1.857 0l-1.014-.406a1.5 1.5 0 0 0-1.114 0L2.443 7.74a2.5 2.5 0 0 1-1.857 0L.328 7.04a.5.5 0 0 1-.292-.726m0 3a.5.5 0 0 1 .65-.278l1.757.703a1.5 1.5 0 0 0 1.114 0l1.014-.406a2.5 2.5 0 0 1 1.857 0l1.015.406a1.5 1.5 0 0 0 1.114 0l1.014-.406a2.5 2.5 0 0 1 1.857 0l1.015.406a1.5 1.5 0 0 0 1.114 0l1.757-.703a.5.5 0 1 1 .372.928l-1.758.703a2.5 2.5 0 0 1-1.857 0l-1.014-.406a1.5 1.5 0 0 0-1.114 0l-1.015.406a2.5 2.5 0 0 1-1.857 0l-1.014-.406a1.5 1.5 0 0 0-1.114 0l-1.015.406a2.5 2.5 0 0 1-1.857 0L.328 10.04a.5.5 0 0 1-.292-.726"/></svg>
                            Water bodies
                        </a>
                    @endif
                    @if (auth()->user()->hasModuleAction('groups', 'view') || auth()->user()->hasModuleAction('users', 'view'))
                        <a href="{{ route('admin.groups.index') }}" class="sidebar-link {{ request()->routeIs('admin.groups.*') ? 'active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-.169.035-.334.098-.487A6 6 0 0 1 0 13s1 0 1-1 1-4 4.5-4c.486 0 .937.07 1.353.197.108.31.282.593.513.824C6.541 10.82 6 11.83 6 13c0 .351.05.69.143 1.008zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/></svg>
                            Groups &amp; Roles
                        </a>
                    @endif
                    @if (auth()->user()->hasModuleAction('users', 'view'))
                        <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-.25.057-.51.157-.764.338-.857.927-1.642 1.78-1.956M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/></svg>
                            User management
                        </a>
                    @endif
                </div>
            </aside>
            <section class="col-lg-9 col-xl-10">
                <div class="page-content">
                    @if (session('status'))
                        <div class="alert alert-success d-flex align-items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                            {{ session('status') }}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <div class="fw-semibold mb-1 d-flex align-items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4m.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/></svg>
                                Please fix the following:
                            </div>
                            <ul class="mb-0 small">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @yield('content')
                </div>
            </section>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
