{{-- resources/views/admin/markups/form.blade.php --}}
@extends('layouts.app')

@section('title', isset($markup) ? 'Редактирование наценки' : 'Создание наценки')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ isset($markup) ? 'Редактирование наценки' : 'Создание новой наценки' }}</h5>
                </div>
                <div class="card-body">
                    <form method="POST"
                          action="{{ isset($markup) ? route('markups.update', $markup) : route('markups.store') }}">
                        @csrf
                        @if(isset($markup)) @method('PUT') @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light"><h6 class="mb-0">Основные настройки</h6></div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Платформа <span class="text-danger">*</span></label>
                                            <select name="platform_id" class="form-select" required>
                                                @foreach($platforms as $p)
                                                    <option value="{{ $p->id }}" {{ (isset($markup) && $markup->platform_id == $p->id) ? 'selected' : '' }}>{{ $p->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Контекст применения <span class="text-danger">*</span></label>
                                            <select name="entity_type" class="form-select" id="entityType" required>
                                                <option value="">— выберите —</option>
                                                <option value="order" {{ (isset($markup) && $markup->entity_type == 'order') ? 'selected' : '' }}>Заказы (каталог)</option>
                                                <option value="rental_request" {{ (isset($markup) && $markup->entity_type == 'rental_request') ? 'selected' : '' }}>Заявки на аренду</option>
                                                <option value="proposal" {{ (isset($markup) && $markup->entity_type == 'proposal') ? 'selected' : '' }}>Предложения</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Тип наценки <span class="text-danger">*</span></label>
                                            <select name="type" class="form-select" id="markupType" required>
                                                <option value="">— выберите —</option>
                                                <option value="fixed" {{ (isset($markup) && $markup->type == 'fixed') ? 'selected' : '' }}>Фиксированная (₽/час)</option>
                                                <option value="percent" {{ (isset($markup) && $markup->type == 'percent') ? 'selected' : '' }}>Процентная (%)</option>
                                                <option value="tiered" {{ (isset($markup) && $markup->type == 'tiered') ? 'selected' : '' }}>Ступенчатая</option>
                                                <option value="combined" {{ (isset($markup) && $markup->type == 'combined') ? 'selected' : '' }}>Комбинированная</option>
                                                <option value="seasonal" {{ (isset($markup) && $markup->type == 'seasonal') ? 'selected' : '' }}>Сезонная</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" id="valueLabel">Значение наценки <span class="text-danger">*</span></label>
                                            <input type="number" name="value" class="form-control" step="0.01" min="0"
                                                   value="{{ $markup->value ?? old('value') }}" required>
                                            <div class="form-text" id="valueHelp">Введите значение наценки</div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Тип расчета</label>
                                            <select name="calculation_type" class="form-select" required>
                                                <option value="addition" {{ (isset($markup) && $markup->calculation_type == 'addition') ? 'selected' : '' }}>Сложение (цена + наценка)</option>
                                                <option value="multiplication" {{ (isset($markup) && $markup->calculation_type == 'multiplication') ? 'selected' : '' }}>Умножение (цена × коэффициент)</option>
                                                <option value="complex" {{ (isset($markup) && $markup->calculation_type == 'complex') ? 'selected' : '' }}>Сложный</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Приоритет</label>
                                            <input type="number" name="priority" class="form-control"
                                                   value="{{ old('priority', $markup->priority ?? 0) }}" min="0" max="999">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light"><h6 class="mb-0">Область применения</h6></div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Применить к</label>
                                            <select name="markupable_type" class="form-select" id="markupableType">
                                                @php
                                                    // Собираем опции — null ставим первым
                                                    $typeOptions = ['' => 'Общая наценка (для всех)'];
                                                    foreach ($markupableTypes as $class => $label) {
                                                        if ($class !== null) {
                                                            $typeOptions[$class] = $label;
                                                        }
                                                    }
                                                @endphp
                                                @foreach($typeOptions as $val => $label)
                                                    <option value="{{ $val }}"
                                                        {{ (!isset($markup) && $val === '') ? 'selected' : '' }}
                                                        {{ (isset($markup) && $markup->markupable_type === $val) ? 'selected' : '' }}
                                                        {{ (isset($markup) && $val === '' && $markup->markupable_type === null) ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3" id="markupableIdContainer" style="display: none;">
                                            <label class="form-label" id="markupableIdLabel">Выберите</label>
                                            <select name="markupable_id" class="form-select" id="markupableId"></select>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                                                    {{ (isset($markup) ? $markup->is_active : true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="isActive">Активна</label>
                                            </div>
                                        </div>

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
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-light"><h6 class="mb-0">Дополнительные правила</h6></div>
                                    <div class="card-body">
                                        <div id="additionalRules"><div class="text-muted">Выберите тип наценки</div></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('markups.index') }}" class="btn btn-secondary">← Назад</a>
                                    <button type="submit" class="btn btn-primary">{{ isset($markup) ? 'Обновить' : 'Создать' }}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    // Данные для JS: {id: "название"}
    $entityDataJS = [
        'App\\Models\\Equipment'     => $equipment->pluck('title', 'id'),
        'App\\Models\\Category'      => $categories->pluck('name', 'id'),
        'App\\Models\\Company'       => $companies->pluck('legal_name', 'id'),
        'App\\Models\\RentalRequest' => $rentalRequests->mapWithKeys(fn($r) => [$r->id => "#{$r->id} — {$r->title}"]),
    ];
@endphp

@push('scripts')
<script>
const entityData = @json($entityDataJS);
const entityLabels = {
    'App\\Models\\Equipment': 'Оборудование',
    'App\\Models\\Category': 'Категорию',
    'App\\Models\\Company': 'Компанию',
    'App\\Models\\RentalRequest': 'Заявку'
};

document.getElementById('markupableType').addEventListener('change', function() {
    const container = document.getElementById('markupableIdContainer');
    const select = document.getElementById('markupableId');
    const label = document.getElementById('markupableIdLabel');

    if (!this.value) {
        container.style.display = 'none';
        select.innerHTML = '';
        return;
    }

    container.style.display = 'block';
    label.textContent = 'Выберите ' + (entityLabels[this.value] || 'объект');
    select.innerHTML = '';

    const items = entityData[this.value] || {};
    const keys = Object.keys(items);

    if (keys.length === 0) {
        select.innerHTML = '<option value="">Нет записей</option>';
        return;
    }

    keys.forEach(id => {
        const opt = document.createElement('option');
        opt.value = id;
        opt.textContent = items[id];
        select.appendChild(opt);
    });
});

// Смена типа наценки
document.getElementById('markupType').addEventListener('change', function() {
    const vLabel = document.getElementById('valueLabel');
    const vHelp = document.getElementById('valueHelp');
    const rulesDiv = document.getElementById('additionalRules');

    const rules = {
        fixed:   {label: 'Сумма в ₽ за час', help: 'Фиксированная сумма, добавляемая за каждый час', html: '<p class="text-muted">Доп. правила не нужны</p>'},
        percent: {label: 'Процент наценки (%)', help: 'Процент от цены', html: '<p class="text-muted">Доп. правила не нужны</p>'},
        tiered:  {label: 'Значение по умолчанию', help: 'Используется если ни одна ступень не подошла', html: `
            <div class="row g-3"><div class="col-12"><h6>Ступени</h6><div id="tiersContainer">` + tierHtml(0,0,100,'fixed',50) +
            `</div><button type="button" class="btn btn-sm btn-outline-primary" onclick="addTier()">+ Ступень</button></div></div>`},
        combined: {label: 'Опорное значение', help: 'Для расчёта', html: `
            <div class="row g-3"><div class="col-md-6"><label>Фикс. часть (₽/ч)</label>
            <input type="number" name="rules[fixed_value]" class="form-control" value="50" step="0.01"></div>
            <div class="col-md-6"><label>% часть</label>
            <input type="number" name="rules[percent_value]" class="form-control" value="10" step="0.01"></div></div>`},
        seasonal: {label: 'Базовый %', help: 'Умножается на коэффициент', html: `
            <div class="row g-3"><div class="col-md-4"><label>Высокий сезон</label>
            <input type="number" name="rules[high_season_coefficient]" class="form-control" value="1.5" step="0.1">
            <div class="form-text">Май-Сент</div></div>
            <div class="col-md-4"><label>Средний сезон</label>
            <input type="number" name="rules[medium_season_coefficient]" class="form-control" value="1.0" step="0.1">
            <div class="form-text">Март-Апр, Окт</div></div>
            <div class="col-md-4"><label>Низкий сезон</label>
            <input type="number" name="rules[low_season_coefficient]" class="form-control" value="0.7" step="0.1">
            <div class="form-text">Ноя-Фев</div></div></div>`}
    };

    const cfg = rules[this.value] || {label: 'Значение наценки', help: 'Введите значение', html: '<p class="text-muted">Выберите тип</p>'};
    vLabel.innerHTML = cfg.label + ' <span class="text-danger">*</span>';
    vHelp.textContent = cfg.help;
    rulesDiv.innerHTML = cfg.html;
});

function tierHtml(i, min, max, type, val) {
    return `<div class="tier-item mb-3 p-3 border rounded">
        <div class="row g-2">
            <div class="col-md-3"><label>Мин. часы</label><input type="number" name="rules[tiers][${i}][min]" class="form-control" value="${min}"></div>
            <div class="col-md-3"><label>Макс. часы</label><input type="number" name="rules[tiers][${i}][max]" class="form-control" value="${max}"></div>
            <div class="col-md-3"><label>Тип</label>
                <select name="rules[tiers][${i}][type]" class="form-select">
                    <option value="fixed" ${type==='fixed'?'selected':''}>Фиксированная</option>
                    <option value="percent" ${type==='percent'?'selected':''}>Процентная</option>
                </select>
            </div>
            <div class="col-md-3"><label>Значение</label><input type="number" name="rules[tiers][${i}][value]" class="form-control" value="${val}" step="0.01"></div>
        </div>
        <button type="button" class="btn btn-sm btn-danger mt-2" onclick="this.closest('.tier-item').remove()">✕</button>
    </div>`;
}
let tierCount = 1;
function addTier() {
    const c = document.getElementById('tiersContainer');
    const d = document.createElement('div');
    d.innerHTML = tierHtml(tierCount, tierCount*100, (tierCount+1)*100, 'fixed', 50+tierCount*10);
    c.appendChild(d.firstElementChild);
    tierCount++;
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('markupableType').dispatchEvent(new Event('change'));
    document.getElementById('markupType').dispatchEvent(new Event('change'));
    @if(isset($markup) && $markup->markupable_id)
        setTimeout(() => {
            const sel = document.getElementById('markupableId');
            if (sel) sel.value = '{{ $markup->markupable_id }}';
        }, 100);
    @endif
});
</script>
@endpush
@endsection
