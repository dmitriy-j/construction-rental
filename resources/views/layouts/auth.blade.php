<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='15' fill='%230b5ed7'/><text x='50' y='70' font-family='Raleway' font-weight='700' font-size='50' text-anchor='middle' fill='white'>ФАП</text></svg>">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Вход в систему') }} | {{ config('app.name', 'Laravel') }}</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-500: #0b5ed7;
            --primary-600: #0a50b9;
            --accent-400: #00d2ff;
            --text-primary: #212529;
            --text-secondary: #495057;
            --bg-surface: #ffffff;
            --bg-secondary: #f8f9fa;
            --divider: #e9ecef;
        }

        [data-theme="dark"] {
            --primary-500: #3d8bfd;
            --primary-600: #0d6efd;
            --accent-400: #00e5ff;
            --text-primary: #f8f9fa;
            --text-secondary: #e9ecef;
            --bg-surface: #212529;
            --bg-secondary: #2b3035;
            --divider: #495057;
        }

        body.auth-layout {
            display: flex;
            min-height: 100vh;
            background: var(--bg-secondary);
            margin: 0;
            padding: 0;
            font-family: 'Nunito', sans-serif;
            color: var(--text-primary);
            transition: background-color 0.3s ease;
        }

        .auth-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        .auth-background {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .auth-background::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Cpath fill='rgba(255,255,255,0.1)' d='M41.9,-58.2C55.3,-51.5,67.3,-40.5,72.3,-26.6C77.3,-12.6,75.3,4.3,71.4,22.2C67.5,40.1,61.8,59.1,50.1,69.3C38.4,79.5,20.7,80.9,3.3,76.8C-14.2,72.7,-28.3,63.1,-41.1,52.3C-53.9,41.5,-65.3,29.4,-70.1,14.9C-74.9,0.4,-73.1,-16.6,-66.7,-31.7C-60.4,-46.8,-49.5,-60.1,-36.6,-67C-23.7,-73.8,-8.7,-74.2,5.5,-79.7C19.7,-85.1,39.3,-95.7,41.9,-58.2Z' transform='translate(100 100)'/%3E%3C/svg%3E");
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .auth-background-content {
            position: relative;
            z-index: 2;
            max-width: 600px;
            color: white;
            text-align: center;
        }

        .auth-background-logo {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1rem;
            font-family: 'Montserrat', sans-serif;
        }

        .auth-background-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .auth-background-text {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .auth-form-container {
            width: 100%;
            max-width: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: var(--bg-surface);
        }

        .auth-card {
            width: 100%;
            max-width: 400px;
        }

        .auth-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-logo-main {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-500), var(--accent-400));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-family: 'Montserrat', sans-serif;
        }

        .auth-logo-subtitle {
            font-size: 1rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }

        .auth-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
            color: var(--text-primary);
        }

        .auth-form-group {
            margin-bottom: 1.5rem;
        }

        .auth-input-group {
            position: relative;
            border: 1px solid var(--divider);
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .auth-input-group:focus-within {
            border-color: var(--primary-500);
            box-shadow: 0 0 0 3px rgba(var(--primary-500), 0.2);
        }

        .auth-input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-500);
            z-index: 2;
        }

        .auth-input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: none;
            background: transparent;
            color: var(--text-primary);
            font-size: 1rem;
            outline: none;
        }

        .auth-input::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }

        .auth-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(var(--primary-500), 0.3);
        }

        .auth-btn i {
            margin-right: 8px;
            font-size: 1.2rem;
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--divider);
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .auth-footer a {
            color: var(--primary-500);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .auth-footer a:hover {
            color: var(--primary-600);
            text-decoration: underline;
        }

        @media (max-width: 992px) {
            .auth-background {
                display: none;
            }

            .auth-form-container {
                max-width: 100%;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body class="auth-layout">
    <div class="auth-container">
        <!-- Левая часть с бэкграундом -->
        <div class="auth-background">
            <div class="auth-background-content">
                <div class="auth-background-logo">ФАП</div>
                <h2 class="auth-background-title">Федеральная Арендная Платформа</h2>
                <p class="auth-background-text">
                    Платформа №1 для аренды строительной техники в России.
                    Присоединяйтесь к тысячам профессионалов, использующих наши сервисы.
                </p>
            </div>
        </div>

        <!-- Правая часть с формой -->
        <div class="auth-form-container">
            <div class="auth-card">
                <div class="auth-logo">
                    <div class="auth-logo-main">ФАП</div>
                    <div class="auth-logo-subtitle">Федеральная Арендная Платформа</div>
                </div>

                <h1 class="auth-title">Вход в систему</h1>

                @yield('content')

                <div class="auth-footer">
                    &copy; {{ date('Y') }} ФАП. Все права защищены.<br>
                    <a href="#">Политика конфиденциальности</a> | <a href="#">Условия использования</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Theme Initialization -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Восстановление темы
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);

            // Переключение темы
            const themeSwitchers = document.querySelectorAll('[data-theme-toggle]');
            themeSwitchers.forEach(toggle => {
                toggle.addEventListener('click', () => {
                    const currentTheme = document.documentElement.getAttribute('data-theme');
                    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                    document.documentElement.setAttribute('data-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                });
            });
        });
    </script>
</body>
</html>
