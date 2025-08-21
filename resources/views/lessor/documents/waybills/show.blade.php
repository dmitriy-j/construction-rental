@extends('layouts.app')

@section('body-class', 'waybill-page')

@section('content')
<div class="waybill-container py-5">

      @php
        $minDate = $waybill->orderItem->start_date ?? $waybill->order->start_date;
        $maxDate = $waybill->orderItem->end_date ?? $waybill->order->end_date;
    @endphp
    <div class="alert alert-{{ $waybill->status === \App\Models\Waybill::STATUS_COMPLETED ? 'danger' : 'info' }} mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-info-circle me-2"></i>
            <strong>Статус путевого листа:</strong>
            <span class="badge bg-{{ $waybill->status_color }}">
                {{ $waybill->status_text }}
            </span>

            {{-- Добавим информацию о том, что путевой лист будущий, но уже есть данные --}}
            @if($waybill->status === \App\Models\Waybill::STATUS_FUTURE && $filledShifts > 0)
                <span class="badge bg-warning ms-2">Есть заполненные смены</span>
            @endif
        </div>

        <div>
            <strong>Период:</strong>
            {{ $waybill->start_date->format('d.m.Y') }} - {{ $waybill->end_date->format('d.m.Y') }}
        </div>
    </div>
</div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-primary text-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h4 mb-0">
                    <i class="fas fa-file-alt me-2"></i>Путевой лист ЭСМ-2 #{{ $waybill->number }}
                    <span class="badge bg-white text-primary ms-2">
                        {{ $waybill->shift_type_text }} смена
                    </span>
                </h1>
                <div>
                    <span class="badge bg-white text-primary fs-6 py-2 px-3">
                        {{ $waybill->start_date->format('d.m.Y') }} - {{ $waybill->end_date->format('d.m.Y') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Информация о заказе и технике -->
            <div class="row mb-4">
                <div class="col-md-4 border-end">
                    <h5 class="text-muted">Арендатор</h5>
                    <p class="mb-1">{{ $waybill->order->lesseeCompany->legal_name ?? 'Платформа' }}</p>
                </div>

                <div class="col-md-4 border-end">
                    <h5 class="text-muted">Техника</h5>
                    <p class="mb-1">{{ $waybill->equipment->title }} ({{ $waybill->equipment->model }})</p>
                    <div class="d-flex align-items-center">
                        <span class="me-2">Гос. номер:</span>
                        <input type="text" class="form-control form-control-sm w-auto"
                            id="license_plate" value="{{ $waybill->license_plate }}"
                            @if($waybill->status !== \App\Models\Waybill::STATUS_ACTIVE) disabled @endif>
                    </div>
                    <p class="mb-0">Ставка: {{ number_format($waybill->lessor_hourly_rate, 2) }} ₽/час</p>
                </div>

                <div class="col-md-4">
                    <h5 class="text-muted">Оператор</h5>
                    <select class="form-select form-select-sm" id="operator_id"
                            @if($waybill->status !== \App\Models\Waybill::STATUS_ACTIVE) disabled @endif>
                        @foreach($operators as $operator)
                            <option value="{{ $operator->id }}"
                                {{ $waybill->operator_id == $operator->id ? 'selected' : '' }}>
                                {{ $operator->full_name }} ({{ $operator->license_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Единая кнопка сохранения для всех полей шапки -->
            <div class="d-flex justify-content-end mb-4">
                <button class="btn btn-sm btn-primary" id="save-waybill-header">
                    <i class="fas fa-save me-1"></i> Сохранить
                </button>
            </div>

            <!-- Статус заполнения -->
            <div class="alert alert-info mb-4">
                <div class="d-flex flex-wrap justify-content-between">
                    <div class="mb-1 mb-md-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Заполнено: <strong>{{ $filledShifts }}/{{ $totalShifts }}</strong> смен
                    </div>
                    <div class="mb-1 mb-md-0">
                        Отработано: <strong>{{ $totalHours }}</strong> часов
                    </div>
                    <div>
                        Итоговая сумма: <strong>{{ number_format($totalHours * $baseHourlyRate, 2) }} ₽</strong>
                    </div>
                </div>

                 <div class="progress mt-2" style="height: 8px;">
                    <div class="progress-bar bg-success"
                        role="progressbar"
                        style="width: {{ $totalShifts > 0 ? ($filledShifts / $totalShifts) * 100 : 0 }}%"
                        aria-valuenow="{{ $totalShifts > 0 ? ($filledShifts / $totalShifts) * 100 : 0 }}"
                        aria-valuemin="0"
                        aria-valuemax="100">
                    </div>
                </div>
            </div>

            <!-- Карточка добавления смены -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Добавить смену</h5>
                </div>
                <div class="card-body">
                    <form id="add-shift-form" data-waybill-id="{{ $waybill->id }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Дата смены</label>
                                <input type="date"
                                    name="shift_date"
                                    class="form-control"
                                    @if($minDate) min="{{ $minDate->format('Y-m-d') }}" @endif
                                    @if($maxDate) max="{{ $maxDate->format('Y-m-d') }}" @endif
                                    value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Добавить смену
                                </button>
                            </div>
                        </div>
                    </form>

                    @if($waybill->shifts()->count() >= 10)
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Достигнут лимит в 10 смен. Для добавления новых смен создайте новый путевой лист.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Таблица заполненных смен -->
            <div class="card border mb-4">
               <div class="card border mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Заполненные смены</h5>
                    </div>
                    <div id="shifts-table-container">
                        @include('lessor.documents.waybills.partials.shifts_table', ['waybill' => $waybill])
                    </div>
                </div>

            <!-- Форма выбора смены -->
            <div class="card border mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Выбор смены для заполнения</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Дата смены</label>
                                <select class="form-select" id="shift-date-select">
                                    @foreach($waybill->shifts as $shiftItem)
                                        <option value="{{ $shiftItem->id }}"
                                            {{ $selectedShift->id == $shiftItem->id ? 'selected' : '' }}
                                            data-shift="{{ json_encode($shiftItem) }}">
                                            {{ $shiftItem->shift_date->format('d.m.Y') }}
                                            ({{ $waybill->shift_type_text }})
                                            @if($shiftItem->hours_worked)
                                                - {{ $shiftItem->hours_worked }} ч.
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Тип смены</label>
                                <div class="form-control bg-light">
                                    {{ $waybill->shift_type_text }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Форма ввода данных -->
            <form method="POST" action="{{ route('lessor.shifts.update', $selectedShift) }}" id="shift-form">
                @csrf
                @method('PUT')

                <div class="card border mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Параметры работ для {{ $selectedShift->shift_date->format('d.m.Y') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            Для ночных смен, переходящих через полночь, указывайте фактическое время окончания
                            (например: 22:00 - 06:00). Система автоматически рассчитает продолжительность смены.
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Наименование объекта работ</label>
                                    <input type="text" class="form-control" name="object_name"
                                        value="{{ $selectedShift->object_name ?? '' }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                @if(empty($selectedShift->object_address))
                                <div class="alert alert-warning">
                                    Требуется указать адрес объекта для смены #{{ $selectedShift->id }}
                                </div>
                                @endif
                                <div class="mb-3">
                                    <label class="form-label">Адрес объекта</label>
                                    <input type="text" class="form-control" name="object_address"
                                        value="{{ $selectedShift->object_address ?? '' }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Время начала работы</label>
                                    <input type="text" class="form-control time-mask" name="work_start_time"
                                        value="{{ $selectedShift->work_start_time ? substr($selectedShift->work_start_time, 0, 5) : '00:00' }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Время окончания работы</label>
                                    <input type="text" class="form-control time-mask" name="work_end_time"
                                        value="{{ $selectedShift->work_end_time ? substr($selectedShift->work_end_time, 0, 5) : '00:00' }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Отработано часов</label>
                            <div class="form-control bg-light" id="hours-display">
                                {{ $selectedShift->hours_worked ?? '0' }} ч.
                            </div>
                        </div>

                        <input type="hidden" name="hours_worked" value="{{ $selectedShift->hours_worked ?? 0 }}">

                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Показания одометра (начало), км</label>
                                    <input type="number" name="odometer_start" class="form-control"
                                           value="{{ $selectedShift->odometer_start }}" required>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Показания одометра (конец), км</label>
                                    <input type="number" name="odometer_end" class="form-control"
                                           value="{{ $selectedShift->odometer_end }}" required>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Топливо (начало), л</label>
                                    <input type="number" step="0.01" name="fuel_start" class="form-control"
                                           value="{{ $selectedShift->fuel_start }}" required>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Топливо (конец), л</label>
                                    <input type="number" step="0.01" name="fuel_end" class="form-control"
                                           value="{{ $selectedShift->fuel_end }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Заправлено топлива, л</label>
                                    <input type="number" step="0.01" name="fuel_refilled_liters" class="form-control"
                                           value="{{ $selectedShift->fuel_refilled_liters }}">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Простой, часов</label>
                                    <input type="number" step="0.5" name="downtime_hours" class="form-control"
                                           value="{{ $selectedShift->downtime_hours }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Причина простоя</label>
                                    <input type="text" name="downtime_cause" class="form-control"
                                           value="{{ $selectedShift->downtime_cause }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Тип заправленного топлива</label>
                                    <input type="text" name="fuel_refilled_type" class="form-control"
                                           value="{{ $selectedShift->fuel_refilled_type ?? 'ДТ' }}">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Описание выполненных работ</label>
                            <textarea name="work_description" class="form-control" rows="2">{{ $selectedShift->work_description }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Примечания</label>
                            <textarea name="notes" class="form-control" rows="2">{{ $selectedShift->notes }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4 mb-4">
                    <a href="{{ route('lessor.waybills.index', $waybill->order) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Назад к списку
                    </a>

                    <div>
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-save me-2"></i>Сохранить данные
                        </button>

                        <button type="button" class="btn btn-success" id="save-and-next">
                            <i class="fas fa-forward me-2"></i>Сохранить и следующая
                        </button>
                    </div>
                </div>
            </form>
            @if($waybill->status === \App\Models\Waybill::STATUS_ACTIVE)
            <div class="mt-4 border-top pt-3">
                <form action="{{ route('lessor.waybills.close', $waybill) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger" id="close-waybill">
                        <i class="fas fa-lock me-2"></i>Закрыть путевой лист
                    </button>
                </form>
            </div>
            @endif
<!-- Секция для уведомления об успешном создании Акта -->
@if(session('success') && isset(session('success')['act_id']))
    <div class="alert alert-success mt-4">
        <i class="fas fa-check-circle"></i> {{ session('success')['message'] }}
        {{-- В будущем здесь будет ссылка на просмотр акта --}}
        {{-- <a href="{{ route('lessor.completion-acts.show', session('success')['act_id']) }}" class="btn btn-outline-primary btn-sm ms-2">Посмотреть акт</a> --}}
    </div>
@endif

<!-- Блок истории актов для этого путевого листа -->
@if($waybill->completionActs->count())
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i>Связанные акты выполненных работ</h5>
    </div>
    <div class="card-body">
        <div class="list-group">
            @foreach($waybill->completionActs as $act)
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <strong>Акт №{{ $act->id }}</strong>
                    <br>
                    <small>Период: {{ $act->service_start_date->format('d.m.Y') }}
                    - {{ $act->service_end_date->format('d.m.Y') }}</small>
                    <br>
                    <span class="badge bg-secondary">{{ $act->status }}</span>
                </div>
                <div>
                    <span class="text-nowrap">{{ number_format($act->total_amount, 2) }} ₽</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
            <!-- Скачивание PDF -->
            <div class="mt-4 border-top pt-3">
                <a href="{{ route('lessor.waybills.download', $waybill) }}" class="btn btn-outline-primary">
                    <i class="fas fa-file-pdf me-2"></i>Скачать PDF
                </a>

                <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#shiftSummaryModal">
                    <i class="fas fa-list me-2"></i>Сводка по сменам
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно сводки -->
<div class="modal fade" id="shiftSummaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Сводка по сменам</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Тип смены</th>
                            <th>Начало</th>
                            <th>Окончание</th>
                            <th>Отработано</th>
                            <th>Простой</th>
                            <th>Оператор</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($waybill->shifts as $shiftItem)
                        <tr>
                            <td>{{ $shiftItem->shift_date->format('d.m.Y') }}</td>
                            <td>{{ $waybill->shift_type_text }}</td>
                            <td>{{ $shiftItem->work_start_time ?? '-' }}</td>
                            <td>{{ $shiftItem->work_end_time ?? '-' }}</td>
                            <td>{{ $shiftItem->hours_worked ?? '0' }} ч.</td>
                            <td>{{ $shiftItem->downtime_hours ?? '0' }} ч.</td>
                            <td>{{ $waybill->operator->full_name }}</td>
                            <td>
                                @if($shiftItem->hours_worked > 0)
                                    <span class="badge bg-success">Заполнена</span>
                                @else
                                    <span class="badge bg-warning">Ожидает</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0"></script>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.6/dist/inputmask.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Функция расчета часов работы
    function calculateHours() {
        const startInput = document.querySelector('input[name="work_start_time"]');
        const endInput = document.querySelector('input[name="work_end_time"]');

        if (!startInput || !endInput) return;

        const start = startInput.value;
        const end = endInput.value;

        if (!start || !end) return;

        const startTime = new Date(`1970-01-01T${start}:00`);
        let endTime = new Date(`1970-01-01T${end}:00`);

        if (endTime < startTime) {
            endTime.setDate(endTime.getDate() + 1);
        }

        const diffMs = endTime - startTime;
        const hours = Math.round((diffMs / (1000 * 60 * 60)) * 2) / 2;

        const hoursWorkedInput = document.querySelector('input[name="hours_worked"]');
        const hoursDisplay = document.getElementById('hours-display');

        if (hoursWorkedInput) hoursWorkedInput.value = hours;
        if (hoursDisplay) hoursDisplay.textContent = hours + ' ч.';
    }

    // Инициализация расчета часов
    const startInput = document.querySelector('input[name="work_start_time"]');
    const endInput = document.querySelector('input[name="work_end_time"]');

    if (startInput) startInput.addEventListener('change', calculateHours);
    if (endInput) endInput.addEventListener('change', calculateHours);
    calculateHours();

    // 2. Функция для показа уведомлений
    function showAlert(message, type = 'error') {
        const background = type === 'success' ? '#4CAF50' : '#F44336';
        Toastify({
            text: message,
            duration: 5000,
            gravity: "top",
            position: "right",
            style: { background },
            className: "custom-toast",
        }).showToast();
    }

    // 3. Обработчик добавления смены (универсальный для дневных/ночных)
    const addShiftForm = document.getElementById('add-shift-form');
    if (addShiftForm) {
        addShiftForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const waybillId = form.dataset.waybillId;
            const formData = new FormData(form);

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalHtml = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Добавление...';

            try {
                const response = await fetch(`/lessor/waybills/${waybillId}/add-shift`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Смена успешно добавлена', 'success');

                    // Обновляем всю карточку путевого листа
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    let errorMessage = result.message || 'Ошибка при добавлении смены';
                    if (result.errors?.shift_date) {
                        errorMessage += ': ' + result.errors.shift_date[0];
                    }
                    showAlert(errorMessage);
                }
            } catch (error) {
                showAlert('Сетевая ошибка: ' + error.message);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
            }
        });
    }


    // 4. Маскировка времени
    const timeInputs = document.querySelectorAll('.time-mask');
    if (timeInputs.length > 0) {
        Inputmask("99:99", {
            placeholder: "HH:MM",
            insertMode: false,
            showMaskOnHover: false
        }).mask(timeInputs);
    }

    // 5. Оптимизированный обработчик формы
    function initFormDebugger() {
        const shiftForm = document.getElementById('shift-form');
        if (!shiftForm) return;

        shiftForm.addEventListener('submit', function(e) {
            const formData = new FormData(this);
            let debugInfo = "Данные формы перед отправкой:\n";

            for (const [key, value] of formData.entries()) {
                debugInfo += `${key}: ${value}\n`;
            }

            console.debug(debugInfo);
        });
    }
    initFormDebugger();

    // 6. Обработчик кнопки "Сохранить и следующая"
    const saveAndNextBtn = document.getElementById('save-and-next');
    if (saveAndNextBtn) {
        saveAndNextBtn.addEventListener('click', function() {
            const shiftForm = document.getElementById('shift-form');
            if (shiftForm) {
                // Добавляем скрытое поле для действия
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'save_and_next';
                input.value = '1';
                shiftForm.appendChild(input);

                // Отправляем форму
                shiftForm.submit();
            }
        });
    }


 // 7. Сохранение шапки путевого листа (оператор и гос. номер)
    const saveHeaderBtn = document.getElementById('save-waybill-header');
    if (saveHeaderBtn) {
        saveHeaderBtn.addEventListener('click', async function() {
            const operatorId = document.getElementById('operator_id').value;
            const licensePlate = document.getElementById('license_plate').value;
            const waybillId = {{ $waybill->id }};

            const btnOriginal = saveHeaderBtn.innerHTML;
            saveHeaderBtn.disabled = true;
            saveHeaderBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Сохранение...';

            try {
                // Исправленный URL и метод
                const response = await fetch(`/lessor/waybills/${waybillId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        operator_id: operatorId,
                        license_plate: licensePlate
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Данные успешно сохранены', 'success');
                } else {
                    showAlert(result.message || 'Ошибка при сохранении');
                }
            } catch (error) {
                showAlert('Сетевая ошибка: ' + error.message);
            } finally {
                saveHeaderBtn.disabled = false;
                saveHeaderBtn.innerHTML = btnOriginal;
            }
        });
    }

    // 8. Обработчик удаления смены с правильным URL
    document.addEventListener('click', async function(e) {
        if (e.target.closest('.delete-shift-btn')) {
            const button = e.target.closest('.delete-shift-btn');
            const shiftId = button.getAttribute('data-id');

            if (!confirm('Вы уверены, что хотите удалить смену? Данные будут утеряны безвозвратно.')) {
                return;
            }

            try {
                // Формируем URL через именованный маршрут
                const url = "{{ route('lessor.shifts.destroy', ['shift' => ':id']) }}".replace(':id', shiftId);

                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                // Проверка статуса ответа
                if (response.status === 404) {
                    throw new Error('Маршрут не найден. Проверьте конфигурацию сервера.');
                }

                const result = await response.json();

                if (result.success) {
                    showAlert('Смена успешно удалена', 'success');

                    // Обновляем таблицу смен
                    const shiftsUrl = "{{ route('lessor.waybills.shifts', ['waybill' => $waybill->id]) }}";
                    const shiftsResponse = await fetch(shiftsUrl);

                    if (!shiftsResponse.ok) {
                        throw new Error('Ошибка при обновлении таблицы смен');
                    }

                    const shiftsHtml = await shiftsResponse.text();
                    document.getElementById('shifts-table-container').innerHTML = shiftsHtml;
                } else {
                    showAlert(result.message || 'Ошибка при удалении смены');
                }
            } catch (error) {
                showAlert('Ошибка: ' + error.message);
                console.error('Ошибка удаления смены:', error);
            }
        }
    });
});
</script>
@endpush
