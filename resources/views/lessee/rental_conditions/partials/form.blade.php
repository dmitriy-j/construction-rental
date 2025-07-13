<div class="row mb-3">
    <div class="col-md-6">
        <label for="shift_hours" class="form-label">Часов в смене</label>
        <input id="shift_hours" type="number" class="form-control"
            name="shift_hours" value="{{ old('shift_hours', $condition->shift_hours ?? 8) }}"
            min="1" max="24" required>
    </div>

    <div class="col-md-6">
        <label for="shifts_per_day" class="form-label">Смен в сутки</label>
        <input id="shifts_per_day" type="number" class="form-control"
            name="shifts_per_day" value="{{ old('shifts_per_day', $condition->shifts_per_day ?? 1) }}"
            min="1" max="3" required>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="transportation" class="form-label">Транспортировка</label>
        <select id="transportation" class="form-select" name="transportation" required>
            <option value="lessor" {{ old('transportation', $condition->transportation ?? '') == 'lessor' ? 'selected' : '' }}>
                Организует арендодатель
            </option>
            <option value="lessee" {{ old('transportation', $condition->transportation ?? '') == 'lessee' ? 'selected' : '' }}>
                Организуем самостоятельно
            </option>
            <option value="shared" {{ old('transportation', $condition->transportation ?? '') == 'shared' ? 'selected' : '' }}>
                Совместная организация
            </option>
        </select>
    </div>

    <div class="col-md-6">
        <label for="fuel_responsibility" class="form-label">Оплата ГСМ</label>
        <select id="fuel_responsibility" class="form-select" name="fuel_responsibility" required>
            <option value="lessor" {{ old('fuel_responsibility', $condition->fuel_responsibility ?? '') == 'lessor' ? 'selected' : '' }}>
                Включено в стоимость
            </option>
            <option value="lessee" {{ old('fuel_responsibility', $condition->fuel_responsibility ?? '') == 'lessee' ? 'selected' : '' }}>
                Оплачиваем отдельно
            </option>
        </select>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="extension_policy" class="form-label">Возможность продления</label>
        <select id="extension_policy" class="form-select" name="extension_policy" required>
            <option value="allowed" {{ old('extension_policy', $condition->extension_policy ?? '') == 'allowed' ? 'selected' : '' }}>
                Разрешено
            </option>
            <option value="not_allowed" {{ old('extension_policy', $condition->extension_policy ?? '') == 'not_allowed' ? 'selected' : '' }}>
                Не разрешено
            </option>
            <option value="conditional" {{ old('extension_policy', $condition->extension_policy ?? '') == 'conditional' ? 'selected' : '' }}>
                По согласованию
            </option>
        </select>
    </div>

    <div class="col-md-6">
        <label for="payment_type" class="form-label">Тип оплаты</label>
        <select id="payment_type" class="form-select" name="payment_type" required>
            <option value="hourly" {{ old('payment_type', $condition->payment_type ?? '') == 'hourly' ? 'selected' : '' }}>
                Почасовая
            </option>
            <option value="shift" {{ old('payment_type', $condition->payment_type ?? '') == 'shift' ? 'selected' : '' }}>
                По сменам
            </option>
            <option value="daily" {{ old('payment_type', $condition->payment_type ?? '') == 'daily' ? 'selected' : '' }}>
                Посуточная
            </option>
            <option value="mileage" {{ old('payment_type', $condition->payment_type ?? '') == 'mileage' ? 'selected' : '' }}>
                За километраж
            </option>
            <option value="volume" {{ old('payment_type', $condition->payment_type ?? '') == 'volume' ? 'selected' : '' }}>
                За объем работ
            </option>
        </select>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <label for="delivery_cost_per_km" class="form-label">Стоимость доставки (₽/км)</label>
        <input id="delivery_cost_per_km" type="number" step="0.01" class="form-control"
            name="delivery_cost_per_km" value="{{ old('delivery_cost_per_km', $condition->delivery_cost_per_km ?? 50) }}">
    </div>

    <div class="col-md-4">
        <label for="loading_cost" class="form-label">Стоимость погрузки (₽)</label>
        <input id="loading_cost" type="number" step="0.01" class="form-control"
            name="loading_cost" value="{{ old('loading_cost', $condition->loading_cost ?? 1000) }}">
    </div>

    <div class="col-md-4">
        <label for="unloading_cost" class="form-label">Стоимость разгрузки (₽)</label>
        <input id="unloading_cost" type="number" step="0.01" class="form-control"
            name="unloading_cost" value="{{ old('unloading_cost', $condition->unloading_cost ?? 1000) }}">
    </div>
</div>

<div class="mb-3 form-check">
    <input type="checkbox" class="form-check-input" id="is_default"
        name="is_default" value="1"
        {{ old('is_default', $condition->is_default ?? false) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_default">
        Использовать как условия по умолчанию
    </label>
</div>
