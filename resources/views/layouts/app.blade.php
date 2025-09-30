<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='15' fill='%230b5ed7'/><text x='50' y='70' font-family='Raleway' font-weight='700' font-size='50' text-anchor='middle' fill='white'>ФАП</text></svg>">
    <link rel="icon" type="image/png" sizes="16x16" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAACXBIWXMAAA7EAAAOxAGVKw4bAAABPklEQVQ4y6WTsUoDQRCGPxMkFQoWFiJYKJjKQhBsrS0s7Sz9B76Jj2BjZWdnYWNhY2UhCIKQShBUsLARBEVQ0CJcYXHX3J7cJYlPst3O/PvPzM7sCv9ZAnwD74W3gXngDngB3oBz4AqYAD6AeaBdAq0a4BQY1oBd4BQ4kQHXwGkNuAP2gA3gCJgD1oB74AC4BJrVJ6rAGrAPdIFJYAFoA+vALrAI9IBmFZgEtoF1YAMYAXrAErAJdIFVYFgF2sAOsAQsA6/AKtABloEOMAMMqkAPmAFmw8gK0AeWgFZ4TgODKvAItIF2eN4As8B8eE4B0zVgADwAc8B9eE4C9+F9CgxrwCPwDLwAL8Ab8Ay8hvdL4KkGPAHfxFfh/QN8F97f1Y8a8A58Fv5f4bPw/qx+1IBn4KPwfg8fhfdH9aMGPAMfhfc78F54vwGfNeAVeCm8X4G3wvsF+KoBb8Bb4f0MvBbez8B3DfgAPgvvT+Cz8P4H/gBQeC0ZQqjT9AAAAABJRU5ErkJggg==">
    <link rel="icon" type="image/png" sizes="32x32" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAACXBIWXMAAA7EAAAOxAGVKw4bAAABdElEQVRYw+2Wv0oDQRCGPxMkFQoWFiJYKJjKQhBsrS0s7Sz9B76Jj2BjZWdnYWNhY2UhCIKQShBUsLARBEVQ0CJcYXHX3J7cJYlPst3O/PvPzM7sCv9ZAnwD74W3gXngDngB3oBz4AqYAD6AeaBdAq0a4BQY1oBd4BQ4kQHXwGkNuAP2gA3gCJgD1oB74AC4BJrVJ6rAGrAPdIFJYAFoA+vALrAI9IBmFZgEtoF1YAMYAXrAErAJdIFVYFgF2sAOsAQsA6/AKtABloEOMAMMqkAPmAFmw8gK0AeWgFZ4TgODKvAItIF2eN4As8B8eE4B0zVgADwAc8B9eM4C9+F9CgxrwCPwDLwAL8Ab8Ay8hvdL4KkGPAHfwFfh/QN8F97f1Y8a8A58Fv5f4bPw/qx+1IBn4KPwfg8fhfdH9aMGPAMfhfc78F54vwGfNeAVeCm8X4G3wvsF+KoBb8Bb4f0MvBbez8B3DfgAPgvvT+Cz8P4H/gBQeC0ZQqjT9AAAAABJRU5ErkJggg==">
    <link rel="icon" type="image/png" sizes="64x64" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAACXBIWXMAAA7EAAAOxAGVKw4bAAACFUlEQVR4nO2Yv0rbURTHPydBqg4FJ0fBwcG/QRwEQRB0cRAcBEEQdHQQHBwEB0EQBEEQBEEQBEEQBEEQBEEQBEEQBEEQBMFBcBAcBEEQBMFyHc41j5d7k3wXfMn9wIG8e+8593d/3XvPPQH+YwXwA7wX3gbmgTvgBfgCzoErYAL4AOaBdgm0aoBTYFgDdoFT4KQGXAOnNeAO2AM2gCNgDlgD7oED4BJoVp+oAmrAPtAFJYAFoA+vALrAI9IBmFZgEtoF1oAMYAXrAEvAJtAFVoFhFWgDO8ASsAy8AqtAB1gGOsAMMKgCPWAGmA0jK0AfWAJa4TkNDKrAI9AG2uF5A8wC8+E5BUzXgAHwAMwB9+E5CdyH9ykwrAGPwDPwArwAb8Az8BreL4GnGvAEfANfhfcP8F14f1c/asA78Fn4f4XPwvuz+lEDnoGPwvs9fBTeH9WPGvAMfBTe78F74f0GfNaAV+Cl8H4F3grvF+CrBrwBb4X3M/BaeD8D3zXgA/gsvD+Bz8L7H/gDQOG1ZAihTtMAAAAASUVORK5CYII=">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    @vite([
        'resources/sass/app.scss',
        'resources/js/app.js'
    ])

    <style>
        :root {
            --navbar-height: 80px;
            --sidebar-width: 280px;
            --sidebar-mini-width: 80px;
            --footer-height: 250px;
            --primary-color: #0b5ed7;
            --primary-dark: #0a58ca;
            --primary-light: #cfe2ff;
            --bg-surface: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #1a1d21;
            --text-secondary: #6c757d;
            --divider: #dee2e6;
        }

        [data-theme="dark"] {
            --primary-color: #3d8bfd;
            --primary-dark: #0d6efd;
            --primary-light: #1e3a5f;
            --bg-surface: #1a1d21;
            --bg-secondary: #2b3035;
            --text-primary: #f8f9fa;
            --text-secondary: #adb5bd;
            --divider: #495057;
        }

        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
            background-size: cover;
            background-attachment: fixed;
        }

        [data-theme="dark"] body {
            background: linear-gradient(135deg, #1a1c23 0%, #232630 50%, #1a1c23 100%) !important;
        }

        /* Content spacing when sidebar is present */
        .content-area {
            margin-left: var(--sidebar-width) !important;
            transition: margin-left 0.3s ease !important;
        }

        body.sidebar-mini .content-area {
            margin-left: var(--sidebar-mini-width) !important;
        }

        @media (max-width: 991.98px) {
            .content-area {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body class="@yield('body-class', 'd-flex flex-column min-vh-100') @if(session('sidebar_mini')) sidebar-mini @endif">
    <div id="app" class="d-flex flex-column flex-grow-1">
        <!-- Navbar -->
        @include('components.navbar')

        <div class="main-container d-flex flex-grow-1">
            <!-- Sidebar -->
            @auth
                @include('partials.sidebar')
            @endauth

            <!-- Main Content Area -->
            <div class="content-area flex-grow-1 d-flex flex-column">
                <main class="main-content flex-grow-1">
                    <div class="content-container py-4 px-3 px-lg-4">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>

        <!-- Site Footer -->
        <footer class="site-footer mt-auto">
            @include('components.footer')
        </footer>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Toastr Notifications -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    @stack('scripts')
</body>
</html>
