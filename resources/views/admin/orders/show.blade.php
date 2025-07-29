@extends('layouts.app')

@section('title', 'Заказ #' . $order->id . ' - ' . $lessee->legal_name)

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">
                    <a href="{{ route('admin.lessees.show', $lessee) }}">{{ $lessee->legal_name }}</a>
                    <span class="mx-2">/</span>
                    Заказ #{{ $order->id }}
                </h4>
            </div>
        </div>
    </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Основная информация</h5>
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
                                    <td>{{ $order->lesseeCompany->legal_name }}</td>
                                </tr>
                                <tr>
                                    <th>Арендодатель:</th>
                                    <td>{{ $order->lessorCompany->legal_name }}</td>
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
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Финансовая информация</h5>
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Общая сумма:</th>
                                    <td>{{ number_format($order->total_amount, 2) }} ₽</td>
                                </tr>
                                <tr>
                                    <th>Базовая сумма:</th>
                                    <td>{{ number_format($order->base_amount, 2) }} ₽</td>
                                </tr>
                                <tr>
                                    <th>Комиссия платформы:</th>
                                    <td>{{ number_format($order->platform_fee, 2) }} ₽</td>
                                </tr>
                                <tr>
                                    <th>Скидка:</th>
                                    <td>{{ number_format($order->discount_amount, 2) }} ₽</td>
                                </tr>
                                <tr>
                                    <th>Сумма к выплате:</th>
                                    <td>{{ number_format($order->lessor_payout, 2) }} ₽</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Оборудование в заказе</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Оборудование</th>
                                    <th>Компания</th>
                                    <th>Кол-во</th>
                                    <th>Цена за ед.</th>
                                    <th>Сумма</th>
                                    <th>Условия аренды</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>{{ $item->equipment->title }}</strong>
                                        <div class="text-muted small">
                                            {{ $item->equipment->brand }} {{ $item->equipment->model }}
                                        </div>
                                    </td>
                                    <td>{{ $item->equipment->company->legal_name }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ number_format($item->price_per_unit, 2) }} ₽</td>
                                    <td>{{ number_format($item->total_price, 2) }} ₽</td>
                                    <td>
                                        @if($item->rentalTerm)
                                            {{ $item->rentalTerm->min_rental_hours }} ч мин.
                                        @else
                                            -
                                        @endif
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

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Документы</h5>
                </div>
                <div class="card-body">
                    @if($order->waybills->count())
                        <ul class="list-group">
                            @foreach($order->waybills as $waybill)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Транспортная накладная #{{ $waybill->id }}</span>
                                <a href="#" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download"></i> Скачать
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="alert alert-info mb-0">
                            Нет прикрепленных документов
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Доставка</h5>
                </div>
                <div class="card-body">
                    @if($order->deliveryNote)
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Адрес доставки:</th>
                                <td>{{ $order->deliveryNote->delivery_address }}</td>
                            </tr>
                            <tr>
                                <th>Стоимость доставки:</th>
                                <td>{{ number_format($order->deliveryNote->calculated_cost, 2) }} ₽</td>
                            </tr>
                            <tr>
                                <th>Дата доставки:</th>
                                <td>{{ $order->deliveryNote->delivery_date->format('d.m.Y') }}</td>
                            </tr>
                        </table>
                    @else
                        <div class="alert alert-info mb-0">
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
</style>
@endsection