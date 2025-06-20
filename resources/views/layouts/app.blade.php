<<<<<<< HEAD
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Construction Rental</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex flex-col min-h-screen bg-gray-50">
  <!-- Header -->
  <header class="bg-blue-800 text-white p-4 shadow-md">
    <div class="container mx-auto flex justify-between items-center">
      <div class="flex items-center space-x-10">
        <div>Логотип</div>
        <nav>
          <ul class="flex space-x-6">
            <li>О компании</li>
            <li>Заявки</li>
            <!-- ... остальные пункты меню -->
          </ul>
        </nav>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="flex flex-1">
    <!-- Sidebar -->
    <aside class="w-64 bg-white p-4 border-r">
      <div class="mb-6">
        <h3 class="font-bold mb-2">Вход для клиентов</h3>
        <form>
          <input type="email" placeholder="Email" class="w-full p-2 mb-2 border rounded">
          <input type="password" placeholder="Пароль" class="w-full p-2 mb-2 border rounded">
          <button class="bg-blue-600 text-white w-full py-2 rounded">Войти</button>
        </form>
        <a href="#" class="block mt-2 text-blue-600 text-sm">Регистрация юрлиц</a>
      </div>
      <div>
        <h3 class="font-bold mb-2">Новости</h3>
        <!-- Блок новостей -->
      </div>
    </aside>

    <!-- Main Area -->
    <main class="flex-1 p-6">
      <div class="max-w-3xl mx-auto mb-6">
        <input 
          type="text" 
          placeholder="Поиск техники..." 
          class="w-full p-3 border rounded-lg shadow-sm"
        >
      </div>
      @yield('content')
    </main>
  </div>

  <!-- Footer -->
  <footer class="bg-gray-800 text-white p-6">
    <div class="container mx-auto">
      <div class="grid grid-cols-3 gap-4">
        <div>
          <h4 class="font-bold mb-2">Контакты</h4>
          <p>Телефон: +7 (XXX) XXX-XX-XX</p>
        </div>
        <!-- ... -->
      </div>
      <div class="mt-4 text-center text-gray-400">
        © 2023 Construction Rental. Разработано [ваша команда]
      </div>
    </div>
  </footer>
</body>
</html>
=======
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>
</body>
</html>
>>>>>>> 522435ced67efae9f01033fffb11dc8d6477f2ef
