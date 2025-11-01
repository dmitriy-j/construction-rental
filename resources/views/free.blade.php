@section('meta')
<title>Свободная строительная техника | Федеральная Арендная Платформа</title>
<meta name="description" content="Аренда доступной строительной техники от федерального оператора. Более 1000 единиц техники по всей России. Быстрое оформление, выгодные условия.">
@endsection

@extends('layouts.app')

@section('content')
<div class="container py-5">
    <!-- Заголовок с федеральным акцентом -->
    <div class="row mb-5">
        <div class="col-12">
            <h1 class="mb-3">Свободная строительная техника</h1>
            <p class="lead text-muted">Федеральная Арендная Платформа предлагает доступную технику для вашего бизнеса по всей России</p>

            <!-- Федеральные бейджи -->
            <div class="d-flex flex-wrap gap-2 mb-4">
                <span class="federal-badge badge">85 регионов</span>
                <span class="federal-badge badge">1000+ единиц техники</span>
                <span class="federal-badge badge">Единые стандарты</span>
            </div>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="card shadow-sm mb-5">
        <div class="card-body">
            <form class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Тип техники</label>
                    <select class="form-select">
                        <option selected>Все типы</option>
                        <option>Экскаваторы</option>
                        <option>Бульдозеры</option>
                        <option>Краны</option>
                        <option>Грузовики</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Регион</label>
                    <select class="form-select">
                        <option>Все регионы</option>
                        <option>Москва и МО</option>
                        <option>Санкт-Петербург и ЛО</option>
                        <option>Центральный ФО</option>
                        <option>Северо-Западный ФО</option>
                        <option>Южный ФО</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Стоимость</label>
                    <select class="form-select">
                        <option>Любая</option>
                        <option>До 20 000 ₽/смена</option>
                        <option>20 000 - 50 000 ₽/смена</option>
                        <option>От 50 000 ₽/смена</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Найти технику
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Информация о федеральном охвате -->
    <div class="alert alert-info mb-5">
        <div class="d-flex align-items-center">
            <i class="bi bi-info-circle fs-4 me-3"></i>
            <div>
                <strong>Федеральное покрытие:</strong> Техника доступна во всех регионах России.
                При заказе из другого региона предоставляем услуги логистики.
            </div>
        </div>
    </div>

    <!-- Список техники -->
    <div class="row">
        @foreach([
            ['name' => 'Экскаватор JCB 3CX', 'type' => 'Экскаватор', 'price' => '25 000 ₽/смена', 'location' => 'Москва', 'region' => 'Центральный ФО', 'image' => 'jcb'],
            ['name' => 'Бульдозер CAT D6', 'type' => 'Бульдозер', 'price' => '38 000 ₽/смена', 'location' => 'Казань', 'region' => 'Приволжский ФО', 'image' => 'cat'],
            ['name' => 'Кран Liebherr LTM 1050', 'type' => 'Кран', 'price' => '65 000 ₽/смена', 'location' => 'Санкт-Петербург', 'region' => 'Северо-Западный ФО', 'image' => 'crane'],
            ['name' => 'Погрузчик XCMG', 'type' => 'Погрузчик', 'price' => '18 000 ₽/смена', 'location' => 'Екатеринбург', 'region' => 'Уральский ФО', 'image' => 'loader'],
            ['name' => 'Каток Volvo', 'type' => 'Каток', 'price' => '22 000 ₽/смена', 'location' => 'Новосибирск', 'region' => 'Сибирский ФО', 'image' => 'roller'],
            ['name' => 'Бетоносмеситель', 'type' => 'Спецтехника', 'price' => '15 000 ₽/смена', 'location' => 'Краснодар', 'region' => 'Южный ФО', 'image' => 'mixer']
        ] as $item)
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm tech-card">
                <div class="position-relative">
                    <img src="https://via.placeholder.com/400x300?text={{ $item['image'] }}"
                         class="card-img-top"
                         alt="{{ $item['name'] }}"
                         style="height: 200px; object-fit: cover;">
                    <span class="position-absolute top-0 end-0 m-2 federal-badge badge">
                        {{ $item['region'] }}
                    </span>
                </div>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">{{ $item['name'] }}</h5>
                    <div class="mb-3">
                        <span class="badge bg-primary me-2">
                            <i class="bi bi-tag"></i> {{ $item['type'] }}
                        </span>
                        <span class="badge bg-secondary">
                            <i class="bi bi-geo-alt"></i> {{ $item['location'] }}
                        </span>
                    </div>

                    <div class="mt-auto">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold fs-5 text-primary">{{ $item['price'] }}</span>
                            <button class="btn btn-primary">
                                <i class="bi bi-cart-plus"></i> Забронировать
                            </button>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="bi bi-lightning-charge"></i> Доставка по России
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Блок федеральных преимуществ -->
    <div class="card bg-light border-0 mt-5">
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-4 mb-3">
                    <i class="bi bi-truck text-primary fs-1 mb-3"></i>
                    <h5>Доставка по РФ</h5>
                    <p class="text-muted">Организуем доставку в любой регион России</p>
                </div>
                <div class="col-md-4 mb-3">
                    <i class="bi bi-shield-check text-primary fs-1 mb-3"></i>
                    <h5>Гарантия качества</h5>
                    <p class="text-muted">Вся техника проходит регулярное ТО</p>
                </div>
                <div class="col-md-4 mb-3">
                    <i class="bi bi-clock text-primary fs-1 mb-3"></i>
                    <h5>Быстрое оформление</h5>
                    <p class="text-muted">Оформление заказа за 30 минут</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Пагинация -->
    <nav class="mt-5">
        <ul class="pagination justify-content-center">
            <li class="page-item disabled">
                <a class="page-link" href="#" tabindex="-1">Предыдущая</a>
            </li>
            <li class="page-item active"><a class="page-link" href="#">1</a></li>
            <li class="page-item"><a class="page-link" href="#">2</a></li>
            <li class="page-item"><a class="page-link" href="#">3</a></li>
            <li class="page-item">
                <a class="page-link" href="#">Следующая</a>
            </li>
        </ul>
    </nav>
</div>
@endsection
