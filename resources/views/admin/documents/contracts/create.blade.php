@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Добавление договора</h1>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('admin.contracts.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Назад к списку
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.contracts.store') }}" method="POST" enctype="multipart/form-data" id="contract-form">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="number">Номер договора *</label>
                            <input type="text" class="form-control @error('number') is-invalid @enderror"
                                   id="number" name="number" value="{{ old('number') }}" required>
                            @error('number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="file">Файл договора</label>
                            <input type="file" class="form-control-file @error('file') is-invalid @enderror"
                                   id="file" name="file" accept=".pdf,.doc,.docx">
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Поддерживаемые форматы: PDF, DOC, DOCX (макс. 10MB)
                            </small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Описание</label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                              id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="counterparty_type">Тип контрагента *</label>
                            <select class="form-control @error('counterparty_type') is-invalid @enderror"
                                    id="counterparty_type" name="counterparty_type" required>
                                <option value="">Выберите тип контрагента</option>
                                <option value="lessor" {{ old('counterparty_type') == 'lessor' ? 'selected' : '' }}>Арендодатель</option>
                                <option value="lessee" {{ old('counterparty_type') == 'lessee' ? 'selected' : '' }}>Арендатор</option>
                            </select>
                            @error('counterparty_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="counterparty_company_id">Контрагент *</label>
                            <select class="form-control @error('counterparty_company_id') is-invalid @enderror"
                                    id="counterparty_company_id" name="counterparty_company_id" required>
                                <option value="">Сначала выберите тип контрагента</option>
                            </select>
                            @error('counterparty_company_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="start_date">Дата начала *</label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                   id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="end_date">Дата окончания *</label>
                            <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                   id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="payment_type">Тип оплаты *</label>
                            <select class="form-control @error('payment_type') is-invalid @enderror"
                                    id="payment_type" name="payment_type" required>
                                <option value="postpay" {{ old('payment_type') == 'postpay' ? 'selected' : '' }}>Постоплата</option>
                                <option value="prepay" {{ old('payment_type') == 'prepay' ? 'selected' : '' }}>Предоплата</option>
                                <option value="mixed" {{ old('payment_type') == 'mixed' ? 'selected' : '' }}>Смешанная</option>
                            </select>
                            @error('payment_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="penalty_rate">Штрафная ставка (%) *</label>
                            <input type="number" step="0.01" min="0" max="100"
                                   class="form-control @error('penalty_rate') is-invalid @enderror"
                                   id="penalty_rate" name="penalty_rate"
                                   value="{{ old('penalty_rate', 0.30) }}" required>
                            @error('penalty_rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="payment_deadline">Срок оплаты (дни) *</label>
                            <input type="number" class="form-control @error('payment_deadline') is-invalid @enderror"
                                   id="payment_deadline" name="payment_deadline"
                                   value="{{ old('payment_deadline', 7) }}" min="1" required>
                            @error('payment_deadline')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="documentation_deadline">Срок документооборота (дни) *</label>
                            <input type="number" class="form-control @error('documentation_deadline') is-invalid @enderror"
                                   id="documentation_deadline" name="documentation_deadline"
                                   value="{{ old('documentation_deadline', 7) }}" min="1" required>
                            @error('documentation_deadline')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                           {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Активный договор</label>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary" id="submit-btn">
                        <i class="fas fa-save"></i> Создать договор
                    </button>
                    <a href="{{ route('admin.contracts.index') }}" class="btn btn-secondary">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - initializing contract form');

    const counterpartyTypeSelect = document.getElementById('counterparty_type');
    const counterpartyCompanySelect = document.getElementById('counterparty_company_id');
    const contractForm = document.getElementById('contract-form');
    const submitBtn = document.getElementById('submit-btn');

    // Данные о компаниях из контроллера
    const companies = {
        lessor: @json($lessorCompanies ?? []),
        lessee: @json($lesseeCompanies ?? [])
    };

    console.log('Companies data:', companies);

    // Сохраняем старый выбор для восстановления при ошибках валидации
    const oldCounterpartyType = '{{ old('counterparty_type') }}';
    const oldCounterpartyCompanyId = '{{ old('counterparty_company_id') }}';

    function updateCompanies() {
        console.log('Updating companies for type:', counterpartyTypeSelect.value);

        const selectedType = counterpartyTypeSelect.value;
        counterpartyCompanySelect.innerHTML = '<option value="">Выберите контрагента</option>';

        if (selectedType && companies[selectedType]) {
            companies[selectedType].forEach(company => {
                const option = document.createElement('option');
                option.value = company.id;
                option.textContent = `${company.legal_name} (ИНН: ${company.inn})`;

                // Восстанавливаем старый выбор если есть
                if (oldCounterpartyType === selectedType && oldCounterpartyCompanyId == company.id) {
                    option.selected = true;
                }

                counterpartyCompanySelect.appendChild(option);
            });

            console.log(`Loaded ${companies[selectedType].length} companies for type ${selectedType}`);
        } else {
            console.log('No companies found for type:', selectedType);
        }
    }

    counterpartyTypeSelect.addEventListener('change', updateCompanies);

    // Обработчик отправки формы для отладки
    contractForm.addEventListener('submit', function(e) {
        console.log('Form submission started');
        console.log('Form data:', {
            number: document.getElementById('number').value,
            counterparty_type: counterpartyTypeSelect.value,
            counterparty_company_id: counterpartyCompanySelect.value,
            start_date: document.getElementById('start_date').value,
            end_date: document.getElementById('end_date').value
        });

        // Проверка обязательных полей
        if (!counterpartyTypeSelect.value || !counterpartyCompanySelect.value) {
            e.preventDefault();
            alert('Пожалуйста, выберите тип контрагента и конкретную компанию');
            return false;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Создание...';
    });

    // Инициализация при загрузке
    if (oldCounterpartyType) {
        counterpartyTypeSelect.value = oldCounterpartyType;
    }
    updateCompanies();

    console.log('Contract form initialization complete');
});
</script>
@endsection
