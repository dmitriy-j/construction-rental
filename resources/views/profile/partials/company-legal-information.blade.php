{{-- resources/views/profile/partials/company-legal-information.blade.php --}}
@if($user->company)
<div class="card-body">
    <form method="POST" action="{{ route('profile.company-details.update') }}">
        @csrf
        @method('PUT')

        <!-- Тип организации -->
        <div class="mb-3">
            <x-input-label for="legal_type" value="Тип организации *" />
            <select id="legal_type" name="legal_type" class="form-select" required
                    onchange="toggleKppField()">
                <option value="">Выберите тип организации</option>
                <option value="ooo" {{ old('legal_type', $user->company->legal_type) == 'ooo' ? 'selected' : '' }}>ООО</option>
                <option value="ip" {{ old('legal_type', $user->company->legal_type) == 'ip' ? 'selected' : '' }}>ИП</option>
            </select>
            <x-input-error :messages="$errors->get('legal_type')" />
        </div>

        <!-- Остальные поля остаются без изменений -->
        <div class="mb-3">
            <x-input-label for="legal_name" value="Наименование организации *" />
            <x-text-input id="legal_name" name="legal_name" type="text" class="form-control"
                          value="{{ old('legal_name', $user->company->legal_name) }}" required />
            <x-input-error :messages="$errors->get('legal_name')" />
        </div>

        <!-- Условное поле KPP -->
        <div class="mb-3" id="kpp_field"
             style="{{ (old('legal_type', $user->company->legal_type) == 'ip') ? 'display: none;' : '' }}">
            <x-input-label for="kpp" value="КПП *" />
            <x-text-input id="kpp" name="kpp" type="text" class="form-control"
                          value="{{ old('kpp', $user->company->kpp) }}" />
            <x-input-error :messages="$errors->get('kpp')" />
        </div>

        <!-- ИНН с динамической валидацией -->
        <div class="mb-3">
            <x-input-label for="inn" value="ИНН *" />
            <x-text-input id="inn" name="inn" type="text" class="form-control"
                          value="{{ old('inn', $user->company->inn) }}" required
                          maxlength="12" />
            <x-input-error :messages="$errors->get('inn')" />
            <small class="form-text text-muted" id="inn_hint">
                @if($user->company->legal_type == 'ip')
                    Для ИП: 12 цифр
                @else
                    Для ООО: 10 цифр
                @endif
            </small>
        </div>

        <!-- ОГРН с динамической валидацией -->
        <div class="mb-3">
            <x-input-label for="ogrn" value="ОГРН/ОГРНИП *" />
            <x-text-input id="ogrn" name="ogrn" type="text" class="form-control"
                          value="{{ old('ogrn', $user->company->ogrn) }}" required
                          maxlength="15" />
            <x-input-error :messages="$errors->get('ogrn')" />
            <small class="form-text text-muted" id="ogrn_hint">
                @if($user->company->legal_type == 'ip')
                    Для ИП: 15 цифр
                @else
                    Для ООО: 13 цифр
                @endif
            </small>
        </div>

        <!-- ОКПО -->
        <div class="mb-3">
            <x-input-label for="okpo" value="ОКПО" />
            <x-text-input id="okpo" name="okpo" type="text" class="form-control"
                          value="{{ old('okpo', $user->company->okpo) }}" />
            <x-input-error :messages="$errors->get('okpo')" />
        </div>

        <!-- Система налогообложения -->
        <div class="mb-3">
            <x-input-label for="tax_system" value="Система налогообложения *" />
            <select id="tax_system" name="tax_system" class="form-select" required>
                <option value="">Выберите систему налогообложения</option>
                <option value="vat" {{ old('tax_system', $user->company->tax_system) == 'vat' ? 'selected' : '' }}>С НДС</option>
                <option value="no_vat" {{ old('tax_system', $user->company->tax_system) == 'no_vat' ? 'selected' : '' }}>Без НДС</option>
            </select>
            <x-input-error :messages="$errors->get('tax_system')" />
        </div>

        <!-- Юридический адрес -->
        <div class="mb-3">
            <x-input-label for="legal_address" value="Юридический адрес *" />
            <x-text-input id="legal_address" name="legal_address" type="text" class="form-control"
                          value="{{ old('legal_address', $user->company->legal_address) }}" required />
            <x-input-error :messages="$errors->get('legal_address')" />
        </div>

        <!-- Фактический адрес -->
        <div class="mb-3">
            <x-input-label for="actual_address" value="Фактический адрес" />
            <x-text-input id="actual_address" name="actual_address" type="text" class="form-control"
                          value="{{ old('actual_address', $user->company->actual_address) }}" />
            <x-input-error :messages="$errors->get('actual_address')" />
        </div>

        <!-- Чекбокс для совпадения адресов -->
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="same_as_legal"
                   name="same_as_legal" value="1"
                   {{ old('same_as_legal', $user->company->actual_address === $user->company->legal_address ? 'checked' : '') }}>
            <label class="form-check-label" for="same_as_legal">
                Совпадает с юридическим адресом
            </label>
        </div>

        <!-- Директор -->
        <div class="mb-3">
            <x-input-label for="director_name" value="ФИО директора *" />
            <x-text-input id="director_name" name="director_name" type="text" class="form-control"
                          value="{{ old('director_name', $user->company->director_name) }}" required />
            <x-input-error :messages="$errors->get('director_name')" />
        </div>

        <!-- Контактное лицо -->
        <div class="mb-3">
            <x-input-label for="contacts" value="Контактное лицо" />
            <x-text-input id="contacts" name="contacts" type="text" class="form-control"
                          value="{{ old('contacts', $user->company->contacts) }}" />
            <x-input-error :messages="$errors->get('contacts')" />
        </div>

        <!-- Статус компании (только для просмотра) -->
        <div class="mb-3">
            <x-input-label value="Статус компании" />
            <div class="form-control bg-light">
                @if($user->company->status === 'verified')
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle me-1"></i>Верифицирована
                    </span>
                @else
                    <span class="badge bg-warning text-dark">
                        <i class="bi bi-clock me-1"></i>На проверке
                    </span>
                @endif
            </div>
        </div>

        <!-- Кнопка отправки формы -->
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-2"></i>Сохранить изменения
            </button>
        </div>

        <script>
        function toggleKppField() {
            const legalType = document.getElementById('legal_type').value;
            const kppField = document.getElementById('kpp_field');
            const innHint = document.getElementById('inn_hint');
            const ogrnHint = document.getElementById('ogrn_hint');
            const kppInput = document.getElementById('kpp');

            if (legalType === 'ip') {
                kppField.style.display = 'none';
                if (kppInput) {
                    kppInput.removeAttribute('required');
                    kppInput.value = '';
                }
                innHint.textContent = 'Для ИП: 12 цифр';
                ogrnHint.textContent = 'Для ИП: 15 цифр';
            } else {
                kppField.style.display = 'block';
                if (kppInput) {
                    kppInput.setAttribute('required', 'required');
                }
                innHint.textContent = 'Для ООО: 10 цифр';
                ogrnHint.textContent = 'Для ООО: 13 цифр';
            }
        }

        // Обработка чекбокса совпадения адресов
        document.addEventListener('DOMContentLoaded', function() {
            toggleKppField();

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

                // Инициализация при загрузке
                updateActualAddress();
            }
        });
        </script>
    </form>
</div>
@else
<div class="alert alert-warning" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Компания не привязана к вашему аккаунту.
</div>
@endif
