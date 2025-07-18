@extends('layouts.app')

@php use App\Models\Order; @endphp  <!-- Добавлен импорт класса Order -->

@section('content')
<div class="container py-5">
    <!-- Заголовок заказа -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="display-5 fw-bold mb-2">Заказ #{{ $order->id }}</h1>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-{{ $order->status_color }} fs-6 py-2 px-3 rounded-pill">
                    {{ $order->status_text }}
                </span>
                <span class="text-muted">
                    {{ $order->created_at->format('d.m.Y H:i') }}
                </span>
            </div>
        </div>
        <a href="{{ route('lessee.orders.index') }}" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>К списку заказов
        </a>
    </div>

    <!-- Общая информация -->
    <div class="card border-0 shadow-sm rounded-3 mb-5">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0">Общая информация</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-calendar-alt fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Период аренды</h6>
                            <p class="mb-0">
                                {{ $order->start_date->format('d.m.Y') }} - {{ $order->end_date->format('d.m.Y') }}
                                <span class="badge bg-light text-dark ms-2">
                                    {{ $order->start_date->diffInDays($order->end_date) }} дней
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-file-invoice-dollar fa-2x text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Общая стоимость</h6>
                            <p class="h4 text-success mb-0">
                                {{ number_format($simpleGrandTotal, 2) }} ₽
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Оборудование -->
    <div class="card border-0 shadow-sm rounded-3 mb-5">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Оборудование в заказе</h5>
                <span class="badge bg-light text-dark">
                    {{ $allItems->count() }} позиций
                </span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3">Оборудование</th>
                            <th class="py-3 text-center">Период</th>
                            <th class="py-3 text-center">Часы</th>
                            <th class="py-3 text-center">Стоимость часа</th>
                            <th class="py-3 text-end">Стоимость аренды</th>
                            <th class="py-3 text-end">Доставка</th>
                            <th class="py-3 text-end">Итоговая стоимость</th>
                            <th class="py-3 text-center">Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($allItems as $item)
                            @php
                                $itemOrder = $item->order;
                            @endphp
                            <tr>
                                <td>
                                    @if($item->equipment)
                                        <div class="d-flex align-items-center">
                                            @if($item->equipment->mainImage && $item->equipment->mainImage->path)
                                            <img src="{{ Storage::url($item->equipment->mainImage->path) }}"
                                                alt="{{ $item->equipment->title }}"
                                                class="rounded me-3" width="60" height="60">
                                            @else
                                            <div class="bg-light border rounded d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                            @endif
                                            <div>
                                                <a href="{{ route('catalog.show', $item->equipment) }}" class="fw-bold text-decoration-none">
                                                    {{ $item->equipment->title }}
                                                </a>
                                                <div class="text-muted small">
                                                    {{ $item->equipment->brand }} {{ $item->equipment->model }}
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-danger">Оборудование недоступно</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column">
                                        <span>{{ $itemOrder->start_date->format('d.m.Y') }}</span>
                                        <span class="text-muted small">по</span>
                                        <span>{{ $itemOrder->end_date->format('d.m.Y') }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill px-3 py-2">
                                        {{ $item->period_count }} ч
                                    </span>
                                </td>
                                <td class="text-center">
                                    {{ number_format($item->price_per_unit, 2) }} ₽/час
                                </td>
                                <td class="text-end">
                                    {{ number_format($item->simple_rental_total, 2) }} ₽
                                </td>
                                <td class="text-end">
                                    {{ number_format($item->delivery_cost, 2) }} ₽
                                </td>
                                <td class="text-end fw-bold">
                                    {{ number_format($item->simple_total, 2) }} ₽
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $itemOrder->status_color }} py-2">
                                        {{ $itemOrder->status_text }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Оборудование не найдено</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Итоговая сумма -->
            <div class="text-end p-3 bg-light fw-bold">
                Итого: {{ number_format($simpleGrandTotal, 2) }} ₽
            </div>
        </div>
    </div>

    <!-- Действия -->
    <div class="d-flex justify-content-between align-items-center border-top pt-4">
        <div class="d-flex gap-2">
            <a href="{{ route('lessee.orders.index') }}" class="btn btn-outline-secondary px-4 py-2">
                <i class="fas fa-arrow-left me-2"></i> Назад
            </a>

            @if(in_array($order->status, [
                Order::STATUS_PENDING,
                Order::STATUS_PENDING_APPROVAL,
                Order::STATUS_CONFIRMED,
                Order::STATUS_AGGREGATED
            ]))
            <form action="{{ route('lessee.orders.cancel', $order) }}" method="POST">
                @csrf
                <button class="btn btn-danger px-4 py-2" onclick="return confirm('Вы уверены, что хотите отменить заказ?')">
                    <i class="fas fa-times me-2"></i> Отменить заказ
                </button>
            </form>
            @endif
        </div>

        @if($order->status === Order::STATUS_ACTIVE)
        <button class="btn btn-warning px-4 py-2" data-bs-toggle="modal"
                data-bs-target="#extensionModal">
            <i class="fas fa-calendar-plus me-2"></i> Запросить продление
        </button>
        @endif
    </div>
</div>

<!-- Модальное окно -->
@if($order->status === Order::STATUS_ACTIVE)
<div class="modal fade" id="extensionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('lessee.orders.requestExtension', $order) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Запрос продления заказа #{{ $order->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Новая дата окончания</label>
                        <input type="date" name="new_end_date" class="form-control form-control-lg"
                               min="{{ \Carbon\Carbon::parse($order->end_date)->addDay()->format('Y-m-d') }}"
                               required>
                    </div>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Запрос будет отправлен всем арендодателям. Для подтверждения продления необходимо согласие каждого из них.
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
