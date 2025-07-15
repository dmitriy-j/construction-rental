<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
</head>
<body>
    <div class="admin-container">
        @include('admin.partials.sidebar')
        <main class="admin-content">
            @yield('content')
        </main>
    </div>
</body>
</html>