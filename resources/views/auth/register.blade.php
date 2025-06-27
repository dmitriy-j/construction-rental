<x-auth-layout title="Регистрация компании">
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-4xl mx-auto px-4">
            <!-- Шапка с логотипом -->
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center bg-orange-100 rounded-full p-4 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Регистрация компании</h1>
                <p class="text-gray-600 mt-2">Заполните информацию о вашей организации</p>
            </div>

            <!-- Общие ошибки -->
            @if($errors->any())
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Ошибка при заполнении формы</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Session Status -->
            @if(session('status'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('register.store') }}">
                @csrf

                <!-- Блок типа компании -->
                <div class="bg-orange-50 rounded-xl p-6 mb-8 border border-orange-100">
                    <div class="flex items-center mb-4">
                        <div class="bg-orange-100 p-2 rounded-lg mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Тип компании</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Тип компании *</h3>
                            <div class="space-y-2">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300"
                                            type="radio" name="type" id="type_lessee"
                                            value="lessee" {{ old('type', 'lessee') == 'lessee' ? 'checked' : '' }}>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="type_lessee" class="font-medium text-gray-700">Арендатор (Tenant)</label>
                                        <p class="text-gray-500 mt-1">Арендуете технику для своих проектов</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300"
                                            type="radio" name="type" id="type_lessor"
                                            value="lessor" {{ old('type') == 'lessor' ? 'checked' : '' }}>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="type_lessor" class="font-medium text-gray-700">Арендодатель (Landlord)</label>
                                        <p class="text-gray-500 mt-1">Предоставляете технику в аренду</p>
                                    </div>
                                </div>
                            </div>
                            @error('type')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Система налогообложения *</h3>
                            <div class="space-y-2">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300"
                                            type="radio" name="tax_system" id="tax_vat"
                                            value="vat" {{ old('tax_system', 'vat') == 'vat' ? 'checked' : '' }}>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="tax_vat" class="font-medium text-gray-700">С НДС</label>
                                        <p class="text-gray-500 mt-1">Общая система налогообложения</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300"
                                            type="radio" name="tax_system" id="tax_no_vat"
                                            value="no_vat" {{ old('tax_system') == 'no_vat' ? 'checked' : '' }}>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="tax_no_vat" class="font-medium text-gray-700">Без НДС</label>
                                        <p class="text-gray-500 mt-1">Упрощенная система налогообложения</p>
                                    </div>
                                </div>
                            </div>
                            @error('tax_system')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Основная информация -->
                <div class="bg-white rounded-xl p-6 mb-8 border border-gray-200 shadow-sm">
                    <div class="flex items-center mb-6">
                        <div class="bg-gray-100 p-2 rounded-lg mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Реквизиты компании</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Company Name -->
                        <div class="col-span-2">
                            <label for="legal_name" class="block text-sm font-medium text-gray-700 mb-2">Название компании *</label>
                            <input id="legal_name" type="text"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('legal_name') border-red-500 @enderror"
                                name="legal_name" value="{{ old('legal_name') }}" required>
                            @error('legal_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- ИНН -->
                        <div>
                            <label for="inn" class="block text-sm font-medium text-gray-700 mb-2">ИНН *</label>
                            <input id="inn" type="text"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('inn') border-red-500 @enderror"
                                name="inn" value="{{ old('inn') }}"
                                required pattern="\d{10}" maxlength="10">
                            @error('inn')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- КПП -->
                        <div>
                            <label for="kpp" class="block text-sm font-medium text-gray-700 mb-2">КПП *</label>
                            <input id="kpp" type="text"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('kpp') border-red-500 @enderror"
                                name="kpp" value="{{ old('kpp') }}"
                                required pattern="\d{9}" maxlength="9">
                            @error('kpp')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- ОГРН -->
                        <div>
                            <label for="ogrn" class="block text-sm font-medium text-gray-700 mb-2">ОГРН *</label>
                            <input id="ogrn" type="text"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('ogrn') border-red-500 @enderror"
                                name="ogrn" value="{{ old('ogrn') }}"
                                required pattern="\d{13}" maxlength="13">
                            @error('ogrn')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- ОКПО -->
                        <div>
                            <label for="okpo" class="block text-sm font-medium text-gray-700 mb-2">ОКПО</label>
                            <input id="okpo" type="text"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('okpo') border-red-500 @enderror"
                                name="okpo" value="{{ old('okpo') }}"
                                pattern="\d{8,10}" maxlength="10">
                            @error('okpo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Адреса -->
                <div class="bg-white rounded-xl p-6 mb-8 border border-gray-200 shadow-sm">
                    <div class="flex items-center mb-6">
                        <div class="bg-gray-100 p-2 rounded-lg mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Адреса компании</h2>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <!-- Юридический адрес -->
                        <div>
                            <label for="legal_address" class="block text-sm font-medium text-gray-700 mb-2">Юридический адрес *</label>
                            <input id="legal_address" type="text"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('legal_address') border-red-500 @enderror"
                                name="legal_address" value="{{ old('legal_address') }}" required>
                            @error('legal_address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Фактический адрес -->
                        <div>
                            <label for="actual_address" class="block text-sm font-medium text-gray-700 mb-2">Фактический адрес</label>
                            <input id="actual_address" type="text"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('actual_address') border-red-500 @enderror"
                                name="actual_address" value="{{ old('actual_address') }}">
                            @error('actual_address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded"
                                id="same_as_legal" name="same_as_legal" value="1" {{ old('same_as_legal') ? 'checked' : '' }}>
                            <label class="ml-2 block text-sm text-gray-700" for="same_as_legal">
                                Совпадает с юридическим адресом
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Банковские реквизиты -->
                <div class="bg-white rounded-xl p-6 mb-8 border border-gray-200 shadow-sm">
                    <div class="flex items-center mb-6">
                        <div class="bg-gray-100 p-2 rounded-lg mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Банковские реквизиты</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Bank Name -->
                        <div class="col-span-2">
                            <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-2">Название банка *</label>
                            <input id="bank_name" type="text"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('bank_name') border-red-500 @enderror"
                                name="bank_name" value="{{ old('bank_name') }}" required>
                            @error('bank_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Расчетный счет -->
                        <div>
                            <label for="bank_account" class="block text-sm font-medium text-gray-700 mb-2">Расчетный счет *</label>
                            <input id="bank_account" type="text"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('bank_account') border-red-500 @enderror"
                                name="bank_account" value="{{ old('bank_account') }}"
                                required pattern="\d{20}" maxlength="20">
                            @error('bank_account')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- БИК -->
                        <div>
                            <label for="bik" class="block text-sm font-medium text-gray-700 mb-2">БИК *</label>
                            <input id="bik" type="text"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('bik') border-red-500 @enderror"
                                name="bik" value="{{ old('bik') }}"
                                required pattern="\d{9}" maxlength="9">
                            @error('bik')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Корреспондентский счет -->
                        <div class="col-span-2">
                            <label for="correspondent_account" class="block text-sm font-medium text-gray-700 mb-2">Корреспондентский счет</label>
                            <input id="correspondent_account" type="text"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('correspondent_account') border-red-500 @enderror"
                                name="correspondent_account" value="{{ old('correspondent_account') }}"
                                pattern="\d{20}" maxlength="20">
                            @error('correspondent_account')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Контактная информация -->
                <div class="bg-white rounded-xl p-6 mb-8 border border-gray-200 shadow-sm">
                    <div class="flex items-center mb-6">
                        <div class="bg-gray-100 p-2 rounded-lg mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Контактная информация</h2>
                    </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Ваше имя (для аккаунта) *</label>
                                <input id="name" type="text"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('name') border-red-500 @enderror"
                                    name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
        <input id="email" type="email"
            class="w-full px-4 py-3
</div>
                        <!-- Director -->
                        <div>
                            <label for="director_name" class="block text-sm font-medium text-gray-700 mb-2">ФИО директора *</label>
                            <input id="director_name" type="text"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('director_name') border-red-500 @enderror"
                                name="director_name" value="{{ old('director_name') }}" required>
                            @error('director_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Contacts (Manager) -->
                        <div>
                            <label for="contacts" class="block text-sm font-medium text-gray-700 mb-2">Контактное лицо (менеджер)</label>
                            <input id="contacts" type="text"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('contacts') border-red-500 @enderror"
                                name="contacts" value="{{ old('contacts') }}">
                            @error('contacts')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input id="email" type="email"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('email') border-red-500 @enderror"
                                name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Телефон *</label>
                            <input id="phone" type="tel"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('phone') border-red-500 @enderror"
                                name="phone" value="{{ old('phone') }}" required>
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Пароль *</label>
                            <input id="password" type="password"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500 @error('password') border-red-500 @enderror"
                                name="password" required>
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Подтверждение пароля *</label>
                            <input id="password_confirmation" type="password"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300 focus:border-orange-500"
                                name="password_confirmation" required>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between bg-gray-50 p-6 rounded-xl">
                    <a href="{{ route('login') }}" class="text-orange-600 hover:text-orange-800 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        Уже есть аккаунт?
                    </a>

                    <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 px-8 rounded-lg transition duration-200 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        Зарегистрировать компанию
                    </button>
                </div>
            </form>
        </div>
    </div>

    @section('scripts')
    <script>
        // Автозаполнение фактического адреса
        document.getElementById('same_as_legal').addEventListener('change', function() {
            if (this.checked) {
                const legalAddress = document.getElementById('legal_address').value;
                document.getElementById('actual_address').value = legalAddress;
            } else {
                document.getElementById('actual_address').value = '';
            }
        });

        // При загрузке страницы, если чекбокс отмечен, заполняем фактический адрес
        window.addEventListener('load', function() {
            if (document.getElementById('same_as_legal').checked) {
                const legalAddress = document.getElementById('legal_address').value;
                document.getElementById('actual_address').value = legalAddress;
            }
        });

        // Маска для телефона
        document.getElementById('phone').addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,1})(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
            e.target.value = !x[2] ? x[1] : '+' + x[1] + ' (' + x[2] + ') ' + x[3] + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '');
        });

        // Валидация ИНН, КПП и др. при вводе
        document.getElementById('inn').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 10);
        });

        document.getElementById('kpp').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 9);
        });

        document.getElementById('ogrn').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 13);
        });
    </script>
    @endsection
</x-auth-layout>
