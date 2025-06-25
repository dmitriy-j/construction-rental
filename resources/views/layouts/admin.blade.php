<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            height: 100vh;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
            position: fixed;
            width: 250px;
            overflow-y: auto;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .nav-link {
            border-radius: 0;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            background-color: #e9ecef;
        }
        .sidebar-header {
            padding: 20px 15px;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Боковое меню -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h5 class="mb-0">Админ-панель</h5>
                <small class="text-muted">{{ Auth::guard('admin')->user()->position }}</small>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-speedometer2 me-2"></i> Дашборд
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-cash-coin me-2"></i> Бухгалтерия
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-people me-2"></i> Арендаторы
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-building me-2"></i> Арендодатели
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-tools me-2"></i> Каталог техники
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-clipboard me-2"></i> Заявки
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-truck me-2"></i> Транспортировка
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-bar-chart me-2"></i> Статистика
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-person-badge me-2"></i> Сотрудники
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-file-earmark-richtext me-2"></i> Редактор страниц
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-envelope me-2"></i> Рассылки
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-newspaper me-2"></i> Новости
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-file-text me-2"></i> Отчёты
                    </a>
                </li>
                <li class="nav-item mt-auto">
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="nav-link w-100 text-start bg-light border-top">
                            <i class="bi bi-box-arrow-right me-2"></i> Выйти
                        </button>
                    </form>
                </li>
            </ul>
        </div>

        <!-- Основной контент -->
        <div class="main-content w-100">
            <!-- Хлебные крошки -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-light p-2">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Главная</a></li>
                    @yield('breadcrumbs')
                </ol>
            </nav>

            <!-- Заголовок страницы -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>@yield('title')</h1>
                <div>
                    <span class="text-muted me-3">{{ Auth::guard('admin')->user()->full_name }}</span>
                    <i class="bi bi-person-circle"></i>
                </div>
            </div>

            <!-- Контент страницы -->
            <div class="card">
                <div class="card-body">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Подсветка активного пункта меню
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            document.querySelectorAll('.nav-link').forEach(link => {
                if (link.getAttribute('href') === currentPath) {
                    link.classList.add('active');
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>
