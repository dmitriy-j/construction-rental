{{-- Партиал: форма для одной позиции заявки --}}
@php
    $hasItem = isset($item) && $item !== null;
    $ic = $hasItem ? ($item->individual_conditions ?? []) : [];
@endphp
<div class="item-card mb-3" data-index="{{ $index }}">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Позиция #<span class="item-num">{{ (int)$index + 1 }}</span></strong>
        <div class="d-flex gap-1">
            <button type="button" class="btn btn-sm btn-outline-info" onclick="loadSpecs(this.closest('.item-card').querySelector('.category-select'))" title="Загрузить спецификации">
                <i class="bi bi-arrow-repeat"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label small">Категория <span class="text-danger">*</span></label>
                <select name="items[{{ $index }}][category_id]" class="form-select form-select-sm category-select"
                        onchange="loadSpecs(this)">
                    <option value="">— выберите —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $hasItem && $item->category_id == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Количество</label>
                <input type="number" name="items[{{ $index }}][quantity]" class="form-control form-control-sm"
                       value="{{ old('items.' . $index . '.quantity', $hasItem ? $item->quantity : 1) }}" min="1" step="1">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Ставка (₽/час)</label>
                <input type="number" name="items[{{ $index }}][hourly_rate]" class="form-control form-control-sm"
                       value="{{ old('items.' . $index . '.hourly_rate', $hasItem ? $item->hourly_rate : '') }}" step="0.01" min="0">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Индивид. условия</label>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="items[{{ $index }}][use_individual_conditions]"
                           value="1" id="indivCond{{ $index }}"
                           onchange="toggleConditions(this, {{ $index }})"
                           {{ $hasItem && $item->use_individual_conditions ? 'checked' : '' }}>
                    <label class="form-check-label small" for="indivCond{{ $index }}">Да</label>
                </div>
            </div>
        </div>

        {{-- Индивидуальные условия --}}
        <div class="individual-conditions mt-2 p-2 bg-light rounded" id="indivCondFields{{ $index }}"
             style="display: {{ $hasItem && $item->use_individual_conditions ? 'block' : 'none' }}">
            <small class="text-muted fw-semibold">Индивидуальные условия аренды</small>
            <div class="row g-2 mt-1">
                <div class="col-md-3">
                    <label class="small">Часов в смену</label>
                    <input type="number" name="items[{{ $index }}][individual_conditions][hours_per_shift]" class="form-control form-control-sm"
                           value="{{ $ic['hours_per_shift'] ?? 8 }}" min="1">
                </div>
                <div class="col-md-3">
                    <label class="small">Смен в день</label>
                    <input type="number" name="items[{{ $index }}][individual_conditions][shifts_per_day]" class="form-control form-control-sm"
                           value="{{ $ic['shifts_per_day'] ?? 1 }}" min="1">
                </div>
                <div class="col-md-3">
                    <label class="small">Тип оплаты</label>
                    <select name="items[{{ $index }}][individual_conditions][payment_type]" class="form-select form-select-sm">
                        <option value="hourly" {{ ($ic['payment_type'] ?? 'hourly') === 'hourly' ? 'selected' : '' }}>Почасовая</option>
                        <option value="fixed" {{ ($ic['payment_type'] ?? '') === 'fixed' ? 'selected' : '' }}>Фиксированная</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small">Доставка</label>
                    <select name="items[{{ $index }}][individual_conditions][transportation_organized_by]" class="form-select form-select-sm">
                        <option value="lessor" {{ ($ic['transportation_organized_by'] ?? 'lessor') === 'lessor' ? 'selected' : '' }}>Арендодатель</option>
                        <option value="lessee" {{ ($ic['transportation_organized_by'] ?? '') === 'lessee' ? 'selected' : '' }}>Арендатор</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small">ГСМ</label>
                    <select name="items[{{ $index }}][individual_conditions][gsm_payment]" class="form-select form-select-sm">
                        <option value="included" {{ ($ic['gsm_payment'] ?? 'included') === 'included' ? 'selected' : '' }}>Включено</option>
                        <option value="separate" {{ ($ic['gsm_payment'] ?? '') === 'separate' ? 'selected' : '' }}>Отдельно</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small">Оператор</label>
                    <select name="items[{{ $index }}][individual_conditions][operator_included]" class="form-select form-select-sm">
                        <option value="0" {{ empty($ic['operator_included']) ? 'selected' : '' }}>Нет</option>
                        <option value="1" {{ !empty($ic['operator_included']) ? 'selected' : '' }}>Включён</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small">Проживание</label>
                    <select name="items[{{ $index }}][individual_conditions][accommodation_payment]" class="form-select form-select-sm">
                        <option value="0" {{ empty($ic['accommodation_payment']) ? 'selected' : '' }}>Нет</option>
                        <option value="1" {{ !empty($ic['accommodation_payment']) ? 'selected' : '' }}>Оплачивается</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small">Продление</label>
                    <select name="items[{{ $index }}][individual_conditions][extension_possibility]" class="form-select form-select-sm">
                        <option value="1" {{ ($ic['extension_possibility'] ?? true) ? 'selected' : '' }}>Возможно</option>
                        <option value="0" {{ empty($ic['extension_possibility']) ? 'selected' : '' }}>Нет</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Спецификации --}}
        <div class="mt-2">
            <small class="text-muted fw-semibold">Спецификации</small>
            <div class="specs-container">
                @if($hasItem && $item->formatted_specifications && count($item->formatted_specifications) > 0)
                    @foreach($item->formatted_specifications as $spec)
                        <div class="spec-row">
                            <small class="text-muted d-block">{{ $spec['label'] ?? $spec['key'] ?? '—' }}</small>
                            <input type="text" class="form-control form-control-sm"
                                   name="items[{{ $index }}][specifications][{{ $spec['key'] ?? '' }}]"
                                   value="{{ $spec['value'] ?? '' }}"
                                   placeholder="{{ $spec['unit'] ?? '' }}">
                            @if(!empty($spec['unit']))
                                <small class="text-muted">{{ $spec['unit'] }}</small>
                            @endif
                        </div>
                    @endforeach
                @else
                    <small class="text-muted">Выберите категорию</small>
                @endif
            </div>
        </div>
    </div>
</div>
