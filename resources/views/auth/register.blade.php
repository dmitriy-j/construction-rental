<x-auth-layout title="Регистрация компании">
    <!-- Общие ошибки валидации -->
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Session Status -->
    @if(session('status'))
        <div class="alert alert-success mb-4">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('register.company.store') }}">
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
                               id="type_lessee" value="lessee" {{ old('type', 'lessee') == 'lessee' ? 'checked' : '' }}>
                        <label class="form-check-label" for="type_lessee">
                            Арендатор (Tenant)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="type"
                               id="type_lessor" value="lessor" {{ old('type') == 'lessor' ? 'checked' : '' }}>
                        <label class="form-check-label" for="type_lessor">
                            Арендодатель (Landlord)
                        </label>
                    </div>
                    @error('type')
                    <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Система налогообложения *</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="tax_system"
                               id="tax_vat" value="vat" {{ old('tax_system', 'vat') == 'vat' ? 'checked' : '' }}>
                        <label class="form-check-label" for="tax_vat">
                            С НДС
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="tax_system"
                               id="tax_no_vat" value="no_vat" {{ old('tax_system') == 'no_vat' ? 'checked' : '' }}>
                        <label class="form-check-label" for="tax_no_vat">
                            Без НДС
                        </label>
                    </div>
                    @error('tax_system')
                    <div class="text-danger small">{{ $message }}</div>
                    @enderror
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
                    <label for="legal_name" class="form-label">Название компании *</label>
                    <input id="legal_name" type="text"
                           class="form-control @error('legal_name') is-invalid @enderror"
                           name="legal_name" value="{{ old('legal_name') }}" required>
                    @error('legal_name')
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
                           required pattern="\d{10}" maxlength="10">
                    @error('inn')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- КПП -->
                <div class="mb-3">
                    <label for="kpp" class="form-label">КПП *</label>
                    <input id="kpp" type="text"
                           class="form-control @error('kpp') is-invalid @enderror"
                           name="kpp" value="{{ old('kpp') }}"
                           required pattern="\d{9}" maxlength="9">
                    @error('kpp')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- ОГРН -->
                <div class="mb-3">
                    <label for="ogrn" class="form-label">ОГРН *</label>
                    <input id="ogrn" type="text"
                           class="form-control @error('ogrn') is-invalid @enderror"
                           name="ogrn" value="{{ old('ogrn') }}"
                           required pattern="\d{13}" maxlength="13">
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
                           id="same_as_legal" name="same_as_legal" value="1" {{ old('same_as_legal') ? 'checked' : '' }}>
                    <label class="form-check-label" for="same_as_legal">
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
                    <label for="correspondent_account" class="form-label">Корреспондентский счет</label>
                    <input id="correspondent_account" type="text"
                           class="form-control @error('correspondent_account') is-invalid @enderror"
                           name="correspondent_account" value="{{ old('correspondent_account') }}"
                           pattern="\d{20}" maxlength="20">
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
                    <label for="director_name" class="form-label">ФИО директора *</label>
                    <input id="director_name" type="text"
                           class="form-control @error('director_name') is-invalid @enderror"
                           name="director_name" value="{{ old('director_name') }}" required>
                    @error('director_name')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Contacts (Manager) -->
                <div class="mb-3">
                    <label for="contacts" class="form-label">Контактное лицо (менеджер)</label>
                    <input id="contacts" type="text"
                           class="form-control @error('contacts') is-invalid @enderror"
                           name="contacts" value="{{ old('contacts') }}">
                    @error('contacts')
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
    </script>
    @endsection
</x-auth-layout>
