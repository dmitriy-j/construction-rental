@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Управление шаблонами документов</h1>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Добавить новый шаблон</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.document-templates.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Название шаблона</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label>Тип документа</label>
                                    <select name="type" class="form-control" required>
                                        <option value="путевой_лист">Путевой лист</option>
                                        <option value="акт">Акт приема-передачи</option>
                                        <option value="счет_на_оплату">Счет на оплату</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Файл шаблона (Excel)</label>
                                    <input type="file" name="template_file" class="form-control" accept=".xlsx,.xls" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div id="document-editor"></div>
                                <input type="hidden" name="mapping" id="mapping-config">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-3">Сохранить шаблон</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Существующие шаблоны</h4>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Название</th>
                                <th>Тип</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($templates as $template)
                            <tr>
                                <td>{{ $template->name }}</td>
                                <td>{{ $template->type }}</td>
                                <td>
                                    <span class="badge badge-{{ $template->is_active ? 'success' : 'secondary' }}">
                                        {{ $template->is_active ? 'Активен' : 'Неактивен' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.document-templates.edit', $template->id) }}"
                                       class="btn btn-sm btn-primary">Редактировать</a>
                                    <form action="{{ route('admin.document-templates.destroy', $template->id) }}"
                                          method="POST" style="display:inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/luckysheet@latest/dist/luckysheet.umd.js"></script>
<script>
    // Инициализация редактора после загрузки страницы
    document.addEventListener('DOMContentLoaded', function() {
        // Загрузка Luckysheet
        luckysheet.create({
            container: 'luckysheet',
            title: 'Новый шаблон',
            lang: 'ru',
            showinfobar: false
        });

        // Сохранение конфигурации маппинга перед отправкой формы
        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('mapping-config').value = JSON.stringify(window.mappingConfig);
        });
    });
</script>
@endpush
