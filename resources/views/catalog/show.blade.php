@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row">
        <!-- Галерея -->
        <div class="col-md-6">
            <div class="mb-3">
                @if($equipment->images->first())
                    <img src="{{ asset('storage/' . $equipment->images->first()->path) }}"
                         class="img-fluid rounded"
                         style="max-height: 400px; width: 100%; object-fit: cover;">
                @endif
            </div>
            <div class="row">
                @foreach($equipment->images as $image)
                    <div class="col-3 mb-3">
                        <img src="{{ asset('storage/' . $image->path) }}"
                             class="img-thumbnail"
                             style="height: 100px; width: 100%; object-fit: cover;">
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Описание -->
        <div class="col-md-6">
            <h1>{{ $equipment->title }}</h1>
            <p class="text-muted">{{ $equipment->category->name }}</p>

            <div class="d-flex align-items-center mb-3">
                @if($equipment->rentalTerms->isNotEmpty())
                    <span class="h4 mb-0 me-2">{{ $equipment->rentalTerms->first()->price }} ₽/час</span>
                    <span class="badge bg-success">Доступно</span>
                @else
                    <span class="text-danger h4">Нет условий аренды</span>
                @endif
            </div>

            <hr>

            <h5>Характеристики</h5>
            <ul class="list-unstyled">
                <li><strong>Бренд:</strong> {{ $equipment->brand ?? 'Не указано' }}</li>
                <li><strong>Модель:</strong> {{ $equipment->model ?? 'Не указано' }}</li>
                <li><strong>Год выпуска:</strong> {{ $equipment->year ?? 'Не указан' }}</li>
                <li><strong>Наработка:</strong> {{ $equipment->hours_worked ?? '0' }} моточасов</li>
            </ul>

            <h5 class="mt-4">Описание</h5>
            <p>{{ $equipment->description }}</p>

            <hr>

            <!-- Форма добавления в корзину -->
            @if($equipment->rentalTerms->isNotEmpty())
                <h5>Условия аренды</h5>
                @foreach($equipment->rentalTerms as $term)
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6>{{ $term->name }} ({{ $term->price }} ₽/{{ $term->period }})</h6>
                            <p>Минимальный срок: {{ $term->min_rental_period }} {{ $term->period }}</p>

                            <form action="{{ route('cart.add', $term) }}" method="POST">
                                @csrf
                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Начало аренды</label>
                                        <input type="datetime-local" name="start_date" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Окончание аренды</label>
                                        <input type="datetime-local" name="end_date" class="form-control" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 mt-2">
                                    <i class="bi bi-cart-plus me-1"></i> Добавить в корзину
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection
