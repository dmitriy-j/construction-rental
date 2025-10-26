@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Детали выписки #{{ $bankStatement->id }}</h3>
        <div class="card-tools">
            <form action="{{ route('admin.bank-statements.destroy', $bankStatement) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Удалить выписку и все транзакции?')">
                    <i class="fas fa-trash"></i> Удалить
                </button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <strong>Файл:</strong> {{ $bankStatement->filename }}<br>
                <strong>Банк:</strong> {{ $bankStatement->bank_name }}<br>
                <strong>Дата загрузки:</strong> {{ $bankStatement->created_at->format('d.m.Y H:i') }}<br>
            </div>
            <div class="col-md-6">
                <strong>Загрузил:</strong> {{ $bankStatement->processedBy->name ?? 'Неизвестно' }}<br>
                <strong>Транзакций:</strong> {{ $bankStatement->transactions_count }}<br>
                <strong>Обработано:</strong> {{ $bankStatement->processed_count }}<br>
                <strong>Ошибок:</strong> {{ $bankStatement->error_count }}<br>
                <strong>Статус:</strong>
                <span class="badge bg-@if($bankStatement->status === 'completed')success @elseif($bankStatement->status === 'processing')warning @elseif($bankStatement->status === 'failed')danger @elseif($bankStatement->status === 'completed_with_errors')warning @else secondary @endif">
                    {{ $bankStatement->status }}
                </span><br>
            </div>
        </div>

      @if($bankStatement->status === 'failed')
    <div class="alert alert-danger">
        <h5>Выписка не была обработана</h5>
        <p>Возникли ошибки при обработке выписки. Детали можно посмотреть в логах системы.</p>

        @if($bankStatement->transactions->count() > 0)
            <p>Найдено транзакций: {{ $bankStatement->transactions->count() }}</p>
        @endif
    </div>
@endif

@if($bankStatement->transactions->count() > 0)
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Сумма</th>
                    <th>Тип</th>
                    <th>Плательщик</th>
                    <th>Получатель</th>
                    <th>Назначение</th>
                    <th>Статус</th>
                    <th>Компания</th>
                    <th>Счет</th>
                    <th>Ошибка</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bankStatement->transactions as $transaction)
                <tr class="@if($transaction->status === 'error') table-danger @elseif($transaction->status === 'processed') table-success @endif">
                    <td>{{ $transaction->date->format('d.m.Y') }}</td>
                    <td>{{ number_format($transaction->amount, 2) }} руб.</td>
                    <td>
                        <span class="badge bg-{{ $transaction->type === 'incoming' ? 'success' : 'info' }}">
                            {{ $transaction->type === 'incoming' ? 'Входящий' : 'Исходящий' }}
                        </span>
                    </td>
                    <td>
                        <small>{{ $transaction->payer_name }}</small>
                        <br><code>{{ $transaction->payer_inn }}</code>
                    </td>
                    <td>
                        <small>{{ $transaction->payee_name }}</small>
                        <br><code>{{ $transaction->payee_inn }}</code>
                    </td>
                    <td>{{ Str::limit($transaction->purpose, 50) }}</td>
                    <td>
                        <span class="badge bg-{{ $transaction->status === 'processed' ? 'success' : ($transaction->status === 'error' ? 'danger' : 'warning') }}">
                            {{ $transaction->status }}
                        </span>
                    </td>
                    <td>{{ $transaction->company->legal_name ?? 'Не найдена' }}</td>
                    <td>{{ $transaction->invoice->number ?? 'Не привязан' }}</td>
                    <td>
                        @if($transaction->status === 'error')
                            <span class="text-danger" title="{{ $transaction->error_message }}">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ Str::limit($transaction->error_message, 50) }}
                            </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="alert alert-info">
        Транзакции не найдены или еще обрабатываются.
    </div>
@endif
    </div>
    <div class="card-footer">
        <a href="{{ route('admin.bank-statements.index') }}" class="btn btn-default">Назад к списку</a>

        @if($bankStatement->error_count > 0)
            <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#errorModal">
                <i class="fas fa-exclamation-triangle"></i> Показать все ошибки
            </button>
        @endif
    </div>
</div>

@if($bankStatement->error_count > 0)
<div class="alert alert-warning mt-4">
    <h5><i class="fas fa-exclamation-triangle"></i> Обнаружены ошибки обработки</h5>
    <p>Количество транзакций с ошибками: {{ $bankStatement->error_count }}</p>

    <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="collapse" data-target="#errorDetails">
        Показать детали ошибок
    </button>

    <div class="collapse mt-2" id="errorDetails">
        <div class="card card-body">
            @foreach($bankStatement->transactions->where('status', 'error') as $transaction)
                <div class="mb-2 p-2 border rounded">
                    <strong>Транзакция от {{ $transaction->date->format('d.m.Y') }}</strong>
                    <br>Плательщик: {{ $transaction->payer_name }} (ИНН: {{ $transaction->payer_inn }})
                    <br>Получатель: {{ $transaction->payee_name }} (ИНН: {{ $transaction->payee_inn }})
                    <br>Сумма: {{ number_format($transaction->amount, 2) }} руб.
                    <br>Назначение: {{ $transaction->purpose }}
                    <br>Ошибка: <span class="text-danger">{{ $transaction->error_message }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
@endsection
