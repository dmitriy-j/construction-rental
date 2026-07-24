<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Laravel'))</title>
    <meta name="description" content="@yield('meta-description', config('app.name') . ' — Федеральная Арендная Платформа')">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">

    {{-- Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @stack('styles')

    <meta name="theme-color" content="#0B5ED7">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo/fap-logo.svg') }}">

    <style>
        :root { --navbar-height: 72px; --sidebar-width: 260px; }
        @media (max-width: 768px) { :root { --navbar-height: 65px; } }
        @media (max-width: 576px) { :root { --navbar-height: 60px; } }
        html { overflow-y: scroll; }
        body { overflow-y: auto; overflow-x: hidden; min-height: 100vh; }
        #app { min-height: 100vh; }
        .table td, .table th { vertical-align: middle; }
        #toast-container > div { border-radius: 8px !important; box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important; opacity: 1 !important; font-family: 'Inter', sans-serif !important; }

        /* Sidebar */
        .sidebar-offcanvas { width: var(--sidebar-width) !important; border-right: 1px solid var(--fap-border, #E9ECEF) !important; background: var(--fap-surface, #fff) !important; z-index: 1045 !important; }
        .sidebar-offcanvas .offcanvas-body { overflow: visible !important; }
        @media (min-width: 992px) {
            .sidebar-offcanvas { position: fixed !important; top: var(--navbar-height) !important; height: calc(100vh - var(--navbar-height)) !important; transform: none !important; visibility: visible !important; }
            body.sidebar-layout .content-area, body.sidebar-layout .content-footer { margin-left: var(--sidebar-width) !important; }
            .offcanvas-backdrop { display: none !important; }
        }
        @media (max-width: 991.98px) {
            .sidebar-offcanvas { width: 85vw !important; max-width: 320px !important; top: var(--navbar-height) !important; height: calc(100vh - var(--navbar-height)) !important; }
            body.sidebar-layout .content-area, body.sidebar-layout .content-footer { margin-left: 0 !important; }
            .offcanvas-backdrop { display: block !important; }
        }
        .sidebar-offcanvas .nav-link { color: var(--fap-text-primary, #1a1d21) !important; display: flex !important; align-items: center !important; gap: 0.75rem !important; transition: all 0.2s ease !important; border-radius: 8px !important; padding: 0.625rem 0.875rem !important; margin: 0.125rem 0.5rem; font-weight: 500; }
        .sidebar-offcanvas .nav-link:hover { background: rgba(11,94,215,0.08) !important; color: var(--fap-primary, #0b5ed7) !important; }
        .sidebar-offcanvas .nav-link.active { background: rgba(11,94,215,0.12) !important; color: var(--fap-primary, #0b5ed7) !important; font-weight: 600 !important; }
        .sidebar-offcanvas .nav-icon { width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; color: var(--fap-text-secondary, #6c757d); flex-shrink: 0; }
        .sidebar-offcanvas .nav-link.active .nav-icon { color: var(--fap-primary, #0b5ed7) !important; }
        .sidebar-offcanvas .dropdown-menu { background: var(--fap-surface, #fff) !important; border: 1px solid var(--fap-border, #E9ECEF) !important; position: static !important; float: none !important; margin-top: 2px !important; margin-left: 2.5rem !important; z-index: 1050 !important; width: calc(100% - 2.5rem) !important; }
        .sidebar-offcanvas .dropdown-menu .dropdown-item { padding: 0.5rem 1rem !important; font-size: 0.875rem !important; }
        .modal { z-index: 10050 !important; }
        .modal-backdrop { z-index: 10040 !important; }

        /* Full-width sections — compensate content-container padding */
        .content-container { padding-left: 0 !important; padding-right: 0 !important; }
        .hero-section, .page-hero, .stats-section, .contact-section { width: 100%; }

        /* Footer inside content area - light for auth users */
        .content-footer { margin-top: auto; width: 100%; transition: margin-left 0.3s ease; }
        .content-footer .site-footer { background: #fff; border-top: 1px solid #e9ecef; padding: 1.5rem 0; margin: 0; }
        .content-footer .site-footer .copyright { color: #6c757d; }
        .content-footer .site-footer a { color: #6c757d; }
        .content-footer .site-footer a:hover { color: #0b5ed7; }
    </style>
</head>
<body class="@auth sidebar-open @endauth @yield('body-class')">
    <div id="app" class="d-flex flex-column">
        @include('components.navbar')
        <div class="d-flex flex-grow-1">
            @auth @include('partials.sidebar') @endauth
            <div class="content-area flex-grow-1 d-flex flex-column">
                <main class="flex-grow-1">
                    <div class="content-container">
                        @yield('content')
                    </div>
                </main>
                {{-- Footer inside content area — follows sidebar offset --}}
                <div class="content-footer">
                    @include('components.footer')
                </div>
            </div>
        </div>
    </div>
    <div id="cart-icon"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    @stack('scripts')

    <script>
    // Force navbar visibility — neutralize any hiding scripts
    (function() {
        var s = document.createElement('style');
        s.textContent = '.navbar--hidden { transform: none !important; }';
        document.head.appendChild(s);
        // Periodically ensure navbar is visible
        setInterval(function() {
            var n = document.querySelector('.navbar');
            if (n && n.classList.contains('navbar--hidden')) { n.classList.remove('navbar--hidden'); }
        }, 200);
    })();
    </script>
</body>
</html>
