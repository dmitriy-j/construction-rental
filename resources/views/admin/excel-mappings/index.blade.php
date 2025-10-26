@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Шаблоны УПД</h1>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Создать новый шаблон</h5>
        </div>
        <div class="card-body">
            <a href="{{ route('admin.excel-mappings.create') }}" class="btn btn-primary">
                Создать шаблон УПД
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Список шаблонов</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Компания</th>
                            <th>Тип</th>
                            <th>Статус</th>
                            <th>Создан</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mappings as $mapping)
                            <tr>
                                <td>{{ $mapping->id }}</td>
                                <td>{{ $mapping->name }}</td>
                                <td>{{ $mapping->company->legal_name }}</td>
                                <td>{{ $mapping->type }}</td>
                                <td>
                                    <span class="badge badge-{{ $mapping->is_active ? 'success' : 'secondary' }}">
                                        {{ $mapping->is_active ? 'Активен' : 'Неактивен' }}
                                    </span>
                                </td>
                                <td>{{ $mapping->created_at ? $mapping->created_at->format('d.m.Y H:i') : 'N/A' }}</td>
                                <td>
                                    <a href="{{ route('admin.excel-mappings.show', $mapping) }}" class="btn btn-sm btn-info">Просмотр</a>
                                    <a href="{{ route('admin.excel-mappings.edit', $mapping) }}" class="btn btn-sm btn-warning">Редактировать</a>
                                    @if($mapping->file_example_path)
                                        <a href="{{ route('admin.excel-mappings.download-example', $mapping) }}" class="btn btn-sm btn-success">Скачать пример</a>
                                    @endif
                                    <form action="{{ route('admin.excel-mappings.destroy', $mapping) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить шаблон?')">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Шаблонов не найдено</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $mappings->links() }}
        </div>
    </div>
</div>
@endsection
