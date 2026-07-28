{{-- resources/views/lessor/rental-requests/show.blade.php --}}
@extends('layouts.app')

@section('title', $request->title . ' — Панель арендодателя')

@php
    // Вычисляем бюджет для отображения (с обратной наценкой)
    $displayBudget = 0;
    if ($lessorPricing && !empty($lessorPricing['total_lessor_budget'])) {
        $displayBudget = $lessorPricing['total_lessor_budget'];
    } elseif ($request->total_budget > 0) {
        $displayBudget = $request->total_budget;
    }
@endphp

@section('content')
<div class="container-fluid px-4 lessor-container">
    {{-- Хедер --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ $request->title }}</h1>
            <span class="badge bg-{{ $request->status_color }} fs-6">{{ $request->status_text }}</span>
            @if($request->visibility === 'public')
                <span class="badge bg-info ms-1">Публичная</span>
            @endif
        </div>
        <div>
            <a href="{{ route('lessor.rental-requests.index') }}" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left me-1"></i>Назад
            </a>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#proposalModal">
                <i class="fas fa-paper-plane me-1"></i>Предложить технику
            </button>
        </div>
    </div>

    {{-- Аналитика --}}
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Аналитика</h5></div>
        <div class="card-body">
            <div class="row text-center g-3">
                <div class="col-3"><div class="fw-bold fs-4 text-primary">{{ $analytics['total_proposals'] ?? 0 }}</div><small class="text-muted">Всего предложений</small></div>
                <div class="col-3"><div class="fw-bold fs-4 text-info">{{ $analytics['my_proposals'] ?? 0 }}</div><small class="text-muted">Ваших</small></div>
                <div class="col-3"><div class="fw-bold fs-4 text-success">{{ $analytics['my_accepted_proposals'] ?? 0 }}</div><small class="text-muted">Принято</small></div>
                <div class="col-3"><div class="fw-bold fs-4 text-warning">{{ $analytics['my_conversion_rate'] ?? 0 }}%</div><small class="text-muted">Конверсия</small></div>
            </div>
        </div>
    </div>

    {{-- Основная информация --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Описание</h5></div>
                <div class="card-body">
                    <p>{{ $request->description ?: 'Описание отсутствует' }}</p>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Период аренды</small>
                            <strong>{{ \Carbon\Carbon::parse($request->rental_period_start)->format('d.m.Y') }} — {{ \Carbon\Carbon::parse($request->rental_period_end)->format('d.m.Y') }}</strong>
                            <span class="badge bg-light text-dark ms-2">{{ \Carbon\Carbon::parse($request->rental_period_start)->diffInDays(\Carbon\Carbon::parse($request->rental_period_end)) + 1 }} дн.</span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Локация</small>
                            <strong><i class="fas fa-map-marker-alt me-1 text-danger"></i>{{ $request->location->name ?? 'Не указана' }}</strong>
                        </div>
                        @if($request->delivery_required)
                        <div class="col-12 mt-3">
                            <span class="badge bg-info"><i class="fas fa-truck me-1"></i>Требуется доставка</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white"><h5 class="mb-0"><i class="fas fa-ruble-sign me-2"></i>Бюджет</h5></div>
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    @if($displayBudget > 0)
                        <div class="display-5 fw-bold text-success">{{ number_format($displayBudget, 0, '.', ' ') }} ₽</div>
                    @else
                        <div class="text-muted">Бюджет не указан</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Позиции заявки --}}
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-list me-2"></i>Требуемая техника ({{ $request->items->count() }} позиций)</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr>
                        <th>Категория</th>
                        <th>Количество</th>
                        <th>Цена за час (₽)</th>
                        <th style="min-width:200px;">Характеристики</th>
                        <th style="min-width:220px;">Условия аренды</th>
                    </tr></thead>
                    <tbody>
                        @foreach($request->items as $item)
                        @php
                            $cond = $item->use_individual_conditions && $item->individual_conditions
                                ? $item->individual_conditions
                                : $request->rental_conditions;
                            // Берём цену из lessorPricing (с вычетом наценки)
                            $lessorItemPrice = $item->hourly_rate;
                            if (!empty($lessorPricing['items'])) {
                                $matched = collect($lessorPricing['items'])->firstWhere('item_id', $item->id);
                                if ($matched && ($matched['lessor_hourly_rate'] ?? 0) > 0) {
                                    $lessorItemPrice = $matched['lessor_hourly_rate'];
                                }
                            }
                        @endphp
                        <tr>
                            <td><strong>{{ $item->category->name ?? '—' }}</strong></td>
                            <td>{{ $item->quantity }} ед.</td>
                            <td>{{ number_format($lessorItemPrice, 0, '.', ' ') }}</td>
                            <td>
                                @if($item->formatted_specifications && count($item->formatted_specifications) > 0)
                                    @foreach($item->formatted_specifications as $spec)
                                        <span class="badge bg-light text-dark me-1 mb-1">{{ $spec['label'] ?? $spec['key'] ?? '—' }}: {{ $spec['display_value'] ?? $spec['value'] ?? '—' }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted small">Нет характеристик</span>
                                @endif
                            </td>
                            <td><small>
                                @if($cond)
                                    {{ $cond['payment_type'] === 'hourly' ? 'Почасовая' : 'Фикс' }} |
                                    {{ $cond['hours_per_shift'] ?? 8 }}ч × {{ $cond['shifts_per_day'] ?? 1 }}см |
                                    Доставка: {{ ($cond['transportation_organized_by'] ?? 'lessor') === 'lessor' ? 'Арендодатель' : 'Арендатор' }} |
                                    ГСМ: {{ ($cond['gsm_payment'] ?? 'included') === 'included' ? 'Вкл' : 'Отд' }}
                                    @if($cond['operator_included'] ?? false) | +Оператор @endif
                                    @if($cond['accommodation_payment'] ?? false) | +Проживание @endif
                                    @if($cond['extension_possibility'] ?? false) | +Продление @endif
                                @else
                                    <span class="text-muted">Не указаны</span>
                                @endif
                            </small></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Мои предложения по этой заявке --}}
    @php $myProposals = $proposalHistory ?? []; @endphp
    @if(count($myProposals) > 0)
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-history me-2"></i>Мои предложения</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr>
                        <th>#</th>
                        <th>Оборудование</th>
                        <th>Цена (₽)</th>
                        <th>Кол-во</th>
                        <th>Статус</th>
                        <th>Причина отказа</th>
                        <th>Дата</th>
                    </tr></thead>
                    <tbody>
                        @foreach($myProposals as $p)
                        <tr>
                            <td class="text-muted">{{ $p['id'] }}</td>
                            <td>{{ $p['equipment_title'] ?? '—' }}</td>
                            <td>{{ number_format($p['proposed_price'] ?? 0, 0, '.', ' ') }}</td>
                            <td>{{ $p['proposed_quantity'] ?? '—' }}</td>
                            <td>
                                @php
                                    $statusClass = match($p['status']) {
                                        'accepted' => 'success',
                                        'rejected' => 'danger',
                                        'pending' => 'warning',
                                        default => 'secondary'
                                    };
                                    $statusText = match($p['status']) {
                                        'accepted' => 'Принято',
                                        'rejected' => 'Отклонено',
                                        'pending' => 'Ожидает',
                                        default => $p['status']
                                    };
                                @endphp
                                <span class="badge bg-{{ $statusClass }}">{{ $statusText }}</span>
                            </td>
                            <td>
                                @if($p['status'] === 'rejected' && !empty($p['rejection_reason']))
                                    <small class="text-danger">{{ $p['rejection_reason'] }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><small>{{ $p['created_at'] ?? '' }}</small></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- МОДАЛКА "Предложить технику" --}}
<div class="modal fade" id="proposalModal" tabindex="-1" aria-labelledby="proposalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md" style="max-width: 700px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="proposalModalLabel">
                    <i class="fas fa-paper-plane me-2"></i>Предложить технику
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form id="proposalForm">
                @csrf
                <div class="modal-body">
                    <div id="modalAlert" class="alert d-none" role="alert"></div>

                    <div class="mb-3">
                        <small class="text-muted">Заявка: <strong>{{ $request->title }}</strong></small>
                    </div>

                    {{-- Категории и техника --}}
                    <div id="equipmentCategories">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Загрузка...</span>
                            </div>
                            <p class="mt-2 text-muted">Загрузка доступной техники...</p>
                        </div>
                    </div>

                    <hr>

                    {{-- Комментарий --}}
                    <div class="mb-3">
                        <label for="proposalMessage" class="form-label fw-semibold">Комментарий к предложению</label>
                        <textarea class="form-control" id="proposalMessage" rows="3" placeholder="Опишите условия, сроки, дополнительные услуги..." required minlength="10"></textarea>
                        <div class="form-text">Минимум 10 символов</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary" id="submitProposalBtn" disabled>
                        <i class="fas fa-paper-plane me-1"></i>Отправить предложение
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.lessor-container { padding: 1.5rem; }
.table th { border-top: none; font-weight: 600; font-size: 0.8125rem; text-transform: uppercase; letter-spacing: 0.3px; color: #6c757d; }
@media (max-width: 768px) { .lessor-container { padding: 0.75rem; } }
.display-5 { font-size: 2.5rem; }

.equipment-card {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 0.75rem;
    transition: all 0.2s;
}
.equipment-card:hover {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.1);
}
.equipment-card.selected {
    border-color: #0d6efd;
    background-color: #f0f7ff;
}
.equipment-price-input {
    max-width: 160px;
}
.category-section h6 {
    background: #f8f9fa;
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem;
    border-left: 4px solid #0d6efd;
}
</style>
@endpush

@php
    $categoryIdsJson = json_encode($request->items->pluck('category_id')->unique()->values()->toArray());
    // Для модалки используем цену с вычетом наценки (из lessorPricing)
    $requestItemsData = $request->items->map(function($item) use ($lessorPricing) {
        $lessorHourlyRate = (float)($item->hourly_rate ?? 0);
        if (!empty($lessorPricing['items'])) {
            $matched = collect($lessorPricing['items'])->firstWhere('item_id', $item->id);
            if ($matched && ($matched['lessor_hourly_rate'] ?? 0) > 0) {
                $lessorHourlyRate = $matched['lessor_hourly_rate'];
            }
        }
        return [
            'id' => $item->id,
            'category_id' => $item->category_id,
            'category_name' => $item->category->name ?? '',
            'quantity' => $item->quantity,
            'hourly_rate' => $lessorHourlyRate, // цена арендодателя (с вычетом наценки)
            'customer_rate' => (float)($item->hourly_rate ?? 0), // исходная цена арендатора
        ];
    })->values()->toArray();
    $requestItemsJson = json_encode($requestItemsData);
@endphp

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('proposalModal');
    const container = document.getElementById('equipmentCategories');
    const form = document.getElementById('proposalForm');
    const submitBtn = document.getElementById('submitProposalBtn');
    const alertBox = document.getElementById('modalAlert');
    const messageInput = document.getElementById('proposalMessage');

    // Категории из заявки
    const categoryIds = {!! $categoryIdsJson !!};
    const requestItems = {!! $requestItemsJson !!};

    let selectedEquipment = {};

    // Загрузка данных при открытии модалки
    modal.addEventListener('show.bs.modal', function() {
        loadEquipment();
    });

    // Сброс при закрытии
    modal.addEventListener('hidden.bs.modal', function() {
        form.reset();
        selectedEquipment = {};
        submitBtn.disabled = true;
        hideAlert();
    });

    // Валидация комментария
    messageInput.addEventListener('input', function() {
        updateSubmitButton();
    });

    function loadEquipment() {
        container.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
                <p class="mt-2 text-muted">Загрузка доступной техники...</p>
            </div>
        `;

        const params = categoryIds.map(id => `ids[]=${id}`).join('&');

        fetch('/api/lessor/equipment/categories?' + params, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(result => {
            if (result.success && result.data) {
                renderEquipment(result.data);
            } else {
                showError(result.message || 'Не удалось загрузить технику');
            }
        })
        .catch(error => {
            console.error('Error loading equipment:', error);
            showError('Ошибка загрузки техники. Проверьте подключение.');
        });
    }

    function renderEquipment(categories) {
        if (!categories || categories.length === 0) {
            container.innerHTML = `
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    У вас нет техники в требуемых категориях.
                    <a href="/lessor/equipment/create" class="alert-link">Добавьте технику в каталог</a>
                </div>
            `;
            submitBtn.disabled = true;
            return;
        }

        let hasEquipment = false;
        let html = '';

        categories.forEach(cat => {
            if (!cat.equipment || cat.equipment.length === 0) return;

            const requestItem = requestItems.find(ri => ri.category_id === cat.category_id);
            const maxQty = requestItem ? requestItem.quantity : 1;
            const lessorRate = requestItem ? requestItem.hourly_rate : 0; // цена с вычетом наценки

            hasEquipment = true;
            html += `
                <div class="category-section mb-4">
                    <h6 class="mb-3">
                        <i class="fas fa-tag me-1"></i>${cat.category_name}
                        <span class="badge bg-secondary ms-2">${cat.equipment_count} ед.</span>
                        <small class="text-muted ms-2">нужно ${maxQty} ед.</small>
                    </h6>
            `;

            cat.equipment.forEach(eq => {
                const pricePerHour = eq.price_per_hour || 0;
                html += `
                    <div class="equipment-card" data-equipment-id="${eq.id}" data-category-id="${cat.category_id}">
                        <div class="form-check">
                            <input class="form-check-input equipment-checkbox" type="checkbox"
                                id="eq_${eq.id}"
                                value="${eq.id}"
                                data-category-id="${cat.category_id}"
                                data-max-qty="${maxQty}"
                                data-lessor-rate="${lessorRate}"
                                ${!eq.has_active_terms ? 'disabled' : ''}>
                            <label class="form-check-label fw-medium" for="eq_${eq.id}">
                                ${eq.title}
                                ${eq.brand ? `(${eq.brand} ${eq.model || ''})` : ''}
                                ${eq.year ? `, ${eq.year} г.` : ''}
                            </label>
                            <span class="badge ${eq.availability_status === 'available' ? 'bg-success' : 'bg-warning'} ms-1">
                                ${eq.availability_status === 'available' ? 'Доступна' : 'Недоступна'}
                            </span>
                            ${!eq.has_active_terms ? '<span class="badge bg-danger ms-1">Нет условий аренды</span>' : ''}
                        </div>
                        <div class="row mt-2 g-2 align-items-center">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Ставка арендодателя</small>
                                <strong>${pricePerHour.toLocaleString('ru-RU')} ₽/час</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Ваша цена (₽/час)</small>
                                <input type="number" class="form-control form-control-sm equipment-price-input"
                                    name="price_${eq.id}"
                                    data-equipment-id="${eq.id}"
                                    value="${lessorRate}"
                                    min="1" step="0.01" disabled>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Количество</small>
                                <input type="number" class="form-control form-control-sm equipment-qty-input"
                                    name="qty_${eq.id}"
                                    data-equipment-id="${eq.id}"
                                    value="1" min="1" max="${maxQty}" disabled>
                            </div>
                        </div>
                        ${eq.specifications && eq.specifications.length > 0 ? `
                        <div class="mt-2">
                            <small class="text-muted">
                                ${eq.specifications.map(s => `<span class="badge bg-light text-dark me-1">${s.name}: ${s.value} ${s.unit || ''}</span>`).join('')}
                            </small>
                        </div>` : ''}
                    </div>
                `;
            });

            html += `</div>`;
        });

        if (!hasEquipment) {
            container.innerHTML = `
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    У вас нет техники в требуемых категориях.
                    <a href="/lessor/equipment/create" class="alert-link">Добавьте технику в каталог</a>
                </div>
            `;
            submitBtn.disabled = true;
            return;
        }

        container.innerHTML = html;
        submitBtn.disabled = true;

        // Обработчики чекбоксов
        document.querySelectorAll('.equipment-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                const eqId = this.value;
                const card = this.closest('.equipment-card');
                const priceInput = card.querySelector('.equipment-price-input');
                const qtyInput = card.querySelector('.equipment-qty-input');

                if (this.checked) {
                    card.classList.add('selected');
                    priceInput.disabled = false;
                    priceInput.focus();
                    qtyInput.disabled = false;
                    selectedEquipment[eqId] = {
                        equipment_id: parseInt(eqId),
                        proposed_price: parseFloat(priceInput.value) || parseFloat(priceInput.placeholder) || 0,
                        quantity: parseInt(qtyInput.value) || 1,
                    };
                } else {
                    card.classList.remove('selected');
                    priceInput.disabled = true;
                    qtyInput.disabled = true;
                    delete selectedEquipment[eqId];
                }
                updateSubmitButton();
            });
        });

        // Обработчики ввода цены и количества (делегирование)
        container.addEventListener('input', function(e) {
            const input = e.target;
            const eqId = input.dataset.equipmentId;
            if (!eqId || !selectedEquipment[eqId]) return;

            if (input.classList.contains('equipment-price-input')) {
                selectedEquipment[eqId].proposed_price = parseFloat(input.value) || parseFloat(input.placeholder) || 0;
            } else if (input.classList.contains('equipment-qty-input')) {
                selectedEquipment[eqId].quantity = parseInt(input.value) || 1;
            }
        });
    }

    function updateSubmitButton() {
        const hasSelected = Object.keys(selectedEquipment).length > 0;
        const hasMessage = messageInput.value.trim().length >= 10;
        submitBtn.disabled = !(hasSelected && hasMessage);
    }

    function showError(message) {
        container.innerHTML = `
            <div class="alert alert-warning mb-0">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
                <div class="mt-2">
                    <a href="/lessor/equipment/create" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-plus me-1"></i>Добавить технику в каталог
                    </a>
                </div>
            </div>
        `;
        submitBtn.disabled = true;
    }

    function showAlert(type, message) {
        alertBox.className = `alert alert-${type} show`;
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    function hideAlert() {
        alertBox.className = 'alert d-none';
        alertBox.textContent = '';
    }

    // Отправка формы
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        hideAlert();

        const equipmentItems = Object.values(selectedEquipment);
        const message = messageInput.value.trim();

        if (equipmentItems.length === 0) {
            showAlert('danger', 'Выберите хотя бы одну единицу техники');
            return;
        }

        if (message.length < 10) {
            showAlert('danger', 'Комментарий должен содержать не менее 10 символов');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Отправка...';

        fetch('/api/rental-requests/{{ $request->id }}/proposals', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                equipment_items: equipmentItems,
                message: message
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showAlert('success', result.message || 'Предложение успешно отправлено!');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showAlert('danger', result.message || 'Ошибка при отправке предложения');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Отправить предложение';
            }
        })
        .catch(error => {
            console.error('Error submitting proposal:', error);
            showAlert('danger', 'Произошла ошибка при отправке. Попробуйте ещё раз.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Отправить предложение';
        });
    });
});
</script>
@endpush
