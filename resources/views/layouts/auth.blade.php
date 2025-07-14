<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RentTech - {{ $title ?? 'Авторизация' }}</title>

    <!-- Bootstrap CSS -->
    @vite(['resources/sass/app.scss'])

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Montserrat Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">

    <style>
        .auth-bg {
            background: linear-gradient(135deg, #3a7bd5 0%, #00d2ff 100%);
            min-height: 100vh;
        }
        .auth-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .auth-logo {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 2.5rem;
            color: #3a7bd5;
        }
        .auth-logo span {
            color: #00d2ff;
        }
        .btn-auth {
            background: linear-gradient(135deg, #3a7bd5 0%, #00d2ff 100%);
            border: none;
            padding: 10px 25px;
        }
    </style>
</head>
<body class="auth-bg">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="auth-card card p-4">
                    <div class="text-center mb-5">
                        <div class="auth-logo mb-3">Rent<span>Tech</span></div>
                        <h2 class="h4">{{ $title ?? 'Добро пожаловать' }}</h2>
                    </div>

                    @yield('content')

                    <div class="text-center mt-4">
                        @if(Route::has('register'))
                        <p class="text-muted">Ещё нет аккаунта? <a href="{{ route('register') }}" class="text-primary">Зарегистрируйтесь</a></p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
