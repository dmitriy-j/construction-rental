<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo/fap-logo.svg') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @stack('styles')
    <style>
        :root {
            --navbar-height: 80px;
            --sidebar-width: 280px;
            --footer-height: auto;
            --primary-color: #0b5ed7;
            --primary-dark: #0a58ca;
            --primary-light: #cfe2ff;
            --bg-surface: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #1a1d21;
            --text-secondary: #6c757d;
            --divider: #dee2e6;
        }
        @media (max-width: 768px) { :root { --navbar-height: 70px; } }
        @media (max-width: 576px) { :root { --navbar-height: 60px; } }
        body { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important; background-attachment: fixed; }
        html { overflow-y: scroll; }
        body { overflow-y: auto; overflow-x: hidden; min-height: 100vh; }
        #app { min-height: 100vh; }
        .site-footer { margin-top: auto; }
        .table td, .table th { vertical-align: middle; }
        .badge { font-size: 0.85em; padding: 0.35em 0.65em; }

        /* Тёмная тема */
        [data-theme="dark"] body {
            background: linear-gradient(135deg, #1a1c23 0%, #232630 50%, #1a1c23 100%) !important;
            color: #f8f9fa !important;
        }
        [data-theme="dark"] .card, [data-theme="dark"] .content-section {
            background: rgba(30,32,40,0.9) !important; color: #f8f9fa !important;
            border-color: rgba(255,255,255,0.1) !important;
        }
        [data-theme="dark"] .table, [data-theme="dark"] .table-light, [data-theme="dark"] .table-striped > tbody > tr:nth-of-type(odd) > * { color: #f8f9fa !important; }
        [data-theme="dark"] .table-light { background: rgba(30,32,40,0.8) !important; }
        [data-theme="dark"] .text-dark { color: #f8f9fa !important; }
        [data-theme="dark"] .navbar { background: linear-gradient(135deg, rgba(25,55,109,0.9) 0%, rgba(15,45,99,0.9) 100%) !important; }
        [data-theme="dark"] .dropdown-menu { background: #2b3035 !important; color: #f8f9fa !important; }
        [data-theme="dark"] .dropdown-item { color: #f8f9fa !important; }
        [data-theme="dark"] .dropdown-item:hover { background: rgba(61,139,253,0.2) !important; color: #fff !important; }
        [data-theme="dark"] a:not(.btn):not(.dropdown-item) { color: #82b1ff !important; }

        /* z-index */
        .modal { z-index: 10050 !important; }
        .modal-backdrop { z-index: 10040 !important; }

        /* Bootstrap Offcanvas Sidebar — десктоп: всегда виден */
        .sidebar-offcanvas {
            width: var(--sidebar-width) !important;
            border-right: 1px solid var(--divider) !important;
            background: var(--bg-surface) !important;
            z-index: 990 !important;
            overflow: visible !important; /* Важно для dropdown */
        }
        .sidebar-offcanvas .offcanvas-body {
            overflow: visible !important;
        }
        @media (min-width: 992px) {
            .sidebar-offcanvas {
                position: fixed !important;
                top: var(--navbar-height) !important;
                height: calc(100vh - var(--navbar-height)) !important;
                transform: none !important;
                visibility: visible !important;
            }
            body.sidebar-layout .content-area {
                margin-left: var(--sidebar-width) !important;
            }
            .offcanvas-backdrop { display: none !important; }
        }
        @media (max-width: 991.98px) {
            .sidebar-offcanvas { width: 85vw !important; max-width: 320px !important; }
            body.sidebar-layout .content-area { margin-left: 0 !important; }
        }

        /* Стили сайдбара */
        .sidebar-offcanvas .nav-link {
            color: var(--text-primary) !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
            transition: all 0.2s ease !important;
            position: relative !important;
        }
        .sidebar-offcanvas .nav-link:hover {
            background: rgba(11,94,215,0.1) !important;
            color: var(--primary-color) !important;
        }
        .sidebar-offcanvas .nav-link.active {
            background: rgba(11,94,215,0.15) !important;
            color: var(--primary-color) !important;
            font-weight: 600 !important;
        }
        .sidebar-offcanvas .nav-icon {
            width: 24px; height: 24px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.1rem; color: var(--text-secondary);
            flex-shrink: 0;
        }
        .sidebar-offcanvas .nav-link.active .nav-icon { color: var(--primary-color) !important; }
        .sidebar-offcanvas .section-header {
            background: rgba(11,94,215,0.05) !important;
            border-left: 4px solid var(--primary-color) !important;
        }

        /* Dropdown внутри Offcanvas — раскрывается вниз с отступом */
        .sidebar-offcanvas .dropdown-menu {
            background: var(--bg-surface) !important;
            border: 1px solid var(--divider) !important;
            position: static !important;
            float: none !important;
            margin-top: 2px !important;
            margin-left: 2.5rem !important;
            z-index: 1050 !important;
            width: calc(100% - 2.5rem) !important;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08) !important;
        }
        .sidebar-offcanvas .dropdown-menu .dropdown-item {
            padding: 0.5rem 1rem !important;
            font-size: 0.9rem !important;
        }
        .sidebar-offcanvas .nav-item {
            position: relative !important;
        }
        .sidebar-offcanvas [data-theme="dark"] .dropdown-menu { background: #2b3035 !important; }
        .sidebar-offcanvas .app-version { font-size: 0.7rem; }
    </style>
</head>
<body class="@auth sidebar-open @endauth @yield('body-class')">
    <div id="app" class="d-flex flex-column">
        @include('components.navbar')
        <div class="d-flex flex-grow-1">
            @auth @include('partials.sidebar') @endauth
            <div class="content-area flex-grow-1 d-flex flex-column">
                <main class="flex-grow-1">
                    <div class="content-container py-4 px-3 px-lg-4">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>
        <footer class="site-footer">@include('components.footer')</footer>
    </div>
    <div id="cart-icon"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    @stack('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Автоматическое оборачивание таблиц
    document.querySelectorAll('.content-container table:not(.table-responsive-custom table)').forEach(function(table) {
        if (!table.closest('.table-responsive')) {
            var w = document.createElement('div'); w.className = 'table-responsive';
            table.parentNode.insertBefore(w, table); w.appendChild(table);
        }
    });
    // Переключатель темы
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const html = document.documentElement;
            const theme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
        });
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) document.documentElement.setAttribute('data-theme', savedTheme);
    }
});
</script>
</body>
</html>
