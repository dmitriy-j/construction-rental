@extends('layouts.app')

@section('content')
<div class="container py-4">
    {{-- Хлебные крошки без изменений --}}

    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-upload"></i> Массовая загрузка техники через Excel
                    </h4>
                </div>

                <div class="card-body">
                    <!-- Информационный блок -->
                    <div class="alert alert-info">
                        <h5 class="alert-heading">
                            <i class="bi bi-info-circle"></i> Инструкция по загрузке
                        </h5>
                        <ol class="mb-0">
                            <li>Скачайте шаблон Excel файла</li>
                            <li>Заполните данные по технике в соответствующих колонках</li>
                            <li>Сохраните файл в формате Excel (.xlsx)</li>
                            <li>Загрузите заполненный файл через форму ниже</li>
                            <li><strong>Максимальный размер файла: 10MB</strong></li>
                        </ol>
                    </div>

                    <!-- Кнопка скачивания шаблона -->
                    <div class="text-center mb-4">
                        <a href="{{ route('lessor.equipment.mass-import.download-template') }}"
                           class="btn btn-success btn-lg">
                            <i class="bi bi-download"></i> Скачать шаблон Excel
                        </a>
                    </div>

                    <!-- Форма загрузки -->
                    <div class="border rounded p-4 bg-light">
                        <form action="{{ route('lessor.equipment.mass-import.store') }}"
                              method="POST"
                              enctype="multipart/form-data"
                              id="importForm">
                            @csrf

                            <div class="mb-4">
                                <label for="import_file" class="form-label fs-5">
                                    <strong>Выберите Excel файл для загрузки</strong>
                                </label>
                                <input type="file"
                                       name="import_file"
                                       id="import_file"
                                       class="form-control form-control-lg"
                                       accept=".xlsx,.xls"
                                       required
                                       onchange="previewFileName(this)">
                                <div class="form-text">
                                    Поддерживаются только Excel файлы (.xlsx, .xls). Файл должен соответствовать структуре шаблона.
                                </div>
                                @error('import_file')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <i class="bi bi-cloud-upload"></i> Начать импорт
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Описание полей -->
                    <div class="mt-5">
                        <h5 class="mb-3">Описание полей в Excel файле:</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="20%">Название поля в Excel</th>
                                        <th width="30%">Описание</th>
                                        <th width="15%">Обязательное</th>
                                        <th width="35%">Пример заполнения</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Название техники</strong></td>
                                        <td>Полное название единицы техники</td>
                                        <td><span class="badge bg-danger">Да</span></td>
                                        <td><em>Экскаватор CAT 320</em></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Описание</strong></td>
                                        <td>Подробное описание техники и её состояния</td>
                                        <td><span class="badge bg-danger">Да</span></td>
                                        <td><em>Гусеничный экскаватор в отличном состоянии</em></td>
                                    </tr>
                                    <tr>
                                        <td><strong>ID категории</strong></td>
                                        <td>Числовой идентификатор категории из системы</td>
                                        <td><span class="badge bg-danger">Да</span></td>
                                        <td>
                                            <strong>Доступные категории:</strong><br>
                                            @foreach($categories as $category)
                                                <strong>{{ $category->id }}</strong> - {{ $category->name }}<br>
                                            @endforeach
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Бренд</strong></td>
                                        <td>Производитель техники</td>
                                        <td><span class="badge bg-danger">Да</span></td>
                                        <td><em>Caterpillar</em></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Модель</strong></td>
                                        <td>Модель техники</td>
                                        <td><span class="badge bg-danger">Да</span></td>
                                        <td><em>320</em></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Год выпуска</strong></td>
                                        <td>Год выпуска техники</td>
                                        <td><span class="badge bg-danger">Да</span></td>
                                        <td><em>2020</em></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Наработка (часы)</strong></td>
                                        <td>Количество отработанных часов</td>
                                        <td><span class="badge bg-danger">Да</span></td>
                                        <td><em>1500.5</em></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Цена за час (руб)</strong></td>
                                        <td>Стоимость аренды за один час</td>
                                        <td><span class="badge bg-danger">Да</span></td>
                                        <td><em>2500</em></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Название локации</strong></td>
                                        <td>Название места хранения техники</td>
                                        <td><span class="badge bg-danger">Да</span></td>
                                        <td><em>Склад Москва</em></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Адрес локации</strong></td>
                                        <td>Физический адрес расположения техники</td>
                                        <td><span class="badge bg-danger">Да</span></td>
                                        <td><em>г. Москва, ул. Промышленная, 15</em></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Вес (кг)</strong></td>
                                        <td>Вес техники в килограммах</td>
                                        <td><span class="badge bg-danger">Да</span></td>
                                        <td><em>22.5</em></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Длина (м)</strong></td>
                                        <td>Длина техники в метрах</td>
                                        <td><span class="badge bg-danger">Да</span></td>
                                        <td><em>8.2</em></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Ширина (м)</strong></td>
                                        <td>Ширина техники в метрах</td>
                                        <td><span class="badge bg-danger">Да</span></td>
                                        <td><em>2.8</em></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Высота (м)</strong></td>
                                        <td>Высота техники в метрах</td>
                                        <td><span class="badge bg-danger">Да</span></td>
                                        <td><em>3.1</em></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- История импортов (без изменений) -->
                    @if($recentImports->count() > 0)
                    <div class="mt-5">
                        <h5 class="mb-3">Последние загрузки:</h5>
                        <div class="list-group">
                            @foreach($recentImports as $import)
                            <a href="{{ route('lessor.equipment.mass-import.show', $import) }}"
                               class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">{{ $import->original_name }}</h6>
                                    <small>
                                        <span class="badge bg-{{ $import->status === 'completed' ? 'success' : ($import->status === 'failed' ? 'danger' : 'warning') }}">
                                            {{ $import->status === 'completed' ? 'Завершен' : ($import->status === 'failed' ? 'Ошибка' : 'В процессе') }}
                                        </span>
                                    </small>
                                </div>
                                <p class="mb-1">
                                    <small>
                                        Создан: {{ $import->created_at->format('d.m.Y H:i') }} |
                                        Записей: {{ $import->successful_rows }}/{{ $import->total_rows }}
                                        @if($import->failed_rows > 0)
                                        | Ошибок: <span class="text-danger">{{ $import->failed_rows }}</span>
                                        @endif
                                    </small>
                                </p>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewFileName(input) {
    const fileName = input.files[0]?.name || 'Файл не выбран';
    const submitBtn = document.getElementById('submitBtn');

    if (input.files[0]) {
        submitBtn.innerHTML = `<i class="bi bi-cloud-upload"></i> Загрузить: ${fileName}`;
        submitBtn.classList.add('btn-success');
        submitBtn.classList.remove('btn-primary');
    } else {
        submitBtn.innerHTML = `<i class="bi bi-cloud-upload"></i> Начать импорт`;
        submitBtn.classList.remove('btn-success');
        submitBtn.classList.add('btn-primary');
    }
}

// Обработка отправки формы
document.getElementById('importForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Обработка Excel файла...';

    setTimeout(() => {
        submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Импорт начат!';
    }, 1000);
});
</script>


<style>
.breadcrumb {
    background-color: transparent;
    padding: 0;
}

.breadcrumb-item a {
    text-decoration: none;
}

.card-header {
    border-bottom: none;
}

.table code {
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    font-weight: bold;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}
</style>
@endsection
