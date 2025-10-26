@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Детали проводки #{{ $transaction->id }}</h3>
        <div class="card-tools">
            <a href="{{ route('admin.finance.transactions') }}" class="btn btn-default btn-sm">
                Назад к списку
            </a>
        </div>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5>Основная информация</h5>
                <table class="table table-bordered">
                    <tr>
                        <th>ID:</th>
                        <td>{{ $transaction->id }}</td>
                    </tr>
                    <tr>
                        <th>Дата создания:</th>
                        <td>{{ $transaction->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Компания:</th>
                        <td>
                            @if($transaction->company)
                                {{ $transaction->company->legal_name }}
                                <br><small class="text-muted">ИНН: {{ $transaction->company->inn }}</small>
                            @else
                                <span class="text-muted">Не указана</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Сумма:</th>
                        <td>{{ number_format($transaction->amount, 2) }} руб.</td>
                    </tr>
                </table>
            </div>

            <div class="col-md-6">
                <h5>Детали операции</h5>
                <table class="table table-bordered">
                    <tr>
                        <th>Тип операции:</th>
                        <td>
                            <span class="badge badge-{{ $transaction->type == 'debit' ? 'success' : 'danger' }}">
                                {{ $transaction->type == 'debit' ? 'Дебит (Приход)' : 'Кредит (Расход)' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Назначение:</th>
                        <td>{{ $transaction->purpose }}</td>
                    </tr>
                    <tr>
                        <th>Снимок баланса:</th>
                        <td>{{ number_format($transaction->balance_snapshot, 2) }} руб.</td>
                    </tr>
                    <tr>
                        <th>Статус:</th>
                        <td>
                            @if($transaction->is_canceled)
                                <span class="badge badge-danger">Отменена</span>
                                <br><small>Причина: {{ $transaction->cancel_reason }}</small>
                                <br><small>Дата отмены: {{ $transaction->canceled_at->format('d.m.Y H:i') }}</small>
                            @else
                                <span class="badge badge-success">Активна</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <h5>Описание</h5>
                <div class="well well-lg">
                    {{ $transaction->description ?? 'Описание отсутствует' }}
                </div>
            </div>
        </div>

        @if($transaction->source)
        <div class="row mt-4">
            <div class="col-12">
                <h5>Источник операции</h5>
                <p>
                    Тип: {{ class_basename($transaction->source) }}
                    <br>ID: {{ $transaction->source->id }}
                </p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
