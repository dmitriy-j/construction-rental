@extends('layouts.app')

@php
use App\Models\Waybill; // Добавьте эту строку
@endphp

@section('content')
<div class="container py-5">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-primary text-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h4 mb-0">
                    <i class="fas fa-file-alt me-2"></i>Путевой лист ЭСМ-2 #{{ $waybill->id }}
                </h1>
                <div>
                    <span class="badge bg-white text-primary fs-6 py-2 px-3">
                        {{ $waybill->work_date->format('d.m.Y') }} |
                        {{ $waybill->shift === 'day' ? 'Дневная смена' : 'Ночная смена' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Информация о заказе и технике -->
            <div class="row mb-4">
                <div class="col-md-4 border-end">
                    <h5 class="text-muted">Заказ</h5>
                    <p class="mb-1">#{{ $waybill->order->id }}</p>
                    <p class="mb-1">Арендатор: {{ $waybill->order->lesseeCompany->legal_name ?? 'Нет данных' }}</p>
                    <p>Статус:
                        <span class="badge bg-{{ $waybill->status_color }}">{{ $waybill->status_text }}</span>
                    </p>
                </div>

                <div class="col-md-4 border-end">
                    <h5 class="text-muted">Техника</h5>
                    <p class="mb-1">{{ $waybill->equipment->title }}</p>
                    <p class="mb-1">Модель: {{ $waybill->equipment->model }}</p>
                    <p>Гос. номер: {{ $waybill->equipment->license_plate ?? 'не указан' }}</p>
                </div>

                <div class="col-md-4">
                    <h5 class="text-muted">Оператор</h5>
                    @if($waybill->operator)
                        <p class="mb-1">{{ $waybill->operator->full_name }}</p>
                        <p class="mb-1">Лицензия: {{ $waybill->operator->license_number }}</p>
                        <p>Телефон: {{ $waybill->operator->phone }}</p>
                    @else
                        <p class="text-danger">Оператор не назначен</p>
                    @endif
                </div>
            </div>

            <!-- Форма ввода данных -->
            <form method="POST" action="{{ route('lessor.waybills.update', $waybill) }}">
                @csrf
                @method('PUT')

                <div class="card border mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Параметры работы</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Показания одометра (начало), км</label>
                                    <input type="number" name="odometer_start"
                                           class="form-control"
                                           value="{{ $waybill->odometer_start ?? '' }}"
                                           required>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Показания одометра (конец), км</label>
                                    <input type="number" name="odometer_end"
                                           class="form-control"
                                           value="{{ $waybill->odometer_end ?? '' }}"
                                           required>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Топливо (начало), л</label>
                                    <input type="number" step="0.01" name="fuel_start"
                                           class="form-control"
                                           value="{{ $waybill->fuel_start ?? '' }}"
                                           required>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Топливо (конец), л</label>
                                    <input type="number" step="0.01" name="fuel_end"
                                           class="form-control"
                                           value="{{ $waybill->fuel_end ?? '' }}"
                                           required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Отработано часов</label>
                                    <input type="number" step="0.5" name="hours_worked"
                                           class="form-control"
                                           value="{{ $waybill->hours_worked ?? '' }}"
                                           required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Простой, часов</label>
                                    <input type="number" step="0.5" name="downtime_hours"
                                           class="form-control"
                                           value="{{ $waybill->downtime_hours ?? '' }}">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Причина простоя</label>
                                    <input type="text" name="downtime_cause"
                                           class="form-control"
                                           value="{{ $waybill->downtime_cause ?? '' }}">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Описание выполненных работ</label>
                            <textarea name="work_description" class="form-control" rows="3">{{ $waybill->work_description ?? '' }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Примечания</label>
                            <textarea name="notes" class="form-control" rows="2">{{ $waybill->notes ?? '' }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('lessor.waybills.index', $waybill->order) }}"
                       class="btn btn-outline-secondary">
                       <i class="fas fa-arrow-left me-2"></i>Назад к списку
                    </a>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Сохранить данные
                    </button>
                </div>
            </form>

            <!-- Блок подписания -->
            @if(in_array($waybill->status, [Waybill::STATUS_CREATED, Waybill::STATUS_IN_PROGRESS]))
            <div class="card border-primary mt-5">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Подписание путевого листа</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        После заполнения всех данных укажите подпись ответственного лица.
                        После подписания путевой лист будет считаться завершенным.
                    </p>

                    <div class="mb-3">
                        <div id="signature-pad" class="border rounded bg-white" style="height: 200px; cursor: crosshair;"></div>
                        <div class="mt-2">
                            <button id="clear-signature" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-eraser me-1"></i>Очистить
                            </button>
                        </div>
                    </div>

                    <button id="sign-button" class="btn btn-success px-4 py-2">
                        <i class="fas fa-signature me-2"></i>Подписать путевой лист
                    </button>
                </div>
            </div>
            @endif

            <!-- Скачивание PDF -->
            <div class="mt-4 border-top pt-3">
                <a href="{{ route('lessor.waybills.download', $waybill) }}"
                   class="btn btn-outline-primary">
                   <i class="fas fa-file-pdf me-2"></i>Скачать PDF
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.css">
<style>
    #signature-pad {
        touch-action: none; /* Для поддержки мобильных устройств */
    }
    .signature-help {
        font-size: 0.9rem;
        color: #6c757d;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvas, {
            minWidth: 1,
            maxWidth: 3,
            penColor: "rgb(0, 0, 0)",
            backgroundColor: "rgb(255, 255, 255)"
        });

        // Очистка подписи
        document.getElementById('clear-signature').addEventListener('click', function() {
            signaturePad.clear();
        });

        // Подписание документа
        document.getElementById('sign-button').addEventListener('click', function() {
            if (signaturePad.isEmpty()) {
                alert('Пожалуйста, поставьте подпись');
                return;
            }

            const signature = signaturePad.toDataURL('image/svg+xml');

            fetch("{{ route('lessor.waybills.sign', $waybill) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ signature })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Путевой лист успешно подписан!');
                    location.reload();
                } else {
                    alert('Ошибка при подписании: ' + (data.message || 'Попробуйте еще раз'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при отправке подписи');
            });
        });

        // Адаптация размера канваса при изменении окна
        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear(); // Очистка при ресайзе
        }

        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();
    });
</script>
@endpush
