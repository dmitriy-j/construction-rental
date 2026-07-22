@extends('layouts.app')

@section('title', config('app.name') . ' — Федеральная Арендная Платформа')

@section('content')
{{-- ============================================================
     1. HERO-БЛОК
     ============================================================ --}}
<section class="hero-section position-relative overflow-hidden" style="background:linear-gradient(135deg,#0B5ED7 0%,#002D72 50%,#001A4D 100%);min-height:75vh;display:flex;align-items:center;padding-top:var(--navbar-height,72px);">
    <div class="container position-relative z-2">
        <div class="row min-vh-75 align-items-center py-5">
            <div class="col-lg-7 text-white">
                <div class="hero-badge mb-3 d-inline-flex">
                    <span class="badge bg-warning text-dark fw-bold px-3 py-2">
                        <i class="bi bi-star-fill me-1"></i> Федеральная платформа №1
                    </span>
                </div>
                <h1 class="display-4 fw-bold mb-3" style="line-height:1.1;">
                    Аренда строительной техники<br>
                    <span class="text-warning">по всей России</span>
                </h1>
                <p class="lead mb-4 text-white-50" style="max-width:560px;">
                    Федеральная Арендная Платформа — ваш надёжный партнёр в аренде строительной
                    техники. Широкий выбор, прозрачные цены, безопасные сделки.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="{{ route('catalog.index') }}" class="btn btn-warning btn-lg px-5 py-3 fw-bold shadow-lg">
                        <i class="bi bi-search me-2"></i> Перейти в каталог
                    </a>
                    <a href="{{ route('rental-requests.index') }}" class="btn btn-outline-light btn-lg px-5 py-3 fw-bold border-2">
                        <i class="bi bi-plus-circle me-2"></i> Создать заявку
                    </a>
                </div>
                <div class="d-flex flex-wrap gap-4">
                    <div><div class="fs-3 fw-bold text-warning">85+</div><div class="text-white-50">Регионов</div></div>
                    <div style="width:1px;height:40px;background:rgba(255,255,255,0.15);align-self:center;"></div>
                    <div><div class="fs-3 fw-bold text-warning">1000+</div><div class="text-white-50">Ед. техники</div></div>
                    <div style="width:1px;height:40px;background:rgba(255,255,255,0.15);align-self:center;"></div>
                    <div><div class="fs-3 fw-bold text-warning">24/7</div><div class="text-white-50">Поддержка</div></div>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <div class="card shadow-lg p-4 border-0 rounded-3">
                    <h5 class="mb-3 fw-bold d-flex align-items-center">
                        <i class="bi bi-search text-primary me-2"></i> Быстрый поиск техники
                    </h5>
                    <form action="{{ route('catalog.index') }}" method="GET">
                        <div class="mb-3">
                            <label class="form-label">Название техники</label>
                            <input type="text" name="search" class="form-control" placeholder="Экскаватор, бульдозер...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Категория</label>
                            <select name="category" class="form-select">
                                <option value="">Все категории</option>
                                <option value="excavators">Экскаваторы</option>
                                <option value="bulldozers">Бульдозеры</option>
                                <option value="cranes">Краны</option>
                                <option value="loaders">Погрузчики</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <i class="bi bi-search me-2"></i> Найти технику
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     2. ПРЕИМУЩЕСТВА
     ============================================================ --}}
<section class="py-5" id="advantages">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold" style="font-size:2rem;">Почему выбирают нас</h2>
            <p class="text-muted mx-auto" style="max-width:600px;">Четыре причины работать с Федеральной Арендной Платформой</p>
        </div>
        <div class="row g-4">
            @foreach([
                ['icon' => 'fa-tractor', 'title' => 'Широкий выбор техники', 'text' => 'Экскаваторы, бульдозеры, краны, погрузчики от проверенных арендодателей'],
                ['icon' => 'fa-ruble-sign', 'title' => 'Прозрачные цены', 'text' => 'Честная стоимость без скрытых платежей и комиссий'],
                ['icon' => 'fa-handshake', 'title' => 'Безопасные сделки', 'text' => 'Юридическая защита, договоры и гарантии для обеих сторон'],
                ['icon' => 'fa-headset', 'title' => 'Поддержка 24/7', 'text' => 'Круглосуточная поддержка клиентов. Поможем в любой ситуации'],
            ] as $adv)
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 h-100 p-4 text-center" style="transition:all 0.3s ease;">
                    <div class="mb-3 mx-auto bg-light rounded-circle d-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                        <i class="fas {{ $adv['icon'] }} text-primary fs-3"></i>
                    </div>
                    <h4 class="fw-bold mb-2">{{ $adv['title'] }}</h4>
                    <p class="text-muted mb-0 small">{{ $adv['text'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================
     3. ПОПУЛЯРНАЯ ТЕХНИКА
     ============================================================ --}}
<section class="py-5 bg-white">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold" style="font-size:2rem;">Популярная техника</h2>
                <p class="text-muted mb-0">Лучшие предложения от арендодателей</p>
            </div>
            <a href="{{ route('catalog.index') }}" class="btn btn-outline-primary d-none d-md-inline-flex">
                Смотреть весь каталог <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>

        @if($popularEquipment->count() > 0)
        <div class="row g-4">
            @foreach($popularEquipment as $equipment)
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card border-0 h-100" style="transition:all 0.3s ease;">
                    <div class="position-relative" style="height:200px;overflow:hidden;background:#f8f9fa;">
                        @php $mainImage = $equipment->mainImage; @endphp
                        @if($mainImage)
                        <img src="{{ Storage::url($mainImage->path) }}" alt="{{ $equipment->title }}"
                             class="w-100 h-100" style="object-fit:cover;">
                        @else
                        <div class="w-100 h-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-tractor text-secondary fs-1"></i>
                        </div>
                        @endif
                        @if($equipment->category)
                        <span class="position-absolute top-0 start-0 m-3 badge bg-warning text-dark fw-bold">
                            {{ $equipment->category->name }}
                        </span>
                        @endif
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="fw-bold mb-1">{{ $equipment->title }}</h5>
                        <p class="text-muted small mb-3"><i class="bi bi-geo-alt me-1"></i>{{ $equipment->location->name ?? 'Регион не указан' }}</p>
                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            <div>
                                @if($equipment->rentalTerms->isNotEmpty())
                                    <span class="fw-bold text-primary fs-5">{{ number_format($equipment->rentalTerms->min('price_per_day'), 0, '.', ' ') }} ₽/сут</span>
                                @else
                                    <span class="text-muted small">Цена не указана</span>
                                @endif
                            </div>
                            <a href="{{ route('catalog.show', $equipment) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                Подробнее <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-5">
            <i class="fas fa-tractor text-muted" style="font-size:4rem;"></i>
            <p class="text-muted fs-5 mt-3">В каталоге пока нет техники.</p>
            <a href="{{ route('catalog.index') }}" class="btn btn-primary btn-lg px-5 mt-2">Перейти в каталог</a>
        </div>
        @endif
    </div>
</section>

{{-- ============================================================
     4. НОВОСТИ
     ============================================================ --}}
@if($latestNews->count() > 0)
<section class="py-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold" style="font-size:2rem;">Новости и обновления</h2>
                <p class="text-muted mb-0">Будьте в курсе последних событий платформы</p>
            </div>
            <a href="{{ route('news.index') }}" class="btn btn-outline-primary d-none d-md-inline-flex">
                Все новости <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
        <div class="row g-4">
            @foreach($latestNews as $newsItem)
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 h-100" style="transition:all 0.3s ease;">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-{{ $newsItem->category === 'all' ? 'primary' : ($newsItem->category === 'lessee' ? 'success' : 'warning') }}">
                                {{ $newsItem->category === 'all' ? 'Для всех' : ($newsItem->category === 'lessee' ? 'Арендаторам' : 'Арендодателям') }}
                            </span>
                            <span class="small text-muted"><i class="bi bi-calendar3 me-1"></i>{{ $newsItem->published_at?->format('d.m.Y') ?? $newsItem->created_at->format('d.m.Y') }}</span>
                        </div>
                        <h5 class="fw-bold mb-2" style="font-size:1rem;">
                            <a href="{{ route('news.show', $newsItem->slug) }}" class="text-decoration-none stretched-link text-dark">{{ $newsItem->title }}</a>
                        </h5>
                        <p class="small text-muted mb-0 flex-grow-1">{{ $newsItem->excerpt ?? Str::limit(strip_tags($newsItem->content), 120) }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============================================================
     5. СТАТИСТИКА
     ============================================================ --}}
<section class="py-5" style="background:linear-gradient(135deg,#002D72,#0B5ED7);">
    <div class="container">
        <div class="row g-4 text-center">
            @foreach([['label'=>'Арендодателей','val'=>$stats['lessors']??0],['label'=>'Арендаторов','val'=>$stats['lessees']??0],['label'=>'Заказов','val'=>$stats['orders']??0],['label'=>'Ед. техники','val'=>$stats['equipment']??0]] as $stat)
            <div class="col-6 col-lg-3">
                <div class="fs-1 fw-bold text-warning">{{ $stat['val'] }}</div>
                <div class="text-white-50 fs-5">{{ $stat['label'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================
     6. ФОРМА ОБРАТНОЙ СВЯЗИ
     ============================================================ --}}
<section class="py-5" style="background:linear-gradient(135deg,#002D72,#0B5ED7);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <h2 class="text-white fw-bold" style="font-size:2.5rem;">Остались вопросы?</h2>
                    <p class="text-white-50">Заполните форму и мы свяжемся с вами</p>
                </div>
                <div class="bg-white rounded-3 shadow-lg p-4 p-lg-5">
                    <form action="{{ route('home.contact') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Ваше имя *</label>
                                <input type="text" class="form-control" name="name" required placeholder="Иван Петров">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Телефон *</label>
                                <input type="tel" class="form-control" name="phone" required placeholder="+7 (999) 123-45-67">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" placeholder="ivan@example.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Сообщение</label>
                                <textarea class="form-control" name="message" rows="4" placeholder="Опишите ваш вопрос..."></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold mt-4">
                            <i class="bi bi-send me-2"></i> Отправить
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
