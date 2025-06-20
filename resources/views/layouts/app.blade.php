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