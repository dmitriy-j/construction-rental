@extends('layouts.app')

@php use App\Models\Order; @endphp

@section('content')
<div class="container py-5">
    <!-- Заголовок заказа -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="display-5 fw-bold mb-2">Заказ #{{ $order->company_order_number }}</h1>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-{{ $order->status_color }} fs-6 py-2 px-3 rounded-pill">
                    {{ $order->status_text }}
                </span>
                <span class="text-muted">
                    {{ $order->created_at->format('d.m.Y H:i') }}
                </span>
            </div>
        </div>
        <a href="{{ route('lessor.orders.index') }}" class="btn btn-outline-primary">
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
                                    {{ $order->rental_days }} дней
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-truck-loading fa-2x text-info"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Адрес доставки:</h6>
                            <p class="mb-0">
                                {{ $orderDetails['delivery_address'] ?? 'Не указан' }}
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
                            <h6 class="text-muted mb-1">Итоговая стоимость</h6>
                            <p class="h4 text-success mb-0">
                                {{ number_format($orderDetails['total_payout'], 2) }} ₽
                            </p>
                        </div>
                    </div>

                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle fa-2x text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Статус оплаты</h6>
                            <p class="mb-0">
                                @if($order->status === \App\Models\Order::STATUS_COMPLETED)
                                    <span class="badge bg-success rounded-pill">Оплачено</span>
                                @else
                                    <span class="badge bg-secondary rounded-pill">Ожидает оплаты</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Детали заказа -->
    <div class="row g-4 mb-5">
        <!-- Условия аренды -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">Условия аренды</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                            <span>Смен в день:</span>
                            <strong>{{ $orderDetails['shifts_per_day'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                            <span>Часов в смену:</span>
                            <strong>{{ $orderDetails['shift_hours'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                            <span>Всего часов:</span>
                            <strong>{{ $orderDetails['total_hours'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                            <span>Тип оплаты:</span>
                            <strong>{{ ucfirst($orderDetails['payment_type']) }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                            <span>Доставка:</span>
                            <strong>{{ $orderDetails['transportation'] == 'lessor' ? 'Наша ответственность' : 'Ответственность арендатора' }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                            <span>Топливо:</span>
                            <strong>{{ $orderDetails['fuel_responsibility'] == 'lessor' ? 'Наша ответственность' : 'Ответственность арендатора' }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Финансовая сводка -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">Финансовая сводка</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="w-75">Стоимость аренды:</td>
                                    <td class="text-end">{{ number_format($orderDetails['lessor_base_amount'], 2) }} ₽</td>
                                </tr>
                                <tr>
                                    <td>Доставка:</td>
                                    <td class="text-end">{{ number_format($orderDetails['delivery_cost'], 2) }} ₽</td>
                                </tr>
                                <tr class="border-top">
                                    <td class="fw-bold">Итого к выплате:</td>
                                    <td class="text-end fw-bold text-primary">{{ number_format($orderDetails['total_payout'], 2) }} ₽</td>
                                </tr>
                            </tbody>
                        </table>
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
                    {{ $order->items->count() }} позиций
                </span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3">Оборудование</th>
                            <th class="py-3 text-center">Цена за час</th>
                            <th class="py-3 text-center">Часы аренды</th>
                            <th class="py-3 text-end">Сумма аренды</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{ route('catalog.show', $item->equipment) }}" class="d-flex align-items-center text-decoration-none">
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
                                            <span class="fw-bold">{{ $item->equipment->title }}</span>
                                            <div class="text-muted small">
                                                {{ $item->equipment->brand }} {{ $item->equipment->model }}
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </td>
                            <td class="text-center">
                                {{ number_format($item->fixed_lessor_price ?? $item->rentalTerm->price_per_hour, 2) }} ₽
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary rounded-pill px-3 py-2">
                                    {{ $item->period_count }} ч
                                </span>
                            </td>
                            <td class="text-end fw-bold">
                                {{ number_format(($item->fixed_lessor_price ?? $item->rentalTerm->price_per_hour) * $item->period_count, 2) }} ₽
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">Итого по аренде:</th>
                            <th class="text-end">{{ number_format($order->items->sum(function($item) {
                                return $item->rentalTerm->price_per_hour * $item->period_count;
                            }), 2) }} ₽</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Действия с заказом -->
    @if($order->status === \App\Models\Order::STATUS_PENDING_APPROVAL)
    <div class="card border-0 shadow-sm rounded-3 mb-5">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0">Подтверждение заказа</h5>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-center gap-3">
                <!-- Измененная кнопка подтверждения -->
                <button type="button" class="btn btn-success px-4 py-2" id="approveOrderBtn">
                    <i class="fas fa-check-circle me-2"></i> Подтвердить
                </button>

                <!-- Форма для подтверждения (скрытая) -->
                <form id="approveForm" method="POST" action="{{ route('lessor.orders.approve', $order) }}" style="display: none;">
                    @csrf
                    <input type="hidden" name="delivery_scenario" id="deliveryScenarioInput">
                </form>

                <button type="button" class="btn btn-outline-danger px-4 py-2" id="rejectOrderBtn">
                    <i class="fas fa-times-circle me-2"></i> Отклонить
                </button>
            </div>
        </div>
    </div>
    @endif

    @if(in_array($order->status, [
    \App\Models\Order::STATUS_CONFIRMED,
    \App\Models\Order::STATUS_IN_DELIVERY,
    \App\Models\Order::STATUS_ACTIVE,
    \App\Models\Order::STATUS_EXTENSION_REQUESTED,
]))
<div class="card border-0 shadow-sm rounded-3 mb-5">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0">Управление заказом</h5>
    </div>
    <div class="card-body">
        <div class="d-grid gap-3">
            @if(in_array($order->status, [
                \App\Models\Order::STATUS_CONFIRMED,
                \App\Models\Order::STATUS_IN_DELIVERY
            ]))
                @php
                    $activationErrors = $order->getActivationErrors();
                @endphp

                <form action="{{ route('lessor.orders.markActive', $order) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="btn btn-primary py-2 position-relative"
                            @if(!$order->canBeActivated())
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Есть проблемы с активацией"
                            @endif>
                        <i class="fas fa-play-circle me-2"></i> Начать аренду

                        @if(!$order->canBeActivated())
                            <span class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-light rounded-circle">
                                <span class="visually-hidden">Ошибки активации</span>
                            </span>
                        @endif
                    </button>

                    @if(!$order->canBeActivated())
                        <div class="mt-3 alert alert-warning">
                            <h6 class="fw-bold mb-2">Аренду можно начать с {{ $order->activationAvailableDate() }}</h6>

                            @if(count($activationErrors) > 0)
                                <ul class="mb-0">
                                    @foreach($activationErrors as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                </form>
            @endif

            <!-- Ссылка на путевые листы, если они созданы -->
            @if($order->waybills()->exists())
                <div class="mt-3">
                    <a href="{{ route('lessor.waybills.index', ['order' => $order]) }}"
                    class="btn btn-outline-secondary d-flex align-items-center">
                        <i class="fas fa-file-alt me-2"></i> Перейти к путевым листам
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endif <!-- Закрывающий тег для внешнего условия -->

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Определяем константы для типов доставки
        const DELIVERY_PICKUP = 'pickup';
        const DELIVERY_DELIVERY = 'delivery';

        // Получаем тип доставки из PHP
        const deliveryType = "{{ $order->delivery_type }}";

        // Обработка подтверждения заказа с выбором сценария доставки
        const approveBtn = document.getElementById('approveOrderBtn');
        if (approveBtn) {
            approveBtn.addEventListener('click', function() {
                if (deliveryType === DELIVERY_DELIVERY) {
                    // Для заказов с доставкой показываем выбор сценария
                    Swal.fire({
                        title: 'Организация доставки',
                        html: `
                            <p>Выберите способ организации доставки:</p>
                            <div class="form-check text-start my-3">
                                <input class="form-check-input" type="radio" name="delivery_scenario"
                                       id="scenario_lessor" value="lessor" checked>
                                <label class="form-check-label" for="scenario_lessor">
                                    <strong>Организую доставку самостоятельно</strong><br>
                                    <small class="text-muted">
                                        (Вы будете грузоотправителем, платформа сгенерирует зеркальный документ для арендатора)
                                    </small>
                                </label>
                            </div>
                            <div class="form-check text-start my-3">
                                <input class="form-check-input" type="radio" name="delivery_scenario"
                                       id="scenario_platform" value="platform">
                                <label class="form-check-label" for="scenario_platform">
                                    <strong>Платформа организует доставку</strong><br>
                                    <small class="text-muted">
                                        (Платформа будет грузоотправителем и назначит перевозчика)
                                    </small>
                                </label>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Подтвердить заказ',
                        cancelButtonText: 'Отмена',
                        focusConfirm: false,
                        width: '650px',
                        customClass: {
                            htmlContainer: 'text-start'
                        },
                        preConfirm: () => {
                            return document.querySelector('input[name="delivery_scenario"]:checked').value;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('deliveryScenarioInput').value = result.value;
                            document.getElementById('approveForm').submit();
                        }
                    });
                } else {
                    // Для заказов с самовывозом подтверждаем сразу
                    document.getElementById('deliveryScenarioInput').value = 'none';
                    document.getElementById('approveForm').submit();
                }
            });
        }

        // Обработка отклонения заказа
        const rejectBtn = document.getElementById('rejectOrderBtn');
        if (rejectBtn) {
            rejectBtn.addEventListener('click', function() {
                Swal.fire({
                    title: 'Отклонить заказ #{{ $order->id }}',
                    html: `
                        <form id="rejectForm" method="POST" action="{{ route('lessor.orders.reject', $order) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Укажите причину отклонения</label>
                                <textarea name="rejection_reason" class="form-control" rows="4" required></textarea>
                            </div>
                        </form>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Подтвердить отклонение',
                    cancelButtonText: 'Отмена',
                    focusConfirm: false,
                    preConfirm: () => {
                        const textarea = document.querySelector('#rejectForm textarea');
                        if (!textarea.value.trim()) {
                            Swal.showValidationMessage('Пожалуйста, укажите причину отклонения');
                            return false;
                        }
                        return true;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('rejectForm').submit();
                    }
                });
            });
        }

        // Обработка продления аренды
        const extensionBtn = document.getElementById('handleExtensionBtn');
        if (extensionBtn) {
            extensionBtn.addEventListener('click', function() {
                const requestedDate = '{{ $order->requested_end_date ? $order->requested_end_date->format("d.m.Y") : "Дата не указана" }}';

                Swal.fire({
                    title: 'Обработка продления заказа #{{ $order->id }}',
                    html: `
                        <form id="extensionForm" method="POST" action="{{ route('lessor.orders.handleExtension', $order) }}">
                            @csrf
                            <p>Арендатор запросил продление до: ${requestedDate}</p>

                            <div class="mb-3">
                                <label class="form-label">Корректировка цены (₽)</label>
                                <input type="number" name="price_adjustment" class="form-control" min="0" step="0.01">
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="action" id="approveAction" value="approve" checked>
                                <label class="form-check-label" for="approveAction">
                                    Одобрить продление
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action" id="rejectAction" value="reject">
                                <label class="form-check-label" for="rejectAction">
                                    Отклонить запрос
                                </label>
                            </div>
                        </form>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Подтвердить решение',
                    cancelButtonText: 'Отмена',
                    focusConfirm: false,
                    width: '600px'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('extensionForm').submit();
                    }
                });
            });
        }
    });
</script>
@endpush

