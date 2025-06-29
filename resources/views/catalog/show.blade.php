@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row">
        <!-- Галерея -->
        <div class="col-md-6">
            <div class="mb-3">
                @if($equipment->mainImage)
                    <img src="{{ asset('storage/' . $equipment->mainImage->path) }}" class="img-fluid rounded">
                @endif
            </div>
            <div class="row">
                @foreach($equipment->images as $image)
                    <div class="col-3 mb-3">
                        <img src="{{ asset('storage/' . $image->path) }}" class="img-thumbnail">
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Описание -->
        <div class="col-md-6">
            <h1>{{ $equipment->title }}</h1>
            <p class="text-muted">{{ $equipment->category->name }}</p>

            <div class="d-flex align-items-center mb-3">
                <span class="h4 mb-0 me-2">{{ $equipment->rentalTerms->first()->price }} ₽/час</span>
                <span class="badge bg-success">Доступно</span>
            </div>

            <hr>

            <h5>Характеристики</h5>
            <ul class="list-unstyled">
                <li><strong>Бренд:</strong> {{ $equipment->brand }}</li>
                <li><strong>Модель:</strong> {{ $equipment->model }}</li>
                <li><strong>Год выпуска:</strong> {{ $equipment->year }}</li>
                <li><strong>Наработка:</strong> {{ $equipment->hours_worked }} моточасов</li>
            </ul>

            <hr>

            <!-- Форма заказа -->
            <form action="{{ route('rental.create') }}" method="POST">
                @csrf
                <input type="hidden" name="equipment_id" value="{{ $equipment->id }}">
                
                <div class="mb-3">
                    <label class="form-label">Дата аренды</label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Количество часов</label>
                    <input type="number" name="hours" class="form-control" min="1" value="1" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-cart3"></i> Забронировать
                </button>
            </form>
        </div>
    </div>
</div>
@endsection