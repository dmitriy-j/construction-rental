@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Банковские выписки</h1>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Загрузить новую выписку</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.bank-statements.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="bank_name">Банк</label>
                            <input type="text" class="form-control" id="bank_name" name="bank_name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="statement">Файл выписки</label>
                            <input type="file" class="form-control-file" id="statement" name="statement" accept=".txt,.xml,.1c" required>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Загрузить</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>История выписок</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Банк</th>
                            <th>Файл</th>
                            <th>Статус</th>
                            <th>Обработано</th>
                            <th>Ошибок</th>
                            <th>Загружена</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($statements as $statement)
                            <tr>
                                <td>{{ $statement->id }}</td>
                                <td>{{ $statement->bank_name }}</td>
                                <td>{{ basename($statement->filename) }}</td>
                                <td>
                                    <span class="badge badge-{{
                                        $statement->status == 'completed' ? 'success' :
                                        ($statement->status == 'processing' ? 'warning' :
                                        ($statement->status == 'failed' ? 'danger' : 'secondary'))
                                    }}">
                                        {{ $statement->status }}
                                    </span>
                                </td>
                                <td>{{ $statement->processed_count }}/{{ $statement->transactions_count }}</td>
                                <td>{{ $statement->error_count }}</td>
                                <td>{{ $statement->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.finance.bank-statements.show', $statement) }}" class="btn btn-sm btn-info">Просмотр</a>
                                    <form action="{{ route('admin.finance.bank-statements.destroy', $statement) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить выписку?')">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Выписок не найдено</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $statements->links() }}
        </div>
    </div>
</div>
@endsection
