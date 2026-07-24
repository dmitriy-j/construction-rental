{{-- resources/views/lessor/rental-requests/show.blade.php --}}
@extends('layouts.app')

@section('title', $request->title . ' — Панель арендодателя')

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
            <a href="#" class="btn btn-primary btn-sm" onclick="alert('Функционал предложений будет добавлен');return false;">
                <i class="fas fa-paper-plane me-1"></i>Предложить технику
            </a>
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
                    @php
                        $budget = null;
                        if ($lessorPricing && !empty($lessorPricing['total_lessor_budget'])) {
                            $budget = $lessorPricing['total_lessor_budget'];
                        } elseif ($request->total_budget > 0) {
                            $budget = $request->total_budget;
                        }
                    @endphp
                    @if($budget)
                        <div class="display-5 fw-bold text-success">{{ number_format($budget, 0, '.', ' ') }} ₽</div>
                        <small class="text-muted">С учётом наценки платформы</small>
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
                        @endphp
                        <tr>
                            <td><strong>{{ $item->category->name ?? '—' }}</strong></td>
                            <td>{{ $item->quantity }} ед.</td>
                            <td>{{ number_format($item->hourly_rate ?? 0, 0, '.', ' ') }}</td>
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
                                    Доставка: {{ $cond['transportation_organized_by'] === 'lessor' ? 'Арендодатель' : 'Арендатор' }} |
                                    ГСМ: {{ $cond['gsm_payment'] === 'included' ? 'Вкл' : 'Отд' }}
                                    @if($cond['operator_included'] ?? false) | +Оператор @endif
                                    @if($cond['accommodation_payment'] ?? false) | +Проживание @endif
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

    {{-- Условия аренды --}}
    @if($request->rental_conditions)
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-file-contract me-2"></i>Условия аренды</h5></div>
        <div class="card-body">
            <div class="row g-3">
                @php $cond = $request->rental_conditions; @endphp
                <div class="col-md-3"><small class="text-muted d-block">Тип оплаты</small><strong>{{ $cond['payment_type'] ?? 'Почасовая' }}</strong></div>
                <div class="col-md-3"><small class="text-muted d-block">Часов в смену</small><strong>{{ $cond['hours_per_shift'] ?? 8 }}</strong></div>
                <div class="col-md-3"><small class="text-muted d-block">Смен в день</small><strong>{{ $cond['shifts_per_day'] ?? 1 }}</strong></div>
                <div class="col-md-3"><small class="text-muted d-block">Доставка</small><strong>{{ $cond['transportation_organized_by'] === 'lessor' ? 'За счёт арендодателя' : 'За счёт арендатора' }}</strong></div>
                <div class="col-md-3"><small class="text-muted d-block">ГСМ</small><strong>{{ $cond['gsm_payment'] === 'included' ? 'Включено' : 'Отдельно' }}</strong></div>
                @if($cond['operator_included'] ?? false)
                <div class="col-md-3"><small class="text-muted d-block">Оператор</small><strong>Включён</strong></div>
                @endif
                @if($cond['accommodation_payment'] ?? false)
                <div class="col-md-3"><small class="text-muted d-block">Проживание</small><strong>Оплачивается</strong></div>
                @endif
                @if($cond['extension_possibility'] ?? false)
                <div class="col-md-3"><small class="text-muted d-block">Продление</small><strong>Возможно</strong></div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.lessor-container { padding: 1.5rem; }
.table th { border-top: none; font-weight: 600; font-size: 0.8125rem; text-transform: uppercase; letter-spacing: 0.3px; color: #6c757d; }
@media (max-width: 768px) { .lessor-container { padding: 0.75rem; } }
.display-5 { font-size: 2.5rem; }
</style>
@endpush
