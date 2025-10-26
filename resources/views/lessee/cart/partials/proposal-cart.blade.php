{{-- resources/views/lessee/cart/partials/proposal-cart.blade.php --}}
<div id="proposal-cart-content">
    <!-- Уведомление об успешном обновлении -->
    <div id="update-success-alert" class="alert alert-success alert-dismissible fade show" style="display: none;">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div class="flex-grow-1">
                <strong>Успешно!</strong> Даты аренды обновлены и стоимость пересчитана.
                <br>
                <small class="text-muted">
                    <i class="bi bi-clock me-1"></i>
                    Резервирование продлено на 24 часа
                </small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>

    @if($proposalCart->items->isEmpty())
        <div class="alert alert-info text-center">
            <i class="bi bi-handshake me-2"></i>
            Нет подтвержденных предложений в корзине
        </div>
    @else
        <!-- Форма для массовых действий (теперь через API) -->
        <div class="mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <h6 class="mb-0">Выбрано элементов: <span id="selected-proposals-count" class="badge bg-primary">0</span></h6>
                </div>
                <div class="col-md-6 text-end">
                    <button type="button" id="remove-selected-proposals" class="btn btn-outline-danger" disabled>
                        <i class="bi bi-trash me-2"></i>Удалить выбранные
                    </button>
                    <button type="button" id="checkout-selected-proposals" class="btn btn-success ms-2" disabled>
                        <i class="bi bi-credit-card me-2"></i>Оформить выбранные
                    </button>
                </div>
            </div>
        </div>
        <!-- Форма для обновления дат аренды -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="bi bi-calendar-range me-2"></i>Обновление периода аренды
                </h6>

                <form id="update-rental-period-form">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Новая дата начала</label>
                            <input type="date" name="start_date"
                                value="{{ now()->format('Y-m-d') }}"
                                class="form-control"
                                min="{{ now()->format('Y-m-d') }}"
                                required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Новая дата окончания</label>
                            <input type="date" name="end_date"
                                value="{{ now()->addDays(7)->format('Y-m-d') }}"
                                class="form-control"
                                min="{{ now()->addDay()->format('Y-m-d') }}"
                                required>
                        </div>
                        <div class="col-md-4">
                            <input type="hidden" name="selected_items" id="update-period-selected-items">
                            <button type="submit" class="btn btn-primary w-100" id="update-period-btn">
                                <i class="bi bi-calculator me-2"></i>Обновить и пересчитать
                            </button>
                        </div>
                    </div>
                </form>

                <div class="mt-2 text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    При изменении дат будет выполнена проверка доступности оборудования и пересчет стоимости
                </div>
            </div>
        </div>
        <div class="card border-warning mb-4">
            <div class="card-body">
                <h6 class="card-title text-warning">
                    <i class="bi bi-bug me-2"></i>Тестирование API
                </h6>
                <button type="button" id="test-api-btn" class="btn btn-warning btn-sm">
                    <i class="bi bi-play-circle me-2"></i>Тест API
                </button>
                <div id="test-result" class="mt-2 small"></div>
            </div>
        </div>
        <div class="alert alert-info mb-4">
            <i class="bi bi-info-circle me-2"></i>Предложения зарезервированы на 24 часа. Для завершения заказа перейдите к оформлению.
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3" style="width: 30px;">
                                    <input type="checkbox" id="select-all-proposals" class="form-check-input">
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
                            @foreach($proposalCart->items as $item)
                                @php
                                    $rentalTotal = $item->base_price * $item->period_count;
                                    $deliveryCost = $item->proposal_data['delivery_cost'] ?? 0;
                                    $itemTotal = $rentalTotal + $deliveryCost;
                                    $hasDelivery = $item->proposal_data['has_delivery'] ?? false;
                                    $deliveryBreakdown = $item->proposal_data['delivery_breakdown'] ?? [];
                                @endphp
                                <tr data-item-id="{{ $item->id }}"
                                    data-base-price="{{ $item->base_price }}"
                                    data-period-count="{{ $item->period_count }}"
                                    data-delivery-cost="{{ $deliveryCost }}">
                                    <td>
                                        <input type="checkbox" class="form-check-input proposal-checkbox" value="{{ $item->id }}">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                @if($item->proposal->equipment->mainImage && $item->proposal->equipment->mainImage->path)
                                                    <img src="{{ Storage::url($item->proposal->equipment->mainImage->path) }}"
                                                        alt="{{ $item->proposal->equipment->title }}"
                                                        class="rounded" width="60">
                                                @else
                                                    <div class="bg-light border rounded d-flex align-items-center justify-content-center"
                                                         style="width: 60px; height: 60px;">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1">
                                                <a href="{{ route('catalog.show', $item->proposal->equipment) }}"
                                                   class="fw-bold text-decoration-none">
                                                    {{ $item->proposal_data['equipment_title'] ?? $item->proposal->equipment->title }}
                                                </a>
                                                <div class="text-muted small mt-1">
                                                    {{ $item->proposal->equipment->brand }} {{ $item->proposal->equipment->model }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center date-cell">
                                        <div class="d-flex flex-column">
                                            <span>{{ $item->start_date->format('d.m.Y') }}</span>
                                            <span class="text-muted small">по</span>
                                            <span>{{ $item->end_date->format('d.m.Y') }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center hours-cell">
                                        <span class="badge bg-primary rounded-pill px-3 py-2">
                                            {{ $item->period_count }} ч
                                        </span>
                                    </td>
                                    <td class="text-end">{{ number_format($item->base_price, 2) }} ₽</td>
                                    <td class="text-end rental-cell">{{ number_format($rentalTotal, 2) }} ₽</td>
                                    <td class="text-center">
                                        @if($hasDelivery && $deliveryCost > 0)
                                            <button type="button" class="btn btn-sm btn-outline-primary delivery-popover"
                                                    data-bs-toggle="popover"
                                                    data-bs-title="Детали доставки"
                                                    data-bs-content='
                                                        <div class="popover-delivery-details">
                                                            <div><strong>Откуда:</strong> {{ $deliveryBreakdown['from_location']['name'] ?? $deliveryBreakdown['from_location']['address'] ?? 'N/A' }}</div>
                                                            <div><strong>Куда:</strong> {{ $deliveryBreakdown['to_location']['name'] ?? $deliveryBreakdown['to_location']['address'] ?? 'N/A' }}</div>
                                                            <div><strong>Расстояние:</strong> {{ $deliveryBreakdown['distance_km'] ?? 0 }} км</div>
                                                            <div><strong>Тип транспорта:</strong> {{ $deliveryBreakdown['vehicle_type'] ?? 'Не указан' }}</div>
                                                            <div><strong>Стоимость:</strong> {{ number_format($deliveryCost, 2) }} ₽</div>
                                                        </div>
                                                    '>
                                                <i class="bi bi-truck"></i>
                                                {{ number_format($deliveryCost, 2) }} ₽
                                            </button>
                                        @else
                                            <span class="badge bg-secondary">Самовывоз</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold total-cell">
                                        {{ number_format($itemTotal, 2) }} ₽
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-proposal-item"
                                                data-item-id="{{ $item->id }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
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
                        @php
                            $totalRentalProposal = $proposalCart->items->sum(function($item) {
                                return $item->base_price * $item->period_count;
                            });
                            $totalDeliveryProposal = $proposalCart->items->sum(function($item) {
                                return $item->proposal_data['delivery_cost'] ?? 0;
                            });
                            $grandTotalProposal = $totalRentalProposal + $totalDeliveryProposal;
                        @endphp

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Итого аренда:</span>
                            <span class="fw-medium" data-total-rental>{{ number_format($totalRentalProposal, 2) }} ₽</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Итого доставка:</span>
                            <span class="fw-medium" data-total-delivery>{{ number_format($totalDeliveryProposal, 2) }} ₽</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fs-5">
                            <span class="fw-bold">Всего к оплате:</span>
                            <span class="fw-bold text-primary" data-grand-total>{{ number_format($grandTotalProposal, 2) }} ₽</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Блок резервирования -->
        <div class="alert alert-warning mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-clock-history me-2"></i>
                    <strong>Временное резервирование</strong>
                    <span id="reservation-time" class="ms-2">
                        @if($proposalCart->is_reservation_active)
                            Активно до: <strong>{{ $proposalCart->reserved_until->format('d.m.Y H:i') }}</strong>
                        @else
                            <span class="text-danger">Резервирование истекло</span>
                        @endif
                    </span>
                </div>
                @if($proposalCart->is_reservation_active)
                    <button type="button" class="btn btn-outline-primary btn-sm" id="extend-reservation">
                        <i class="bi bi-arrow-clockwise me-1"></i>Продлить на 24 часа
                    </button>
                @endif
            </div>
        </div>

        <!-- Кнопки действий -->
        <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('rental-requests.index') }}" class="btn btn-lg btn-outline-primary">
                <i class="bi bi-arrow-left me-2"></i> К заявкам
            </a>
            <button type="button" id="checkout-all-proposals" class="btn btn-lg btn-success shadow-sm">
                <i class="bi bi-check-circle me-2"></i> Оформить все предложения
            </button>
        </div>
    @endif
</div>

@push('scripts')
<script>
// 🔥 УЛУЧШЕННАЯ ФУНКЦИЯ ФОРМАТИРОВАНИЯ ВАЛЮТЫ
function formatCurrency(amount) {
    if (amount === undefined || amount === null || isNaN(amount)) {
        console.warn('[FRONTEND] Invalid amount for formatting:', amount);
        return '0.00 ₽';
    }
    return new Intl.NumberFormat('ru-RU', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount) + ' ₽';
}

// 🔥 УЛУЧШЕННАЯ ФУНКЦИЯ ФОРМАТИРОВАНИЯ ДАТЫ
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'N/A';
        return date.toLocaleDateString('ru-RU');
    } catch (e) {
        console.error('[FRONTEND] Date formatting error:', e);
        return 'N/A';
    }
}

// 🔥 ФУНКЦИЯ ДЛЯ ПОЛУЧЕНИЯ ВЫБРАННЫХ ПРЕДЛОЖЕНИЙ
const getSelectedProposals = () => {
    try {
        return [...document.querySelectorAll('.proposal-checkbox:checked')]
            .map(el => {
                const value = el.value;
                if (!value || value === 'undefined') {
                    console.warn('[FRONTEND] Invalid checkbox value:', el);
                    return null;
                }
                return value;
            })
            .filter(Boolean);
    } catch (error) {
        console.error('[FRONTEND] Error getting selected proposals:', error);
        return [];
    }
};

// 🔥 ФУНКЦИЯ ДЛЯ ОБНОВЛЕНИЯ СЧЕТЧИКА ВЫБРАННЫХ ПРЕДЛОЖЕНИЙ
function updateSelectedProposals() {
    const selectedItems = getSelectedProposals();
    const selectedCount = selectedItems.length;

    // Обновляем счетчик
    const countElement = document.getElementById('selected-proposals-count');
    if (countElement) {
        countElement.textContent = selectedCount;
    }

    // Активируем/деактивируем кнопки
    const removeBtn = document.getElementById('remove-selected-proposals');
    const checkoutSelectedBtn = document.getElementById('checkout-selected-proposals');
    const updatePeriodBtn = document.getElementById('update-period-btn');

    if (removeBtn) removeBtn.disabled = selectedCount === 0;
    if (checkoutSelectedBtn) checkoutSelectedBtn.disabled = selectedCount === 0;
    if (updatePeriodBtn) updatePeriodBtn.disabled = selectedCount === 0;

    console.log('[FRONTEND] Selected proposals updated:', selectedCount);
}

// 🔥 ФУНКЦИЯ ДЛЯ ВЫБОРА ВСЕХ ПРЕДЛОЖЕНИЙ
function initSelectAllProposals() {
    const selectAll = document.getElementById('select-all-proposals');
    if (!selectAll) {
        console.warn('[FRONTEND] select-all-proposals checkbox not found');
        return;
    }

    selectAll.addEventListener('change', function() {
        document.querySelectorAll('.proposal-checkbox').forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelectedProposals();
    });

    // Обработчик изменений для отдельных чекбоксов
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('proposal-checkbox')) {
            const checkboxes = document.querySelectorAll('.proposal-checkbox');
            const allChecked = [...checkboxes].every(cb => cb.checked);
            if (selectAll) selectAll.checked = allChecked;
            updateSelectedProposals();
        }
    });
}

// 🔥 ФУНКЦИЯ ДЛЯ МАССОВОГО УДАЛЕНИЯ ПРЕДЛОЖЕНИЙ
function initRemoveSelectedProposals() {
    const removeBtn = document.getElementById('remove-selected-proposals');
    if (!removeBtn) return;

    removeBtn.addEventListener('click', async function() {
        const selected = getSelectedProposals();
        if (selected.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Не выбраны элементы',
                text: 'Пожалуйста, выберите хотя бы один элемент для удаления',
            });
            return;
        }

        const result = await Swal.fire({
            title: 'Вы уверены?',
            html: `Вы собираетесь удалить <strong>${selected.length}</strong> выбранных предложений`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Да, удалить!',
            cancelButtonText: 'Отмена'
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch('/api/cart/proposal/remove-selected-items', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ selected_items: selected })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Успешно!',
                        text: data.message,
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Ошибка',
                    text: error.message,
                });
            }
        }
    });
}

// 🔥 ФУНКЦИЯ ДЛЯ УДАЛЕНИЯ ОДНОГО ЭЛЕМЕНТА
function initRemoveSingleItem() {
    document.addEventListener('click', async function(e) {
        if (e.target.classList.contains('remove-proposal-item') ||
            e.target.closest('.remove-proposal-item')) {

            const button = e.target.classList.contains('remove-proposal-item')
                ? e.target
                : e.target.closest('.remove-proposal-item');
            const itemId = button.dataset.itemId;

            const result = await Swal.fire({
                title: 'Вы уверены?',
                text: 'Вы собираетесь удалить это предложение из корзины',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Да, удалить!',
                cancelButtonText: 'Отмена'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch(`/api/cart/proposal/items/${itemId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': window.csrfToken,
                        },
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Успешно!',
                            text: data.message,
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        throw new Error(data.message);
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Ошибка',
                        text: error.message,
                    });
                }
            }
        }
    });
}


// 🔥 ФУНКЦИЯ ДЛЯ ОФОРМЛЕНИЯ ВЫБРАННЫХ ПРЕДЛОЖЕНИЙ
function initCheckoutSelected() {
    const checkoutSelectedBtn = document.getElementById('checkout-selected-proposals');
    if (!checkoutSelectedBtn) return;

    checkoutSelectedBtn.addEventListener('click', async function() {
        const selected = getSelectedProposals();
        if (selected.length === 0) return;

        const checkoutBtn = this;
        const originalText = checkoutBtn.innerHTML;
        checkoutBtn.disabled = true;
        checkoutBtn.innerHTML = '<i class="bi bi-arrow-repeat spinner me-2"></i>Оформление...';

        try {
            console.log('[CHECKOUT] Starting checkout for selected items:', selected);

            // 🔥 ИСПРАВЛЕННЫЙ ENDPOINT
            const response = await fetch('/api/proposal-cart/checkout', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    selected_items: selected
                })
            });

            const data = await response.json();

            if (data.success) {
                console.log('[CHECKOUT] Checkout successful:', data);

                // 🔥 НЕМЕДЛЕННЫЙ РЕДИРЕКТ БЕЗ ДОПОЛНИТЕЛЬНЫХ ДИАЛОГОВ
                if (data.data && data.data.redirect_url) {
                    window.location.href = data.data.redirect_url;
                } else {
                    // Fallback: редирект на страницу заказов
                    window.location.href = '/lessee/orders';
                }
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('[CHECKOUT] Checkout error:', error);

            await Swal.fire({
                icon: 'error',
                title: 'Ошибка оформления',
                html: `
                    <div class="text-start">
                        <p class="mb-3">${error.message}</p>
                        <div class="alert alert-warning small">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Проверьте доступность оборудования и попробуйте снова
                        </div>
                    </div>
                `,
                confirmButtonText: 'Понятно'
            });

            // Возвращаем кнопку в исходное состояние
            checkoutBtn.disabled = false;
            checkoutBtn.innerHTML = originalText;
        }
    });
}

// 🔥 ФУНКЦИЯ ДЛЯ ОФОРМЛЕНИЯ ВСЕХ ПРЕДЛОЖЕНИЙ
function initCheckoutAll() {
    const checkoutAllBtn = document.getElementById('checkout-all-proposals');
    if (!checkoutAllBtn) return;

    checkoutAllBtn.addEventListener('click', async function() {
        const allItems = [...document.querySelectorAll('.proposal-checkbox')].map(el => el.value);

        try {
            const response = await fetch('/api/cart/proposal/checkout-selected', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ selected_items: allItems })
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Успешно!',
                    text: data.message,
                    confirmButtonText: 'Перейти к заказу'
                }).then((result) => {
                    if (result.isConfirmed && data.redirect_url) {
                        window.location.href = data.redirect_url;
                    }
                });
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Checkout all error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Ошибка',
                text: error.message || 'Не удалось оформить заказ',
            });
        }
    });
}

// 🔥 ФУНКЦИЯ ДЛЯ ПРОДЛЕНИЯ РЕЗЕРВИРОВАНИЯ
function initExtendReservation() {
    const extendBtn = document.getElementById('extend-reservation');
    if (!extendBtn) return;

    extendBtn.addEventListener('click', async function() {
        try {
            const response = await fetch('/api/cart/proposal/extend-reservation', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();

            if (data.success) {
                // Вставляем обновленный блок успешного ответа
                console.log('[FRONTEND] Update successful, updating interface');

                // 🔥 ОБНОВЛЯЕМ ИНТЕРФЕЙС
                updateCartDisplay(data.data);

                // 🔥 ПРОВЕРЯЕМ И ПЕРЕЗАГРУЗКАЕМ ЕСЛИ НУЖНО
                setTimeout(() => {
                    if (!isCartUpdated(data.data)) {
                        console.log('[FRONTEND] Interface not updated properly, forcing reload');
                        location.reload();
                        return;
                    }

                    // 🔥 ПОКАЗЫВАЕМ УВЕДОМЛЕНИЕ ПОСЛЕ ОБНОВЛЕНИЯ ИНТЕРФЕЙСА
                    Swal.fire({
                        icon: 'success',
                        title: 'Успешно!',
                        html: `
                            <div class="text-start">
                                <p>${data.message}</p>
                                <div class="mt-3 p-2 bg-light rounded">
                                    <small>
                                        <i class="bi bi-info-circle me-1"></i>
                                        Резервирование продлено до: <strong>${new Date(data.data.reserved_until).toLocaleString('ru-RU')}</strong>
                                    </small>
                                </div>
                            </div>
                        `,
                        confirmButtonText: 'Отлично'
                    }).then(() => {
                        // 🔥 ПОКАЗЫВАЕМ УВЕДОМЛЕНИЕ ОБ УСПЕШНОМ ОБНОВЛЕНИИ
                        const alert = document.getElementById('update-success-alert');
                        if (alert) {
                            alert.style.display = 'block';
                            setTimeout(() => {
                                alert.style.display = 'none';
                            }, 5000);
                        }
                    });
                }, 500);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Ошибка',
                text: error.message,
            });
        }
    });
}

// 🔥 ФУНКЦИЯ ДЛЯ ИНИЦИАЛИЗАЦИИ ВСПЛЫВАЮЩИХ ПОДСКАЗОК
function initProposalPopovers() {
    const popoverTriggerList = document.querySelectorAll('.delivery-popover');
    popoverTriggerList.forEach(popoverTriggerEl => {
        new bootstrap.Popover(popoverTriggerEl, {
            html: true,
            sanitize: false,
            trigger: 'hover focus'
        });
    });
}

// 🔥 ФУНКЦИЯ ДЛЯ ТЕСТИРОВАНИЯ API
function initTestApi() {
    const testBtn = document.getElementById('test-api-btn');
    if (!testBtn) return;

    testBtn.addEventListener('click', async function() {
        const testResult = document.getElementById('test-result');
        testResult.innerHTML = '<div class="text-info">Тестирование API...</div>';

        try {
            const response = await fetch('/api/cart/proposal/test-api', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ test: 'data', items: [1, 2, 3] })
            });

            const responseText = await response.text();
            console.log('[API_TEST] Raw response:', responseText);

            if (!responseText) {
                testResult.innerHTML = '<div class="text-danger">❌ Пустой ответ от сервера</div>';
                return;
            }

            try {
                const data = JSON.parse(responseText);
                testResult.innerHTML = `<div class="text-success">✅ API работает: ${data.message}</div>`;
                console.log('[API_TEST] Parsed data:', data);
            } catch (e) {
                testResult.innerHTML = `<div class="text-danger">❌ Ошибка парсинга JSON: ${e.message}</div>`;
            }
        } catch (error) {
            testResult.innerHTML = `<div class="text-danger">❌ Ошибка сети: ${error.message}</div>`;
        }
    });
}

// 🔥 ПОЛНОСТЬЮ ПЕРЕРАБОТАННАЯ ФУНКЦИЯ ОБНОВЛЕНИЯ ОТОБРАЖЕНИЯ
function updateCartDisplay(responseData) {
    console.log('[FRONTEND] Updating cart display with full response:', responseData);

    // 🔥 ГЛУБОКАЯ ПРОВЕРКА ДАННЫХ
    if (!responseData || typeof responseData !== 'object') {
        console.error('[FRONTEND] Invalid response data structure');
        return;
    }

    // 🔥 ИЗВЛЕКАЕМ ДАННЫЕ ИЗ ПРАВИЛЬНОЙ СТРУКТУРЫ
    const cartData = responseData.data || responseData;
    if (!cartData) {
        console.error('[FRONTEND] No cart data in response');
        return;
    }

    const cart = cartData.cart || cartData;
    const totalRental = cartData.total_rental;
    const totalDelivery = cartData.total_delivery;
    const grandTotal = cartData.grand_total;

    console.log('[FRONTEND] Extracted data for display:', {
        hasCart: !!cart,
        totalRental,
        totalDelivery,
        grandTotal,
        itemsCount: cart?.items?.length || 0
    });

    // 🔥 ОБНОВЛЕНИЕ ИТОГОВЫХ СУММ С ЗАЩИТОЙ ОТ ОШИБОК
    try {
        // Общая стоимость аренды
        if (totalRental !== undefined && totalRental !== null) {
            const rentalElement = document.querySelector('[data-total-rental]');
            if (rentalElement) {
                rentalElement.textContent = formatCurrency(totalRental);
                console.log('[FRONTEND] Updated rental total:', totalRental);
            }
        }

        // Стоимость доставки
        if (totalDelivery !== undefined && totalDelivery !== null) {
            const deliveryElement = document.querySelector('[data-total-delivery]');
            if (deliveryElement) {
                deliveryElement.textContent = formatCurrency(totalDelivery);
                console.log('[FRONTEND] Updated delivery total:', totalDelivery);
            }
        }

        // Итоговая сумма
        if (grandTotal !== undefined && grandTotal !== null) {
            const grandTotalElement = document.querySelector('[data-grand-total]');
            if (grandTotalElement) {
                grandTotalElement.textContent = formatCurrency(grandTotal);
                console.log('[FRONTEND] Updated grand total:', grandTotal);
            }
        }

        // 🔥 ОБНОВЛЕНИЕ ТАБЛИЦЫ С ДАННЫМИ КОРЗИНЫ
        if (cart && cart.items) {
            updateCartTable(cart);
        }

    } catch (error) {
        console.error('[FRONTEND] Error updating cart display:', error);
    }

    console.log('[FRONTEND] Cart display update completed');
}

// 🔥 УЛУЧШЕННАЯ ФУНКЦИЯ ОБНОВЛЕНИЯ ТАБЛИЦЫ
function updateCartTable(cart) {
    if (!cart || !cart.items || !Array.isArray(cart.items)) {
        console.warn('[FRONTEND] No valid cart items for table update');
        return;
    }

    console.log('[FRONTEND] Updating cart table with items:', cart.items.length);

    cart.items.forEach(item => {
        const row = document.querySelector(`tr[data-item-id="${item.id}"]`);
        if (!row) {
            console.warn('[FRONTEND] Row not found for item:', item.id);
            return;
        }

        console.log('[FRONTEND] Updating row for item:', item.id, item);

        // 🔥 ОБНОВЛЕНИЕ ДАТ С ЗАЩИТОЙ ОТ ОШИБОК
        try {
            const dateCells = row.querySelectorAll('.date-cell');
            if (dateCells.length >= 2) {
                dateCells[0].textContent = formatDate(item.start_date);
                dateCells[1].textContent = formatDate(item.end_date);
                console.log('[FRONTEND] Updated dates:', item.start_date, item.end_date);
            }
        } catch (e) {
            console.error('[FRONTEND] Error updating dates:', e);
        }

        // 🔥 ОБНОВЛЕНИЕ ЧАСОВ АРЕНДЫ
        try {
            const hoursCell = row.querySelector('.hours-cell');
            if (hoursCell) {
                hoursCell.textContent = `${item.period_count} ч`;
                console.log('[FRONTEND] Updated hours:', item.period_count);
            }
        } catch (e) {
            console.error('[FRONTEND] Error updating hours:', e);
        }

        // 🔥 ОБНОВЛЕНИЕ СТОИМОСТИ АРЕНДЫ (base_price × period_count)
        try {
            const rentalCell = row.querySelector('.rental-cell');
            if (rentalCell) {
                const rentalTotal = (item.base_price || 0) * (item.period_count || 0);
                rentalCell.textContent = formatCurrency(rentalTotal);
                console.log('[FRONTEND] Updated rental cost:', rentalTotal);
            }
        } catch (e) {
            console.error('[FRONTEND] Error updating rental cost:', e);
        }

        // 🔥 ОБНОВЛЕНИЕ СТОИМОСТИ ДОСТАВКИ
        try {
            const deliveryCell = row.querySelector('.delivery-cell');
            if (deliveryCell) {
                deliveryCell.textContent = formatCurrency(item.delivery_cost || 0);
                console.log('[FRONTEND] Updated delivery cost:', item.delivery_cost);
            }
        } catch (e) {
            console.error('[FRONTEND] Error updating delivery cost:', e);
        }

        // 🔥 ОБНОВЛЕНИЕ ОБЩЕЙ СТОИМОСТИ ПОЗИЦИИ
        try {
            const totalCell = row.querySelector('.total-cell');
            if (totalCell) {
                const rentalTotal = (item.base_price || 0) * (item.period_count || 0);
                const deliveryCost = item.delivery_cost || 0;
                const itemTotal = rentalTotal + deliveryCost;
                totalCell.textContent = formatCurrency(itemTotal);
                console.log('[FRONTEND] Updated item total:', itemTotal);
            }
        } catch (e) {
            console.error('[FRONTEND] Error updating item total:', e);
        }
    });

    console.log('[FRONTEND] Cart table update completed');
}

// 🔥 ФУНКЦИЯ ДЛЯ ПРОВЕРКИ ОБНОВЛЕНИЯ ИНТЕРФЕЙСА
function isCartUpdated(responseData) {
    if (!responseData || !responseData.cart) return false;

    const currentItems = document.querySelectorAll('.proposal-checkbox');
    return currentItems.length === (responseData.cart.items?.length || 0);
}

// 🔥 ФУНКЦИЯ ДЛЯ ПЕРЕЗАГРУЗКИ ЕСЛИ НУЖНО
function reloadIfNeeded(responseData) {
    if (!isCartUpdated(responseData)) {
        console.warn('[FRONTEND] Cart not updated properly, reloading page');
        setTimeout(() => {
            location.reload();
        }, 1000);
    }
}

// 🔥 УЛУЧШЕННАЯ ФУНКЦИЯ ОБНОВЛЕНИЯ ПЕРИОДА АРЕНДЫ
function initUpdateRentalPeriod() {
    const form = document.getElementById('update-rental-period-form');
    if (!form) {
        console.warn('[FRONTEND] Update rental period form not found');
        return;
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const selectedItems = getSelectedProposals();
        if (selectedItems.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Не выбраны элементы',
                text: 'Пожалуйста, выберите предложения для обновления дат'
            });
            return;
        }

        const formData = new FormData(this);
        formData.append('selected_items', JSON.stringify(selectedItems));

        const updateBtn = document.getElementById('update-period-btn');
        const originalText = updateBtn.innerHTML;
        updateBtn.disabled = true;
        updateBtn.innerHTML = '<i class="bi bi-arrow-repeat spinner me-2"></i>Обновление...';

        try {
            console.log('[FRONTEND] Sending update rental period request', {
                selectedItems: selectedItems,
                start_date: formData.get('start_date'),
                end_date: formData.get('end_date')
            });

            const response = await fetch('/api/cart/proposal/update-rental-period', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            // 🔥 УЛУЧШЕННАЯ ОБРАБОТКА ОТВЕТА
            const responseText = await response.text();
            console.log('[FRONTEND] Raw response text:', responseText);

            if (!responseText || responseText.trim() === '') {
                throw new Error('Пустой ответ от сервера');
            }

            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('[FRONTEND] JSON parse error:', parseError);

                if (responseText.includes('<html') || responseText.includes('<!DOCTYPE')) {
                    throw new Error('Сервер вернул HTML страницу. Проверьте аутентификацию.');
                } else {
                    throw new Error('Неверный формат ответа от сервера');
                }
            }

            console.log('[FRONTEND] Update rental period response', data);

            if (data.success) {
                console.log('[FRONTEND] Success response data structure:', data);

                await Swal.fire({
                    icon: 'success',
                    title: 'Успешно!',
                    text: data.message,
                    showConfirmButton: true,
                    confirmButtonText: 'OK'
                });

                // 🔥 УЛУЧШЕННАЯ ЛОГИКА ОБНОВЛЕНИЯ ИНТЕРФЕЙСА
                if (data.data) {
                    console.log('[FRONTEND] Updating interface with data:', data.data);

                    // Обновляем отображение корзины
                    updateCartDisplay(data.data);

                    // 🔥 ДОПОЛНИТЕЛЬНО: Обновляем данные в форме, если нужно
                    const startDateInput = document.querySelector('input[name="start_date"]');
                    const endDateInput = document.querySelector('input[name="end_date"]');

                    if (startDateInput && data.data.start_date) {
                        startDateInput.value = data.data.start_date;
                    }
                    if (endDateInput && data.data.end_date) {
                        endDateInput.value = data.data.end_date;
                    }

                    console.log('[FRONTEND] Interface updated successfully');
                } else {
                    console.log('[FRONTEND] No data in response, reloading page');
                    location.reload();
                }
            } else {
                throw new Error(data.message || 'Неизвестная ошибка сервера');
            }
        } catch (error) {
            console.error('[FRONTEND] Update rental period error', error);
            Swal.fire({
                icon: 'error',
                title: 'Ошибка',
                text: error.message,
            });
        } finally {
            updateBtn.disabled = false;
            updateBtn.innerHTML = originalText;
        }
    });
}

// 🔥 ОСНОВНАЯ ФУНКЦИЯ ИНИЦИАЛИЗАЦИИ МОДУЛЯ
function initProposalCartModule() {
    console.log('[FRONTEND] Initializing enhanced proposal cart module');

    // Получаем CSRF токен
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (!csrfMeta) {
        console.error('[FRONTEND] CSRF token meta tag not found!');
        return;
    }

    window.csrfToken = csrfMeta.getAttribute('content');
    console.log('[FRONTEND] CSRF token loaded');

    // Инициализируем все компоненты в правильном порядке
    try {
        initSelectAllProposals();
        initRemoveSelectedProposals();
        initRemoveSingleItem();
        initCheckoutSelected();
        initCheckoutAll();
        initExtendReservation();
        initProposalPopovers();
        initUpdateRentalPeriod();
        initTestApi();
        updateSelectedProposals();

        console.log('[FRONTEND] Enhanced proposal cart module initialized successfully');
    } catch (error) {
        console.error('[FRONTEND] Error initializing cart module:', error);
    }
}

// 🔥 ЗАПУСКАЕМ УЛУЧШЕННУЮ ИНИЦИАЛИЗАЦИЮ
document.addEventListener('DOMContentLoaded', function() {
    console.log('[FRONTEND] DOM loaded, starting enhanced initialization');
    initProposalCartModule();
});

// 🔥 ДУБЛИРУЮЩИЙ ЗАПУСК ДЛЯ НАДЕЖНОСТИ
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProposalCartModule);
} else {
    initProposalCartModule();
}
</script>
@endpush

<style>
.popover-delivery-details div {
    margin-bottom: 0.3rem;
}
.popover-delivery-details div:last-child {
    margin-bottom: 0;
}
</style>
