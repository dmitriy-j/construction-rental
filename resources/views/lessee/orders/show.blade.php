@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Заказ #{{ $order->id }}</h1>
        <span class="badge bg-{{ $order->status_color }} fs-6">
            {{ $order->status_text }}
        </span>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Информация о заказе</h5>
                    <p><strong>Арендодатель:</strong> {{ $order->lessorCompany->legal_name }}</p>
                    <p><strong>Дата создания:</strong> {{ $order->created_at->format('d.m.Y H:i') }}</p>
                    <p><strong>Дата начала:</strong> {{ $order->start_date->format('d.m.Y') }}</p>
                    <p><strong>Дата окончания:</strong> {{ $order->end_date->format('d.m.Y') }}</p>
                </div>
                <div class="col-md-6">
                    <h5>Стоимость</h5>
                    <p><strong>Базовая сумма:</strong> {{ number_format($order->base_amount, 2) }} ₽</p>
                    <p><strong>Комиссия платформы:</strong> {{ number_format($order->platform_fee, 2) }} ₽</p>
                    <p><strong>Скидка:</strong> {{ number_format($order->discount_amount, 2) }} ₽</p>
                    <p><strong>Итого:</strong> {{ number_format($order->total_amount, 2) }} ₽</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Оборудование</div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Оборудование</th>
                        <th>Период</th>
                        <th>Кол-во</th>
                        <th>Цена/ед.</th>
                        <th>Итого</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->equipment->title }}</td>
                        <td>{{ $item->rentalTerm->full_period }}</td>
                        <td>{{ $item->period_count }}</td>
                        <td>{{ number_format($item->price_per_unit, 2) }} ₽</td>
                        <td>{{ number_format($item->price_per_unit * $item->period_count, 2) }} ₽</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('lessee.orders') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Назад к списку
        </a>
        
        @if($order->canBeCanceled())
        <form action="{{ route('lessee.orders.cancel', $order) }}" method="POST">
            @csrf
            <button class="btn btn-danger">
                <i class="fas fa-times"></i> Отменить заказ
            </button>
        </form>
        @endif
        
        @if($order->status === Order::STATUS_ACTIVE)
        <button class="btn btn-warning" data-bs-toggle="modal" 
                data-bs-target="#extensionModal">
            <i class="fas fa-calendar-plus"></i> Запросить продление
        </button>
        @endif
    </div>
</div>

@if($order->status === Order::STATUS_ACTIVE)
<!-- Modal -->
<div class="modal fade" id="extensionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('lessee.orders.requestExtension', $order) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Запрос продления заказа #{{ $order->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Новая дата окончания</label>
                        <input type="date" name="new_end_date" class="form-control" 
                               min="{{ \Carbon\Carbon::parse($order->end_date)->addDay()->format('Y-m-d') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Отправить запрос</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection