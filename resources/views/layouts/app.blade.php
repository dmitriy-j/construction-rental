<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js', 'resources/css/sidebar.css'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .navbar-nav .nav-link {
            transition: color 0.3s ease;
        }
        .navbar-nav .nav-link:hover {
            color: #ff8c00 !important;
        }
        .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div id="app">
        @include('components.navbar')
        
        <div class="container-fluid">
            <div class="row">
                <!-- Сайдбар -->
                <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                    @include('partials.sidebar')
                </div>
                
                <!-- Основной контент -->
                <div class="col-md-9 col-lg-10 ml-sm-auto px-4 py-4">
                    @yield('content')
                </div>
            </div>
        </div>
        
        @include('components.footer')
    </div>
</body>
</html>