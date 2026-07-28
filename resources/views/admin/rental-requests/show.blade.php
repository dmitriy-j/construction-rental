@extends('layouts.app')

@section('title', 'Заявка #' . $rentalRequest->id . ' — ' . $rentalRequest->title)

@push('styles')
<style>
    .stat-card { border-left: 4px solid; border-radius: 0.5rem; transition: transform .15s; }
    .stat-card:hover { transform: translateY(-2px); }
    .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
    .progress-margin { height: 10px; border-radius: 5px; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    {{-- Хлебные крошки --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.rental-requests.index') }}">Заявки</a></li>
            <li class="breadcrumb-item active">#{{ $rentalRequest->id }} {{ $rentalRequest->title }}</li>
        </ol>
    </nav>

    {{-- Шапка --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <h1 class="h3 mb-0">{{ $rentalRequest->title }}</h1>
            <span class="badge fs-6 bg-{{ $rentalRequest->status_color }}">{{ $rentalRequest->status_text }}</span>
            @if($rentalRequest->visibility === 'public')
                <span class="badge fs-6 bg-info">Публичная</span>
            @else
                <span class="badge fs-6 bg-secondary">Приватная</span>
            @endif
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.rental-requests.edit', $rentalRequest->id) }}" class="btn btn-outline-warning">
                <i class="bi bi-pencil"></i> Редактировать
            </a>
            <form action="{{ route('admin.rental-requests.destroy', $rentalRequest->id) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Удалить заявку и все связанные данные (позиции, ответы, комментарии)?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-trash"></i> Удалить
                </button>
            </form>
        </div>
    </div>

    {{-- ФИНАНСОВЫЙ БЛОК --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card border-start-primary h-100">
                <div class="card-body d-flex align-items-start gap-3">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-cart"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Бюджет арендатора</small>
                        <div class="fs-4 fw-bold text-primary">{{ number_format($customerBudget, 0, '.', ' ') }} ₽</div>
                        <small class="text-muted">{{ $rentalRequest->user?->company?->legal_name ?? '—' }}</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card border-start-success h-100">
                <div class="card-body d-flex align-items-start gap-3">
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Бюджет арендодателя</small>
                        <div class="fs-4 fw-bold text-success">{{ number_format($lessorBudget, 0, '.', ' ') }} ₽</div>
                        <small class="text-muted">с вычетом наценки</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card border-start-warning h-100">
                <div class="card-body d-flex align-items-start gap-3">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Маржа платформы</small>
                        <div class="fs-4 fw-bold {{ $platformMargin > 0 ? 'text-warning' : 'text-muted' }}">
                            {{ number_format($platformMargin, 0, '.', ' ') }} ₽
                        </div>
                        @php $marginPercent = $customerBudget > 0 ? round($platformMargin / $customerBudget * 100, 1) : 0; @endphp
                        <small class="text-muted">{{ $marginPercent }}% от бюджета</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="progress progress-margin">
                @php $lessorPct = $customerBudget > 0 ? max(0, min(100, round($lessorBudget / $customerBudget * 100))) : 0; @endphp
                <div class="progress-bar bg-success" style="width: {{ $lessorPct }}%">
                    Арендодателю {{ $lessorPct }}%
                </div>
                <div class="progress-bar bg-warning" style="width: {{ 100 - $lessorPct }}%">
                    Платформе {{ 100 - $lessorPct }}%
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            {{-- ОСНОВНАЯ ИНФОРМАЦИЯ --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Основная информация</h5>
                    <small class="text-muted">ID: {{ $rentalRequest->id }} • Создана {{ $rentalRequest->created_at?->diffForHumans() }}</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block text-uppercase fw-semibold">Заказчик</small>
                                <strong>{{ $rentalRequest->user?->name ?? '—' }}</strong><br>
                                <small class="text-muted">{{ $rentalRequest->user?->email ?? '—' }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block text-uppercase fw-semibold">Компания</small>
                                <strong>{{ $rentalRequest->company?->legal_name ?? '—' }}</strong><br>
                                <small class="text-muted">ИНН {{ $rentalRequest->company?->inn ?? '—' }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block text-uppercase fw-semibold">Период аренды</small>
                            <strong>
                                <i class="bi bi-calendar-range me-1"></i>
                                {{ $rentalRequest->rental_period_start?->format('d.m.Y') }}
                                —
                                {{ $rentalRequest->rental_period_end?->format('d.m.Y') }}
                            </strong>
                            <span class="badge bg-light text-dark ms-2">
                                {{ \Carbon\Carbon::parse($rentalRequest->rental_period_start)->diffInDays(\Carbon\Carbon::parse($rentalRequest->rental_period_end)) + 1 }} дн.
                            </span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block text-uppercase fw-semibold">Локация</small>
                            <strong><i class="bi bi-geo-alt me-1"></i>{{ $rentalRequest->location?->name ?? '—' }}</strong>
                            @if($rentalRequest->delivery_required)
                                <span class="badge bg-info ms-1"><i class="bi bi-truck"></i> Доставка</span>
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold mb-1">Описание</small>
                        <p class="mb-0">{{ $rentalRequest->description ?: 'Нет описания' }}</p>
                    </div>
                </div>
            </div>

            {{-- ПОЗИЦИИ ЗАЯВКИ --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Позиции ({{ $rentalRequest->items->count() }})</h5>
                    <small class="text-muted">Всего {{ $rentalRequest->items->sum('quantity') }} ед.</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Категория</th>
                                    <th style="width:70px">Кол-во</th>
                                    <th style="width:120px">Ставка (₽/ч)</th>
                                    <th style="width:120px">Расчётная цена</th>
                                    <th>Условия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rentalRequest->items as $item)
                                    @php
                                        $conds = $item->effective_conditions;
                                        $lessorItem = collect($lessorPricing['items'] ?? [])->firstWhere('item_id', $item->id);
                                    @endphp
                                    <tr>
                                        <td class="text-muted">{{ $item->id }}</td>
                                        <td><strong>{{ $item->category?->name ?? '—' }}</strong></td>
                                        <td>{{ $item->quantity }} ед.</td>
                                        <td>
                                            <span class="text-decoration-line-through text-muted small">{{ number_format($item->hourly_rate ?? 0, 0) }}</span><br>
                                            <span class="fw-bold text-success">{{ number_format($lessorItem['lessor_hourly_rate'] ?? 0, 0) }} ₽</span>
                                        </td>
                                        <td>{{ number_format($item->calculated_price ?? 0, 0) }} ₽</td>
                                        <td>
                                            <small>
                                                @if($conds)
                                                    {{ ($conds['payment_type'] ?? 'hourly') === 'hourly' ? 'Почасовая' : 'Фикс' }} ·
                                                    {{ $conds['hours_per_shift'] ?? 8 }}ч × {{ $conds['shifts_per_day'] ?? 1 }}см
                                                    @if($conds['operator_included'] ?? false) · +Опер @endif
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ПРЕДЛОЖЕНИЯ / ОТВЕТЫ --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Ответы ({{ $rentalRequest->responses->count() }})</h5>
                    <div class="d-flex gap-2">
                        <span class="badge bg-warning">{{ $pendingProposals->count() }} ожидают</span>
                        <span class="badge bg-success">{{ $acceptedProposals->count() }} принято</span>
                        <span class="badge bg-danger">{{ $rejectedProposals->count() }} отклонено</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($rentalRequest->responses->isEmpty())
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Нет ответов на заявку
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Арендодатель</th>
                                        <th>Тип</th>
                                        <th>Цена</th>
                                        <th>Кол-во</th>
                                        <th>Дата</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rentalRequest->responses as $response)
                                        <tr>
                                            <td class="text-muted">{{ $response->id }}</td>
                                            <td>
                                                <strong>{{ $response->lessor?->company?->legal_name ?? $response->lessor?->name ?? '—' }}</strong>
                                                <br><small class="text-muted">{{ $response->lessor?->email ?? '' }}</small>
                                            </td>
                                            <td>
                                                @if($response->isComment())
                                                    <span class="badge bg-secondary">Комментарий</span>
                                                @else
                                                    <span class="badge bg-{{ $response->status === 'accepted' ? 'success' : ($response->status === 'rejected' ? 'danger' : 'warning') }}">
                                                        @switch($response->status)
                                                            @case('pending') Ожидает @break
                                                            @case('accepted') Принято @break
                                                            @case('rejected') Отклонено @break
                                                            @default {{ $response->status }}
                                                        @endswitch
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(!$response->isComment())
                                                    <strong>{{ number_format($response->proposed_price, 0) }} ₽</strong>
                                                    <br><small class="text-muted">{{ number_format($response->proposed_price / max($response->proposed_quantity, 1), 0) }} ₽/ед</small>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>{{ $response->isComment() ? '—' : $response->proposed_quantity }}</td>
                                            <td><small>{{ $response->created_at?->format('d.m.Y H:i') }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ПРАВАЯ КОЛОНКА --}}
        <div class="col-lg-4">
            {{-- Управление статусом --}}
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Статус</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.rental-requests.update', $rentalRequest->id) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <select name="status" class="form-select">
                                @foreach($statuses as $s)
                                    <option value="{{ $s }}" {{ $rentalRequest->status === $s ? 'selected' : '' }}>
                                        {{ \App\Models\RentalRequest::getStatusText($s) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-1"></i> Обновить статус
                        </button>
                    </form>
                </div>
            </div>

            {{-- Статистика --}}
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Статистика</h5></div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted">Позиций в заявке</span>
                            <strong>{{ $rentalRequest->items->count() }}</strong>
                        </li>
                        <li class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted">Всего единиц техники</span>
                            <strong>{{ $rentalRequest->items->sum('quantity') }}</strong>
                        </li>
                        <li class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted">Предложений</span>
                            <strong>{{ $proposals->count() }}</strong>
                        </li>
                        <li class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted">Рабочих часов</span>
                            <strong>{{ $lessorPricing['working_hours'] ?? 0 }}</strong>
                        </li>
                        <li class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted">Дней аренды</span>
                            <strong>{{ $lessorPricing['rental_days'] ?? 0 }}</strong>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span class="text-muted">Истекает</span>
                            <strong>{{ $rentalRequest->expires_at?->format('d.m.Y') ?? '—' }}</strong>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Применённые наценки --}}
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-tags me-2"></i>Наценки</h5></div>
                <div class="card-body">
                    @if($appliedMarkups->isEmpty())
                        <p class="text-muted mb-0 small">Наценки не применяются</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach($appliedMarkups as $m)
                                <li class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                    <div>
                                        <small class="fw-semibold d-block">
                                            @switch($m->markupable_type)
                                                @case(null) Общая @break
                                                @case(\App\Models\RentalRequest::class) Заявка #{{ $m->markupable_id }} @break
                                                @case(\App\Models\Equipment::class) Оборудование @break
                                                @case(\App\Models\Category::class) Категория @break
                                                @case(\App\Models\Company::class) Компания @break
                                                @default {{ class_basename($m->markupable_type) }}
                                            @endswitch
                                        </small>
                                        <small class="text-muted">{{ $m->type === 'fixed' ? '₽/час' : '%' }} · приоритет {{ $m->priority }}</small>
                                    </div>
                                    <span class="badge bg-info">{{ $m->value }}{{ $m->type === 'fixed' ? ' ₽' : '%' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <a href="{{ route('markups.create') }}?entity_type=rental_request" class="btn btn-sm btn-outline-primary mt-2 w-100">
                        <i class="bi bi-plus-circle me-1"></i> Создать наценку
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
