@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h1 class="mb-5">Свободная техника</h1>

    <!-- Фильтры -->
    <div class="card shadow-sm mb-5">
        <div class="card-body">
            <form class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Тип техники</label>
                    <select class="form-select">
                        <option selected>Все</option>
                        <option>Экскаваторы</option>
                        <option>Бульдозеры</option>
                        <option>Краны</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Город</label>
                    <select class="form-select">
                        <option>Москва</option>
                        <option>Санкт-Петербург</option>
                        <option>Казань</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Найти
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Список техники -->
    <div class="row">
        @foreach([
            ['name' => 'Экскаватор JCB 3CX', 'type' => 'Экскаватор', 'price' => '25 000 ₽/смена', 'location' => 'Москва', 'image' => 'jcb'],
            ['name' => 'Бульдозер CAT D6', 'type' => 'Бульдозер', 'price' => '38 000 ₽/смена', 'location' => 'Казань', 'image' => 'cat'],
            ['name' => 'Кран Liebherr LTM 1050', 'type' => 'Кран', 'price' => '65 000 ₽/смена', 'location' => 'Санкт-Петербург', 'image' => 'crane']
        ] as $item)
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm tech-card">
                <img src="https://via.placeholder.com/400x300?text={{ $item['image'] }}" 
                     class="card-img-top" 
                     alt="{{ $item['name'] }}">
                <div class="card-body">
                    <h5 class="card-title">{{ $item['name'] }}</h5>
                    <div class="mb-3">
                        <span class="badge bg-primary me-2">
                            <i class="bi bi-tag"></i> {{ $item['type'] }}
                        </span>
                        <span class="badge bg-secondary">
                            <i class="bi bi-geo-alt"></i> {{ $item['location'] }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="fw-bold fs-5">{{ $item['price'] }}</span>
                        <button class="btn btn-primary">
                            <i class="bi bi-cart-plus"></i> Арендовать
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Пагинация -->
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item"><a class="page-link" href="#">1</a></li>
            <li class="page-item"><a class="page-link" href="#">2</a></li>
            <li class="page-item"><a class="page-link" href="#">3</a></li>
        </ul>
    </nav>
</div>
@endsection