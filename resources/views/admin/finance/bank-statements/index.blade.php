@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Банковские выписки</h3>
        <div class="card-tools d-flex gap-2">
            <a href="{{ route('admin.bank-statements.pending') }}" class="btn btn-warning">
                <i class="fas fa-clock mr-1"></i> Отложенные транзакции
                <span class="badge badge-light ml-1">{{ $pendingCount }}</span>
            </a>
            <a href="{{ route('admin.bank-statements.create') }}" class="btn btn-primary">
                <i class="fas fa-upload mr-1"></i> Загрузить выписку
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th class="text-center">ID</th>
                        <th>Файл</th>
                        <th>Банк</th>
                        <th class="text-center">Дата загрузки</th>
                        <th class="text-center">Транзакций</th>
                        <th class="text-center">Обработано</th>
                        <th class="text-center">Ошибок</th>
                        <th class="text-center">Отложено</th>
                        <th class="text-center">Статус</th>
                        <th class="text-center">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statements as $statement)
                    <tr>
                        <td class="text-center">{{ $statement->id }}</td>
                        <td class="text-truncate" style="max-width: 200px;" title="{{ $statement->filename }}">
                            {{ $statement->filename }}
                        </td>
                        <td>{{ $statement->bank_name }}</td>
                        <td class="text-center">{{ $statement->created_at->format('d.m.Y H:i') }}</td>
                        <td class="text-center">{{ $statement->transactions_count }}</td>
                        <td class="text-center">
                            <span class="badge badge-success">{{ $statement->processed_count }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-danger">{{ $statement->error_count }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-warning">{{ $statement->pending_count }}</span>
                        </td>
                        <td class="text-center">
                            @php
                                $statusClass = [
                                    'completed' => 'success',
                                    'processing' => 'warning',
                                    'failed' => 'danger',
                                    'completed_with_errors' => 'warning'
                                ][$statement->status] ?? 'secondary';
                            @endphp
                            <span class="badge badge-{{ $statusClass }}">
                                {{ $statement->status }}
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.bank-statements.show', $statement) }}"
                               class="btn btn-info btn-sm" title="Просмотр деталей">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <i class="fas fa-file-invoice-dollar fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Нет загруженных выписок</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($statements->hasPages())
        <div class="card-footer d-flex justify-content-center">
            {{ $statements->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@section('styles')
<style>
    .table th {
        border-top: none;
        font-weight: 600;
        color: #495057;
        background-color: #f8f9fa;
    }
    .card-header {
        background: linear-gradient(to right, #f8f9fa, #e9ecef);
        border-bottom: 1px solid #dee2e6;
    }
    .badge {
        font-size: 0.85em;
        padding: 0.35em 0.65em;
    }
</style>
@endsection
