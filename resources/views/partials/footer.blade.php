<footer class="site-footer py-4 mt-auto">
    <div class="container">
        <div class="row g-4 align-items-start">
            <div class="col-md-5">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <img src="{{ asset('images/logo-2.png') }}" alt="KP Fisheries Logo" height="32" class="me-1">
                    <div class="fw-semibold text-white">Directorate General of Fisheries</div>
                </div>
                <div class="small opacity-75">Khyber Pakhtunkhwa, Pakistan</div>
                <div class="small opacity-50 mt-1">Reservoir Fishing E-Licensing &amp; Management System</div>
            </div>
            <div class="col-md-3">
                <div class="footer-heading">Quick links</div>
                <div class="d-flex flex-column gap-1">
                    <a href="{{ route('catalogue.index') }}" class="small">Water body catalogue</a>
                    <a href="{{ route('violations.create') }}" class="small">Report violation</a>
                    <a href="{{ url('/') }}#how-it-works" class="small">How it works</a>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="footer-heading">Services</div>
                <div class="d-flex flex-column gap-1 align-items-md-end">
                    <a href="#" class="small">Verify licence QR</a>
                    <a href="#" class="small">Support &amp; help</a>
                </div>
                <div class="small opacity-50 mt-3">&copy; {{ date('Y') }} DG Fisheries KP</div>
            </div>
        </div>
    </div>
</footer>
