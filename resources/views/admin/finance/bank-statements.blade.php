@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Банковские выписки</h3>
        <div class="card-tools">
            <a href="{{ route('admin.bank-statements.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Загрузить выписку
            </a>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Файл</th>
                    <th>Банк</th>
                    <th>Дата загрузки</th>
                    <th>Транзакций</th>
                    <th>Обработано</th>
                    <th>Ошибок</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statements as $statement)
                <tr>
                    <td>{{ $statement->id }}</td>
                    <td>{{ $statement->filename }}</td>
                    <td>{{ $statement->bank_name }}</td>
                    <td>{{ $statement->created_at->format('d.m.Y H:i') }}</td>
                    <td>{{ $statement->transactions_count }}</td>
                    <td>{{ $statement->processed_count }}</td>
                    <td>{{ $statement->error_count }}</td>
                    <td>
                        <span class="badge badge-{{ $statement->status === 'completed' ? 'success' : ($statement->status === 'processing' ? 'warning' : 'danger') }}">
                            {{ $statement->status }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.bank-statements.show', $statement) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
