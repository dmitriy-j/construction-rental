<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='15' fill='%230b5ed7'/><text x='50' y='70' font-family='Raleway' font-weight='700' font-size='50' text-anchor='middle' fill='white'>ФАП</text></svg>">
    <link rel="icon" type="image/png" sizes="16x16" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAACXBIWXMAAA7EAAAOxAGVKw4bAAABPklEQVQ4y6WTsUoDQRCGPxMkFQoWFiJYKJjKQhBsrS0s7Sz9B76Jj2BjZWdnYWNhY2UhCIKQShBUsLARBEVQ0CJcYXHX3J7cJYlPst3O/PvPzM7sCv9ZAnwD74W3gXngDngB3oBz4AqYAD6AeaBdAq0a4BQY1oBd4BQ4qQHXwGkNuAP2gA3gCJgD1oB74AC4BJrVJ6rAGrAPdIFJYAHoA+vALrAI9IBmFZgEtoF1YAMYAXrAErAJdIFVYFgF2sAOsAQsA6/AKtABloEOMAMMqkAPmAFmw8gK0AeWgFZ4TgODKvAItIF2eN4As8B8eE4B0zVgADwAc8B9eM4C9+F9CgxrwCPwDLwAL8Ab8Ay8hvdL4KkGPAHfwFfh/QN8F97f1Y8a8A58Fv5f4bPw/qx+1IBn4KPwfg8fhfdH9aMGPAMfhfc78F54vwGfNeAVeCm8X4G3wvsF+KoBb8Bb4f0MvBbez8B3DfgAPgvvT+Cz8P4H/gBQeC0ZQqjT9AAAAABJRU5ErkJggg==">
    <link rel="icon" type="image/png" sizes="32x32" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAACXBIWXMAAA7EAAAOxAGVKw4bAAABdElEQVRYw+2Wv0oDQRCGPxMkFQoWFiJYKJjKQhBsrS0s7Sz9B76Jj2BjZWdnYWNhY2UhCIKQShBUsLARBEVQ0CJcYXHX3J7cJYlPst3O/PvPzM7sCv9ZAnwD74W3gXngDngB3oBz4AqYAD6AeaBdAq0a4BQY1oBd4BQ4qQHXwGkNuAP2gA3gCJgD1oB74AC4BJrVJ6rAGrAPdIFJYAHoA+vALrAI9IBmFZgEtoF1YAMYAXrAErAJdIFVYFgF2sAOsAQsA6/AKtABloEOMAMMqkAPmAFmw8gK0AeWgFZ4TgODKvAItIF2eN4As8B8eE4B0zVgADwAc8B9eM4C9+F9CgxrwCPwDLwAL8Ab8Ay8hvdL4KkGPAHfwFfh/QN8F97f1Y8a8A58Fv5f4bPw/qx+1IBn4KPwfg8fhfdH9aMGPAMfhfc78F54vwGfNeAVeCm8X4G3wvsF+KoBb8Bb4f0MvBbez8B3DfgAPgvvT+Cz8P4H/gBQeC0ZQqjT9AAAAABJRU5ErkJggg==">
    <link rel="icon" type="image/png" sizes="64x64" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAACXBIWXMAAA7EAAAOxAGVKw4bAAACFUlEQVR4nO2Yv0tbURTHPydBqg4FJ0fBwcG/QRwEQRB0cRAcBEEQdHQQHBwEB0EQBEEQBEEQBEEQBEEQBEEQBEEQBEEQBMFBcBAcBEEQBMFyHc41j5d7k7wXfMn9wIG8e+8593d/3XvPPQH+YwXwA7wX3gbmgTvgBfgCzoErYAL4AOaBdgm0aoBTYFgDdoFT4KQGXAOnNeAO2AM2gCNgDlgD7oED4BJoVp+oAmvAPtAFJYAFoA+sA7vAItADmlVgEtgG1oENYAToAUvAJtAFVoFhFWgDO8ASsAy8AqtAB1gGOsAMMKgCPWAGmA0jK0AfWAJa4TkNDKrAI9AG2uF5A8wC8+E5BUzXgAHwAMwB9+E5C9yH9ykwrAGPwDPwArwAb8Az8BreL4GnGvAEfANfhfcP8F14f1c/asA78Fn4f4XPwvuz+lEDnoGPwvs9fBTeH9WPGvAMfBTeb8B74f0GfNaAV+Cl8H4F3grvF+CrBrwBb4X3M/BaeD8D3zXgA/gsvD+Bz8L7H/gDQOG1ZAihTt8AAAAASUVORK5CYII=">
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

    <!-- Bootstrap JS Bundle (Popper + Bootstrap) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <!-- Подключаем Vite -->
    @vite([
        'resources/sass/app.scss',
        'resources/sass/sidebar.scss',
        'resources/sass/navbar.scss',
        'resources/sass/footer.scss',
        'resources/js/app.js',
        'resources/js/theme.js',
        'resources/js/ripple.js',
        'resources/js/sidebar.js'
    ])

    <style>
        :root {
            --navbar-height: 80px;
            --sidebar-width: 280px;
            --sidebar-mini-width: 80px; // Важно для иконок
            --footer-height: 250px; // Добавьте при необходимости
        }

         :root {
    scroll-behavior: smooth;
    scroll-padding-top: var(--navbar-height);
  }

  @media (prefers-reduced-motion: reduce) {
    * {
      animation-duration: 0.01ms !important;
      transition-duration: 0.01ms !important;
    }
  }

  :root {
    --bg-gradient-light: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    --bg-gradient-dark: linear-gradient(135deg, #1a1c23 0%, #232630 50%, #1a1c23 100%);
}

body {
    background: var(--bg-gradient-light) !important;
    background-size: cover;
    background-attachment: fixed;
}

[data-theme="dark"] body {
    background: var(--bg-gradient-dark) !important;
}
</style>


     <script>
        // Глобальный доступ к bootstrap
        window.bootstrap = bootstrap;
    </script>

</head>
<body class="@yield('body-class', 'd-flex flex-column min-vh-100')"
      style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
  <div id="app" class="d-flex flex-column flex-grow-1">
    <!-- Навбар -->
    @include('components.navbar')

    <div class="main-container d-flex flex-grow-1">
      <!-- Сайдбар -->
      @auth
      <aside class="sidebar-container flex-shrink-0" id="sidebarContainer">
        @include('partials.sidebar')
      </aside>
      @endauth

      <!-- Основной контент -->
      <div class="content-area flex-grow-1 d-flex flex-column">
        <main class="main-content flex-grow-1">
          <div class="content-container py-4 px-3 px-lg-4">
            @yield('content')
          </div>
        </main>
      </div>
    </div>

    <!-- Футер сайта -->
    <footer class="site-footer mt-auto">
      @include('components.footer')
    </footer>
  </div>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <!-- Bootstrap JS -->
    <!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>-->
    @stack('scripts')
    <script>
  // Только функция для отладки
  window.debug = function(element) {
    console.log(element);
    element.style.border = '2px solid red';
  };
</script>
</body>
</html>
