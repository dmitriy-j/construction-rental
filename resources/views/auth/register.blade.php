@extends('layouts.auth')

@section('title', 'Регистрация компании')
@section('page-title', 'Регистрация компании')
@section('background-text', 'Создайте аккаунт для вашей организации')

@section('content')
<form method="POST" action="{{ route('register.store') }}" class="auth-form" id="registrationForm">
    @csrf

    @if($errors->has('error'))
        <div class="alert alert-danger mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle me-3"></i>
                <span class="small">{{ $errors->first('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Простой прогресс-бар без текста -->
    <div class="mb-5">
        <div class="progress" style="height: 8px;">
            <div id="progressBar" class="progress-bar bg-primary" style="width: 25%; transition: width 0.5s ease;"></div>
        </div>
    </div>

    <!-- Шаг 1: Тип компании -->
    <div id="step1" class="step-content active">
        <div class="text-center mb-5">
            <h2 class="h3 mb-3">Тип компании</h2>
            <p class="text-muted">Выберите как вы будете использовать платформу</p>
        </div>

        <!-- Добавленное поле company_type в виде селекта -->
        <div class="mb-4">
            <x-input-label for="company_type" value="Тип аккаунта *" />
            <select id="company_type" name="company_type" class="auth-input @error('company_type') is-invalid @enderror" required>
                <option value="">Выберите тип аккаунта</option>
                <option value="lessor" {{ old('company_type') == 'lessor' ? 'selected' : '' }}>Арендодатель</option>
                <option value="lessee" {{ old('company_type') == 'lessee' ? 'selected' : '' }}>Арендатор</option>
                <option value="carrier" {{ old('company_type') == 'carrier' ? 'selected' : '' }}>Перевозчик</option>
            </select>
            <x-input-error :messages="$errors->get('company_type')" />
        </div>

        <div class="row g-4 mb-4">
            <!-- Арендатор -->
            <div class="col-md-4">
                <div class="card h-100 option-card">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <div class="option-icon bg-primary bg-opacity-10 text-primary rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="bi bi-tools fs-2"></i>
                            </div>
                        </div>
                        <h5 class="card-title mb-2">Арендатор</h5>
                        <p class="card-text text-muted small mb-3">
                            Арендуете технику для строительных проектов
                        </p>
                        <div class="form-check d-flex justify-content-center">
                            <input class="form-check-input" type="radio" name="company_type_radio"
                                   id="type_lessee" value="lessee" {{ old('company_type', 'lessee') == 'lessee' ? 'checked' : '' }}>
                            <label class="form-check-label ms-2" for="type_lessee">
                                Выбрать
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Арендодатель -->
            <div class="col-md-4">
                <div class="card h-100 option-card">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <div class="option-icon bg-success bg-opacity-10 text-success rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="bi bi-truck fs-2"></i>
                            </div>
                        </div>
                        <h5 class="card-title mb-2">Арендодатель</h5>
                        <p class="card-text text-muted small mb-3">
                            Предоставляете технику в аренду
                        </p>
                        <div class="form-check d-flex justify-content-center">
                            <input class="form-check-input" type="radio" name="company_type_radio"
                                   id="type_lessor" value="lessor" {{ old('company_type') == 'lessor' ? 'checked' : '' }}>
                            <label class="form-check-label ms-2" for="type_lessor">
                                Выбрать
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Перевозчик -->
            <div class="col-md-4">
                <div class="card h-100 option-card">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <div class="option-icon bg-info bg-opacity-10 text-info rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="bi bi-truck-flatbed fs-2"></i>
                            </div>
                        </div>
                        <h5 class="card-title mb-2">Перевозчик</h5>
                        <p class="card-text text-muted small mb-3">
                            Оказываете услуги перевозки
                        </p>
                        <div class="form-check d-flex justify-content-center">
                            <input class="form-check-input" type="radio" name="company_type_radio"
                                   id="type_carrier" value="carrier" {{ old('company_type') == 'carrier' ? 'checked' : '' }}>
                            <label class="form-check-label ms-2" for="type_carrier">
                                Выбрать
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @error('company_type')
            <div class="text-danger small text-center mb-3">{{ $message }}</div>
        @enderror

        <div class="d-flex justify-content-end pt-4 border-top">
            <button type="button" class="auth-btn step-next" data-next="2">
                Далее
                <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>
    </div>

    <!-- Остальные шаги остаются без изменений -->
    <!-- Шаг 2: Система налогообложения -->
    <div id="step2" class="step-content d-none">
        <div class="text-center mb-5">
            <h2 class="h3 mb-3">Система налогообложения</h2>
            <p class="text-muted">Выберите подходящую систему для вашего бизнеса</p>
        </div>

        <div class="row g-4 mb-4">
            <!-- С НДС -->
            <div class="col-md-6">
                <div class="card h-100 option-card">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <div class="option-icon bg-primary bg-opacity-10 text-primary rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="bi bi-building fs-2"></i>
                            </div>
                        </div>
                        <h5 class="card-title mb-2">С НДС</h5>
                        <p class="card-text text-muted small mb-3">
                            Общая система налогообложения
                        </p>
                        <div class="form-check d-flex justify-content-center">
                            <input class="form-check-input" type="radio" name="tax_system"
                                   id="tax_vat" value="vat" {{ old('tax_system', 'vat') == 'vat' ? 'checked' : '' }}>
                            <label class="form-check-label ms-2" for="tax_vat">
                                Выбрать
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Без НДС -->
            <div class="col-md-6">
                <div class="card h-100 option-card">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <div class="option-icon bg-warning bg-opacity-10 text-warning rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="bi bi-file-text fs-2"></i>
                            </div>
                        </div>
                        <h5 class="card-title mb-2">Без НДС</h5>
                        <p class="card-text text-muted small mb-3">
                            Упрощенная система налогообложения
                        </p>
                        <div class="form-check d-flex justify-content-center">
                            <input class="form-check-input" type="radio" name="tax_system"
                                   id="tax_no_vat" value="no_vat" {{ old('tax_system') == 'no_vat' ? 'checked' : '' }}>
                            <label class="form-check-label ms-2" for="tax_no_vat">
                                Выбрать
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @error('tax_system')
            <div class="text-danger small text-center mb-3">{{ $message }}</div>
        @enderror

        <div class="d-flex justify-content-between pt-4 border-top">
            <button type="button" class="auth-btn bg-secondary step-prev" data-prev="1">
                <i class="bi bi-arrow-left me-2"></i>
                Назад
            </button>
            <button type="button" class="auth-btn step-next" data-next="3">
                Далее
                <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>
    </div>

    <!-- Шаг 3: Тип организации -->
    <div id="step3" class="step-content d-none">
        <div class="text-center mb-5">
            <h2 class="h3 mb-3">Тип организации</h2>
            <p class="text-muted">Выберите организационно-правовую форму</p>
        </div>

        <div class="row g-4 mb-4">
            <!-- ООО -->
            <div class="col-md-6">
                <div class="card h-100 option-card">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <div class="option-icon bg-primary bg-opacity-10 text-primary rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="bi bi-building fs-2"></i>
                            </div>
                        </div>
                        <h5 class="card-title mb-2">ООО</h5>
                        <p class="card-text text-muted small mb-3">
                            Общество с ограниченной ответственностью
                        </p>
                        <div class="form-check d-flex justify-content-center">
                            <input class="form-check-input" type="radio" name="legal_type"
                                   id="legal_type_ooo" value="ooo" {{ old('legal_type') == 'ooo' ? 'checked' : '' }}>
                            <label class="form-check-label ms-2" for="legal_type_ooo">
                                Выбрать
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ИП -->
            <div class="col-md-6">
                <div class="card h-100 option-card">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <div class="option-icon bg-success bg-opacity-10 text-success rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="bi bi-person fs-2"></i>
                            </div>
                        </div>
                        <h5 class="card-title mb-2">ИП</h5>
                        <p class="card-text text-muted small mb-3">
                            Индивидуальный предприниматель
                        </p>
                        <div class="form-check d-flex justify-content-center">
                            <input class="form-check-input" type="radio" name="legal_type"
                                   id="legal_type_ip" value="ip" {{ old('legal_type') == 'ip' ? 'checked' : '' }}>
                            <label class="form-check-label ms-2" for="legal_type_ip">
                                Выбрать
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @error('legal_type')
            <div class="text-danger small text-center mb-3">{{ $message }}</div>
        @enderror

        <div class="d-flex justify-content-between pt-4 border-top">
            <button type="button" class="auth-btn bg-secondary step-prev" data-prev="2">
                <i class="bi bi-arrow-left me-2"></i>
                Назад
            </button>
            <button type="button" class="auth-btn step-next" data-next="4">
                Далее
                <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>
    </div>

    <!-- Шаг 4: Реквизиты компании -->
    <div id="step4" class="step-content d-none">
        <div class="text-center mb-5">
            <h2 class="h3 mb-3">Реквизиты компании</h2>
            <p class="text-muted">Заполните информацию о вашей организации</p>
        </div>

        <div class="vstack gap-4">
            <!-- Основная информация -->
            <div>
                <h4 class="h5 border-bottom pb-2 mb-3">Основная информация</h4>
                <div class="vstack gap-3">
                    <div>
                        <label for="legal_name" class="form-label">Название компании *</label>
                        <div class="auth-input-group">
                            <i class="bi bi-building auth-input-icon"></i>
                            <input id="legal_name" type="text"
                                class="auth-input @error('legal_name') is-invalid @enderror"
                                name="legal_name" value="{{ old('legal_name') }}"
                                required placeholder="ООО 'Ваша Компания'">
                        </div>
                        @error('legal_name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Тип организации -->
                    <div class="mb-3">
                        <label for="legal_type" class="form-label">Тип организации *</label>
                        <select id="legal_type" name="legal_type" class="auth-input @error('legal_type') is-invalid @enderror" required
                                onchange="toggleKppField()">
                            <option value="">Выберите тип организации</option>
                            <option value="ooo" {{ old('legal_type') == 'ooo' ? 'selected' : '' }}>ООО</option>
                            <option value="ip" {{ old('legal_type') == 'ip' ? 'selected' : '' }}>ИП</option>
                        </select>
                        @error('legal_type')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="inn" class="form-label">ИНН *</label>
                            <div class="auth-input-group">
                                <i class="bi bi-123 auth-input-icon"></i>
                                <input id="inn" type="text"
                                    class="auth-input @error('inn') is-invalid @enderror"
                                    name="inn" value="{{ old('inn') }}"
                                    required inputmode="numeric" pattern="\d{10,12}" maxlength="12"
                                    placeholder="1234567890">
                            </div>
                            @error('inn')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Поле KPP делаем условным -->
                        <div class="col-md-6" id="kpp_field" style="{{ old('legal_type') == 'ip' ? 'display: none;' : '' }}">
                            <label for="kpp" class="form-label">КПП *</label>
                            <div class="auth-input-group">
                                <i class="bi bi-123 auth-input-icon"></i>
                                <input id="kpp" type="text"
                                    class="auth-input @error('kpp') is-invalid @enderror"
                                    name="kpp" value="{{ old('kpp') }}"
                                    inputmode="numeric" pattern="\d{9}" maxlength="9"
                                    placeholder="123456789">
                            </div>
                            @error('kpp')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="ogrn" class="form-label">ОГРН *</label>
                            <div class="auth-input-group">
                                <i class="bi bi-123 auth-input-icon"></i>
                                <input id="ogrn" type="text"
                                    class="auth-input @error('ogrn') is-invalid @enderror"
                                    name="ogrn" value="{{ old('ogrn') }}"
                                    required inputmode="numeric" pattern="\d{13,15}" maxlength="15"
                                    placeholder="1234567890123">
                            </div>
                            @error('ogrn')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="okpo" class="form-label">ОКПО</label>
                            <div class="auth-input-group">
                                <i class="bi bi-123 auth-input-icon"></i>
                                <input id="okpo" type="text"
                                    class="auth-input @error('okpo') is-invalid @enderror"
                                    name="okpo" value="{{ old('okpo') }}"
                                    inputmode="numeric" pattern="\d{8,10}" maxlength="10"
                                    placeholder="12345678">
                            </div>
                            @error('okpo')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Адреса -->
            <div>
                <h4 class="h5 border-bottom pb-2 mb-3">Адреса компании</h4>
                <div class="vstack gap-3">
                    <div>
                        <label for="legal_address" class="form-label">Юридический адрес *</label>
                        <div class="auth-input-group">
                            <i class="bi bi-geo-alt auth-input-icon"></i>
                            <input id="legal_address" type="text"
                                class="auth-input @error('legal_address') is-invalid @enderror"
                                name="legal_address" value="{{ old('legal_address') }}"
                                required placeholder="г. Москва, ул. Примерная, д. 1">
                        </div>
                        @error('legal_address')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="actual_address" class="form-label">Фактический адрес</label>
                        <div class="auth-input-group">
                            <i class="bi bi-geo-alt auth-input-icon"></i>
                            <input id="actual_address" type="text"
                                class="auth-input @error('actual_address') is-invalid @enderror"
                                name="actual_address" value="{{ old('actual_address') }}"
                                placeholder="г. Москва, ул. Примерная, д. 1">
                        </div>
                        @error('actual_address')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check">
                        <input type="checkbox" class="form-check-input"
                            id="same_as_legal" name="same_as_legal" value="1" {{ old('same_as_legal') ? 'checked' : '' }}>
                        <label class="form-check-label" for="same_as_legal">
                            Совпадает с юридическим адресом
                        </label>
                    </div>
                </div>
            </div>

            <!-- Банковские реквизиты -->
            <div>
                <h4 class="h5 border-bottom pb-2 mb-3">Банковские реквизиты</h4>
                <div class="vstack gap-3">
                    <div>
                        <label for="bank_name" class="form-label">Название банка *</label>
                        <div class="auth-input-group">
                            <i class="bi bi-bank auth-input-icon"></i>
                            <input id="bank_name" type="text"
                                class="auth-input @error('bank_name') is-invalid @enderror"
                                name="bank_name" value="{{ old('bank_name') }}"
                                required placeholder="ПАО 'Сбербанк'">
                        </div>
                        @error('bank_name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="bank_account" class="form-label">Расчетный счет *</label>
                            <div class="auth-input-group">
                                <i class="bi bi-credit-card auth-input-icon"></i>
                                <input id="bank_account" type="text"
                                    class="auth-input @error('bank_account') is-invalid @enderror"
                                    name="bank_account" value="{{ old('bank_account') }}"
                                    required inputmode="numeric" pattern="\d{20}" maxlength="20"
                                    placeholder="12345678901234567890">
                            </div>
                            @error('bank_account')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="bik" class="form-label">БИК *</label>
                            <div class="auth-input-group">
                                <i class="bi bi-123 auth-input-icon"></i>
                                <input id="bik" type="text"
                                    class="auth-input @error('bik') is-invalid @enderror"
                                    name="bik" value="{{ old('bik') }}"
                                    required inputmode="numeric" pattern="\d{9}" maxlength="9"
                                    placeholder="123456789">
                            </div>
                            @error('bik')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="correspondent_account" class="form-label">Корреспондентский счет</label>
                        <div class="auth-input-group">
                            <i class="bi bi-credit-card auth-input-icon"></i>
                            <input id="correspondent_account" type="text"
                                class="auth-input @error('correspondent_account') is-invalid @enderror"
                                name="correspondent_account" value="{{ old('correspondent_account') }}"
                                inputmode="numeric" pattern="\d{20}" maxlength="20"
                                placeholder="12345678901234567890">
                        </div>
                        @error('correspondent_account')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Контактная информация -->
            <div>
                <h4 class="h5 border-bottom pb-2 mb-3">Контактная информация</h4>
                <div class="vstack gap-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Ваше имя *</label>
                            <div class="auth-input-group">
                                <i class="bi bi-person auth-input-icon"></i>
                                <input id="name" type="text"
                                    class="auth-input @error('name') is-invalid @enderror"
                                    name="name" value="{{ old('name') }}"
                                    required placeholder="Иван Иванов">
                            </div>
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <div class="auth-input-group">
                                <i class="bi bi-envelope auth-input-icon"></i>
                                <input id="email" type="email"
                                    class="auth-input @error('email') is-invalid @enderror"
                                    name="email" value="{{ old('email') }}"
                                    required placeholder="ваш@email.com">
                            </div>
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="director_name" class="form-label">ФИО директора *</label>
                            <div class="auth-input-group">
                                <i class="bi bi-person-badge auth-input-icon"></i>
                                <input id="director_name" type="text"
                                    class="auth-input @error('director_name') is-invalid @enderror"
                                    name="director_name" value="{{ old('director_name') }}"
                                    required placeholder="Иванов Иван Иванович">
                            </div>
                            @error('director_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="contacts" class="form-label">Контактное лицо</label>
                            <div class="auth-input-group">
                                <i class="bi bi-person auth-input-icon"></i>
                                <input id="contacts" type="text"
                                    class="auth-input @error('contacts') is-invalid @enderror"
                                    name="contacts" value="{{ old('contacts') }}"
                                    placeholder="Петрова Мария Сергеевна">
                            </div>
                            @error('contacts')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Телефон *</label>
                            <div class="auth-input-group">
                                <i class="bi bi-telephone auth-input-icon"></i>
                                <input id="phone" type="tel"
                                    class="auth-input @error('phone') is-invalid @enderror"
                                    name="phone" value="{{ old('phone') }}"
                                    required inputmode="tel" placeholder="+7 (999) 999-99-99">
                            </div>
                            @error('phone')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label">Пароль *</label>
                            <div class="auth-input-group">
                                <i class="bi bi-lock auth-input-icon"></i>
                                <input id="password" type="password"
                                    class="auth-input @error('password') is-invalid @enderror"
                                    name="password" required placeholder="••••••••" minlength="8">
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="password_confirmation" class="form-label">Подтверждение пароля *</label>
                        <div class="auth-input-group">
                            <i class="bi bi-lock auth-input-icon"></i>
                            <input id="password_confirmation" type="password"
                                class="auth-input"
                                name="password_confirmation" required placeholder="••••••••" minlength="8">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center pt-4 border-top mt-4">
            <a href="{{ route('login') }}" class="text-primary text-decoration-none small">
                <i class="bi bi-arrow-left me-2"></i>
                Уже есть аккаунт?
            </a>

            <div class="d-flex gap-3">
                <button type="button" class="auth-btn bg-secondary step-prev" data-prev="3">
                    <i class="bi bi-arrow-left me-2"></i>
                    Назад
                </button>
                <button type="submit" class="auth-btn" id="submitBtn">
                    <i class="bi bi-building me-2"></i> Зарегистрировать
                </button>
            </div>
        </div>
    </div>
</form>

<style>
.step-content {
    opacity: 0;
    height: 0;
    overflow: hidden;
    transition: opacity 0.3s ease-in-out;
}

.step-content.active {
    opacity: 1;
    height: auto;
    overflow: visible;
}

/* Простые стили для карточек */
.option-card {
    border: 2px solid transparent;
    transition: all 0.3s ease;
    cursor: pointer;
}

.option-card:hover {
    border-color: #dee2e6;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Стиль для выбранной карточки */
.option-card .form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.option-card .form-check-input:checked ~ .card-body {
    border: 2px solid #0d6efd;
    border-radius: 12px;
    background-color: rgba(13, 110, 253, 0.05);
}

/* Гарантируем, что текст всегда виден */
.card-title {
    color: #212529 !important;
    font-weight: 600;
}

.card-text {
    color: #6c757d !important;
    line-height: 1.4;
}

/* Кастомные стили для кнопок навигации */
.auth-btn.bg-secondary {
    background: #6c757d !important;
    border: none;
}

.auth-btn.bg-secondary:hover {
    background: #5a6268 !important;
}

/* Правильные отступы для кнопок */
.d-flex.gap-3 > * {
    margin: 0 !important;
}

/* Стили для select */
.auth-input[type="text"],
.auth-input[type="email"],
.auth-input[type="tel"],
.auth-input[type="password"],
select.auth-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    transition: all 0.15s ease-in-out;
}

select.auth-input {
    padding-left: 1rem;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
    appearance: none;
}
</style>

<script>
// JavaScript для переключения поля KPP
function toggleKppField() {
    const legalType = document.getElementById('legal_type').value;
    const kppField = document.getElementById('kpp_field');
    const kppInput = document.getElementById('kpp');

    if (legalType === 'ip') {
        kppField.style.display = 'none';
        if (kppInput) {
            kppInput.value = '';
            kppInput.removeAttribute('required');
        }
    } else {
        kppField.style.display = 'block';
        if (kppInput) {
            kppInput.setAttribute('required', 'required');
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registrationForm');
    const steps = document.querySelectorAll('.step-content');
    const progressBar = document.getElementById('progressBar');
    let currentStep = 1;

    // Инициализация поля KPP
    toggleKppField();

    // Синхронизация селекта и радиокнопок для типа компании
    const companyTypeSelect = document.getElementById('company_type');
    const companyTypeRadios = document.querySelectorAll('input[name="company_type_radio"]');

    // Обновляем селект при выборе радиокнопки
    companyTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                companyTypeSelect.value = this.value;
            }
        });
    });

    // Обновляем радиокнопки при изменении селекта
    companyTypeSelect.addEventListener('change', function() {
        const value = this.value;
        companyTypeRadios.forEach(radio => {
            radio.checked = (radio.value === value);
        });
    });

    // Функция обновления прогресса
    function updateProgress() {
        const progress = ((currentStep - 1) / 3) * 100;
        progressBar.style.width = `${progress}%`;
    }

    // Функция переключения шагов
    function showStep(stepNumber) {
        steps.forEach((step, index) => {
            if (index + 1 === stepNumber) {
                step.classList.remove('d-none');
                setTimeout(() => step.classList.add('active'), 10);
            } else {
                step.classList.remove('active');
                setTimeout(() => step.classList.add('d-none'), 300);
            }
        });
        currentStep = stepNumber;
        updateProgress();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Обработчики для кнопок навигации
    document.querySelectorAll('.step-next').forEach(button => {
        button.addEventListener('click', function() {
            const nextStep = parseInt(this.getAttribute('data-next'));
            let isValid = true;

            if (nextStep === 2) {
                const companyTypeSelected = document.querySelector('input[name="company_type_radio"]:checked');
                if (!companyTypeSelected) {
                    isValid = false;
                    showNotification('Пожалуйста, выберите тип компании', 'error');
                } else {
                    // Синхронизируем значение с селектом
                    companyTypeSelect.value = companyTypeSelected.value;
                }
            } else if (nextStep === 3) {
                const taxSystemSelected = document.querySelector('input[name="tax_system"]:checked');
                if (!taxSystemSelected) {
                    isValid = false;
                    showNotification('Пожалуйста, выберите систему налогообложения', 'error');
                }
            } else if (nextStep === 4) {
                const legalTypeSelected = document.querySelector('input[name="legal_type"]:checked');
                if (!legalTypeSelected) {
                    isValid = false;
                    showNotification('Пожалуйста, выберите тип организации', 'error');
                }
            }

            if (isValid) {
                showStep(nextStep);
            }
        });
    });

    document.querySelectorAll('.step-prev').forEach(button => {
        button.addEventListener('click', function() {
            const prevStep = parseInt(this.getAttribute('data-prev'));
            showStep(prevStep);
        });
    });

    // Упрощенная функция показа уведомлений
    function showNotification(message, type = 'info') {
        alert(message); // Простой alert вместо сложных уведомлений
    }

    // Обработчики для карточек - делаем всю карточку кликабельной
    document.querySelectorAll('.option-card').forEach(card => {
        const radioInput = card.querySelector('input[type="radio"]');
        card.addEventListener('click', function(e) {
            if (e.target !== radioInput && e.target.type !== 'radio') {
                radioInput.checked = true;

                // Снимаем выделение с других карточек в той же группе
                const groupName = radioInput.name;
                document.querySelectorAll(`input[name="${groupName}"]`).forEach(otherInput => {
                    if (otherInput !== radioInput) {
                        otherInput.checked = false;
                    }
                });

                // Обновляем визуальное выделение
                document.querySelectorAll('.option-card').forEach(otherCard => {
                    otherCard.style.borderColor = 'transparent';
                    otherCard.style.backgroundColor = '';
                });

                card.style.borderColor = '#0d6efd';
                card.style.backgroundColor = 'rgba(13, 110, 253, 0.05)';

                // Обновляем селект legal_type если это карточка типа организации
                if (radioInput.name === 'legal_type') {
                    const select = document.getElementById('legal_type');
                    if (select) {
                        select.value = radioInput.value;
                        toggleKppField();
                    }
                }

                // Обновляем селект company_type если это карточка типа компании
                if (radioInput.name === 'company_type_radio') {
                    companyTypeSelect.value = radioInput.value;
                }
            }
        });
    });

    // Обновляем радиокнопки при изменении селекта legal_type
    const legalTypeSelect = document.getElementById('legal_type');
    if (legalTypeSelect) {
        legalTypeSelect.addEventListener('change', function() {
            const value = this.value;
            const radioInput = document.querySelector(`input[name="legal_type"][value="${value}"]`);
            if (radioInput) {
                radioInput.checked = true;

                // Обновляем визуальное выделение карточек
                document.querySelectorAll('.option-card').forEach(card => {
                    const cardRadio = card.querySelector('input[name="legal_type"]');
                    if (cardRadio) {
                        if (cardRadio.value === value) {
                            card.style.borderColor = '#0d6efd';
                            card.style.backgroundColor = 'rgba(13, 110, 253, 0.05)';
                        } else {
                            card.style.borderColor = 'transparent';
                            card.style.backgroundColor = '';
                        }
                    }
                });
            }
            toggleKppField();
        });
    }

    // Автозаполнение адреса
    const sameAsLegalCheckbox = document.getElementById('same_as_legal');
    const legalAddressInput = document.getElementById('legal_address');
    const actualAddressInput = document.getElementById('actual_address');

    function updateActualAddress() {
        if (sameAsLegalCheckbox && sameAsLegalCheckbox.checked) {
            actualAddressInput.value = legalAddressInput.value;
            actualAddressInput.disabled = true;
        } else if (actualAddressInput) {
            actualAddressInput.disabled = false;
        }
    }

    if (sameAsLegalCheckbox && legalAddressInput && actualAddressInput) {
        sameAsLegalCheckbox.addEventListener('change', updateActualAddress);
        legalAddressInput.addEventListener('input', function() {
            if (sameAsLegalCheckbox.checked) {
                updateActualAddress();
            }
        });
    }

    // Маска для телефона
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,1})(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
            e.target.value = !x[2] ? x[1] : '+' + x[1] + ' (' + x[2] + ') ' + x[3] + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '');
        });
    }

    // Валидация числовых полей
    const numericFields = ['inn', 'kpp', 'ogrn', 'okpo', 'bank_account', 'bik', 'correspondent_account'];
    numericFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function(e) {
                this.value = this.value.replace(/\D/g, '');
                const maxLength = parseInt(this.getAttribute('maxlength'));
                if (this.value.length > maxLength) {
                    this.value = this.value.slice(0, maxLength);
                }
            });
        }
    });

    // Предотвращение двойной отправки
    form.addEventListener('submit', function() {
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Регистрация...';
        }
    });

    // Инициализация
    if (sameAsLegalCheckbox && legalAddressInput && actualAddressInput) {
        updateActualAddress();
    }
    updateProgress();
});
</script>
@endsection
