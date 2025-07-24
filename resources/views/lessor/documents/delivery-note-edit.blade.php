@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h2 class="mb-0">Редактирование транспортной накладной</h2>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('lessor.delivery-notes.update', $note) }}">
                @csrf
                @method('PUT')

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Номер документа</label>
                            <input type="text" name="document_number" class="form-control form-control-lg"
                                   value="{{ old('document_number', $note->document_number) }}" required>
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

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Расстояние (км)</label>
                            <input type="number" step="0.01" name="distance_km" class="form-control form-control-lg"
                                   value="{{ old('distance_km', $note->distance_km) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Стоимость перевозки (руб)</label>
                            <input type="number" step="0.01" name="calculated_cost" class="form-control form-control-lg"
                                   value="{{ old('calculated_cost', $note->calculated_cost) }}" required>
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
                                <input type="text" name="driver_name" class="form-control"
                                       value="{{ old('driver_name', $note->driver_name) }}" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Модель ТС</label>
                                <input type="text" name="vehicle_model" class="form-control"
                                       value="{{ old('vehicle_model', $note->vehicle_model) }}" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Гос. номер</label>
                                <input type="text" name="vehicle_number" class="form-control"
                                       value="{{ old('vehicle_number', $note->vehicle_number) }}" required>
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
                                <textarea name="cargo_condition" class="form-control" rows="2" required>{{ old('cargo_condition', $note->cargo_condition) }}</textarea>
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
@endsection
