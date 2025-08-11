@extends('layouts.auth')

@section('title', 'Регистрация компании')
@section('page-title', 'Регистрация компании')
@section('background-text', 'Заполните информацию о вашей организации для создания аккаунта')

@section('content')
<form method="POST" action="{{ route('register.store') }}" class="auth-form">
    @csrf

    <!-- Общие ошибки -->
    @if($errors->any())
        <div class="auth-form-group bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="bi bi-x-circle-fill text-red-500 text-xl"></i>
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
        <div class="auth-form-group bg-green-50 border-l-4 border-green-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="bi bi-check-circle-fill text-green-500 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">{{ session('status') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Блок типа компании -->
    <div class="auth-form-group bg-blue-50 rounded-xl p-6 mb-6 border border-blue-100">
        <div class="flex items-center mb-4">
            <div class="bg-blue-100 p-2 rounded-lg mr-3">
                <i class="bi bi-building text-blue-600 text-xl"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-800">Тип компании</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3">Тип компании *</h3>
                <div class="space-y-2">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                type="radio" name="type" id="type_lessee"
                                value="lessee" {{ old('type', 'lessee') == 'lessee' ? 'checked' : '' }}>
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="type_lessee" class="font-medium text-gray-700">Арендатор</label>
                            <p class="text-gray-500 mt-1">Арендуете технику для своих проектов</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                type="radio" name="type" id="type_lessor"
                                value="lessor" {{ old('type') == 'lessor' ? 'checked' : '' }}>
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="type_lessor" class="font-medium text-gray-700">Арендодатель</label>
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
                            <input class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
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
                            <input class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
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
    <div class="auth-form-group bg-white rounded-xl p-6 mb-6 border border-gray-200 shadow-sm">
        <div class="flex items-center mb-6">
            <div class="bg-gray-100 p-2 rounded-lg mr-3">
                <i class="bi bi-file-text text-gray-600 text-xl"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-800">Реквизиты компании</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Company Name -->
            <div class="col-span-2">
                <label for="legal_name" class="block text-sm font-medium text-gray-700 mb-2">Название компании *</label>
                <div class="auth-input-group">
                    <i class="bi bi-building auth-input-icon"></i>
                    <input id="legal_name" type="text"
                        class="auth-input @error('legal_name') border-red-500 @enderror"
                        name="legal_name" value="{{ old('legal_name') }}"
                        required placeholder="ООО 'Ваша Компания'">
                </div>
                @error('legal_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- ИНН -->
            <div>
                <label for="inn" class="block text-sm font-medium text-gray-700 mb-2">ИНН *</label>
                <div class="auth-input-group">
                    <i class="bi bi-123 auth-input-icon"></i>
                    <input id="inn" type="text"
                        class="auth-input @error('inn') border-red-500 @enderror"
                        name="inn" value="{{ old('inn') }}"
                        required pattern="\d{10}" maxlength="10" placeholder="1234567890">
                </div>
                @error('inn')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- КПП -->
            <div>
                <label for="kpp" class="block text-sm font-medium text-gray-700 mb-2">КПП *</label>
                <div class="auth-input-group">
                    <i class="bi bi-123 auth-input-icon"></i>
                    <input id="kpp" type="text"
                        class="auth-input @error('kpp') border-red-500 @enderror"
                        name="kpp" value="{{ old('kpp') }}"
                        required pattern="\d{9}" maxlength="9" placeholder="123456789">
                </div>
                @error('kpp')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- ОГРН -->
            <div>
                <label for="ogrn" class="block text-sm font-medium text-gray-700 mb-2">ОГРН *</label>
                <div class="auth-input-group">
                    <i class="bi bi-123 auth-input-icon"></i>
                    <input id="ogrn" type="text"
                        class="auth-input @error('ogrn') border-red-500 @enderror"
                        name="ogrn" value="{{ old('ogrn') }}"
                        required pattern="\d{13}" maxlength="13" placeholder="1234567890123">
                </div>
                @error('ogrn')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- ОКПО -->
            <div>
                <label for="okpo" class="block text-sm font-medium text-gray-700 mb-2">ОКПО</label>
                <div class="auth-input-group">
                    <i class="bi bi-123 auth-input-icon"></i>
                    <input id="okpo" type="text"
                        class="auth-input @error('okpo') border-red-500 @enderror"
                        name="okpo" value="{{ old('okpo') }}"
                        pattern="\d{8,10}" maxlength="10" placeholder="12345678">
                </div>
                @error('okpo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Адреса -->
    <div class="auth-form-group bg-white rounded-xl p-6 mb-6 border border-gray-200 shadow-sm">
        <div class="flex items-center mb-6">
            <div class="bg-gray-100 p-2 rounded-lg mr-3">
                <i class="bi bi-geo-alt text-gray-600 text-xl"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-800">Адреса компании</h2>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <!-- Юридический адрес -->
            <div>
                <label for="legal_address" class="block text-sm font-medium text-gray-700 mb-2">Юридический адрес *</label>
                <div class="auth-input-group">
                    <i class="bi bi-geo auth-input-icon"></i>
                    <input id="legal_address" type="text"
                        class="auth-input @error('legal_address') border-red-500 @enderror"
                        name="legal_address" value="{{ old('legal_address') }}"
                        required placeholder="г. Москва, ул. Примерная, д. 1">
                </div>
                @error('legal_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Фактический адрес -->
            <div>
                <label for="actual_address" class="block text-sm font-medium text-gray-700 mb-2">Фактический адрес</label>
                <div class="auth-input-group">
                    <i class="bi bi-geo auth-input-icon"></i>
                    <input id="actual_address" type="text"
                        class="auth-input @error('actual_address') border-red-500 @enderror"
                        name="actual_address" value="{{ old('actual_address') }}"
                        placeholder="г. Москва, ул. Примерная, д. 1">
                </div>
                @error('actual_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center">
                <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    id="same_as_legal" name="same_as_legal" value="1" {{ old('same_as_legal') ? 'checked' : '' }}>
                <label class="ml-2 block text-sm text-gray-700" for="same_as_legal">
                    Совпадает с юридическим адресом
                </label>
            </div>
        </div>
    </div>

    <!-- Банковские реквизиты -->
    <div class="auth-form-group bg-white rounded-xl p-6 mb-6 border border-gray-200 shadow-sm">
        <div class="flex items-center mb-6">
            <div class="bg-gray-100 p-2 rounded-lg mr-3">
                <i class="bi bi-bank text-gray-600 text-xl"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-800">Банковские реквизиты</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Bank Name -->
            <div class="col-span-2">
                <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-2">Название банка *</label>
                <div class="auth-input-group">
                    <i class="bi bi-bank2 auth-input-icon"></i>
                    <input id="bank_name" type="text"
                        class="auth-input @error('bank_name') border-red-500 @enderror"
                        name="bank_name" value="{{ old('bank_name') }}"
                        required placeholder="ПАО 'Сбербанк'">
                </div>
                @error('bank_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Расчетный счет -->
            <div>
                <label for="bank_account" class="block text-sm font-medium text-gray-700 mb-2">Расчетный счет *</label>
                <div class="auth-input-group">
                    <i class="bi bi-wallet auth-input-icon"></i>
                    <input id="bank_account" type="text"
                        class="auth-input @error('bank_account') border-red-500 @enderror"
                        name="bank_account" value="{{ old('bank_account') }}"
                        required pattern="\d{20}" maxlength="20" placeholder="12345678901234567890">
                </div>
                @error('bank_account')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- БИК -->
            <div>
                <label for="bik" class="block text-sm font-medium text-gray-700 mb-2">БИК *</label>
                <div class="auth-input-group">
                    <i class="bi bi-123 auth-input-icon"></i>
                    <input id="bik" type="text"
                        class="auth-input @error('bik') border-red-500 @enderror"
                        name="bik" value="{{ old('bik') }}"
                        required pattern="\d{9}" maxlength="9" placeholder="123456789">
                </div>
                @error('bik')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Корреспондентский счет -->
            <div class="col-span-2">
                <label for="correspondent_account" class="block text-sm font-medium text-gray-700 mb-2">Корреспондентский счет</label>
                <div class="auth-input-group">
                    <i class="bi bi-wallet auth-input-icon"></i>
                    <input id="correspondent_account" type="text"
                        class="auth-input @error('correspondent_account') border-red-500 @enderror"
                        name="correspondent_account" value="{{ old('correspondent_account') }}"
                        pattern="\d{20}" maxlength="20" placeholder="12345678901234567890">
                </div>
                @error('correspondent_account')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Контактная информация -->
    <div class="auth-form-group bg-white rounded-xl p-6 mb-6 border border-gray-200 shadow-sm">
        <div class="flex items-center mb-6">
            <div class="bg-gray-100 p-2 rounded-lg mr-3">
                <i class="bi bi-person-badge text-gray-600 text-xl"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-800">Контактная информация</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Имя пользователя -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Ваше имя (для аккаунта) *</label>
                <div class="auth-input-group">
                    <i class="bi bi-person auth-input-icon"></i>
                    <input id="name" type="text"
                        class="auth-input @error('name') border-red-500 @enderror"
                        name="name" value="{{ old('name') }}"
                        required placeholder="Иван Иванов">
                </div>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                <div class="auth-input-group">
                    <i class="bi bi-envelope auth-input-icon"></i>
                    <input id="email" type="email"
                        class="auth-input @error('email') border-red-500 @enderror"
                        name="email" value="{{ old('email') }}"
                        required placeholder="ваш@email.com">
                </div>
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Director -->
            <div>
                <label for="director_name" class="block text-sm font-medium text-gray-700 mb-2">ФИО директора *</label>
                <div class="auth-input-group">
                    <i class="bi bi-person-badge auth-input-icon"></i>
                    <input id="director_name" type="text"
                        class="auth-input @error('director_name') border-red-500 @enderror"
                        name="director_name" value="{{ old('director_name') }}"
                        required placeholder="Иванов Иван Иванович">
                </div>
                @error('director_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Contacts (Manager) -->
            <div>
                <label for="contacts" class="block text-sm font-medium text-gray-700 mb-2">Контактное лицо (менеджер)</label>
                <div class="auth-input-group">
                    <i class="bi bi-person auth-input-icon"></i>
                    <input id="contacts" type="text"
                        class="auth-input @error('contacts') border-red-500 @enderror"
                        name="contacts" value="{{ old('contacts') }}"
                        placeholder="Петрова Мария Сергеевна">
                </div>
                @error('contacts')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone -->
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Телефон *</label>
                <div class="auth-input-group">
                    <i class="bi bi-telephone auth-input-icon"></i>
                    <input id="phone" type="tel"
                        class="auth-input @error('phone') border-red-500 @enderror"
                        name="phone" value="{{ old('phone') }}"
                        required placeholder="+7 (999) 999-99-99">
                </div>
                @error('phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Пароль *</label>
                <div class="auth-input-group">
                    <i class="bi bi-lock auth-input-icon"></i>
                    <input id="password" type="password"
                        class="auth-input @error('password') border-red-500 @enderror"
                        name="password" required placeholder="••••••••">
                </div>
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Подтверждение пароля *</label>
                <div class="auth-input-group">
                    <i class="bi bi-lock auth-input-icon"></i>
                    <input id="password_confirmation" type="password"
                        class="auth-input"
                        name="password_confirmation" required placeholder="••••••••">
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col-reverse md:flex-row justify-between items-center gap-4 mt-8">
        <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 flex items-center">
            <i class="bi bi-arrow-left mr-2"></i>
            Уже есть аккаунт?
        </a>

        <button type="submit" class="auth-btn w-full md:w-auto">
            <i class="bi bi-building me-2"></i> Зарегистрировать компанию
        </button>
    </div>
</form>

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
    const numericFields = ['inn', 'kpp', 'ogrn', 'okpo', 'bank_account', 'bik', 'correspondent_account'];

    numericFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function(e) {
                this.value = this.value.replace(/\D/g, '');
            });
        }
    });
</script>
@endsection
