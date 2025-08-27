@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Управление балансом</h1>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5>Текущий баланс</h5>
                    <h2>{{ number_format($balance, 2) }} ₽</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>История операций</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Сумма</th>
                            <th>Тип</th>
                            <th>Назначение</th>
                            <th>Описание</th>
                            <th>Баланс после операции</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->created_at->format('d.m.Y H:i') }}</td>
                            <td>{{ number_format($transaction->amount, 2) }} ₽</td>
                            <td>
                                <span class="badge bg-{{ $transaction->type == 'debit' ? 'success' : 'danger' }}">
                                    {{ $transaction->type == 'debit' ? 'Поступление' : 'Списание' }}
                                </span>
                            </td>
                            <td>{{ $transaction->purpose }}</td>
                            <td>{{ $transaction->description }}</td>
                            <td>{{ number_format($transaction->balance_snapshot, 2) }} ₽</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">Операций не найдено</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
