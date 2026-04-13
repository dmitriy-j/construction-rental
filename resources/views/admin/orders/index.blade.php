@extends('layouts.app')

@section('title', 'Управление заказами')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Управление заказами</h4>
                <div class="page-title-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download"></i> Экспорт
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Excel</a></li>
                            <li><a class="dropdown-item" href="#">PDF</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Статистика -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="text-muted fw-normal mt-0">Всего заказов</h5>
                            <h3 class="my-2">{{ $totalOrders }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-receipt text-primary h1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="text-muted fw-normal mt-0">Ожидают обработки</h5>
                            <h3 class="my-2">{{ $pendingOrders }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-clock-history text-warning h1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="text-muted fw-normal mt-0">Активные</h5>
                            <h3 class="my-2">{{ $activeOrders }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-play-circle text-success h1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="text-muted fw-normal mt-0">Завершённые</h5>
                            <h3 class="my-2">{{ $completedOrders }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-check-circle text-info h1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.orders.index') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Статус</label>
                                <select name="status" class="form-select">
                                    <option value="">Все статусы</option>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                            {{ \App\Models\Order::statusText($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Дата от</label>
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Дата до</label>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Применить</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Таблица заказов -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Арендатор</th>
                                    <th>Дата создания</th>
                                    <th>Период аренды</th>
                                    <th>Статус</th>
                                    <th>Финансовая информация</th>
                                    <th>Дочерних заказов</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                <tr>
                                    <td>
                                        <strong>#{{ $order->id }}</strong>
                                        @if($order->company_order_number)
                                            <br><small class="text-muted">Внутр. №{{ $order->company_order_number }}</small>
                                        @endif
                                        @if($order->isParent())
                                            <br><span class="badge bg-info">Агрегированный</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $order->lesseeCompany->legal_name ?? 'Не указан' }}</div>
                                        <small class="text-muted">{{ $order->user->name ?? 'Нет пользователя' }}</small>
                                    </td>
                                    <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
                                    <td>
                                        {{ $order->start_date->format('d.m.Y') }} -
                                        {{ $order->end_date->format('d.m.Y') }}
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $order->status_color }}">
                                            {{ $order->status_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="financial-info">
                                            <div class="mb-1">
                                                <strong class="text-primary">{{ number_format($order->calculated_total_amount, 2) }} ₽</strong>
                                                <small class="text-muted"> - общая сумма</small>
                                            </div>
                                            <div class="mb-1">
                                                <span class="text-success">{{ number_format($order->calculated_base_amount, 2) }} ₽</span>
                                                <small class="text-muted"> - база (арендодатель)</small>
                                            </div>
                                            <div>
                                                <span class="text-warning">{{ number_format($order->calculated_platform_fee, 2) }} ₽</span>
                                                <small class="text-muted"> - наценка платформы</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($order->isParent())
                                            <span class="badge bg-info">{{ $order->childOrders->count() }}</span>
                                            <small class="text-muted d-block">арендодателей</small>
                                        @else
                                            <span class="badge bg-secondary">Дочерний</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.orders.show', $order) }}"
                                               class="btn btn-sm btn-outline-primary"
                                               title="Просмотр">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.orders.edit-dates', $order) }}"
                                               class="btn btn-sm btn-outline-warning"
                                               title="Изменить даты">
                                                <i class="bi bi-calendar-range"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                            Заказы не найдены
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Пагинация -->
                    @if($orders->hasPages())
                    <div class="row mt-3">
                        <div class="col-12">
                            {{ $orders->links() }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.table-centered td, .table-centered th {
    vertical-align: middle;
}
.badge {
    font-size: 0.75em;
}
.financial-info {
    font-size: 0.85rem;
}
.financial-info .text-success {
    font-weight: 500;
}
.financial-info .text-warning {
    font-weight: 500;
}
</style>
@endsection
