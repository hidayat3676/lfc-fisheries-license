<nav class="navbar navbar-expand-lg navbar-dark bg-rflms sticky-top">
    <div class="container">
        <a class="navbar-brand navbar-brand-mark d-flex align-items-center gap-2" href="{{ url('/') }}">
            <img src="{{ asset('images/logo-2.png') }}" alt="KP Fisheries Logo" height="36" class="brand-logo me-1">
            <span>KP Fisheries E-License</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
            aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('catalogue.index') }}">
                        <i class="bi bi-water me-1"></i>Water bodies
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/') }}#how-it-works">
                        <i class="bi bi-signpost-2 me-1"></i>How it works
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('violations.create') }}">
                        <i class="bi bi-flag me-1"></i>Report violation
                    </a>
                </li>
                <li class="d-none d-lg-block"><span class="gold-sep"></span></li>
                @auth
                    @if (auth()->user()->isAdminStaff())
                        <li class="nav-item">
                            <a class="btn btn-sm btn-warning text-dark ms-lg-1 btn-chip" href="{{ route('admin.dashboard') }}">
                                <i class="bi bi-speedometer2"></i> Admin
                            </a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="btn btn-sm btn-warning text-dark ms-lg-1 btn-chip" href="{{ route('citizen.dashboard') }}">
                                <i class="bi bi-person-circle"></i> My account
                            </a>
                        </li>
                    @endif
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}" class="ms-lg-1">
                            @csrf
                            <button class="btn btn-sm btn-outline-light btn-chip" type="submit"><i class="bi bi-box-arrow-right"></i> Logout</button>
                        </form>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="btn btn-sm btn-outline-light ms-lg-1 btn-chip" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right"></i> Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-warning text-dark ms-lg-1 btn-chip" href="{{ route('register') }}"><i class="bi bi-person-plus"></i> Register</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link small ms-lg-1 opacity-75" href="{{ route('staff.login') }}"><i class="bi bi-briefcase me-1"></i>Staff portal</a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
