@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Редактирование транспортной накладной #{{ $note->document_number }}</h2>

    <form method="POST" action="{{ route('lessor.delivery-notes.update', $note) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Водитель</label>
            <input type="text" name="driver_name" class="form-control"
                   value="{{ old('driver_name', $note->driver_name) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Модель ТС</label>
            <input type="text" name="vehicle_model" class="form-control"
                   value="{{ old('vehicle_model', $note->vehicle_model) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Гос. номер</label>
            <input type="text" name="vehicle_number" class="form-control"
                   value="{{ old('vehicle_number', $note->vehicle_number) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Контакт водителя</label>
            <input type="text" name="driver_contact" class="form-control"
                   value="{{ old('driver_contact', $note->driver_contact) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Дата/время отправки</label>
            <input type="datetime-local" name="departure_time" class="form-control"
                   value="{{ old('departure_time', $note->departure_time?->format('Y-m-d\TH:i')) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Состояние груза</label>
            <textarea name="cargo_condition" class="form-control" required>{{ old('cargo_condition', $note->cargo_condition) }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">Сохранить</button>

        @if($note->canBeClosed())
        <a href="{{ route('lessor.delivery-notes.close', $note) }}" class="btn btn-success ms-2">
            Закрыть накладную (отправить технику)
        </a>
        @endif
    </form>
</div>
@endsection
