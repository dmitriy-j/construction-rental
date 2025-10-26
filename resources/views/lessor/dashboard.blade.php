@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Личный кабинет арендодателя</h1>

    <div class="row mb-4">

         <!-- Карточка "Баланс" -->
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-info h-100">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">Текущий баланс</h5>
                    <p class="card-text display-4 mt-auto">{{ number_format($balance, 2) }} ₽</p>
                    <a href="{{ route('lessor.balance.index') }}" class="text-white">Подробнее →</a>
                </div>
            </div>
        </div>
        <!-- Карточка "Техника" -->
        <div class="col-md-3 mb-3">
            <a href="{{ route('lessor.equipment.index') }}" class="text-decoration-none">
                <div class="card text-white bg-primary h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Техника</h5>
                        <p class="card-text display-4 mt-auto">{{ $stats['equipment_count'] }}</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Карточка "Новые заказы" с миганием -->
        <div class="col-md-3 mb-3">
            <a href="{{ route('lessor.orders.index', ['status' => \App\Models\Order::STATUS_PENDING_APPROVAL]) }}"
               class="text-decoration-none"
               id="pending-orders-card">
                <div class="card text-white bg-warning h-100 {{ $stats['pending_orders'] > 0 ? 'blinking' : '' }}">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Новые заказы</h5>
                        <p class="card-text display-4 mt-auto">{{ $stats['pending_orders'] }}</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Карточка "Активные заказы" -->
        <div class="col-md-3 mb-3">
            <a href="{{ route('lessor.orders.index', ['status' => \App\Models\Order::STATUS_ACTIVE]) }}"
               class="text-decoration-none">
                <div class="card text-white bg-success h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Активные заказы</h5>
                        <p class="card-text display-4 mt-auto">{{ $stats['active_orders'] }}</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Карточка "Выручка" -->
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-info h-100">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">Выручка</h5>
                    <p class="card-text display-4 mt-auto">{{ number_format($stats['revenue'], 0) }} ₽</p>
                </div>
            </div>
        </div>
    </div>

     <!-- Блок с финансовой информацией -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Последние финансовые операции
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Сумма</th>
                                    <th>Тип</th>
                                    <th>Описание</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->created_at->format('d.m.Y H:i') }}</td>
                                    <td>{{ number_format($transaction->amount, 2) }} ₽</td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->type == 'debit' ? 'success' : 'danger' }}">
                                            {{ $transaction->type == 'debit' ? 'Поступление' : 'Списание' }}
                                        </span>
                                    </td>
                                    <td>{{ $transaction->description }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">Операций не найдено</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <a href="{{ route('lessor.balance.index') }}" class="btn btn-sm btn-outline-primary mt-2">
                        Вся история операций
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Статистика доходов
                </div>
                <div class="card-body">
                    <canvas id="incomeChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Последние заказы -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Последние заказы</h5>
            <a href="{{ route('lessor.orders.index') }}" class="btn btn-sm btn-outline-primary">Все заказы</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Период аренды</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                            <th>Дата</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                        <tr>
                            <td>{{ $order->id }}</td>
                            <td>
                                {{ $order->start_date->format('d.m.Y') }} - {{ $order->end_date->format('d.m.Y') }}
                            </td>
                            <td>{{ number_format($order->lessor_base_amount + $order->delivery_cost, 2) }} ₽</td>
                            <td>
                                <span class="badge bg-{{ $order->status_color }}">
                                    {{ $order->status_text }}
                                </span>
                            </td>
                            <td>{{ $order->created_at->format('d.m.Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Рекомендуемое оборудование -->
    @if($featuredEquipment->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Рекомендуемое оборудование</h5>
            <a href="{{ route('lessor.equipment.index') }}" class="btn btn-sm btn-outline-primary">Вся техника</a>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($featuredEquipment as $equipment)
                <div class="col-md-3 mb-3">
                    <div class="card h-100 shadow-sm">
                        @if($equipment->mainImage)
                        <img src="{{ Storage::url($equipment->mainImage->path) }}"
                             class="card-img-top"
                             alt="{{ $equipment->title }}"
                             style="height: 150px; object-fit: cover;">
                        @else
                        <div class="bg-light d-flex align-items-center justify-content-center"
                             style="height: 150px;">
                            <i class="fas fa-image fa-3x text-muted"></i>
                        </div>
                        @endif
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">{{ $equipment->title }}</h5>
                            <p class="card-text text-muted">{{ $equipment->category->name }}</p>
                            <a href="{{ route('catalog.show', $equipment) }}"
                               class="btn btn-sm btn-outline-primary mt-auto">
                                Подробнее
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    /* Анимация мигания для новых заказов */
    .blinking {
        animation: blink 1.5s linear infinite;
    }

    @keyframes blink {
        0% { opacity: 1; }
        50% { opacity: 0.6; }
        100% { opacity: 1; }
    }

    /* Плавное наведение на карточки */
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    /* Фиксированная высота для карточек статистики */
    .card .card-body {
        min-height: 120px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Остановка мигания при клике на карточку "Новые заказы"
    const pendingOrdersCard = document.getElementById('pending-orders-card');
    if (pendingOrdersCard) {
        pendingOrdersCard.addEventListener('click', function() {
            // Убираем класс мигания
            const card = this.querySelector('.card');
            if (card) {
                card.classList.remove('blinking');
            }

            // Отправляем запрос на сервер для отметки о просмотре
            fetch("{{ route('lessor.dashboard.markAsViewed') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            });
        });
    }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // График доходов
    const incomeCtx = document.getElementById('incomeChart').getContext('2d');
    new Chart(incomeCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($incomeMonths) !!},
            datasets: [{
                label: 'Доходы по месяцам',
                data: {!! json_encode($incomeAmounts) !!},
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('ru-RU') + ' ₽';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endsection
