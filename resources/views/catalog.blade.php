@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h1 class="mb-5">Каталог техники</h1>

    <!-- Фильтры -->
    <div class="card mb-4">
        <div class="card-body">
            <form>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Тип техники</label>
                        <select class="form-select">
                            <option selected>Все</option>
                            <option>Экскаваторы</option>
                            <option>Бульдозеры</option>
                            <option>Краны</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Цена за смену</label>
                        <input type="text" class="form-control" placeholder="до 50 000 ₽">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Год выпуска</label>
                        <select class="form-select">
                            <option>Новые (2020-2023)</option>
                            <option>2015-2019</option>
                            <option>2010-2014</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Применить
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Карточки техники -->
    <div class="row">
        @for($i = 1; $i <= 6; $i++)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <img src="https://via.placeholder.com/300x200?text=Technic-{{$i}}" class="card-img-top" alt="Техника">
                    <div class="card-body">
                        <h5 class="card-title">Экскаватор JCB {{$i}}</h5>
                        <p class="text-muted">Год выпуска: 202{{$i-1}}</p>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-speedometer2"></i> Мощность: 150 л.с.</li>
                            <li><i class="bi bi-droplet"></i> Гидравлика: {{$i*2}}-контурная</li>
                        </ul>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">25 000 ₽/смена</span>
                            <a href="#" class="btn btn-outline-primary">Подробнее</a>
                        </div>
                    </div>
                </div>
            </div>
        @endfor
    </div>

    <!-- Пагинация -->
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item disabled">
                <a class="page-link" href="#">Назад</a>
            </li>
            <li class="page-item active"><a class="page-link" href="#">1</a></li>
            <li class="page-item"><a class="page-link" href="#">2</a></li>
            <li class="page-item">
                <a class="page-link" href="#">Вперед</a>
            </li>
        </ul>
    </nav>
</div>
@endsection