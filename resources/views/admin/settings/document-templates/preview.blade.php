@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Предпросмотр шаблона: {{ $documentTemplate->name }}</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('admin.settings.document-templates.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Назад к списку
            </a>
            <a href="{{ route('admin.settings.document-templates.download', $documentTemplate) }}"
               class="btn btn-primary">
                <i class="bi bi-download"></i> Скачать оригинал
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Данные шаблона</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Название:</strong> {{ $documentTemplate->name }}</p>
                    <p><strong>Тип:</strong> {{ $documentTemplate->type }}</p>
                    <p><strong>Статус:</strong>
                        <span class="badge bg-{{ $documentTemplate->is_active ? 'success' : 'secondary' }}">
                            {{ $documentTemplate->is_active ? 'Активен' : 'Неактивен' }}
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>Описание:</strong> {{ $documentTemplate->description ?? 'Отсутствует' }}</p>
                    <p><strong>Дата создания:</strong> {{ $documentTemplate->created_at->format('d.m.Y H:i') }}</p>
                    <p><strong>Последнее обновление:</strong> {{ $documentTemplate->updated_at->format('d.m.Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5>Содержимое Excel-файла</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <tbody>
                        @foreach($data as $rowIndex => $row)
                            <tr>
                                @foreach($row as $cellIndex => $cell)
                                    <td style="border: 1px solid #dee2e6; padding: 5px;">
                                        {{ $cell ?? '' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5>Настройки маппинга полей</h5>
        </div>
        <div class="card-body">
            @if($documentTemplate->mapping && count($documentTemplate->mapping) > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Поле данных</th>
                                <th>Ячейка в Excel</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documentTemplate->mapping as $field => $cell)
                                <tr>
                                    <td><code>{{ $field }}</code></td>
                                    <td><code>{{ $cell }}</code></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    Настройки маппинга полей не заданы.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .table td {
        font-size: 12px;
        word-break: break-all;
    }
    code {
        background-color: #f8f9fa;
        padding: 2px 5px;
        border-radius: 3px;
        font-family: 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', monospace;
    }
</style>
@endsection
