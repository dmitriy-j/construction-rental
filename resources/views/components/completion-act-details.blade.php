{{-- Универсальный компонент для детального отображения акта --}}
@props(['completionAct', 'userType'])

<div class="completion-act-details">
    <!-- Заголовок -->
    <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 mb-0">
                    <i class="fas fa-file-contract me-2"></i>Акт выполненных работ
                </h2>
                {{-- ИСПРАВЛЕНИЕ: Добавлен text-dark для номера акта --}}
                <p class="mb-0 text-dark bg-light px-2 py-1 rounded d-inline-block mt-1">
                    № {{ $completionAct->number }}
                </p>
            </div>
            <div class="text-end">
                <span class="badge bg-{{ $completionAct->status_color }} text-white fs-6">
                    {{ $completionAct->status_text }}
                </span>
                <p class="mb-0 mt-2 text-white-50">{{ $completionAct->act_date->format('d.m.Y') }}</p>
            </div>
        </div>
    </div>

    <div class="card-body">
        @php
            $details = $completionAct->detailed_info;
            $shifts = $completionAct->shifts_data;
        @endphp

        <!-- Основная информация -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="info-section">
                    <h5 class="section-title">Информация о заказе</h5>
                    <div class="info-grid">
                        <span class="info-label">Номер заказа:</span>
                        <span class="info-value">#{{ $completionAct->order_id }}</span>

                        @if($userType === 'lessee')
                        <span class="info-label">Исполнитель:</span>
                        <span class="info-value">{{ $details['lessor_name'] }}</span>
                        @else
                        <span class="info-label">Арендатор:</span>
                        <span class="info-value">{{ $details['lessee_name'] }}</span>
                        @endif

                        <span class="info-label">Период услуг:</span>
                        <span class="info-value">
                            {{ $details['service_start_date'] }} - {{ $details['service_end_date'] }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="info-section">
                    <h5 class="section-title">Техническая информация</h5>
                    <div class="info-grid">
                        <span class="info-label">Оборудование:</span>
                        <span class="info-value">{{ $details['equipment'] }}</span>

                        <span class="info-label">Оператор:</span>
                        <span class="info-value">{{ $details['operator_name'] }}</span>

                        @if($userType === 'lessor')
                        <span class="info-label">Путевой лист:</span>
                        <span class="info-value">{{ $details['waybill_number'] }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Финансовая сводка -->
        <div class="financial-summary card border-0 bg-light mb-4">
            <div class="card-body">
                <h5 class="card-title mb-4">Финансовая сводка</h5>
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-value text-primary">{{ $details['total_hours'] }} ч</div>
                            <div class="stat-label">Отработано часов</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-value text-info">{{ number_format($details['hourly_rate'], 2) }} ₽</div>
                            <div class="stat-label">Ставка в час</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-value text-warning">{{ $details['total_downtime'] }} ч</div>
                            <div class="stat-label">Время простоя</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-value text-success">{{ number_format($details['total_amount'], 2) }} ₽</div>
                            <div class="stat-label">Итоговая сумма</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Детали смен -->
        @if($shifts->count() > 0)
        <div class="shifts-section">
            <h5 class="section-title mb-3">Детали смен</h5>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Дата</th>
                            <th>Объект</th>
                            <th>Адрес объекта</th>
                            <th>Часы работы</th>
                            <th>Простой</th>
                            <th>Причина простоя</th>
                            <th class="text-end">Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shifts as $shift)
                        <tr>
                            <td>{{ $shift['date'] }}</td>
                            <td>{{ $shift['object_name'] }}</td>
                            <td>{{ $shift['object_address'] }}</td>
                            <td>
                                <span class="badge bg-primary rounded-pill">{{ $shift['hours_worked'] }} ч</span>
                            </td>
                            <td>
                                @if($shift['downtime_hours'] > 0)
                                <span class="badge bg-warning rounded-pill">{{ $shift['downtime_hours'] }} ч</span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($shift['downtime_cause'])
                                <small class="text-muted">{{ $shift['downtime_cause'] }}</small>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold">
                                {{ number_format($shift['amount'], 2) }} ₽
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-group-divider">
                        <tr class="table-active">
                            <th colspan="3">Итого</th>
                            <th>{{ $shifts->sum('hours_worked') }} ч</th>
                            <th>{{ $shifts->sum('downtime_hours') }} ч</th>
                            <th></th>
                            <th class="text-end">{{ number_format($shifts->sum('amount'), 2) }} ₽</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>Нет данных о сменах
        </div>
        @endif

        <!-- Примечания -->
        @if($completionAct->notes)
        <div class="notes-section mt-4">
            <h5 class="section-title">Примечания</h5>
            <div class="alert alert-light border">
                <p class="mb-0">{{ $completionAct->notes }}</p>
            </div>
        </div>
        @endif
    </div>
</div>

<style>
.completion-act-details .section-title {
    color: #2c3e50;
    font-weight: 600;
    border-bottom: 2px solid #3498db;
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
}

.completion-act-details .info-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    height: 100%;
}

.completion-act-details .info-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 0.75rem;
}

.completion-act-details .info-label {
    font-weight: 600;
    color: #6c757d;
}

.completion-act-details .info-value {
    color: #2c3e50;
}

.completion-act-details .stat-item {
    padding: 1rem;
}

.completion-act-details .stat-value {
    font-size: 1.5rem;
    font-weight: 700;
}

.completion-act-details .stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.5rem;
}

.completion-act-details .financial-summary {
    border-radius: 12px;
}
</style>
