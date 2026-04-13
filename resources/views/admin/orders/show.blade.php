@extends('layouts.app')

@section('title', 'Заказ #' . $order->id . ' - ' . ($order->lesseeCompany->legal_name ?? 'Арендатор'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="page-title mb-0">
                            @if($order->lesseeCompany)
                                <a href="{{ route('admin.lessees.show', $order->lesseeCompany) }}">{{ $order->lesseeCompany->legal_name }}</a>
                            @else
                                <span>Арендатор не указан</span>
                            @endif
                            <span class="mx-2">/</span>
                            Заказ #{{ $order->id }}
                            @if($order->company_order_number)
                                <small class="text-muted">(Внутр. №{{ $order->company_order_number }})</small>
                            @endif
                            @if($order->isParent())
                                <span class="badge bg-info ms-2">Агрегированный</span>
                            @endif
                        </h4>
                    </div>
                    <div class="col-md-4">
                        <div class="float-end">
                            <a href="{{ route('admin.orders.edit-dates', $order) }}" class="btn btn-warning btn-sm me-2">
                                <i class="bi bi-calendar-range"></i> Изменить даты
                            </a>
                            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary btn-sm">
                                <i class="bi bi-arrow-left"></i> К списку
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Основная информация -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Основная информация</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Статус:</th>
                                    <td>
                                        <span class="badge bg-{{ $order->status_color }}">
                                            {{ $order->status_text }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Арендатор:</th>
                                    <td>{{ $order->lesseeCompany->legal_name ?? 'Не указан' }}</td>
                                </tr>
                                <tr>
                                    <th>Тип заказа:</th>
                                    <td>
                                        @if($order->isParent())
                                            <span class="badge bg-info">Родительский (агрегированный)</span>
                                            <small class="text-muted">- объединяет {{ $order->childOrders->count() }} дочерних заказов</small>
                                        @else
                                            <span class="badge bg-secondary">Дочерний</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Дата создания:</th>
                                    <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Период аренды:</th>
                                    <td>
                                        {{ $order->start_date->format('d.m.Y') }} -
                                        {{ $order->end_date->format('d.m.Y') }}
                                        ({{ $order->rental_days }} дней)
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Финансовая информация</h5>
                            <table class="table table-sm">
                                <tr>
                                    <th width="50%">Общая сумма (арендатор):</th>
                                    <td class="fw-bold">{{ number_format($calculatedTotalAmount, 2) }} ₽</td>
                                </tr>
                                <tr>
                                    <th>
                                        <span class="text-success">▼ Базовая сумма (арендодатель):</span>
                                        <br><small class="text-muted">Цена арендодателя без наценки платформы</small>
                                    </th>
                                    <td class="text-success fw-bold">{{ number_format($calculatedBaseAmount, 2) }} ₽</td>
                                </tr>
                                <tr>
                                    <th>
                                        <span class="text-primary">▲ Наценка платформы:</span>
                                        <br><small class="text-muted">Наша комиссия</small>
                                    </th>
                                    <td class="text-primary fw-bold">{{ number_format($calculatedPlatformFee, 2) }} ₽</td>
                                </tr>
                                <tr>
                                    <th>Стоимость доставки:</th>
                                    <td>{{ number_format($order->delivery_cost, 2) }} ₽</td>
                                </tr>
                                <tr>
                                    <th>
                                        <strong>Сумма к выплате арендодателю:</strong>
                                        <br><small class="text-muted">База + доставка</small>
                                    </th>
                                    <td class="fw-bold text-success">
                                        {{ number_format($calculatedBaseAmount + $order->delivery_cost, 2) }} ₽
                                    </td>
                                </tr>
                                @if($order->discount_amount > 0)
                                <tr>
                                    <th>Скидка арендатору:</th>
                                    <td class="text-danger">-{{ number_format($order->discount_amount, 2) }} ₽</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Секция счетов -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Счета по заказу</h5>
                    @if(in_array($order->status, ['pending', 'active']))
                    <form action="{{ route('admin.invoices.create-for-order', $order) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm"
                                onclick="return confirm('Вы уверены, что хотите создать предоплатный счет для этого заказа?')">
                            <i class="fas fa-file-invoice"></i> Выставить предоплатный счет
                        </button>
                    </form>
                    @endif
                </div>
                <div class="card-body">
                    @php
                        $orderInvoices = $order->invoices ?? collect();
                    @endphp

                    @if($orderInvoices->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Номер счета</th>
                                        <th>Тип</th>
                                        <th>Дата</th>
                                        <th>Срок оплаты</th>
                                        <th>Сумма</th>
                                        <th>Статус</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orderInvoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->number }}</td>
                                        <td>
                                            @if($invoice->upd_id)
                                                <span class="badge bg-info">Постоплата к УПД</span>
                                            @else
                                                <span class="badge bg-warning">Предоплата к заказу</span>
                                            @endif
                                        </td>
                                        <td>{{ $invoice->issue_date->format('d.m.Y') }}</td>
                                        <td>{{ $invoice->due_date->format('d.m.Y') }}</td>
                                        <td>{{ number_format($invoice->amount, 2) }} ₽</td>
                                        <td>
                                            <span class="badge bg-{{ $invoice->getStatusColor() }}">
                                                {{ $invoice->getStatusText() }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.invoices.download', $invoice) }}" class="btn btn-sm btn-success">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            @if(!in_array($invoice->status, ['paid', 'canceled']))
                                            <button type="button" class="btn btn-sm btn-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#cancelInvoiceModal{{ $invoice->id }}">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                            @endif
                                        </td>
                                    </tr>

                                    <!-- Modal for cancel invoice -->
                                    <div class="modal fade" id="cancelInvoiceModal{{ $invoice->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('admin.invoices.cancel', $invoice) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Отмена счета {{ $invoice->number }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="reason" class="form-label">Причина отмены</label>
                                                            <textarea class="form-control" id="reason" name="reason" rows="3" required
                                                                    placeholder="Укажите причину отмены счета"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                                        <button type="submit" class="btn btn-danger">Отменить счет</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            По этому заказу еще не созданы счета
                        </div>
                    @endif

                    <!-- Информация о доступных сценариях -->
                    @if(in_array($order->status, ['pending', 'active']))
                    <div class="mt-3 p-3 bg-light rounded">
                        <h6>Доступные типы счетов:</h6>
                        <ul class="mb-0">
                            <li><strong>Предоплатный счет</strong> - создается к заказу для авансовой оплаты</li>
                            @if($order->upds->where('status', 'accepted')->count() > 0)
                            <li><strong>Постоплатный счет</strong> - создается к принятому УПД для окончательного расчета (доступен в карточке УПД)</li>
                            @endif
                        </ul>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Оборудование -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Оборудование в заказе</h5>
                    <span class="badge bg-primary">Всего позиций: {{ $allItems->count() }}</span>
                </div>
                <div class="card-body">
                    @if($allItems->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Оборудование</th>
                                    <th>Арендодатель</th>
                                    <th>Кол-во</th>
                                    <th>Цена арендодателя/час</th>
                                    <th>Наценка/час</th>
                                    <th>Цена арендатора/час</th>
                                    <th>Часы аренды</th>
                                    <th>Сумма арендатора</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allItems as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>{{ $item->equipment->title ?? 'Оборудование удалено' }}</strong>
                                        <div class="text-muted small">
                                            {{ $item->equipment->brand ?? '' }} {{ $item->equipment->model ?? '' }}
                                        </div>
                                    </td>
                                    <td>
                                        {{ $item->lessorCompany->legal_name ?? $item->equipment->company->legal_name ?? 'Не указан' }}
                                    </td>
                                    <td>{{ $item->quantity }}</td>
                                    <td class="text-success">
                                        {{ number_format($item->fixed_lessor_price ?? $item->rentalTerm->price_per_hour ?? 0, 2) }} ₽
                                    </td>
                                    <td class="text-primary">
                                        {{ number_format($item->platform_fee / max(1, $item->period_count), 2) }} ₽
                                    </td>
                                    <td class="fw-bold">
                                        {{ number_format($item->price_per_unit, 2) }} ₽
                                    </td>
                                    <td>{{ $item->period_count }} ч</td>
                                    <td class="fw-bold">{{ number_format($item->total_price, 2) }} ₽</td>
                                </tr>
                                @endforeach
                                <tr class="table-primary">
                                    <td colspan="8" class="text-end"><strong>Итого:</strong></td>
                                    <td><strong>{{ number_format($allItems->sum('total_price'), 2) }} ₽</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Нет оборудования в заказе
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Дочерние заказы (если есть) -->
    @if($order->isParent() && $order->childOrders->count() > 0)
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Дочерние заказы по арендодателям</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Арендодатель</th>
                                    <th>Статус</th>
                                    <th>Позиций</th>
                                    <th>Сумма арендатора</th>
                                    <th>База (арендодатель)</th>
                                    <th>Наценка</th>
                                    <th>К выплате арендодателю</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->childOrders as $childOrder)
                                @php
                                    $childItems = $childOrder->items;
                                    $childBaseAmount = $childItems->sum(function($item) {
                                        return ($item->fixed_lessor_price ?? $item->base_price) * $item->period_count;
                                    });
                                    $childPlatformFee = $childItems->sum('platform_fee');
                                    $childTotalAmount = $childItems->sum('total_price');
                                    $childPayout = $childBaseAmount + $childOrder->delivery_cost;
                                @endphp
                                <tr>
                                    <td>#{{ $childOrder->id }}</td>
                                    <td>{{ $childOrder->lessorCompany->legal_name ?? 'Не указан' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $childOrder->status_color }}">
                                            {{ $childOrder->status_text }}
                                        </span>
                                    </td>
                                    <td>{{ $childItems->count() }}</td>
                                    <td class="fw-bold">{{ number_format($childTotalAmount, 2) }} ₽</td>
                                    <td class="text-success">{{ number_format($childBaseAmount, 2) }} ₽</td>
                                    <td class="text-primary">{{ number_format($childPlatformFee, 2) }} ₽</td>
                                    <td class="text-success fw-bold">{{ number_format($childPayout, 2) }} ₽</td>
                                </tr>
                                @endforeach
                                <tr class="table-info">
                                    <td colspan="4" class="text-end"><strong>Итого по всем арендодателям:</strong></td>
                                    <td><strong>{{ number_format($order->childOrders->sum(function($co) { return $co->items->sum('total_price'); }), 2) }} ₽</strong></td>
                                    <td class="text-success"><strong>{{ number_format($calculatedBaseAmount, 2) }} ₽</strong></td>
                                    <td class="text-primary"><strong>{{ number_format($calculatedPlatformFee, 2) }} ₽</strong></td>
                                    <td class="text-success fw-bold">{{ number_format($calculatedBaseAmount + $order->delivery_cost, 2) }} ₽</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Документы и доставка -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Документы</h5>
                </div>
                <div class="card-body">
                    @if($order->waybills->count() > 0)
                        <div class="list-group">
                            @foreach($order->waybills as $waybill)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-file-text text-primary me-2"></i>
                                    <span>Транспортная накладная #{{ $waybill->id }}</span>
                                </div>
                                <a href="#" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download"></i>
                                </a>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Нет прикрепленных документов
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Информация о доставке</h5>
                </div>
                <div class="card-body">
                    @if($order->deliveryNote)
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Тип доставки:</th>
                                <td>{{ $order->delivery_type == 'delivery' ? 'Доставка' : 'Самовывоз' }}</td>
                            </tr>
                            <tr>
                                <th>Адрес доставки:</th>
                                <td>{{ $order->deliveryNote->delivery_address ?? 'Не указан' }}</td>
                            </tr>
                            <tr>
                                <th>Стоимость доставки:</th>
                                <td>{{ number_format($order->deliveryNote->calculated_cost ?? 0, 2) }} ₽</td>
                            </tr>
                            @if($order->deliveryNote->delivery_date)
                            <tr>
                                <th>Дата доставки:</th>
                                <td>{{ $order->deliveryNote->delivery_date->format('d.m.Y') }}</td>
                            </tr>
                            @endif
                        </table>
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Информация о доставке отсутствует
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table-sm th, .table-sm td {
    padding: 0.75rem;
}
.badge {
    font-size: 0.8em;
}
</style>
@endsection
