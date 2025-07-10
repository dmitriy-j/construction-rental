@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">Каталог спецтехники</h1>

    <div class="row">
        <!-- Фильтры -->
        <div class="col-md-3">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Фильтры</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('catalog.index') }}" method="GET">
                        <!-- Категории -->
                        <div class="mb-3">
                            <label class="form-label">Категория</label>
                            <select name="category" class="form-select">
                                <option value="">Все</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Цена -->
                        <div class="mb-3">
                            <label class="form-label">Цена за час (от)</label>
                            <input type="number" name="min_price" class="form-control"
                                   value="{{ request('min_price') }}" placeholder="₽">
                        </div>

                        <!-- Кнопка -->
                        <button type="submit" class="btn btn-primary w-100">Применить</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Список техники -->
        <div class="col-md-9">
            <!-- Сортировка -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="text-muted">
                    Найдено: {{ $equipments->total() }} единиц
                </div>
                <div>
                    <select class="form-select" onchange="window.location.href = this.value">
                        <option value="{{ route('catalog.index', ['sort' => 'newest']) }}" {{ request('sort') == 'newest' ? 'selected' : '' }}>
                            Новые сначала
                        </option>
                        <option value="{{ route('catalog.index', ['sort' => 'price_asc']) }}" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>
                            Цена (по возрастанию)
                        </option>
                        <option value="{{ route('catalog.index', ['sort' => 'price_desc']) }}" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>
                            Цена (по убыванию)
                        </option>
                        <option value="{{ route('catalog.index', ['sort' => 'popular']) }}" {{ request('sort') == 'popular' ? 'selected' : '' }}>
                            По популярности
                        </option>
                    </select>
                </div>
            </div>

            <!-- Карточки -->
            <div class="row">
                @foreach($equipments as $equipment)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            @if($equipment->images->first())
                                <img src="{{ asset('storage/' . $equipment->images->first()->path) }}"
                                     class="card-img-top"
                                     alt="{{ $equipment->title }}"
                                     style="height: 200px; object-fit: cover;">
                            @else
                                <div class="bg-light border-bottom" style="height: 200px"></div>
                            @endif
                            <div class="card-body">
                                <h5 class="card-title">{{ $equipment->title }}</h5>
                                <p class="card-text text-muted small">{{ $equipment->category->name }}</p>
                                <p class="card-text">{{ Str::limit($equipment->description, 100) }}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    @if($equipment->rentalTerms->isNotEmpty())
                                        <span class="fw-bold">{{ $equipment->rentalTerms->first()->price }} ₽/час</span>
                                    @else
                                        <span class="text-danger">Нет условий аренды</span>
                                    @endif
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="{{ route('catalog.show', $equipment) }}" class="btn btn-primary w-100">
                                    <i class="bi bi-eye me-1"></i> Подробнее
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Пагинация -->
            <div class="d-flex justify-content-center mt-4">
                {{ $equipments->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
