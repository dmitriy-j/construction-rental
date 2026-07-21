@extends('layouts.app')

@section('title', 'Редактирование заявки #' . $rentalRequest->id)

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.rental-requests.index') }}">Заявки</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.rental-requests.show', $rentalRequest->id) }}">#{{ $rentalRequest->id }}</a></li>
            <li class="breadcrumb-item active">Редактирование</li>
        </ol>
    </nav>

    <h1 class="h3 mb-4">Редактирование заявки #{{ $rentalRequest->id }}</h1>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Основные параметры</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.rental-requests.update', $rentalRequest->id) }}" method="POST">
                        @csrf @method('PUT')

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Название</label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', $rentalRequest->title) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Статус</label>
                                <select name="status" class="form-select">
                                    @foreach($statuses as $s)
                                        <option value="{{ $s }}" {{ $rentalRequest->status === $s ? 'selected' : '' }}>
                                            {{ \App\Models\RentalRequest::getStatusText($s) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Видимость</label>
                                <select name="visibility" class="form-select">
                                    <option value="public" {{ $rentalRequest->visibility === 'public' ? 'selected' : '' }}>Публичная</option>
                                    <option value="private" {{ $rentalRequest->visibility === 'private' ? 'selected' : '' }}>Приватная</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Локация</label>
                                <select name="location_id" class="form-select">
                                    <option value="">—</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" {{ $rentalRequest->location_id === $loc->id ? 'selected' : '' }}>
                                            {{ $loc->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Ставка (₽/час)</label>
                                <input type="number" step="0.01" name="hourly_rate" class="form-control"
                                       value="{{ old('hourly_rate', $rentalRequest->hourly_rate) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Дата начала</label>
                                <input type="date" name="rental_period_start" class="form-control"
                                       value="{{ old('rental_period_start', $rentalRequest->rental_period_start?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Дата окончания</label>
                                <input type="date" name="rental_period_end" class="form-control"
                                       value="{{ old('rental_period_end', $rentalRequest->rental_period_end?->format('Y-m-d')) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Описание</label>
                            <textarea name="description" class="form-control" rows="4">{{ old('description', $rentalRequest->description) }}</textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.rental-requests.show', $rentalRequest->id) }}" class="btn btn-secondary">
                                Отмена
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Сохранить
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Информация</h5></div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><strong>Создатель:</strong> {{ $rentalRequest->user?->name ?? '—' }}</li>
                        <li class="mb-2"><strong>Компания:</strong> {{ $rentalRequest->company?->legal_name ?? '—' }}</li>
                        <li class="mb-2"><strong>Позиций:</strong> {{ $rentalRequest->items->count() }}</li>
                        <li class="mb-2"><strong>Создана:</strong> {{ $rentalRequest->created_at?->format('d.m.Y H:i') }}</li>
                        <li><strong>Обновлена:</strong> {{ $rentalRequest->updated_at?->format('d.m.Y H:i') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
