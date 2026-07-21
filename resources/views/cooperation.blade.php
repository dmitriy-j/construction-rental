@extends('layouts.app')

@section('title', 'Сотрудничество — Федеральная Арендная Платформа')
@section('meta-description', 'Станьте партнёром Федеральной Арендной Платформы. Выгодные условия сотрудничества для арендодателей и арендаторов.')

@section('content')
{{-- ============================================================
    1. HERO-БЛОК
    ============================================================ --}}
<section class="page-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold text-white mb-3 animate__animated animate__fadeInUp">
                    Сотрудничество
                </h1>
                <p class="lead text-white-50 mb-0 animate__animated animate__fadeInUp animate__delay-1s">
                    Станьте партнёром Федеральной Арендной Платформы. Работаем с арендодателями
                    и арендаторами по всей России.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
    2. ПРЕИМУЩЕСТВА ДЛЯ АРЕНДОДАТЕЛЕЙ
    ============================================================ --}}
<section class="page-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="page-section-title fw-bold">Арендодателям</h2>
            <p class="page-section-subtitle mt-3">Сдавайте технику в аренду с выгодой</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-people text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Доступ к клиентам</h4>
                    <p class="text-muted mb-0">Получите доступ к тысячам арендаторов по всей России через единую федеральную платформу</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-shield-check text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Юридическая защита</h4>
                    <p class="text-muted mb-0">Автоматическое оформление договоров, актов и закрывающих документов</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-graph-up text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Прозрачные расчёты</h4>
                    <p class="text-muted mb-0">Автоматизированная система расчётов, баланс и история транзакций в личном кабинете</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-geo-alt text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Федеральный охват</h4>
                    <p class="text-muted mb-0">Работайте с клиентами из любого региона России без ограничений</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-wrench text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Управление техникой</h4>
                    <p class="text-muted mb-0">Удобный личный кабинет для управления парком техники и отслеживания заказов</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-clock text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Быстрый старт</h4>
                    <p class="text-muted mb-0">Регистрация за 5 минут, добавление техники и начало работы сразу</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
    3. ПРЕИМУЩЕСТВА ДЛЯ АРЕНДАТОРОВ
    ============================================================ --}}
<section class="page-section bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="page-section-title fw-bold">Арендаторам</h2>
            <p class="page-section-subtitle mt-3">Найдите технику для любых задач</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-search text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Широкий выбор</h4>
                    <p class="text-muted mb-0">Тысячи единиц техники от проверенных арендодателей по всей России</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-cash-coin text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Прозрачные цены</h4>
                    <p class="text-muted mb-0">Честная стоимость без скрытых платежей. Вы видите цену сразу</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-file-text text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Простое оформление</h4>
                    <p class="text-muted mb-0">Быстрое создание заявки, электронный документооборот</p>
                </div>
            </div>
        </div>
        <div class="text-center mt-5">
            <a href="{{ route('catalog.index') }}" class="btn btn-primary btn-lg px-5 py-3 fw-bold">
                <i class="bi bi-search me-2"></i> Перейти в каталог
            </a>
        </div>
    </div>
</section>

{{-- ============================================================
    4. УСЛОВИЯ СОТРУДНИЧЕСТВА
    ============================================================ --}}
<section class="page-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="page-section-title fw-bold">Условия сотрудничества</h2>
            <p class="page-section-subtitle mt-3">Всё просто и прозрачно</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-circle bg-primary flex-shrink-0 me-3">
                                <i class="bi bi-person-plus fs-3 text-white"></i>
                            </div>
                            <h5 class="fw-bold mb-0">Регистрация</h5>
                        </div>
                        <p class="text-muted mb-0">Зарегистрируйтесь на платформе, укажите данные компании и подтвердите аккаунт</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-circle bg-success flex-shrink-0 me-3">
                                <i class="bi bi-wrench-adjustable-circle fs-3 text-white"></i>
                            </div>
                            <h5 class="fw-bold mb-0">Добавление техники</h5>
                        </div>
                        <p class="text-muted mb-0">Добавьте технику с описанием, фото и ценой. Модерация занимает до 24 часов</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-circle bg-warning flex-shrink-0 me-3">
                                <i class="bi bi-cash-stack fs-3 text-dark"></i>
                            </div>
                            <h5 class="fw-bold mb-0">Получение дохода</h5>
                        </div>
                        <p class="text-muted mb-0">Получайте заказы, оформляйте документы и выводите средства на расчётный счёт</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
    5. CTA И ФОРМА ЗАЯВКИ
    ============================================================ --}}
<section class="page-section bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <h2 class="page-section-title fw-bold">Стать партнёром</h2>
                    <p class="page-section-subtitle mt-3">Заполните форму, и мы свяжемся с вами в ближайшее время</p>
                </div>

                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                <div class="contact-form-card bg-white rounded-4 shadow-sm p-4 p-lg-5">
                    <form method="POST" action="{{ route('cooperation.submit') }}" novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Ваше имя *</label>
                                <input type="text" name="name" class="form-control form-control-lg @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" required placeholder="Иван Петров">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Телефон *</label>
                                <input type="tel" name="phone" class="form-control form-control-lg @error('phone') is-invalid @enderror"
                                       value="{{ old('phone') }}" required placeholder="+7 (999) 123-45-67">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Компания</label>
                                <input type="text" name="company" class="form-control form-control-lg"
                                       value="{{ old('company') }}" placeholder="ООО «Ваша компания»">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Направление сотрудничества *</label>
                                <select name="direction" class="form-select form-select-lg @error('direction') is-invalid @enderror" required>
                                    <option value="">Выберите...</option>
                                    <option value="Поставка техники" @selected(old('direction') == 'Поставка техники')>Поставка техники</option>
                                    <option value="Сервисное обслуживание" @selected(old('direction') == 'Сервисное обслуживание')>Сервисное обслуживание</option>
                                    <option value="Логистические услуги" @selected(old('direction') == 'Логистические услуги')>Логистические услуги</option>
                                    <option value="Другое" @selected(old('direction') == 'Другое')>Другое</option>
                                </select>
                                @error('direction') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Регион деятельности</label>
                                <select name="region" class="form-select form-select-lg">
                                    <option value="">Не указан</option>
                                    <option value="Москва и МО" @selected(old('region') == 'Москва и МО')>Москва и МО</option>
                                    <option value="Санкт-Петербург и ЛО" @selected(old('region') == 'Санкт-Петербург и ЛО')>Санкт-Петербург и ЛО</option>
                                    <option value="Все регионы России" @selected(old('region') == 'Все регионы России')>Все регионы России</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input @error('agree') is-invalid @enderror"
                                           type="checkbox" name="agree" id="agree" value="1" @checked(old('agree'))>
                                    <label class="form-check-label" for="agree">
                                        Я согласен на обработку персональных данных
                                    </label>
                                    @error('agree') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                                <i class="bi bi-send me-2"></i> Отправить заявку
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Появление карточек при скролле
    const cards = document.querySelectorAll('.feature-card');
    if (cards.length) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        cards.forEach(el => {
            el.style.opacity = '0';
            observer.observe(el);
        });
    }
});
</script>
@endpush
