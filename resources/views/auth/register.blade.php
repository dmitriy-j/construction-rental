<x-auth-layout title="Регистрация компании">
    <!-- Session Status -->
    @if(session('status'))
    <div class="alert alert-success mb-4">
        {{ session('status') }}
    </div>
    @endif

    <form method="POST" action="{{ route('register.company') }}">
        @csrf

        <!-- Блок типа компании -->
        <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-card-checklist me-2"></i> Тип компании
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Тип компании *</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="type" 
                               id="type_tenant" value="tenant" checked>
                        <label class="form-check-label" for="type_tenant">
                            Арендатор (Tenant)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="type" 
                               id="type_landlord" value="landlord">
                        <label class="form-check-label" for="type_landlord">
                            Арендодатель (Landlord)
                        </label>
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" 
                           id="vat" name="vat" value="1">
                    <label class="form-check-label" for="vat">
                        Работаем с НДС
                    </label>
                </div>
            </div>
        </div>

        <!-- Блок основной информации -->
        <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-building me-2"></i> Основная информация
            </div>
            <div class="card-body">
                <!-- Company Name -->
                <div class="mb-3">
                    <label for="name" class="form-label">Название компании *</label>
                    <input id="name" type="text" 
                           class="form-control @error('name') is-invalid @enderror" 
                           name="name" value="{{ old('name') }}" required>
                    @error('name')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- ИНН -->
                <div class="mb-3">
                    <label for="inn" class="form-label">ИНН *</label>
                    <input id="inn" type="text" 
                           class="form-control @error('inn') is-invalid @enderror" 
                           name="inn" value="{{ old('inn') }}" 
                           required pattern="\d{10,12}" maxlength="12">
                    @error('inn')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- КПП -->
                <div class="mb-3">
                    <label for="kpp" class="form-label">КПП</label>
                    <input id="kpp" type="text" 
                           class="form-control @error('kpp') is-invalid @enderror" 
                           name="kpp" value="{{ old('kpp') }}" 
                           pattern="\d{9}" maxlength="9">
                    @error('kpp')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- ОГРН -->
                <div class="mb-3">
                    <label for="ogrn" class="form-label">ОГРН/ОГРНИП *</label>
                    <input id="ogrn" type="text" 
                           class="form-control @error('ogrn') is-invalid @enderror" 
                           name="ogrn" value="{{ old('ogrn') }}" 
                           required pattern="\d{13,15}" maxlength="15">
                    @error('ogrn')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- ОКПО -->
                <div class="mb-3">
                    <label for="okpo" class="form-label">ОКПО</label>
                    <input id="okpo" type="text" 
                           class="form-control @error('okpo') is-invalid @enderror" 
                           name="okpo" value="{{ old('okpo') }}" 
                           pattern="\d{8,10}" maxlength="10">
                    @error('okpo')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Блок адресов -->
        <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-geo-alt me-2"></i> Адреса
            </div>
            <div class="card-body">
                <!-- Юридический адрес -->
                <div class="mb-3">
                    <label for="legal_address" class="form-label">Юридический адрес *</label>
                    <input id="legal_address" type="text" 
                           class="form-control @error('legal_address') is-invalid @enderror" 
                           name="legal_address" value="{{ old('legal_address') }}" required>
                    @error('legal_address')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Фактический адрес -->
                <div class="mb-3">
                    <label for="actual_address" class="form-label">Фактический адрес</label>
                    <input id="actual_address" type="text" 
                           class="form-control @error('actual_address') is-invalid @enderror" 
                           name="actual_address" value="{{ old('actual_address') }}">
                    @error('actual_address')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" 
                           id="same_address" name="same_address" value="1">
                    <label class="form-check-label" for="same_address">
                        Совпадает с юридическим адресом
                    </label>
                </div>
            </div>
        </div>

        <!-- Блок банковских реквизитов -->
        <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-bank me-2"></i> Банковские реквизиты
            </div>
            <div class="card-body">
                <!-- Bank Name -->
                <div class="mb-3">
                    <label for="bank_name" class="form-label">Название банка *</label>
                    <input id="bank_name" type="text" 
                           class="form-control @error('bank_name') is-invalid @enderror" 
                           name="bank_name" value="{{ old('bank_name') }}" required>
                    @error('bank_name')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Расчетный счет -->
                <div class="mb-3">
                    <label for="bank_account" class="form-label">Расчетный счет *</label>
                    <input id="bank_account" type="text" 
                           class="form-control @error('bank_account') is-invalid @enderror" 
                           name="bank_account" value="{{ old('bank_account') }}" 
                           required pattern="\d{20}" maxlength="20">
                    @error('bank_account')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- БИК -->
                <div class="mb-3">
                    <label for="bik" class="form-label">БИК *</label>
                    <input id="bik" type="text" 
                           class="form-control @error('bik') is-invalid @enderror" 
                           name="bik" value="{{ old('bik') }}" 
                           required pattern="\d{9}" maxlength="9">
                    @error('bik')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Корреспондентский счет -->
                <div class="mb-3">
                    <label for="correspondent_account" class="form-label">Корреспондентский счет *</label>
                    <input id="correspondent_account" type="text" 
                           class="form-control @error('correspondent_account') is-invalid @enderror" 
                           name="correspondent_account" value="{{ old('correspondent_account') }}" 
                           required pattern="\d{20}" maxlength="20">
                    @error('correspondent_account')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Блок контактной информации -->
        <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-person-badge me-2"></i> Контактная информация
            </div>
            <div class="card-body">
                <!-- Director -->
                <div class="mb-3">
                    <label for="director" class="form-label">ФИО директора *</label>
                    <input id="director" type="text" 
                           class="form-control @error('director') is-invalid @enderror" 
                           name="director" value="{{ old('director') }}" required>
                    @error('director')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Manager -->
                <div class="mb-3">
                    <label for="manager" class="form-label">Контактное лицо (менеджер)</label>
                    <input id="manager" type="text" 
                           class="form-control @error('manager') is-invalid @enderror" 
                           name="manager" value="{{ old('manager') }}">
                    @error('manager')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input id="email" type="email" 
                           class="form-control @error('email') is-invalid @enderror" 
                           name="email" value="{{ old('email') }}" required>
                    @error('email')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Phone -->
                <div class="mb-3">
                    <label for="phone" class="form-label">Телефон *</label>
                    <input id="phone" type="tel" 
                           class="form-control @error('phone') is-invalid @enderror" 
                           name="phone" value="{{ old('phone') }}" required>
                    @error('phone')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">Пароль *</label>
                    <input id="password" type="password" 
                           class="form-control @error('password') is-invalid @enderror" 
                           name="password" required>
                    @error('password')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Подтверждение пароля *</label>
                    <input id="password_confirmation" type="password" 
                           class="form-control" 
                           name="password_confirmation" required>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('login') }}" class="text-primary">
                Уже есть аккаунт?
            </a>
            
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-building-check me-2"></i> Зарегистрировать компанию
            </button>
        </div>
    </form>

    @section('scripts')
    <script>
        // Автозаполнение фактического адреса
        document.getElementById('same_address').addEventListener('change', function() {
            if (this.checked) {
                const legalAddress = document.getElementById('legal_address').value;
                document.getElementById('actual_address').value = legalAddress;
            }
        });

        // Маска для телефона
        document.getElementById('phone').addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,1})(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
            e.target.value = !x[2] ? x[1] : '+' + x[1] + ' (' + x[2] + ') ' + x[3] + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '');
        });
    </script>
    @endsection
</x-auth-layout>