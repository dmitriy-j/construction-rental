@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Редактирование шаблона УПД</h1>
        </div>
    </div>
    <form action="{{ route('admin.excel-mappings.update', $excelMapping) }}" method="POST" enctype="multipart/form-data" id="templateForm">
        @csrf
        @method('PUT')

        <!-- Основные поля -->
        <div class="form-group">
            <label for="company_id">Компания *</label>
            <select class="form-control" id="company_id" name="company_id" required>
                <option value="">Выберите компанию</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" {{ old('company_id', $excelMapping->company_id) == $company->id ? 'selected' : '' }}>
                        {{ $company->legal_name }} (ИНН: {{ $company->inn }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="name">Название шаблона *</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $excelMapping->name) }}" required>
        </div>
        <div class="form-group">
            <label for="file_example">Пример файла УПД</label>
            <input type="file" class="form-control-file" id="file_example" name="file_example" accept=".xlsx,.xls">
            @if($excelMapping->file_example_path)
                <small class="form-text text-muted">
                    Текущий файл: {{ basename($excelMapping->file_example_path) }}
                </small>
            @endif
        </div>
        <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $excelMapping->is_active) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Активный шаблон</label>
        </div>
        <!-- Настройки маппинга -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Настройки маппинга полей</h5>
                <button type="button" class="btn btn-sm btn-primary" id="toggleAllFields">Развернуть/свернуть все</button>
            </div>
            <div class="card-body">
                <!-- Заголовок документа -->
                <h5 class="mt-3">Заголовок документа</h5>
                @foreach($defaultConfig['header'] as $field => $config)
                    @if(is_array($config) && isset($config['cell']))
                        <div class="field-group">
                            <h6>{{ $config['description'] ?? $field }}</h6>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="mapping_{{ $field }}_cell">Ячейка *</label>
                                    <input type="text" class="form-control" id="mapping_{{ $field }}_cell"
                                           name="mapping[header][{{ $field }}][cell]"
                                           value="{{ old('mapping.header.' . $field . '.cell', $excelMapping->mapping['header'][$field]['cell'] ?? $config['cell'] ?? '') }}"
                                           required placeholder="Например: A1">
                                </div>
                                <div class="col-md-4">
                                    <label for="mapping_{{ $field }}_parser">Парсер</label>
                                    <select class="form-control" id="mapping_{{ $field }}_parser"
                                            name="mapping[header][{{ $field }}][parser]">
                                        @foreach($parsers as $parserValue => $parserName)
                                            <option value="{{ $parserValue }}" {{ old('mapping.header.' . $field . '.parser', $excelMapping->mapping['header'][$field]['parser'] ?? 'none') == $parserValue ? 'selected' : '' }}>
                                                {{ $parserName }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="mapping_{{ $field }}_description">Описание</label>
                                    <input type="text" class="form-control" id="mapping_{{ $field }}_description"
                                           name="mapping[header][{{ $field }}][description]"
                                           value="{{ old('mapping.header.' . $field . '.description', $excelMapping->mapping['header'][$field]['description'] ?? $config['description'] ?? '') }}"
                                           placeholder="Описание поля">
                                </div>
                            </div>
                        </div>
                    @elseif(is_array($config))
                        <div class="field-group">
                            <h6 class="mt-4">{{ ucfirst($field) }}</h6>
                            @foreach($config as $subField => $subConfig)
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="mapping_{{ $field }}_{{ $subField }}_cell">Ячейка для {{ $subConfig['description'] ?? $subField }} *</label>
                                        <input type="text" class="form-control"
                                               id="mapping_{{ $field }}_{{ $subField }}_cell"
                                               name="mapping[header][{{ $field }}][{{ $subField }}][cell]"
                                               value="{{ old('mapping.header.' . $field . '.' . $subField . '.cell', $excelMapping->mapping['header'][$field][$subField]['cell'] ?? $subConfig['cell'] ?? '') }}"
                                               required placeholder="Например: B2">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="mapping_{{ $field }}_{{ $subField }}_parser">Парсер</label>
                                        <select class="form-control"
                                                id="mapping_{{ $field }}_{{ $subField }}_parser"
                                                name="mapping[header][{{ $field }}][{{ $subField }}][parser]">
                                            @foreach($parsers as $parserValue => $parserName)
                                                <option value="{{ $parserValue }}" {{ old('mapping.header.' . $field . '.' . $subField . '.parser', $excelMapping->mapping['header'][$field][$subField]['parser'] ?? 'none') == $parserValue ? 'selected' : '' }}>
                                                    {{ $parserName }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="mapping_{{ $field }}_{{ $subField }}_description">Описание</label>
                                        <input type="text" class="form-control"
                                               id="mapping_{{ $field }}_{{ $subField }}_description"
                                               name="mapping[header][{{ $field }}][{{ $subField }}][description]"
                                               value="{{ old('mapping.header.' . $field . '.' . $subField . '.description', $excelMapping->mapping['header'][$field][$subField]['description'] ?? $subConfig['description'] ?? '') }}"
                                               placeholder="Описание поля">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endforeach
                <!-- Суммы -->
                <h5 class="mt-4">Суммы</h5>
                @foreach($defaultConfig['amounts'] as $field => $config)
                    <div class="field-group">
                        <h6>{{ $config['description'] ?? $field }}</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="mapping_amounts_{{ $field }}_cell">Ячейка *</label>
                                <input type="text" class="form-control" id="mapping_amounts_{{ $field }}_cell"
                                       name="mapping[amounts][{{ $field }}][cell]"
                                       value="{{ old('mapping.amounts.' . $field . '.cell', $excelMapping->mapping['amounts'][$field]['cell'] ?? $config['cell'] ?? '') }}"
                                       required placeholder="Например: C10">
                            </div>
                            <div class="col-md-4">
                                <label for="mapping_amounts_{{ $field }}_parser">Парсер</label>
                                <select class="form-control" id="mapping_amounts_{{ $field }}_parser"
                                        name="mapping[amounts][{{ $field }}][parser]">
                                    @foreach($parsers as $parserValue => $parserName)
                                        <option value="{{ $parserValue }}" {{ old('mapping.amounts.' . $field . '.parser', $excelMapping->mapping['amounts'][$field]['parser'] ?? 'none') == $parserValue ? 'selected' : '' }}>
                                            {{ $parserName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="mapping_amounts_{{ $field }}_description">Описание</label>
                                <input type="text" class="form-control" id="mapping_amounts_{{ $field }}_description"
                                       name="mapping[amounts][{{ $field }}][description]"
                                       value="{{ old('mapping.amounts.' . $field . '.description', $excelMapping->mapping['amounts'][$field]['description'] ?? $config['description'] ?? '') }}"
                                       placeholder="Описание поля">
                            </div>
                        </div>
                    </div>
                @endforeach
                <!-- Табличная часть -->
                <h5 class="mt-4">Табличная часть (позиции УПД)</h5>
                <div class="field-group">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="mapping_items_start_row">Начальная строка *</label>
                            <input type="number" class="form-control" id="mapping_items_start_row"
                                   name="mapping[items][start_row]"
                                   value="{{ old('mapping.items.start_row', $excelMapping->mapping['items']['start_row'] ?? $defaultConfig['items']['start_row'] ?? 15) }}"
                                   required>
                        </div>
                    </div>
                    @foreach($defaultConfig['items']['columns'] as $field => $config)
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="mapping_items_{{ $field }}_cell">Колонка для {{ $config['description'] ?? $field }} *</label>
                                <input type="text" class="form-control"
                                       id="mapping_items_{{ $field }}_cell"
                                       name="mapping[items][columns][{{ $field }}][cell]"
                                       value="{{ old('mapping.items.columns.' . $field . '.cell', $excelMapping->mapping['items']['columns'][$field]['cell'] ?? $config['cell'] ?? '') }}"
                                       required placeholder="Только буква колонки (напр. A)">
                            </div>
                            <div class="col-md-4">
                                <label for="mapping_items_{{ $field }}_parser">Парсер</label>
                                <select class="form-control"
                                        id="mapping_items_{{ $field }}_parser"
                                        name="mapping[items][columns][{{ $field }}][parser]">
                                    @foreach($parsers as $parserValue => $parserName)
                                        <option value="{{ $parserValue }}" {{ old('mapping.items.columns.' . $field . '.parser', $excelMapping->mapping['items']['columns'][$field]['parser'] ?? 'none') == $parserValue ? 'selected' : '' }}>
                                            {{ $parserName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="mapping_items_{{ $field }}_description">Описание</label>
                                <input type="text" class="form-control"
                                       id="mapping_items_{{ $field }}_description"
                                       name="mapping[items][columns][{{ $field }}][description]"
                                       value="{{ old('mapping.items.columns.' . $field . '.description', $excelMapping->mapping['items']['columns'][$field]['description'] ?? $config['description'] ?? '') }}"
                                       placeholder="Описание поля">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Обновить шаблон</button>
        <a href="{{ route('admin.excel-mappings.index') }}" class="btn btn-secondary">Отмена</a>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Функция для переключения видимости всех полей
    document.getElementById('toggleAllFields').addEventListener('click', function() {
        const fieldGroups = document.querySelectorAll('.field-group');
        fieldGroups.forEach(group => {
            const isVisible = group.style.display !== 'none';
            group.style.display = isVisible ? 'none' : 'block';
        });
    });

    // Добавляем валидацию ячеек
    document.getElementById('templateForm').addEventListener('submit', function(e) {
        const cellInputs = document.querySelectorAll('input[name$="[cell]"]');
        let hasError = false;

        cellInputs.forEach(input => {
            // Для табличной части (колонки) допускаем только буквы
            if (input.name.includes('[items][columns]')) {
                if (!input.value.match(/^[A-Z]+$/i)) {
                    alert(`Неверный формат колонки: ${input.value}. Используйте только буквы, например "A", "B" и т.д.`);
                    input.focus();
                    hasError = true;
                    e.preventDefault();
                    return false; // Прерываем цикл
                }
            } else {
                // Для остальных полей - стандартный формат ячейки
                if (!input.value.match(/^[A-Z]+[0-9]+$/i)) {
                    alert(`Неверный формат ячейки: ${input.value}. Используйте формат типа "A1", "B2" и т.д.`);
                    input.focus();
                    hasError = true;
                    e.preventDefault();
                    return false; // Прерываем цикл
                }
            }
        });

        if (hasError) {
            // Останавливаем отправку формы
            e.preventDefault();
            return false;
        }

        return true;
    });

    // Автоматическое преобразование в верхний регистр для колонок
    document.querySelectorAll('input[name*="[items][columns]"]').forEach(input => {
        input.addEventListener('blur', function() {
            // Преобразуем значение в верхний регистр
            this.value = this.value.toUpperCase().replace(/[^A-Z]/g, '');
        });
    });

    // Автоматическое преобразование в верхний регистр для ячеек
    document.querySelectorAll('input[name$="[cell]"]:not([name*="[items][columns]"])').forEach(input => {
        input.addEventListener('blur', function() {
            // Преобразуем значение в верхний регистр
            this.value = this.value.toUpperCase();
        });
    });
});
</script>
<style>
.field-group {
    padding: 10px;
    border: 1px solid #eee;
    margin-bottom: 10px;
    border-radius: 5px;
}
</style>
@endsection
