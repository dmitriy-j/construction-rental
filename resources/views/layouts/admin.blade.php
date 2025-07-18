<!DOCTYPE html>
<html>
<head>
    <title>@yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @include('partials.sidebar')
    
    <div class="main-content">
        @yield('breadcrumbs')
        @yield('content')
    </div>
</body>
</html>