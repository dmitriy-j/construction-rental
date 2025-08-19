@extends('layouts.app')

@section('body-class', 'completion-act-page')

@section('content')
<div class="completion-act-container py-5">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-primary text-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h4 mb-0">
                    <i class="fas fa-file-contract me-2"></i>Акт выполненных работ #{{ $act->id }}
                </h1>
                <div>
                    <span class="badge bg-white text-primary fs-6 py-2 px-3">
                        {{ $act->act_date->format('d.m.Y') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Информация о заказе и путевом листе -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="text-muted">Заказ</h5>
                    <p class="mb-1">
                        <a href="{{ route('lessor.orders.show', $act->order) }}">
                            Заказ #{{ $act->order_id }}
                        </a>
                    </p>
                    <p class="mb-0">{{ $act->order->lesseeCompany->legal_name ?? 'Нет данных' }}</p>
                </div>

                <div class="col-md-6">
                    <h5 class="text-muted">Путевой лист</h5>
                    <p class="mb-1">Номер: {{ $act->waybill->number }}</p>
                    <p class="mb-0">Период: {{ $act->service_start_date->format('d.m.Y') }} - {{ $act->service_end_date->format('d.m.Y') }}</p>
                </div>
            </div>

            <!-- Информация о технике -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="text-muted">Оборудование</h5>
                    <p class="mb-1">{{ $act->waybill->equipment->title }} ({{ $act->waybill->equipment->model }})</p>
                </div>

                <div class="col-md-6">
                    <h5 class="text-muted">Оператор</h5>
                    <p class="mb-1">{{ $act->waybill->operator->full_name ?? 'Нет данных' }}</p>
                </div>
            </div>

            <!-- Сводка по работам -->
            <div class="card border mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Сводка выполненных работ</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Показатель</th>
                                <th class="text-end">Значение</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Общее количество отработанных часов</td>
                                <td class="text-end">{{ $act->total_hours }} ч.</td>
                            </tr>
                            <tr>
                                <td>Общее время простоя</td>
                                <td class="text-end">{{ $act->total_downtime }} ч.</td>
                            </tr>
                            <tr>
                                <td>Ставка аренды</td>
                                <td class="text-end">{{ number_format($act->hourly_rate, 2) }} ₽/час</td>
                            </tr>
                            <tr class="table-primary">
                                <td><strong>Итоговая сумма</strong></td>
                                <td class="text-end"><strong>{{ number_format($act->total_amount, 2) }} ₽</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Кнопки действий -->
            <div class="d-flex justify-content-between">
                <a href="{{ route('lessor.documents.index', ['type' => 'completion_acts']) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Назад к списку
                </a>
                <div>
                    <a href="{{ route('lessor.documents.download', ['id' => $act->id, 'type' => 'completion_acts']) }}"
                       class="btn btn-primary">
                       <i class="fas fa-download me-2"></i>Скачать PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
