<x-auth-layout title="Доступ к строительной площадке">
    <div class="min-h-screen flex items-center justify-center bg-gray-50 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1503387762-592deb58ef4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1951&q=80');">
        <div class="max-w-md w-full bg-white bg-opacity-95 rounded-xl shadow-2xl overflow-hidden border-2 border-orange-300">
            <!-- Шапка с тематикой -->
            <div class="bg-orange-500 py-6 px-4 text-center">
                <div class="flex justify-center mb-3">
                    <div class="bg-white rounded-full p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
                <h1 class="text-2xl font-bold text-white">ConstructionRental</h1>
                <p class="text-orange-100 mt-1">Платформа для аренды строительной техники</p>
            </div>

            <div class="py-8 px-6">
                <!-- Session Status -->
                @if(session('status'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('status') }}
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email -->
                    <div class="mb-5">
                        <label for="email" class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Рабочий Email
                        </label>
                        <input id="email" type="email"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('email') border-red-500 @enderror"
                            name="email" value="{{ old('email') }}"
                            required autofocus placeholder="ваш@email.com">
                        @error('email')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-5">
                        <label for="password" class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Пароль
                        </label>
                        <input id="password" type="password"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('password') border-red-500 @enderror"
                            name="password" required placeholder="••••••••">
                        @error('password')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="mb-5 flex items-center">
                        <input type="checkbox" class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded" id="remember" name="remember">
                        <label class="ml-2 block text-sm text-gray-700" for="remember">
                            Запомнить на этом устройстве
                        </label>
                    </div>

                    <div class="flex items-center justify-between">
                        <a href="{{ route('password.request') }}" class="text-sm text-orange-600 hover:text-orange-800 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Забыли пароль?
                        </a>

                        <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            Войти на площадку
                        </button>
                    </div>
                </form>

                <div class="mt-8 pt-5 border-t border-gray-200">
                    <p class="text-sm text-gray-600 text-center">
                        Еще нет аккаунта?
                        <a href="{{ route('register') }}" class="font-medium text-orange-600 hover:text-orange-500 ml-1">
                            Зарегистрировать компанию
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-auth-layout>
