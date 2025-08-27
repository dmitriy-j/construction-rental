@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Просмотр шаблона УПД: {{ $excelMapping->name }}</h1>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('admin.excel-mappings.edit', $excelMapping) }}" class="btn btn-warning">Редактировать</a>
            <a href="{{ route('admin.excel-mappings.index') }}" class="btn btn-secondary">Назад к списку</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Основная информация</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Компания:</strong> {{ $excelMapping->company->legal_name }}</p>
                    <p><strong>Название шаблона:</strong> {{ $excelMapping->name }}</p>
                    <p><strong>Тип:</strong> {{ $excelMapping->type }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Статус:</strong>
                        <span class="badge badge-{{ $excelMapping->is_active ? 'success' : 'secondary' }}">
                            {{ $excelMapping->is_active ? 'Активен' : 'Неактивен' }}
                        </span>
                    </p>
                    <p><strong>Создан:</strong> {{ $excelMapping->created_at->format('d.m.Y H:i') }}</p>
                    <p><strong>Обновлен:</strong> {{ $excelMapping->updated_at->format('d.m.Y H:i') }}</p>
                </div>
            </div>

            @if($excelMapping->file_example_path)
            <div class="mt-3">
                <a href="{{ route('admin.excel-mappings.download-example', $excelMapping) }}"
                   class="btn btn-sm btn-success">
                   Скачать пример файла
                </a>
            </div>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Настройки маппинга</h5>
        </div>
        <div class="card-body">
            <!-- Заголовок документа -->
            <h6 class="mt-3">Заголовок документа</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Поле</th>
                            <th>Ячейка</th>
                            <th>Парсер</th>
                            <th>Описание</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($excelMapping->mapping['header'] as $field => $config)
                            @if(is_array($config) && isset($config['cell']))
                                <tr>
                                    <td>{{ $field }}</td>
                                    <td>{{ $config['cell'] ?? '' }}</td>
                                    <td>{{ $config['parser'] ?? 'нет' }}</td>
                                    <td>{{ $config['description'] ?? '' }}</td>
                                </tr>
                            @elseif(is_array($config))
                                @foreach($config as $subField => $subConfig)
                                    <tr>
                                        <td>{{ $field }}.{{ $subField }}</td>
                                        <td>{{ $subConfig['cell'] ?? '' }}</td>
                                        <td>{{ $subConfig['parser'] ?? 'нет' }}</td>
                                        <td>{{ $subConfig['description'] ?? '' }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Суммы -->
            <h6 class="mt-4">Суммы</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Поле</th>
                            <th>Ячейка</th>
                            <th>Парсер</th>
                            <th>Описание</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($excelMapping->mapping['amounts'] as $field => $config)
                            <tr>
                                <td>{{ $field }}</td>
                                <td>{{ $config['cell'] ?? '' }}</td>
                                <td>{{ $config['parser'] ?? 'нет' }}</td>
                                <td>{{ $config['description'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Табличная часть -->
            <h6 class="mt-4">Табличная часть</h6>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Начальная строка:</strong> {{ $excelMapping->mapping['items']['start_row'] ?? '' }}</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Колонка</th>
                            <th>Поле</th>
                            <th>Парсер</th>
                            <th>Описание</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($excelMapping->mapping['items']['columns'] as $field => $config)
                            <tr>
                                <td>{{ $config['cell'] ?? '' }}</td>
                                <td>{{ $field }}</td>
                                <td>{{ $config['parser'] ?? 'нет' }}</td>
                                <td>{{ $config['description'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if(isset($parsedData))
    <div class="card">
        <div class="card-header">
            <h5>Тестовый парсинг примера файла</h5>
        </div>
        <div class="card-body">
            @if($parsedData)
                <div class="alert alert-success">
                    Файл успешно распарсен! Данные ниже показывают результат парсинга.
                </div>

                <h6>Заголовок документа</h6>
                <pre class="bg-light p-3">{{ json_encode($parsedData['header'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

                <h6>Суммы</h6>
                <pre class="bg-light p-3">{{ json_encode($parsedData['amounts'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

                <h6>Позиции (первые 5 записей)</h6>
                <pre class="bg-light p-3">{{ json_encode(array_slice($parsedData['items'] ?? [], 0, 5), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

                <p class="text-muted">Всего позиций: {{ count($parsedData['items'] ?? []) }}</p>
            @else
                <div class="alert alert-danger">
                    Ошибка при парсинге файла. Проверьте настройки маппинга.
                </div>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
