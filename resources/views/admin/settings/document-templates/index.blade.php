@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Шаблоны документов</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('admin.settings.document-templates.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Добавить шаблон
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th>Тип</th>
                            <th>Статус</th>
                            <th>Дата создания</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            <tr>
                                <td>{{ $template->name }}</td>
                                <td>
                                    @switch($template->type)
                                        @case('путевой_лист') Путевой лист @break
                                        @case('акт') Акт приема-передачи @break
                                        @case('счет_на_оплату') Счет на оплату @break
                                        @case('договор') Договор аренды @break
                                        @case('упд') УПД @break
                                        @case('акт_сверки') Акт сверки @break
                                        @default {{ $template->type }}
                                    @endswitch
                                </td>
                                <td>
                                    <span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }}">
                                        {{ $template->is_active ? 'Активен' : 'Неактивен' }}
                                    </span>
                                </td>
                                <td>{{ $template->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.settings.document-templates.download', $template) }}"
                                           class="btn btn-sm btn-outline-primary" title="Скачать">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <a href="{{ route('admin.settings.document-templates.preview', $template) }}"
                                           class="btn btn-sm btn-outline-info" title="Предпросмотр">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.settings.document-templates.edit', $template) }}"
                                           class="btn btn-sm btn-outline-warning" title="Редактировать">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.settings.document-templates.destroy', $template) }}"
                                              method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    title="Удалить" onclick="return confirm('Удалить шаблон?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Шаблоны документов не найдены</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
