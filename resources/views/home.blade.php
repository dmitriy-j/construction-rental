@extends('layouts.app')

@section('title', config('app.name') . ' — Федеральная Арендная Платформа')

@section('content')
{{-- ============================================================
     1. HERO-БЛОК (Главный баннер)
     ============================================================ --}}
<section class="hero-section position-relative overflow-hidden">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="container position-relative z-1">
        <div class="row min-vh-75 align-items-center py-5">
            <div class="col-lg-7 text-white">
                <h1 class="hero-title display-3 fw-bold mb-3 animate__animated animate__fadeInUp">
                    Аренда строительной техники<br>
                    <span class="text-warning">по всей России</span>
                </h1>
                <p class="hero-subtitle lead mb-4 animate__animated animate__fadeInUp animate__delay-1s">
                    Федеральная Арендная Платформа — ваш надёжный партнёр в аренде строительной
                    техники. Широкий выбор, прозрачные цены, безопасные сделки.
                </p>
                <div class="hero-cta d-flex flex-wrap gap-3 animate__animated animate__fadeInUp animate__delay-2s">
                    <a href="{{ route('catalog.index') }}" class="btn btn-warning btn-lg px-4 py-3 fw-bold">
                        <i class="bi bi-search me-2"></i> Перейти в каталог
                    </a>
                    <a href="{{ route('rental-requests.index') }}" class="btn btn-outline-light btn-lg px-4 py-3 fw-bold">
                        <i class="bi bi-plus-circle me-2"></i> Создать заявку
                    </a>
                </div>
                <div class="hero-badges mt-4 d-flex flex-wrap gap-2 animate__animated animate__fadeInUp animate__delay-3s">
                    <span class="badge bg-light text-primary fs-6 p-2 px-3">
                        <i class="bi bi-geo-alt me-1"></i> 85 регионов
                    </span>
                    <span class="badge bg-light text-primary fs-6 p-2 px-3">
                        <i class="bi bi-shield-check me-1"></i> Безопасные сделки
                    </span>
                    <span class="badge bg-light text-primary fs-6 p-2 px-3">
                        <i class="bi bi-headset me-1"></i> Поддержка 24/7
                    </span>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <div class="hero-search-card bg-white rounded-4 shadow-lg p-4 animate__animated animate__fadeInRight animate__delay-1s">
                    <h5 class="mb-3 text-dark fw-bold">
                        <i class="bi bi-search text-primary me-2"></i>Быстрый поиск техники
                    </h5>
                    <form action="{{ route('catalog.index') }}" method="GET">
                        <div class="mb-3">
                            <input type="text" name="search" class="form-control form-control-lg"
                                   placeholder="Название техники..." aria-label="Поиск техники">
                        </div>
                        <div class="mb-3">
                            <select name="category" class="form-select form-select-lg" aria-label="Категория">
                                <option value="">Все категории</option>
                                <option value="excavators">Экскаваторы</option>
                                <option value="bulldozers">Бульдозеры</option>
                                <option value="cranes">Краны</option>
                                <option value="loaders">Погрузчики</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <select name="region" class="form-select form-select-lg" aria-label="Регион">
                                <option value="">Все регионы</option>
                                <option value="moscow">Москва и область</option>
                                <option value="spb">Санкт-Петербург</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                            <i class="bi bi-search me-2"></i> Найти
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-scroll-indicator text-center animate__animated animate__fadeInUp animate__delay-4s">
        <a href="#advantages" class="text-white opacity-75 text-decoration-none">
            <i class="bi bi-chevron-down fs-2"></i>
        </a>
    </div>
</section>

{{-- ============================================================
     2. ПРЕИМУЩЕСТВА (4 колонки)
     ============================================================ --}}
<section id="advantages" class="section-padding bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title fw-bold">Почему выбирают нас</h2>
            <p class="section-subtitle text-muted">Четыре причины работать с Федеральной Арендной Платформой</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="advantage-card text-center p-4 rounded-4 bg-white shadow-sm h-100">
                    <div class="advantage-icon mb-3">
                        <i class="fas fa-tractor text-primary"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Широкий выбор техники</h4>
                    <p class="text-muted mb-0">Экскаваторы, бульдозеры, краны, погрузчики и другая техника от проверенных арендодателей</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="advantage-card text-center p-4 rounded-4 bg-white shadow-sm h-100">
                    <div class="advantage-icon mb-3">
                        <i class="fas fa-ruble-sign text-primary"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Прозрачные цены</h4>
                    <p class="text-muted mb-0">Честная стоимость без скрытых платежей и комиссий. Вы видите цену сразу</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="advantage-card text-center p-4 rounded-4 bg-white shadow-sm h-100">
                    <div class="advantage-icon mb-3">
                        <i class="fas fa-handshake text-primary"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Безопасные сделки</h4>
                    <p class="text-muted mb-0">Юридическая защита, договоры и гарантии для обеих сторон сделки</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="advantage-card text-center p-4 rounded-4 bg-white shadow-sm h-100">
                    <div class="advantage-icon mb-3">
                        <i class="fas fa-headset text-primary"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Поддержка 24/7</h4>
                    <p class="text-muted mb-0">Круглосуточная поддержка клиентов. Поможем в любой ситуации</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     3. ПОПУЛЯРНАЯ ТЕХНИКА (динамический блок)
     ============================================================ --}}
<section class="section-padding">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="section-title fw-bold mb-1">Популярная техника</h2>
                <p class="section-subtitle text-muted mb-0">Лучшие предложения от арендодателей</p>
            </div>
            <a href="{{ route('catalog.index') }}" class="btn btn-outline-primary btn-lg d-none d-md-inline-flex">
                Смотреть весь каталог <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>

        @if($popularEquipment->count() > 0)
        <div class="row g-4">
            @foreach($popularEquipment as $equipment)
            <div class="col-md-6 col-lg-4">
                <div class="equipment-card card border-0 shadow-sm h-100">
                    <div class="equipment-card-img-wrapper position-relative">
                        @php
                            $mainImage = $equipment->mainImage;
                        @endphp
                        @if($mainImage)
                        <img src="{{ Storage::url($mainImage->path) }}"
                             alt="{{ $equipment->title }}"
                             class="card-img-top equipment-card-img"
                             loading="lazy">
                        @else
                        <div class="equipment-card-placeholder d-flex align-items-center justify-content-center bg-light">
                            <i class="fas fa-tractor text-secondary" style="font-size: 3rem;"></i>
                        </div>
                        @endif
                        @if($equipment->category)
                        <span class="equipment-card-badge badge bg-primary position-absolute top-0 start-0 m-3">
                            {{ $equipment->category->name }}
                        </span>
                        @endif
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold mb-1">{{ $equipment->title }}</h5>
                        <p class="card-text text-muted small mb-3">
                            <i class="bi bi-geo-alt me-1"></i>
                            {{ $equipment->location->name ?? 'Регион не указан' }}
                        </p>
                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            <div>
                                @if($equipment->rentalTerms->isNotEmpty())
                                    @php
                                        $minPrice = $equipment->rentalTerms->min('price_per_hour');
                                    @endphp
                                    <span class="fw-bold text-primary fs-5">{{ number_format($minPrice, 0, '.', ' ') }} ₽/час</span>
                                @else
                                    <span class="text-muted">Цена не указана</span>
                                @endif
                            </div>
                            <a href="{{ route('catalog.show', $equipment) }}"
                               class="btn btn-sm btn-outline-primary">
                                Подробнее
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="text-center mt-4 d-md-none">
            <a href="{{ route('catalog.index') }}" class="btn btn-outline-primary btn-lg w-100">
                Смотреть весь каталог <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
        @else
        <div class="text-center py-5">
            <i class="fas fa-tractor text-muted" style="font-size: 4rem;"></i>
            <p class="text-muted mt-3 fs-5">В каталоге пока нет техники. Скоро здесь появятся предложения!</p>
            <a href="{{ route('catalog.index') }}" class="btn btn-primary btn-lg">Перейти в каталог</a>
        </div>
        @endif
    </div>
</section>

{{-- ============================================================
     4. КАК ЭТО РАБОТАЕТ (3 шага)
     ============================================================ --}}
<section class="section-padding bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title fw-bold">Как это работает</h2>
            <p class="section-subtitle text-muted">Всего три простых шага для аренды техники</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="step-card text-center p-4">
                    <div class="step-number mx-auto mb-3">1</div>
                    <div class="step-icon mb-3">
                        <i class="fas fa-search text-primary"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Выберите технику</h4>
                    <p class="text-muted mb-0">Изучите каталог, сравните цены и характеристики. Найдите подходящую технику для ваших задач</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card text-center p-4">
                    <div class="step-number mx-auto mb-3">2</div>
                    <div class="step-icon mb-3">
                        <i class="fas fa-file-invoice text-primary"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Оставьте заявку</h4>
                    <p class="text-muted mb-0">Заполните простую форму заявки. Укажите даты аренды и дополнительные требования</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card text-center p-4">
                    <div class="step-number mx-auto mb-3">3</div>
                    <div class="step-icon mb-3">
                        <i class="fas fa-check-circle text-primary"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Получите предложения</h4>
                    <p class="text-muted mb-0">Получите предложения от арендодателей. Выберите лучшее и заключите договор</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     5. НОВОСТИ (лента последних 4 новостей)
     ============================================================ --}}
@if($latestNews->count() > 0)
<section class="section-padding">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="section-title fw-bold mb-1">Новости и обновления</h2>
                <p class="section-subtitle text-muted mb-0">Будьте в курсе последних событий платформы</p>
            </div>
            <a href="{{ route('news.index') }}" class="btn btn-outline-primary btn-lg d-none d-md-inline-flex">
                Все новости <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
        <div class="row g-4">
            @foreach($latestNews as $newsItem)
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="small text-muted mb-2">
                            <span class="badge bg-{{ $newsItem->category === 'all' ? 'info' : ($newsItem->category === 'lessee' ? 'success' : 'warning') }} me-1" style="font-size:0.7rem;">
                                {{ $newsItem->category === 'all' ? 'Все' : ($newsItem->category === 'lessee' ? 'Арендаторам' : 'Арендодателям') }}
                            </span>
                            {{ $newsItem->published_at?->format('d.m.Y') ?? $newsItem->created_at->format('d.m.Y') }}
                        </div>
                        <h5 class="card-title fw-bold mb-2" style="font-size:1rem;">
                            <a href="{{ route('news.show', $newsItem->slug) }}" class="text-decoration-none stretched-link">{{ $newsItem->title }}</a>
                        </h5>
                        <p class="card-text small text-muted mb-0 flex-grow-1">{{ $newsItem->excerpt ?? Str::limit(strip_tags($newsItem->content), 120) }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="text-center mt-4 d-md-none">
            <a href="{{ route('news.index') }}" class="btn btn-outline-primary btn-lg w-100">
                Все новости <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>
@endif

{{-- ============================================================
     6. СТАТИСТИКА (цифры)
     ============================================================ --}}
<section class="section-padding stats-section position-relative overflow-hidden">
    <div class="stats-bg"></div>
    <div class="container position-relative z-1">
        <div class="row g-4 text-center">
            <div class="col-6 col-lg-3">
                <div class="stat-item">
                    <div class="stat-number fs-1 fw-bold text-white" data-count="{{ $stats['lessors'] ?? 0 }}">0</div>
                    <div class="stat-label text-white-50 fs-5">Арендодателей</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-item">
                    <div class="stat-number fs-1 fw-bold text-white" data-count="{{ $stats['lessees'] ?? 0 }}">0</div>
                    <div class="stat-label text-white-50 fs-5">Арендаторов</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-item">
                    <div class="stat-number fs-1 fw-bold text-white" data-count="{{ $stats['orders'] ?? 0 }}">0</div>
                    <div class="stat-label text-white-50 fs-5">Заказов</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-item">
                    <div class="stat-number fs-1 fw-bold text-white" data-count="{{ $stats['equipment'] ?? 0 }}">0</div>
                    <div class="stat-label text-white-50 fs-5">Ед. техники</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     6. ОТЗЫВЫ (карусель Bootstrap)
     ============================================================ --}}
<section class="section-padding">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title fw-bold">Что говорят наши клиенты</h2>
            <p class="section-subtitle text-muted">Реальные отзывы пользователей платформы</p>
        </div>

        <div id="testimonialsCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
            <div class="carousel-inner">
                @php
                    $testimonials = [
                        [
                            'name' => 'Алексей Иванов',
                            'role' => 'Прораб, ООО "СтройИнвест"',
                            'text' => 'Отличная платформа! Нашли экскаватор для срочных работ за один день. Цены ниже рыночных, всё прозрачно. Рекомендую!',
                            'rating' => 5,
                            'avatar' => null,
                        ],
                        [
                            'name' => 'Мария Петрова',
                            'role' => 'Начальник снабжения',
                            'text' => 'Давно искали сервис, где можно быстро сравнить предложения от разных арендодателей. ФАП решила эту проблему. Очень удобно!',
                            'rating' => 5,
                            'avatar' => null,
                        ],
                        [
                            'name' => 'Дмитрий Соколов',
                            'role' => 'ИП Соколов Д.А.',
                            'text' => 'Как арендодатель, очень доволен платформой. Быстро нахожу клиентов, все документы оформляются онлайн. Спасибо команде!',
                            'rating' => 4,
                            'avatar' => null,
                        ],
                    ];
                @endphp

                @foreach($testimonials as $key => $testimonial)
                <div class="carousel-item {{ $key === 0 ? 'active' : '' }}">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="testimonial-card text-center p-5">
                                <div class="testimonial-avatar mx-auto mb-3">
                                    @if($testimonial['avatar'])
                                    <img src="{{ $testimonial['avatar'] }}" alt="{{ $testimonial['name'] }}" class="rounded-circle" width="80" height="80">
                                    @else
                                    <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center mx-auto">
                                        <span class="fw-bold text-white fs-3">{{ mb_substr($testimonial['name'], 0, 1) }}</span>
                                    </div>
                                    @endif
                                </div>
                                <div class="testimonial-rating mb-3">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $testimonial['rating'])
                                        <i class="bi bi-star-fill text-warning"></i>
                                        @else
                                        <i class="bi bi-star text-warning"></i>
                                        @endif
                                    @endfor
                                </div>
                                <p class="testimonial-text lead mb-4">"{{ $testimonial['text'] }}"</p>
                                <div class="testimonial-author">
                                    <h5 class="fw-bold mb-1">{{ $testimonial['name'] }}</h5>
                                    <span class="text-muted">{{ $testimonial['role'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#testimonialsCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon bg-primary rounded-circle" aria-hidden="true"></span>
                <span class="visually-hidden">Предыдущий</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#testimonialsCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon bg-primary rounded-circle" aria-hidden="true"></span>
                <span class="visually-hidden">Следующий</span>
            </button>
        </div>

        <div class="carousel-indicators position-static mt-4">
            @foreach($testimonials as $key => $testimonial)
            <button type="button" data-bs-target="#testimonialsCarousel" data-bs-slide-to="{{ $key }}"
                    class="bg-primary rounded-circle mx-1 {{ $key === 0 ? 'active' : '' }}"
                    style="width: 12px; height: 12px;"
                    aria-current="{{ $key === 0 ? 'true' : 'false' }}"
                    aria-label="Отзыв {{ $key + 1 }}">
            </button>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================
     7. ФОРМА ОБРАТНОЙ СВЯЗИ
     ============================================================ --}}
<section class="section-padding bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <h2 class="section-title fw-bold">Остались вопросы?</h2>
                    <p class="section-subtitle text-muted">Заполните форму и мы свяжемся с вами в ближайшее время</p>
                </div>

                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                <div class="contact-form-card bg-white rounded-4 shadow-sm p-4 p-lg-5">
                    <form id="contactForm" action="{{ route('home.contact') }}" method="POST" novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="contactName" class="form-label fw-semibold">Ваше имя *</label>
                                <input type="text" class="form-control form-control-lg" id="contactName"
                                       name="name" required placeholder="Иван Петров">
                                <div class="invalid-feedback">Пожалуйста, укажите ваше имя</div>
                            </div>
                            <div class="col-md-6">
                                <label for="contactPhone" class="form-label fw-semibold">Телефон *</label>
                                <input type="tel" class="form-control form-control-lg" id="contactPhone"
                                       name="phone" required placeholder="+7 (999) 123-45-67">
                                <div class="invalid-feedback">Пожалуйста, укажите номер телефона</div>
                            </div>
                            <div class="col-md-12">
                                <label for="contactEmail" class="form-label fw-semibold">Email <span class="text-muted">(необязательно)</span></label>
                                <input type="email" class="form-control form-control-lg" id="contactEmail"
                                       name="email" placeholder="ivan@example.com">
                                <div class="invalid-feedback">Пожалуйста, укажите корректный email</div>
                            </div>
                            <div class="col-md-12">
                                <label for="contactMessage" class="form-label fw-semibold">Сообщение</label>
                                <textarea class="form-control form-control-lg" id="contactMessage"
                                          name="message" rows="4" placeholder="Опишите ваш вопрос..."></textarea>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold" id="contactSubmitBtn">
                                <i class="bi bi-send me-2"></i> Отправить
                            </button>
                        </div>
                        <div id="contactSuccess" class="alert alert-success mt-3 d-none">
                            <i class="bi bi-check-circle me-2"></i> <span id="contactSuccessMsg"></span>
                        </div>
                        <div id="contactError" class="alert alert-danger mt-3 d-none">
                            <i class="bi bi-exclamation-circle me-2"></i> <span id="contactErrorMsg"></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
/* ==========================================================================
   HOME PAGE STYLES
   ========================================================================== */

/* Hero Section — edge-to-edge without white borders */
.hero-section {
    min-height: 75vh;
    display: flex;
    align-items: center;
}

.hero-bg {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #0b5ed7 0%, #002d72 50%, #001a4d 100%);
    z-index: 0;
}

.hero-bg::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background:
        radial-gradient(ellipse at 20% 50%, rgba(255, 193, 7, 0.15) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 20%, rgba(112, 179, 255, 0.15) 0%, transparent 50%),
        radial-gradient(ellipse at 50% 80%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
    z-index: 1;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    z-index: 1;
    pointer-events: none;
}

.hero-title {
    line-height: 1.1;
    text-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
}

.hero-subtitle {
    opacity: 0.9;
    max-width: 600px;
}

.hero-scroll-indicator {
    position: absolute;
    bottom: 30px;
    left: 0;
    right: 0;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-10px); }
    60% { transform: translateY(-5px); }
}

.hero-search-card {
    max-width: 400px;
    margin-left: auto;
}

.min-vh-75 {
    min-height: 75vh;
}

.section-padding {
    padding: 5rem 0;
}

@media (max-width: 768px) {
    .section-padding {
        padding: 3rem 0;
    }
    .hero-section {
        min-height: 60vh;
    }
    .hero-title {
        font-size: 2.2rem !important;
    }
}

@media (max-width: 576px) {
    .hero-section {
        min-height: 50vh;
        padding-top: calc(var(--navbar-height, 80px) + 0.5rem);
    }
    .hero-title {
        font-size: 1.8rem !important;
    }
    .hero-subtitle {
        font-size: 1rem;
    }
    .hero-cta .btn {
        font-size: 0.9rem;
        padding: 0.6rem 1rem !important;
    }
}

/* --- Section Titles --- */
.section-title {
    position: relative;
    display: inline-block;
    margin-bottom: 0.5rem;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, var(--fap-primary), var(--fap-secondary));
    border-radius: 2px;
}

/* --- Advantage Cards --- */
.advantage-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.advantage-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1) !important;
    border-color: var(--fap-primary);
}

.advantage-icon i {
    font-size: 2.5rem;
    transition: transform 0.3s ease;
}

.advantage-card:hover .advantage-icon i {
    transform: scale(1.15);
}

/* --- Equipment Cards --- */
.equipment-card {
    transition: all 0.3s ease;
    overflow: hidden;
}

.equipment-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12) !important;
}

.equipment-card-img-wrapper {
    height: 220px;
    overflow: hidden;
    background: #f8f9fa;
}

.equipment-card-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.equipment-card:hover .equipment-card-img {
    transform: scale(1.08);
}

.equipment-card-placeholder {
    width: 100%;
    height: 220px;
}

.equipment-card-badge {
    font-size: 0.8rem;
    padding: 0.4rem 0.8rem;
}

/* --- Steps --- */
.step-card {
    position: relative;
    transition: all 0.3s ease;
}

.step-card:hover {
    transform: translateY(-5px);
}

.step-number {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--fap-primary), var(--fap-dark));
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
}

.step-icon i {
    font-size: 2.5rem;
    transition: transform 0.3s ease;
}

.step-card:hover .step-icon i {
    transform: scale(1.15);
}

/* --- Stats Section --- */
.stats-section {
    padding: 5rem 0;
}

.stats-bg {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #002d72 0%, #0b5ed7 50%, #0056b3 100%);
    z-index: 0;
}

.stats-bg::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background:
        radial-gradient(ellipse at 30% 50%, rgba(255, 193, 7, 0.12) 0%, transparent 60%),
        radial-gradient(ellipse at 70% 50%, rgba(255, 255, 255, 0.08) 0%, transparent 60%);
    z-index: 1;
}

.stat-number {
    font-size: 3rem !important;
    line-height: 1;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

/* --- Testimonials --- */
.testimonial-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
}

.avatar-placeholder {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--fap-primary), var(--fap-dark));
}

.testimonial-text {
    font-style: italic;
    color: #555;
}

.carousel-control-prev-icon,
.carousel-control-next-icon {
    width: 40px;
    height: 40px;
    background-size: 50%;
}

/* --- Contact Form --- */
.contact-form-card {
    border: 1px solid rgba(0, 0, 0, 0.05);
}

#contactForm .form-control:focus {
    border-color: var(--fap-primary);
    box-shadow: 0 0 0 0.2rem rgba(0, 86, 179, 0.15);
}

/* --- Animations on scroll --- */
.animate-on-scroll {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.6s ease;
}

.animate-on-scroll.visible {
    opacity: 1;
    transform: translateY(0);
}

/* --- Responsive fixes --- */
@media (max-width: 991.98px) {
    .hero-search-card {
        display: none;
    }
    .stat-number {
        font-size: 2.2rem !important;
    }
}

@media (max-width: 576px) {
    .stat-number {
        font-size: 1.8rem !important;
    }
    .stat-label {
        font-size: 0.9rem !important;
    }
    .testimonial-card {
        padding: 2rem 1.5rem !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // ===========================
    // 1. Анимация счётчика статистики
    // ===========================
    function animateCounters() {
        const counters = document.querySelectorAll('.stat-number[data-count]');
        if (!counters.length) return;

        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-count')) || 0;
            const duration = 2000;
            const steps = 60;
            const increment = target / steps;
            let current = 0;
            let step = 0;

            const timer = setInterval(() => {
                step++;
                current = Math.round(increment * step);
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                counter.textContent = current.toLocaleString('ru-RU');
            }, duration / steps);
        });
    }

    // Запускаем счётчики через Intersection Observer
    const statsSection = document.querySelector('.stats-section');
    if (statsSection) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });
        observer.observe(statsSection);
    }

    // ===========================
    // 2. Плавный скролл к якорям
    // ===========================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                const navHeight = 80;
                const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - navHeight;
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });

    // ===========================
    // 3. Валидация и отправка формы
    // ===========================
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        const submitBtn = document.getElementById('contactSubmitBtn');
        const successAlert = document.getElementById('contactSuccess');
        const successMsg = document.getElementById('contactSuccessMsg');
        const errorAlert = document.getElementById('contactError');
        const errorMsg = document.getElementById('contactErrorMsg');

        // Bootstrap-валидация
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Скрываем предыдущие сообщения
            successAlert.classList.add('d-none');
            errorAlert.classList.add('d-none');

            // Проверка валидности
            if (!contactForm.checkValidity()) {
                e.stopPropagation();
                contactForm.classList.add('was-validated');
                return;
            }

            // Отправка AJAX
            const formData = new FormData(contactForm);
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Отправка...';

            fetch(contactForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successMsg.textContent = data.message;
                    successAlert.classList.remove('d-none');
                    contactForm.reset();
                    contactForm.classList.remove('was-validated');
                } else {
                    errorMsg.textContent = data.message || 'Произошла ошибка. Попробуйте позже.';
                    errorAlert.classList.remove('d-none');
                }
            })
            .catch(error => {
                errorMsg.textContent = 'Произошла ошибка. Попробуйте позже.';
                errorAlert.classList.remove('d-none');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-send me-2"></i> Отправить';
            });
        });
    }

    // ===========================
    // 4. Появление блоков при скролле (Intersection Observer)
    // ===========================
    const animateElements = document.querySelectorAll('.advantage-card, .step-card, .equipment-card, .testimonial-card');
    if (animateElements.length) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        animateElements.forEach(el => {
            el.style.opacity = '0';
            observer.observe(el);
        });
    }

    // ===========================
    // 5. Маска телефона (простая)
    // ===========================
    const phoneInput = document.getElementById('contactPhone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.startsWith('7') || value.startsWith('8')) {
                    let formatted = '+7 ';
                    if (value.startsWith('8')) value = '7' + value.substring(1);
                    if (value.length > 1) formatted += '(' + value.substring(1, 4);
                    if (value.length >= 4) formatted += ') ' + value.substring(4, 7);
                    if (value.length >= 7) formatted += '-' + value.substring(7, 9);
                    if (value.length >= 9) formatted += '-' + value.substring(9, 11);
                    this.value = formatted;
                }
            }
        });
    }
});
</script>
@endpush
