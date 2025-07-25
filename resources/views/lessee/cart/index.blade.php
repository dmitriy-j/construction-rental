@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">Корзина</h1>

    @if($cart->items->isEmpty())
        <div class="alert alert-info">Ваша корзина пуста</div>
    @else
        <!-- Форма для массовых действий -->
        <form id="bulk-form" action="{{ route('cart.update-dates') }}" method="POST" class="mb-4">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Дата начала аренды</label>
                    <input type="date" name="start_date"
                           value="{{ $cart->start_date ? $cart->start_date->format('Y-m-d') : now()->format('Y-m-d') }}"
                           class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Дата окончания</label>
                    <input type="date" name="end_date"
                           value="{{ $cart->end_date ? $cart->end_date->format('Y-m-d') : now()->addDays(1)->format('Y-m-d') }}"
                           class="form-control" required>
                </div>
                <div class="col-md-3">
                    <input type="hidden" name="selected_items" id="selected-items-input">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-calendar-check me-2"></i>Обновить даты
                    </button>
                </div>
                <div class="col-md-3">
                    <button type="button" id="remove-selected" class="btn btn-outline-danger w-100">
                        <i class="bi bi-trash me-2"></i>Удалить выбранные
                    </button>
                </div>
            </div>
        </form>

        <div class="alert alert-info mb-4">
            <i class="bi bi-info-circle me-2"></i>Рассчитанная стоимость является ориентировочной. Окончательная сумма
            будет определена в акте выполненных работ на основании фактического времени использования техники.
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3" style="width: 30px;">
                                    <input type="checkbox" id="select-all" class="form-check-input">
                                </th>
                                <th class="py-3">Оборудование</th>
                                <th class="py-3 text-center">Период</th>
                                <th class="py-3 text-center">Часы</th>
                                <th class="py-3 text-end">Цена/час</th>
                                <th class="py-3 text-end">Аренда</th>
                                <th class="py-3 text-center">Доставка</th>
                                <th class="py-3 text-end">Итого</th>
                                <th class="py-3 text-center">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cart->items as $item)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input item-checkbox" value="{{ $item->id }}">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                @if($item->rentalTerm->equipment->mainImage && $item->rentalTerm->equipment->mainImage->path)
                                                    <img src="{{ Storage::url($item->rentalTerm->equipment->mainImage->path) }}"
                                                        alt="{{ $item->rentalTerm->equipment->title }}"
                                                        class="rounded" width="60">
                                                @else
                                                    <div class="bg-light border rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1">
                                                <a href="{{ route('catalog.show', $item->rentalTerm->equipment) }}" class="fw-bold text-decoration-none">
                                                    {{ $item->rentalTerm->equipment->title }}
                                                </a>
                                                <div class="text-muted small mt-1">
                                                    {{ $item->rentalTerm->equipment->brand }} {{ $item->rentalTerm->equipment->model }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($item->start_date && $item->end_date)
                                            <div class="d-flex flex-column">
                                                <span>{{ $item->start_date->format('d.m.Y') }}</span>
                                                <span class="text-muted small">по</span>
                                                <span>{{ $item->end_date->format('d.m.Y') }}</span>
                                            </div>
                                        @else
                                            <span class="text-danger">Даты не указаны</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary rounded-pill px-3 py-2">
                                            {{ $item->period_count }} ч
                                        </span>
                                    </td>
                                    <td class="text-end">{{ number_format($item->base_price, 2) }} ₽</td>
                                    <td class="text-end">{{ number_format($item->base_price * $item->period_count, 2) }} ₽</td>
                                    <td class="text-center">
                                        @if($item->delivery_cost > 0)
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="popover"
                                                    data-bs-title="Детали доставки"
                                                    data-bs-content="
                                                        <div><strong>От:</strong> {{ $item->deliveryFrom->short_address ?? 'N/A' }}</div>
                                                        <div><strong>До:</strong> {{ $item->deliveryTo->short_address ?? 'N/A' }}</div>
                                                        <div class='mt-2'><strong>Стоимость:</strong> {{ number_format($item->delivery_cost, 2) }} ₽</div>
                                                    ">
                                                <i class="bi bi-truck"></i>
                                                {{ number_format($item->delivery_cost, 2) }} ₽
                                            </button>
                                        @else
                                            <span class="badge bg-secondary">Самовывоз</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">
                                        {{ number_format(($item->base_price * $item->period_count) + $item->delivery_cost, 2) }} ₽
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('cart.remove', $item->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Блок с итоговыми суммами -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row justify-content-end">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Итого аренда:</span>
                            <span class="fw-medium">{{ number_format($totalRental, 2) }} ₽</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Итого доставка:</span>
                            <span class="fw-medium">{{ number_format($totalDelivery, 2) }} ₽</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fs-5">
                            <span class="fw-bold">Всего к оплате:</span>
                            <span class="fw-bold text-primary">{{ number_format($grandTotal, 2) }} ₽</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('catalog.index') }}" class="btn btn-lg btn-outline-primary">
                <i class="bi bi-arrow-left me-2"></i> Продолжить выбор
            </a>
            <form action="{{ route('checkout') }}" method="POST" id="checkout-form">
                @csrf
                <!-- Скрытое поле для передачи выбранных элементов -->
                <input type="hidden" name="selected_items" id="selected-items" value="">
                <button type="submit" class="btn btn-lg btn-success shadow-sm">
                    <i class="bi bi-check-circle me-2"></i> Оформить заказ
                </button>
            </form>
        </div>
    @endif
</div>

<!-- Скрытый элемент для передачи данных в JS -->
<div id="cart-data"
     data-remove-selected-route="{{ route('cart.remove-selected') }}"
     data-csrf-token="{{ csrf_token() }}">
</div>

@endsection

@push('scripts')
    @vite(['resources/js/cart/index.js'])

    <script>
        // Инициализация popover для деталей доставки
        document.addEventListener('DOMContentLoaded', function() {
            const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
            const popoverList = [...popoverTriggerList].map(popoverTriggerEl => {
                return new bootstrap.Popover(popoverTriggerEl, {
                    html: true,
                    trigger: 'hover focus'
                })
            });

            // Обработка оформления заказа
            const checkoutForm = document.getElementById('checkout-form');
            if (checkoutForm) {
                checkoutForm.addEventListener('submit', function(e) {
                    // Собираем выбранные элементы
                    const selectedItems = Array.from(document.querySelectorAll('.item-checkbox:checked'))
                        .map(checkbox => checkbox.value);

                    // Записываем в скрытое поле
                    document.getElementById('selected-items').value = JSON.stringify(selectedItems);

                    // Если ничего не выбрано, отменяем отправку
                    if (selectedItems.length === 0) {
                        e.preventDefault();
                        alert('Пожалуйста, выберите хотя бы один элемент для оформления заказа.');
                    }
                });
            }

            // Обработчик для чекбокса "Выбрать все"
            document.getElementById('select-all').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.item-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });
        });
    </script>
@endpush

@push('styles')
<style>
    .table-hover tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05);
    }
    .card {
        border-radius: 12px;
        overflow: hidden;
    }
    .badge.bg-primary {
        background-color: #0d6efd!important;
    }
    .btn-outline-primary:hover {
        background-color: #0d6efd;
        color: white;
    }
    .summary-card {
        background-color: #f8f9fa;
        border-left: 4px solid #0d6efd;
    }
</style>
@endpush
