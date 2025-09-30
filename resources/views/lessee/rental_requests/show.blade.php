@extends('layouts.app')

@section('title', 'Просмотр заявки на аренду: ' . $rentalRequest->title)

@section('content')
<div class="container-fluid px-4">
    <!-- Точка монтирования Vue приложения -->
    <div id="rental-request-show-app"
        data-request-id="{{ $rentalRequest->id }}"
        data-api-url="{{ url('/api/lessee/rental-requests/' . $rentalRequest->id) }}"
        data-pause-url="{{ url('/api/lessee/rental-requests/' . $rentalRequest->id . '/pause') }}"
        data-cancel-url="{{ url('/api/lessee/rental-requests/' . $rentalRequest->id . '/cancel') }}"
        data-csrf-token="{{ csrf_token() }}"
        data-base-url="{{ url('/') }}">
        <!-- Загрузка Vue приложения -->
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <p class="mt-2">Загружаем интерактивную версию заявки...</p>
        </div>
    </div>

    <!-- Резервный вариант: полная Blade-версия (показывается если Vue не загрузился) -->
    <div id="blade-fallback-content" style="display: none;">
        <!-- Старое содержимое show.blade.php -->
        <div class="row">
            <div class="col-12">
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <h1 class="page-title">Заявка на аренду: {{ $rentalRequest->title }}</h1>
                    <div>
                        <a href="{{ route('lessee.rental-requests.index') }}" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-2"></i>Назад к списку
                        </a>
                        @if($rentalRequest->status === 'active')
                        <button class="btn btn-warning me-2" data-bs-toggle="modal" data-bs-target="#pauseModal">
                            <i class="fas fa-pause me-2"></i>Приостановить
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Хлебные крошки статуса --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="status-breadcrumb">
                    <div class="step {{ $rentalRequest->status === 'active' ? 'active' : '' }} {{ $rentalRequest->status === 'processing' || $rentalRequest->status === 'completed' ? 'completed' : '' }}">
                        <span class="step-number">1</span>
                        <span class="step-label">Активна</span>
                    </div>
                    <div class="step {{ $rentalRequest->status === 'processing' ? 'active' : '' }} {{ $rentalRequest->status === 'completed' ? 'completed' : '' }}">
                        <span class="step-number">2</span>
                        <span class="step-label">В процессе</span>
                    </div>
                    <div class="step {{ $rentalRequest->status === 'completed' ? 'active' : '' }}">
                        <span class="step-number">3</span>
                        <span class="step-label">Завершена</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Основная информация --}}
            <div class="col-lg-8">
                {{-- Карточка основной информации --}}
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Основная информация
                        </h5>
                        <span class="badge bg-{{ $rentalRequest->status_color }} fs-6">
                            {{ $rentalRequest->status_text }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <label class="text-muted small">Описание проекта</label>
                                    <p class="mb-0">{{ $rentalRequest->description }}</p>
                                </div>

                                <div class="info-item mb-3">
                                    <label class="text-muted small">Категория техники</label>
                                    <p class="mb-0">
                                        <span class="badge bg-light text-dark fs-6">{{ $rentalRequest->category->name ?? 'Не указана' }}</span>
                                    </p>
                                </div>

                                <div class="info-item mb-3">
                                    <label class="text-muted small">Локация объекта</label>
                                    <p class="mb-0">
                                        <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                        {{ $rentalRequest->location->name ?? 'Не указана' }}
                                        <br>
                                        <small class="text-muted">{{ $rentalRequest->location->address ?? '' }}</small>
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <label class="text-muted small">Период аренды</label>
                                    <p class="mb-0">
                                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                                        {{ $rentalRequest->rental_period_start->format('d.m.Y') }} - {{ $rentalRequest->rental_period_end->format('d.m.Y') }}
                                        <br>
                                        <small class="text-muted">
                                            {{ $rentalRequest->rental_period_start->diffInDays($rentalRequest->rental_period_end) + 1 }} дней
                                        </small>
                                    </p>
                                </div>

                                <div class="info-item mb-3">
                                    <label class="text-muted small">Бюджет заявки</label>
                                    <p class="mb-0 fs-5 text-success fw-bold">
                                        {{ number_format($rentalRequest->total_budget ?? $rentalRequest->calculated_budget_from ?? $rentalRequest->budget_from, 0, ',', ' ') }} ₽
                                    </p>
                                </div>

                                <div class="info-item">
                                    <label class="text-muted small">Доставка</label>
                                    <p class="mb-0">
                                        <i class="fas fa-truck me-2"></i>
                                        {{ $rentalRequest->delivery_required ? 'Требуется' : 'Не требуется' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        @if($rentalRequest->desired_specifications)
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <strong>Дополнительные требования:</strong><br>
                                    {{ is_array($rentalRequest->desired_specifications) ? ($rentalRequest->desired_specifications['description'] ?? '') : $rentalRequest->desired_specifications }}
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Позиции заявки --}}
                @if($rentalRequest->items && $rentalRequest->items->count() > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cubes me-2"></i>Позиции заявки ({{ $rentalRequest->items->count() }})
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Категория</th>
                                        <th>Количество</th>
                                        <th>Стоимость/час</th>
                                        <th>Условия</th>
                                        <th>Стоимость позиции</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rentalRequest->items as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->category->name ?? 'Не указана' }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary rounded-pill">{{ $item->quantity }}</span>
                                        </td>
                                        <td>
                                            {{ number_format($item->hourly_rate ?? $rentalRequest->hourly_rate, 0, ',', ' ') }} ₽
                                        </td>
                                        <td>
                                            @if($item->use_individual_conditions)
                                                <span class="badge bg-warning">Индивидуальные</span>
                                            @else
                                                <span class="badge bg-secondary">Общие</span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ number_format($item->calculated_price ?? 0, 0, ',', ' ') }} ₽</strong>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Итого:</strong></td>
                                        <td><strong>{{ number_format($rentalRequest->total_budget ?? $rentalRequest->calculated_budget_from ?? $rentalRequest->budget_from, 0, ',', ' ') }} ₽</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Предложения от арендодателей --}}
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-handshake me-2"></i>
                            Предложения от арендодателей
                            <span class="badge bg-primary ms-2">{{ $rentalRequest->responses_count }}</span>
                        </h5>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-sort me-1"></i>Сортировка
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="sortProposals('price')">По цене</a></li>
                                <li><a class="dropdown-item" href="#" onclick="sortProposals('rating')">По рейтингу</a></li>
                                <li><a class="dropdown-item" href="#" onclick="sortProposals('date')">По дате</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($rentalRequest->responses->count() > 0)
                            <div id="proposalsList">
                                @foreach($rentalRequest->responses->sortByDesc('created_at') as $response)
                                <div class="proposal-card card mb-3" data-price="{{ $response->proposed_price }}"
                                     data-rating="{{ $response->lessor->company->average_rating ?? 0 }}"
                                     data-date="{{ $response->created_at->timestamp }}">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-start mb-2">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">
                                                            {{ $response->lessor->company->legal_name ?? 'Компания' }}
                                                            @if($response->lessor->company->average_rating)
                                                                <span class="badge bg-warning ms-2">
                                                                    <i class="fas fa-star me-1"></i>{{ number_format($response->lessor->company->average_rating, 1) }}
                                                                </span>
                                                            @endif
                                                        </h6>
                                                        <p class="text-muted small mb-1">
                                                            Оборудование: {{ $response->equipment->title ?? 'Не указано' }}
                                                        </p>
                                                        @if($response->message)
                                                        <p class="mb-2">{{ $response->message }}</p>
                                                        @endif
                                                        <div class="proposal-details small text-muted">
                                                            <span class="me-3">
                                                                <i class="fas fa-cube me-1"></i>
                                                                Предложено: {{ $response->proposed_quantity }} ед.
                                                            </span>
                                                            <span>
                                                                <i class="fas fa-clock me-1"></i>
                                                                {{ $response->created_at->format('d.m.Y H:i') }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-end">
                                                    <div class="proposal-price mb-2">
                                                        <span class="h5 text-primary">
                                                            {{ number_format($response->proposed_price, 0, ',', ' ') }} ₽
                                                        </span>
                                                        <div class="small text-muted">за весь период</div>
                                                    </div>
                                                    <div class="proposal-actions">
                                                        @if($response->status === 'pending')
                                                        <button class="btn btn-sm btn-success me-1"
                                                                onclick="acceptProposal({{ $response->id }})">
                                                            <i class="fas fa-check me-1"></i>Принять
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger"
                                                                onclick="rejectProposal({{ $response->id }})">
                                                            Отклонить
                                                        </button>
                                                        @else
                                                        <span class="badge bg-{{ $response->status === 'accepted' ? 'success' : 'secondary' }}">
                                                            {{ $response->status === 'accepted' ? 'Принято' : 'Отклонено' }}
                                                        </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-handshake fa-3x text-muted mb-3"></i>
                                <h5>Пока нет предложений</h5>
                                <p class="text-muted">Арендодатели увидят вашу заявку и скоро предложат свои варианты</p>
                                <div class="alert alert-info">
                                    <small>
                                        <i class="fas fa-lightbulb me-2"></i>
                                        Совет: Чтобы привлечь больше предложений, убедитесь что заявка содержит подробное описание и реальный бюджет
                                    </small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Боковая панель --}}
            <div class="col-lg-4">
                {{-- Статистика заявки --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Статистика заявки
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value">{{ $rentalRequest->views_count }}</div>
                                <div class="stat-label">Просмотров</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">{{ $rentalRequest->responses_count }}</div>
                                <div class="stat-label">Предложений</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">{{ $rentalRequest->items_count ?? 1 }}</div>
                                <div class="stat-label">Позиций</div>
                            </div>
                        </div>

                        <div class="progress mt-3" style="height: 10px;">
                            @php
                                $completionRate = min(100, ($rentalRequest->responses_count / max(1, $rentalRequest->items_count ?? 1)) * 100);
                            @endphp
                            <div class="progress-bar bg-success" role="progressbar"
                                 style="width: {{ $completionRate }}%"
                                 title="Заполнено {{ round($completionRate) }}%">
                            </div>
                        </div>
                        <div class="text-center small text-muted mt-1">
                            Заполнено {{ round($completionRate) }}% заявки
                        </div>

                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Создана: {{ $rentalRequest->created_at->format('d.m.Y H:i') }}
                                @if($rentalRequest->expires_at)
                                <br>
                                <i class="fas fa-hourglass-end me-1"></i>
                                Истекает: {{ $rentalRequest->expires_at->format('d.m.Y') }}
                                @endif
                            </small>
                        </div>
                    </div>
                </div>

                {{-- Действия --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Действия с заявкой</h6>
                    </div>
                    <div class="card-body">
                        @if($rentalRequest->status === 'active')
                        <div class="d-grid gap-2">
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#pauseModal">
                                <i class="fas fa-pause me-2"></i>Приостановить заявку
                            </button>
                            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                <i class="fas fa-times me-2"></i>Отменить заявку
                            </button>
                            <a href="{{ route('lessee.rental-requests.edit', $rentalRequest->id) }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-edit me-2"></i>Редактировать
                            </a>
                        </div>
                        @endif

                        @if($rentalRequest->status === 'processing')
                        <div class="alert alert-success">
                            <i class="fas fa-check me-2"></i>
                            Заявка в процессе обработки. Ожидайте подтверждения.
                        </div>
                        @endif

                        @if($rentalRequest->status === 'completed')
                        <div class="alert alert-info">
                            <i class="fas fa-flag-checkered me-2"></i>
                            Заявка успешно завершена.
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Быстрые действия --}}
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Быстрые действия</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('lessee.rental-requests.create') }}?copy_from={{ $rentalRequest->id }}"
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-copy me-2"></i>Создать похожую заявку
                            </a>
                            <button class="btn btn-outline-secondary btn-sm" onclick="exportToPDF()">
                                <i class="fas fa-download me-2"></i>Экспорт в PDF
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="shareRequest()">
                                <i class="fas fa-share-alt me-2"></i>Поделиться заявкой
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Модальные окна --}}
<div class="modal fade" id="pauseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Приостановка заявки</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите приостановить заявку? Арендодатели больше не будут видеть её в поиске.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-warning" onclick="pauseRequest()">Приостановить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Отмена заявки</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите отменить заявку? Это действие нельзя будет отменить.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" onclick="cancelRequest()">Отменить заявку</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.status-breadcrumb {
    display: flex;
    justify-content: center;
    margin-bottom: 2rem;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0 2rem;
    position: relative;
}

.step:not(:last-child):after {
    content: '';
    position: absolute;
    top: 20px;
    right: -1rem;
    width: 2rem;
    height: 2px;
    background-color: #dee2e6;
}

.step.completed:not(:last-child):after {
    background-color: #198754;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.5rem;
    font-weight: bold;
}

.step.active .step-number {
    background-color: #0d6efd;
    color: white;
}

.step.completed .step-number {
    background-color: #198754;
    color: white;
}

.step-label {
    font-size: 0.9rem;
    font-weight: 500;
}

.info-item {
    border-left: 3px solid #0d6efd;
    padding-left: 1rem;
}

.proposal-card {
    transition: transform 0.2s ease;
    border-left: 4px solid #198754;
}

.proposal-card:hover {
    transform: translateX(5px);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    text-align: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #0d6efd;
}

.stat-label {
    font-size: 0.8rem;
    color: #6c757d;
}

/* Стили для Vue приложения */
#rental-request-show-app {
    min-height: 400px;
}

#blade-fallback-content {
    transition: opacity 0.3s ease;
}
</style>
@endpush

@push('scripts')
@vite(['resources/js/pages/rental-request-show.js'])

<script>
// Функции для Blade-резервной версии
function acceptProposal(proposalId) {
    if (confirm('Вы уверены, что хотите принять это предложение?')) {
        fetch(`/lessee/rental-requests/{{ $rentalRequest->id }}/proposals/${proposalId}/accept`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Предложение принято!');
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showToast('error', 'Ошибка: ' + data.message);
            }
        })
        .catch(error => {
            showToast('error', 'Произошла ошибка: ' + error.message);
        });
    }
}

function rejectProposal(proposalId) {
    if (confirm('Вы уверены, что хотите отклонить это предложение?')) {
        fetch(`/lessee/rental-requests/{{ $rentalRequest->id }}/proposals/${proposalId}/reject`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Предложение отклонено');
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showToast('error', 'Ошибка: ' + data.message);
            }
        })
        .catch(error => {
            showToast('error', 'Произошла ошибка: ' + error.message);
        });
    }
}

function sortProposals(criteria) {
    const proposals = Array.from(document.querySelectorAll('.proposal-card'));

    proposals.sort((a, b) => {
        const aValue = a.getAttribute('data-' + criteria);
        const bValue = b.getAttribute('data-' + criteria);

        if (criteria === 'date') {
            return bValue - aValue;
        }
        return aValue - bValue;
    });

    const container = document.getElementById('proposalsList');
    container.innerHTML = '';
    proposals.forEach(proposal => container.appendChild(proposal));

    showToast('info', 'Отсортировано по ' +
        (criteria === 'price' ? 'цене' :
         criteria === 'rating' ? 'рейтингу' : 'дате'));
}

function pauseRequest() {
    fetch(`/lessee/rental-requests/{{ $rentalRequest->id }}/pause`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Заявка приостановлена');
            location.reload();
        }
    })
    .catch(error => {
        showToast('error', 'Ошибка при приостановке заявки');
    });
}

function cancelRequest() {
    fetch(`/lessee/rental-requests/{{ $rentalRequest->id }}/cancel`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Заявка отменена');
            location.reload();
        }
    })
    .catch(error => {
        showToast('error', 'Ошибка при отмене заявки');
    });
}

function exportToPDF() {
    showToast('info', 'Функция экспорта в PDF в разработке');
}

function shareRequest() {
    if (navigator.share) {
        navigator.share({
            title: 'Заявка на аренду: {{ $rentalRequest->title }}',
            text: '{{ $rentalRequest->description }}',
            url: window.location.href
        });
    } else {
        // Fallback для браузеров без поддержки Web Share API
        navigator.clipboard.writeText(window.location.href);
        showToast('success', 'Ссылка скопирована в буфер обмена');
    }
}

function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 5000);
}

// Резервный вариант: если Vue не загрузился через 3 секунды, показываем Blade-версию
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const vueApp = document.querySelector('#rental-request-show-app');
        if (!vueApp.__vue_app__) {
            console.log('Vue не загрузился, показываем резервный Blade-контент');
            document.getElementById('blade-fallback-content').style.display = 'block';
            vueApp.style.display = 'none';

            // Инициализируем Bootstrap компоненты для резервной версии
            if (typeof bootstrap !== 'undefined') {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        }
    }, 3000);
});

// Авто-обновление для Blade-версии (если Vue не загрузился)
setInterval(() => {
    if (document.visibilityState === 'visible' &&
        document.getElementById('blade-fallback-content').style.display !== 'none') {
        const proposalsCount = {{ $rentalRequest->responses_count }};
        fetch(window.location.href)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const newDoc = parser.parseFromString(html, 'text/html');
                const newCount = newDoc.querySelector('.badge.bg-primary')?.textContent;

                if (newCount && newCount != proposalsCount) {
                    showToast('info', 'Появились новые предложения! Обновляем страницу...');
                    setTimeout(() => location.reload(), 2000);
                }
            });
    }
}, 120000);
</script>
@endpush
