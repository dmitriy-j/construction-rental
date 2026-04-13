{{-- resources/views/admin/markups/form.blade.php --}}
@extends('layouts.app')

@section('title', isset($markup) ? 'Редактирование наценки' : 'Создание наценки')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ isset($markup) ? 'Редактирование наценки' : 'Создание новой наценки' }}</h5>
                </div>
                <div class="card-body">
                    <form method="POST"
                          action="{{ isset($markup) ? route('markups.update', $markup) : route('markups.store') }}">
                        @csrf
                        @if(isset($markup))
                            @method('PUT')
                        @endif

                        <div class="row g-3">
                            <!-- Основные настройки -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Основные настройки</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Платформа -->
                                        <div class="mb-3">
                                            <label class="form-label">Платформа <span class="text-danger">*</span></label>
                                            <select name="platform_id" class="form-select" required>
                                                @foreach($platforms as $platform)
                                                    <option value="{{ $platform->id }}"
                                                        {{ (isset($markup) && $markup->platform_id == $platform->id) ? 'selected' : '' }}>
                                                        {{ $platform->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Контекст применения -->
                                        <div class="mb-3">
                                            <label class="form-label">Контекст применения <span class="text-danger">*</span></label>
                                            <select name="entity_type" class="form-select" required>
                                                <option value="">Выберите контекст</option>
                                                <option value="order" {{ (isset($markup) && $markup->entity_type == 'order') ? 'selected' : '' }}>
                                                    Заказы
                                                </option>
                                                <option value="rental_request" {{ (isset($markup) && $markup->entity_type == 'rental_request') ? 'selected' : '' }}>
                                                    Заявки на аренду
                                                </option>
                                                <option value="proposal" {{ (isset($markup) && $markup->entity_type == 'proposal') ? 'selected' : '' }}>
                                                    Предложения
                                                </option>
                                            </select>
                                            <div class="form-text">
                                                Определяет в каких процессах применяется наценка
                                            </div>
                                        </div>

                                        <!-- Тип наценки -->
                                        <div class="mb-3">
                                            <label class="form-label">Тип наценки <span class="text-danger">*</span></label>
                                            <select name="type" class="form-select" id="markupType" required>
                                                <option value="">Выберите тип</option>
                                                <option value="fixed" {{ (isset($markup) && $markup->type == 'fixed') ? 'selected' : '' }}>
                                                    Фиксированная (₽/час)
                                                </option>
                                                <option value="percent" {{ (isset($markup) && $markup->type == 'percent') ? 'selected' : '' }}>
                                                    Процентная (%)
                                                </option>
                                                <option value="tiered" {{ (isset($markup) && $markup->type == 'tiered') ? 'selected' : '' }}>
                                                    Ступенчатая
                                                </option>
                                                <option value="combined" {{ (isset($markup) && $markup->type == 'combined') ? 'selected' : '' }}>
                                                    Комбинированная
                                                </option>
                                                <option value="seasonal" {{ (isset($markup) && $markup->type == 'seasonal') ? 'selected' : '' }}>
                                                    Сезонная
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Значение наценки -->
                                        <div class="mb-3">
                                            <label class="form-label" id="valueLabel">
                                                Значение наценки <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" name="value" class="form-control"
                                                   step="0.01" min="0"
                                                   value="{{ $markup->value ?? old('value') }}"
                                                   required>
                                            <div class="form-text" id="valueHelp">
                                                Введите значение наценки
                                            </div>
                                        </div>

                                        <!-- Тип расчета -->
                                        <div class="mb-3">
                                            <label class="form-label">Тип расчета <span class="text-danger">*</span></label>
                                            <div class="mb-3">
                                                <label class="form-label">Приоритет <span class="text-danger">*</span></label>
                                                <input type="number" name="priority" class="form-control"
                                                    value="{{ old('priority', $markup->priority ?? 0) }}"
                                                    min="0" max="999" step="1" required>
                                                <div class="form-text">
                                                    Чем выше число, тем выше приоритет наценки.
                                                    <strong>Общие наценки:</strong> 0-99,
                                                    <strong>Компании:</strong> 100-199,
                                                    <strong>Категории:</strong> 200-299,
                                                    <strong>Оборудование:</strong> 300-399
                                                </div>
                                                @error('priority')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <select name="calculation_type" class="form-select" required>
                                                <option value="addition" {{ (isset($markup) && $markup->calculation_type == 'addition') ? 'selected' : '' }}>
                                                    Сложение (цена + наценка)
                                                </option>
                                                <option value="multiplication" {{ (isset($markup) && $markup->calculation_type == 'multiplication') ? 'selected' : '' }}>
                                                    Умножение (цена * коэффициент)
                                                </option>
                                                <option value="complex" {{ (isset($markup) && $markup->calculation_type == 'complex') ? 'selected' : '' }}>
                                                    Сложный расчет
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Область применения -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Область применения</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Тип сущности -->
                                        <div class="mb-3">
                                            <label class="form-label">Применить к</label>
                                            <select name="markupable_type" class="form-select" id="markupableType">
                                                <option value="">Общая наценка (для всех)</option>
                                                <option value="App\Models\Equipment"
                                                    {{ (isset($markup) && $markup->markupable_type == 'App\Models\Equipment') ? 'selected' : '' }}>
                                                    Конкретное оборудование
                                                </option>
                                                <option value="App\Models\Category"
                                                    {{ (isset($markup) && $markup->markupable_type == 'App\Models\Category') ? 'selected' : '' }}>
                                                    Категория оборудования
                                                </option>
                                                <option value="App\Models\Company"
                                                    {{ (isset($markup) && $markup->markupable_type == 'App\Models\Company') ? 'selected' : '' }}>
                                                    Компания
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Выбор сущности -->
                                        <div class="mb-3" id="markupableIdContainer" style="display: none;">
                                            <label class="form-label" id="markupableIdLabel">Выберите</label>
                                            <select name="markupable_id" class="form-select" id="markupableId">
                                                <!-- Динамически заполняется через JavaScript -->
                                            </select>
                                        </div>

                                        <!-- Статус -->
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_active"
                                                       value="1" id="isActive"
                                                       {{ (isset($markup) ? $markup->is_active : true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="isActive">
                                                    Активная наценка
                                                </label>
                                            </div>
                                            <div class="form-text">
                                                Неактивные наценки не применяются в расчетах
                                            </div>
                                        </div>

                                        <!-- Период действия -->
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label">Действует с</label>
                                                <input type="date" name="valid_from" class="form-control"
                                                       value="{{ isset($markup) && $markup->valid_from ? $markup->valid_from->format('Y-m-d') : '' }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Действует до</label>
                                                <input type="date" name="valid_to" class="form-control"
                                                       value="{{ isset($markup) && $markup->valid_to ? $markup->valid_to->format('Y-m-d') : '' }}">
                                            </div>
                                            <div class="col-12">
                                                <div class="form-text">
                                                    Оставьте пустыми для постоянного действия
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Дополнительные правила -->
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Дополнительные правила</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="additionalRules">
                                            <!-- Динамически меняется в зависимости от типа наценки -->
                                            @if(isset($markup) && !empty($markup->rules))
                                                <div class="alert alert-info">
                                                    Правила загружены из базы данных
                                                </div>
                                            @else
                                                <div class="text-muted">
                                                    Дополнительные правила появятся после выбора типа наценки
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Кнопки -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('markups.index') }}" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Назад к списку
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i>
                                        {{ isset($markup) ? 'Обновить наценку' : 'Создать наценку' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Данные для динамического заполнения
const entities = {
    'App\\Models\\Equipment': @json($equipment->pluck('title', 'id')),
    'App\\Models\\Category': @json($categories->pluck('name', 'id')),
    'App\\Models\\Company': @json($companies->pluck('legal_name', 'id'))
};

const labels = {
    'App\\Models\\Equipment': 'Оборудование',
    'App\\Models\\Category': 'Категория',
    'App\\Models\\Company': 'Компания'
};

// Обработчик изменения типа сущности
document.getElementById('markupableType').addEventListener('change', function() {
    const container = document.getElementById('markupableIdContainer');
    const select = document.getElementById('markupableId');
    const label = document.getElementById('markupableIdLabel');

    if (this.value) {
        container.style.display = 'block';
        label.textContent = 'Выберите ' + (labels[this.value] || 'объект');

        // Очищаем и заполняем select
        select.innerHTML = '';
        const entitiesList = entities[this.value];

        if (entitiesList && Object.keys(entitiesList).length > 0) {
            for (const [id, name] of Object.entries(entitiesList)) {
                const option = document.createElement('option');
                option.value = id;
                option.textContent = name;
                select.appendChild(option);
            }
        } else {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Нет доступных записей';
            select.appendChild(option);
        }
    } else {
        container.style.display = 'none';
        select.innerHTML = '';
    }
});

// Обработчик изменения типа наценки
document.getElementById('markupType').addEventListener('change', function() {
    const valueLabel = document.getElementById('valueLabel');
    const valueHelp = document.getElementById('valueHelp');
    const rulesDiv = document.getElementById('additionalRules');

    switch(this.value) {
        case 'fixed':
            valueLabel.innerHTML = 'Фиксированная сумма (₽/час) <span class="text-danger">*</span>';
            valueHelp.textContent = 'Сумма в рублях, которая добавляется за каждый час аренды';
            rulesDiv.innerHTML = '<p class="text-muted">Для фиксированной наценки дополнительные правила не требуются</p>';
            break;

        case 'percent':
            valueLabel.innerHTML = 'Процент наценки (%) <span class="text-danger">*</span>';
            valueHelp.textContent = 'Процент от базовой цены, который добавляется к стоимости';
            rulesDiv.innerHTML = '<p class="text-muted">Для процентной наценки дополнительные правила не требуются</p>';
            break;

        case 'tiered':
            valueLabel.innerHTML = 'Базовое значение <span class="text-danger">*</span>';
            valueHelp.textContent = 'Значение по умолчанию, если не подходят ступени';
            rulesDiv.innerHTML = `
                <div class="row g-3">
                    <div class="col-12">
                        <h6>Настройка ступеней</h6>
                        <div id="tiersContainer">
                            <div class="tier-item mb-3 p-3 border rounded">
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <label>Мин. часы</label>
                                        <input type="number" name="rules[tiers][0][min]" class="form-control" value="0" min="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Макс. часы</label>
                                        <input type="number" name="rules[tiers][0][max]" class="form-control" value="100" min="1">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Тип значения</label>
                                        <select name="rules[tiers][0][type]" class="form-select">
                                            <option value="fixed">Фиксированная</option>
                                            <option value="percent">Процентная</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Значение</label>
                                        <input type="number" name="rules[tiers][0][value]" class="form-control" value="50" step="0.01">
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeTier(this)">Удалить</button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addTier()">+ Добавить ступень</button>
                    </div>
                </div>
            `;
            break;

        case 'combined':
            valueLabel.innerHTML = 'Базовое значение <span class="text-danger">*</span>';
            valueHelp.textContent = 'Опорное значение для расчета';
            rulesDiv.innerHTML = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <label>Фиксированная часть (₽/час)</label>
                        <input type="number" name="rules[fixed_value]" class="form-control" value="50" step="0.01">
                    </div>
                    <div class="col-md-6">
                        <label>Процентная часть (%)</label>
                        <input type="number" name="rules[percent_value]" class="form-control" value="10" step="0.01">
                    </div>
                </div>
            `;
            break;

        case 'seasonal':
            valueLabel.innerHTML = 'Базовый процент (%) <span class="text-danger">*</span>';
            valueHelp.textContent = 'Базовый процент наценки, который умножается на сезонный коэффициент';
            rulesDiv.innerHTML = `
                <div class="row g-3">
                    <div class="col-md-4">
                        <label>Коэффициент высокого сезона</label>
                        <input type="number" name="rules[high_season_coefficient]" class="form-control" value="1.5" step="0.1">
                        <div class="form-text">Май-Сентябрь</div>
                    </div>
                    <div class="col-md-4">
                        <label>Коэффициент среднего сезона</label>
                        <input type="number" name="rules[medium_season_coefficient]" class="form-control" value="1.0" step="0.1">
                        <div class="form-text">Март-Апрель, Октябрь</div>
                    </div>
                    <div class="col-md-4">
                        <label>Коэффициент низкого сезона</label>
                        <input type="number" name="rules[low_season_coefficient]" class="form-control" value="0.7" step="0.1">
                        <div class="form-text">Ноябрь-Февраль</div>
                    </div>
                </div>
            `;
            break;

        default:
            valueLabel.innerHTML = 'Значение наценки <span class="text-danger">*</span>';
            valueHelp.textContent = 'Введите значение наценки';
            rulesDiv.innerHTML = '<p class="text-muted">Выберите тип наценки для настройки правил</p>';
    }
});

// Функции для ступенчатых наценок
let tierCount = 1;

function addTier() {
    const container = document.getElementById('tiersContainer');
    const newTier = document.createElement('div');
    newTier.className = 'tier-item mb-3 p-3 border rounded';
    newTier.innerHTML = `
        <div class="row g-2">
            <div class="col-md-3">
                <label>Мин. часы</label>
                <input type="number" name="rules[tiers][${tierCount}][min]" class="form-control" value="${tierCount * 100}" min="0">
            </div>
            <div class="col-md-3">
                <label>Макс. часы</label>
                <input type="number" name="rules[tiers][${tierCount}][max]" class="form-control" value="${(tierCount + 1) * 100}" min="1">
            </div>
            <div class="col-md-3">
                <label>Тип значения</label>
                <select name="rules[tiers][${tierCount}][type]" class="form-select">
                    <option value="fixed">Фиксированная</option>
                    <option value="percent">Процентная</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>Значение</label>
                <input type="number" name="rules[tiers][${tierCount}][value]" class="form-control" value="${50 + tierCount * 10}" step="0.01">
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeTier(this)">Удалить</button>
    `;
    container.appendChild(newTier);
    tierCount++;
}

function removeTier(button) {
    button.closest('.tier-item').remove();
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    // Триггерим изменения для инициализации
    document.getElementById('markupableType').dispatchEvent(new Event('change'));
    document.getElementById('markupType').dispatchEvent(new Event('change'));

    // Восстанавливаем выбранную сущность если редактируем
    @if(isset($markup) && $markup->markupable_id)
        const markupableSelect = document.getElementById('markupableId');
        if (markupableSelect) {
            setTimeout(() => {
                markupableSelect.value = '{{ $markup->markupable_id }}';
            }, 100);
        }
    @endif
});

// Валидация формы перед отправкой
document.querySelector('form').addEventListener('submit', function(e) {
    if (!validateForm(this)) {
        e.preventDefault();
        showFormErrors(this);
    }
});

function validateForm(form) {
    const markupType = form.querySelector('#markupType').value;
    const rules = getFormRules(form);
    const isValid = validateRules(markupType, rules);

    // Дополнительная валидация дат
    const validFrom = form.querySelector('input[name="valid_from"]').value;
    const validTo = form.querySelector('input[name="valid_to"]').value;

    if (validFrom && validTo && new Date(validFrom) > new Date(validTo)) {
        showFieldError('valid_to', 'Дата "Действует до" не может быть раньше даты "Действует с"');
        return false;
    }

    return isValid;
}

function getFormRules(form) {
    const rules = {};
    const rulesInputs = form.querySelectorAll('[name^="rules"]');

    rulesInputs.forEach(input => {
        const name = input.name.replace('rules[', '').replace(']', '');
        const value = input.type === 'number' ? parseFloat(input.value) : input.value;
        setNestedValue(rules, name, value);
    });

    return rules;
}

function setNestedValue(obj, path, value) {
    const keys = path.split('][').join('.').split('.');
    let current = obj;

    for (let i = 0; i < keys.length - 1; i++) {
        const key = keys[i];
        if (!current[key]) current[key] = {};
        current = current[key];
    }

    current[keys[keys.length - 1]] = value;
}

function validateRules(type, rules) {
    switch(type) {
        case 'tiered':
            return validateTieredRules(rules);
        case 'combined':
            return validateCombinedRules(rules);
        case 'seasonal':
            return validateSeasonalRules(rules);
        default:
            return true;
    }
}

function validateTieredRules(rules) {
    if (!rules.tiers || !Array.isArray(rules.tiers) || rules.tiers.length === 0) {
        showGlobalError('Для ступенчатой наценки необходимо указать хотя бы одну ступень');
        return false;
    }

    let previousMax = -1;

    for (let i = 0; i < rules.tiers.length; i++) {
        const tier = rules.tiers[i];

        if (!tier.min || !tier.max || !tier.type || !tier.value) {
            showGlobalError(`Заполните все поля для ступени ${i + 1}`);
            return false;
        }

        if (parseInt(tier.min) >= parseInt(tier.max)) {
            showGlobalError(`В ступени ${i + 1} минимальное значение должно быть меньше максимального`);
            return false;
        }

        if (parseInt(tier.min) <= previousMax) {
            showGlobalError(`Ступени должны идти последовательно без перекрытий (ступень ${i + 1})`);
            return false;
        }

        previousMax = parseInt(tier.max);

        if (tier.type === 'percent' && (tier.value < 0 || tier.value > 1000)) {
            showGlobalError(`Процентное значение в ступени ${i + 1} должно быть между 0 и 1000`);
            return false;
        }
    }

    return true;
}

function validateCombinedRules(rules) {
    if (!rules.fixed_value || !rules.percent_value) {
        showGlobalError('Для комбинированной наценки необходимо указать оба значения');
        return false;
    }

    if (rules.percent_value < 0 || rules.percent_value > 1000) {
        showGlobalError('Процентное значение должно быть между 0 и 1000');
        return false;
    }

    if (rules.fixed_value < 0) {
        showGlobalError('Фиксированное значение не может быть отрицательным');
        return false;
    }

    return true;
}

function validateSeasonalRules(rules) {
    const requiredFields = ['high_season_coefficient', 'medium_season_coefficient', 'low_season_coefficient'];

    for (const field of requiredFields) {
        if (!rules[field] || rules[field] < 0.1) {
            showGlobalError('Все сезонные коэффициенты должны быть указаны и не менее 0.1');
            return false;
        }
    }

    return true;
}

function showGlobalError(message) {
    // Создаем или обновляем глобальный алерт ошибки
    let alertDiv = document.getElementById('global-form-error');

    if (!alertDiv) {
        alertDiv = document.createElement('div');
        alertDiv.id = 'global-form-error';
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        alertDiv.innerHTML = `
            <strong>Ошибка валидации:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        const form = document.querySelector('form');
        form.parentNode.insertBefore(alertDiv, form);
    } else {
        alertDiv.querySelector('strong').nextSibling.textContent = message;
    }

    // Прокручиваем к ошибке
    alertDiv.scrollIntoView({ behavior: 'smooth' });
}

function showFieldError(fieldName, message) {
    const field = document.querySelector(`[name="${fieldName}"]`);
    if (field) {
        // Добавляем класс ошибки
        field.classList.add('is-invalid');

        // Создаем или обновляем сообщение об ошибке
        let errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            field.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    }
}

function showFormErrors(form) {
    // Убираем предыдущие ошибки
    const previousErrors = form.querySelectorAll('.is-invalid, .invalid-feedback');
    previousErrors.forEach(el => {
        if (el.classList.contains('is-invalid')) {
            el.classList.remove('is-invalid');
        } else {
            el.remove();
        }
    });
}
</script>
@endpush
