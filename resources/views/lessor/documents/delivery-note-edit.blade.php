@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h2 class="mb-0">Редактирование транспортной накладной</h2>
        </div>

        @if($note->status !== \App\Models\DeliveryNote::STATUS_DRAFT)
            <div class="alert alert-warning">
                <i class="fas fa-lock me-2"></i>
                Накладная закрыта для редактирования. Вы можете только скачать документ.
            </div>
        @else
        <div class="card-body">
            <form method="POST" action="{{ route('lessor.delivery-notes.update', $note) }}">
                @csrf
                @method('PUT')

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Номер документа</label>
                            <input type="text" name="document_number" class="form-control form-control-lg"
                                value="{{ old('document_number', $note->document_number) }}"
                                placeholder="Введите номер транспортной накладной" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Дата выпуска документа</label>
                            <input type="date" name="issue_date" class="form-control form-control-lg"
                                   value="{{ old('issue_date', $note->issue_date?->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Дата доставки (план)</label>
                            <input type="date" name="delivery_date" class="form-control form-control-lg"
                                   value="{{ old('delivery_date', $note->delivery_date?->format('Y-m-d')) }}">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Расстояние (км)</label>
                                <input type="text" class="form-control form-control-lg bg-light"
                                    value="{{ number_format($note->orderItem->distance_km, 2) }}" readonly>
                                <input type="hidden" name="distance_km" value="{{ $note->orderItem->distance_km }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Стоимость перевозки (руб)</label>
                                <input type="text" class="form-control form-control-lg bg-light"
                                    value="{{ number_format($note->orderItem->delivery_cost, 2) }}" readonly>
                                <input type="hidden" name="calculated_cost" value="{{ $note->orderItem->delivery_cost }}">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Дата/время отправки</label>
                            <input type="datetime-local" name="departure_time" class="form-control form-control-lg"
                                   value="{{ old('departure_time', $note->departure_time?->format('Y-m-d\TH:i')) }}" required>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-4 mb-4">
                    <h4 class="mb-3">Информация о транспорте</h4>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Водитель</label>
                                <input type="text" name="transport_driver_name" class="form-control"
                                       value="{{ old('transport_driver_name', $note->transport_driver_name) }}" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Модель ТС</label>
                                <input type="text" name="transport_vehicle_model" class="form-control"
                                       value="{{ old('transport_vehicle_model', $note->transport_vehicle_model) }}" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Гос. номер</label>
                                <input type="text" name="transport_vehicle_number" class="form-control"
                                       value="{{ old('transport_vehicle_number', $note->transport_vehicle_number) }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Контакт водителя</label>
                                <input type="text" name="driver_contact" class="form-control"
                                       value="{{ old('driver_contact', $note->driver_contact) }}" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Состояние груза</label>
                                  <textarea name="equipment_condition" class="form-control" rows="2" required>
                                        {{ old('equipment_condition', $note->equipment_condition) }}
                                </textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-4 mb-4">
                    <h4 class="mb-3">Адреса доставки</h4>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Пункт погрузки</h5>
                                    <p class="card-text">
                                        {{ $note->deliveryFrom->name }}<br>
                                        {{ $note->deliveryFrom->address }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Пункт разгрузки</h5>
                                    <p class="card-text">
                                        {{ $note->deliveryTo->name }}<br>
                                        {{ $note->deliveryTo->address }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-5">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-save me-2"></i> Сохранить
                    </button>

                    @if($note->canBeClosed())
                    <a href="{{ route('lessor.delivery-notes.close', $note) }}" class="btn btn-success btn-lg px-5">
                        <i class="fas fa-check-circle me-2"></i> Закрыть накладную
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
