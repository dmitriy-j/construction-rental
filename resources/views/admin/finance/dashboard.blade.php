@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Финансовый дашборд</h1>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Общий оборот</h5>
                    <h2>{{ number_format($totalTurnover, 2) }} ₽</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Комиссия платформы</h5>
                    <h2>{{ number_format($platformRevenue, 2) }} ₽</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5>Ожидающие УПД</h5>
                    <h2>{{ $pendingUpdsCount }}</h2>
                    <a href="{{ route('admin.upds.index') }}" class="text-dark">Перейти к УПД</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Новые платежи</h5>
                    <h2>{{ $recentPaymentsCount }}</h2>
                    <a href="{{ route('admin.finance.transactions') }}" class="text-white">Все транзакции</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Статистика по типам операций
                </div>
                <div class="card-body">
                    <canvas id="transactionTypesChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Топ компаний по обороту
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Компания</th>
                                    <th>Оборот</th>
                                    <th>Тип</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topCompanies as $company)
                                    <tr>
                                        <td>{{ $company->legal_name }}</td>
                                        <td>{{ number_format($company->turnover, 2) }} ₽</td>
                                        <td>
                                            <span class="badge badge-{{ $company->is_lessor ? 'info' : 'success' }}">
                                                {{ $company->is_lessor ? 'Арендодатель' : 'Арендатор' }}
                                            </span>
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

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    Последние финансовые операции
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Компания</th>
                                    <th>Сумма</th>
                                    <th>Тип</th>
                                    <th>Назначение</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->created_at->format('d.m.Y H:i') }}</td>
                                        <td>{{ $transaction->company->legal_name }}</td>
                                        <td>{{ number_format($transaction->amount, 2) }} ₽</td>
                                        <td>
                                            <span class="badge badge-{{ $transaction->type == 'debit' ? 'success' : 'danger' }}">
                                                {{ $transaction->type == 'debit' ? 'Поступление' : 'Списание' }}
                                            </span>
                                        </td>
                                        <td>{{ $transaction->purpose }}</td>
                                        <td>
                                            <span class="badge badge-{{ $transaction->is_canceled ? 'danger' : 'success' }}">
                                                {{ $transaction->is_canceled ? 'Отменена' : 'Завершена' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">Операций не найдено</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing financial chart...');

    // Проверяем доступность Chart.js
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not available');
        return;
    }

    const ctx = document.getElementById('transactionTypesChart');
    if (!ctx) {
        console.error('Canvas element not found');
        return;
    }

    // Создаем карту перевода значений purpose на русский
    const purposeTranslations = {
        'upd_debt': 'Долг по УПД',
        'lessee_payment': 'Платежи арендаторов',
        'lessor_payout': 'Выплаты арендодателям',
        'platform_fee': 'Комиссия платформы',
        'refund': 'Возврат средств',
        'penalty': 'Штрафы',
        'correction': 'Корректировка'
        // Добавьте другие возможные значения по мере необходимости
    };

    // Переводим labels на русский
    const originalLabels = {!! json_encode($transactionTypesData['labels']) !!};
    const translatedLabels = originalLabels.map(label => {
        return purposeTranslations[label] || label;
    });

    try {
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: translatedLabels,
                datasets: [{
                    data: {!! json_encode($transactionTypesData['data']) !!},
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56',
                        '#4BC0C0', '#9966FF', '#FF9F40'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 14
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.formattedValue || '';
                                return `${label}: ${value} ₽`;
                            }
                        }
                    }
                }
            }
        });
        console.log('Financial chart initialized successfully');
    } catch (error) {
        console.error('Error initializing chart:', error);
    }
});
</script>
@endsection
