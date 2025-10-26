@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i>Путевой лист #{{ $waybillData['number'] }}
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Информация о заказе</h5>
                            <p><strong>Номер заказа:</strong> #{{ $waybillData['parent_order_id'] ?? 'Не указан' }}</p>
                            <p><strong>Исполнитель:</strong> Платформа</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Период:</strong> {{ $waybillData['period'] }}</p>
                            <p><strong>Статус:</strong>
                                <span class="badge bg-{{ $waybillData['status'] === 'Активный' ? 'success' : 'secondary' }}">
                                    {{ $waybillData['status'] }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Информация об оборудовании</h5>
                            <p><strong>Оборудование:</strong> {{ $waybillData['equipment'] }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Финансовая информация</h5>
                            <p><strong>Отработано часов:</strong> {{ $waybillData['total_hours'] }}</p>
                            <p><strong>Ставка:</strong> {{ number_format($waybillData['hourly_rate'], 2) }} ₽</p>
                            <p><strong>Сумма:</strong> {{ number_format($waybillData['total_amount'], 2) }} ₽</p>
                        </div>
                    </div>

                    <!-- Детальная сводка по дням -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5>Детальная сводка по дням</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Дата</th>
                                            <th>Отработано часов</th>
                                            <th>Объект</th>
                                            <th>Описание работ</th>
                                            <th>Сумма</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($waybill->shifts as $shift)
                                        <tr>
                                            <td>{{ $shift->shift_date->format('d.m.Y') }}</td>
                                            <td>{{ $shift->hours_worked }}</td>
                                            <td>{{ $shift->object_name ?? 'Не указано' }}</td>
                                            <td>{{ $shift->work_description ?? 'Не указано' }}</td>
                                            <td>{{ number_format($shift->hours_worked * $waybillData['hourly_rate'], 2) }} ₽</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center">Данные по сменам отсутствуют</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ url('/lessee/documents/waybills/' . $waybillData['id'] . '/download') }}"
                        class="btn btn-primary">
                            <i class="fas fa-download me-1"></i>Скачать PDF
                        </a>
                        <a href="{{ route('lessee.orders.show', $waybillData['parent_order_id'] ?? '#') }}"
                        class="btn btn-outline-secondary">
                            <i class="fas fa-external-link-alt me-1"></i>Перейти к заказу
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
