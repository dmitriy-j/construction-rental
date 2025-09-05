@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Создание шаблона документа</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('admin.settings.document-templates.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Назад к списку
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.settings.document-templates.store') }}" method="POST" enctype="multipart/form-data" id="template-form">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Название шаблона *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">Тип документа *</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">Выберите тип документа</option>
                                @foreach($templateTypes as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Описание</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="template_file" class="form-label">Файл шаблона (Excel) *</label>
                            <input type="file" class="form-control" id="template_file" name="template_file" accept=".xlsx,.xls" required>
                            <div class="form-text">Загрузите файл Excel с метками для подстановки данных</div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">Активный шаблон</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Настройка полей</label>
                            <div id="field-mapping-container">
                                <div class="field-mapping-item mb-2">
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Поле данных (например: order.id)" name="field_names[]">
                                        <input type="text" class="form-control" placeholder="Ячейка (например: A1)" name="field_cells[]">
                                        <button type="button" class="btn btn-danger remove-field"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="add-field" class="btn btn-sm btn-primary mt-2">
                                <i class="bi bi-plus"></i> Добавить поле
                            </button>
                            <input type="hidden" name="mapping" id="mapping-data">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Сохранить шаблон</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Добавление нового поля
    document.getElementById('add-field').addEventListener('click', function() {
        const container = document.getElementById('field-mapping-container');
        const newField = document.createElement('div');
        newField.className = 'field-mapping-item mb-2';
        newField.innerHTML = `
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Поле данных (например: order.id)" name="field_names[]">
                <input type="text" class="form-control" placeholder="Ячейка (например: A1)" name="field_cells[]">
                <button type="button" class="btn btn-danger remove-field"><i class="bi bi-trash"></i></button>
            </div>
        `;
        container.appendChild(newField);

        // Добавляем обработчик для кнопки удаления
        newField.querySelector('.remove-field').addEventListener('click', function() {
            newField.remove();
        });
    });

    // Обработчики для кнопок удаления существующих полей
    document.querySelectorAll('.remove-field').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.field-mapping-item').remove();
        });
    });

    // Подготовка данных перед отправкой формы
    document.getElementById('template-form').addEventListener('submit', function(e) {
        const mapping = {};
        const fieldNames = document.getElementsByName('field_names[]');
        const fieldCells = document.getElementsByName('field_cells[]');

        for (let i = 0; i < fieldNames.length; i++) {
            if (fieldNames[i].value && fieldCells[i].value) {
                mapping[fieldNames[i].value] = fieldCells[i].value;
            }
        }

        document.getElementById('mapping-data').value = JSON.stringify(mapping);
    });
});
</script>
@endpush
