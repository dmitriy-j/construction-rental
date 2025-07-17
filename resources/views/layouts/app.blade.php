<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
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

    <!-- Подключаем Vite только для CSS -->
    @vite(['resources/sass/app.scss', 'resources/css/sidebar.css'])

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

        /* Стили для центрирования модальных окон */
        .modal {
            display: flex !important;
            align-items: center;
            justify-content: center;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-dialog {
            margin: 0 auto;
            max-width: 500px;
            width: 90%;
        }
        .modal-content {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        /* Фикс для выпадающего меню */
        .dropdown-toggle::after {
            display: none;
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

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Подключаем JS отдельно через Vite -->
    @vite(['resources/js/app.js'])

    <!-- Стек для скриптов конкретных страниц -->
    @stack('scripts')

    <script>
        // Инициализация после загрузки DOM
        document.addEventListener('DOMContentLoaded', function() {
            // Инициализация компонентов Bootstrap
            if (typeof bootstrap !== 'undefined') {
                // Тултипы
                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(tooltipEl => {
                    new bootstrap.Tooltip(tooltipEl);
                });

                // Выпадающие меню
                document.querySelectorAll('.dropdown-toggle').forEach(dropdownEl => {
                    new bootstrap.Dropdown(dropdownEl);
                });

                // Модальные окна
                document.querySelectorAll('.modal').forEach(modalEl => {
                    new bootstrap.Modal(modalEl);
                });
            }
        });
    </script>
</body>
</html>
