@extends('layouts.app')

@section('title', 'О компании — Федеральная Арендная Платформа')
@section('meta-description', 'Федеральная Арендная Платформа — федеральный оператор аренды строительной техники с 2023 года')

@section('content')
{{-- ============================================================
    1. HERO-БЛОК
    ============================================================ --}}
<section class="page-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold text-white mb-3 animate__animated animate__fadeInUp">
                    О компании
                </h1>
                <p class="lead text-white-50 mb-0 animate__animated animate__fadeInUp animate__delay-1s">
                    Федеральная Арендная Платформа — ваш надёжный партнёр в аренде строительной
                    техники по всей России. Работаем с 2023 года.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
    2. МИССИЯ И ЦЕННОСТИ
    ============================================================ --}}
<section class="page-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="page-section-title fw-bold">Наша миссия</h2>
            <p class="page-section-subtitle mt-3">
                Обеспечивать бизнес надёжной строительной техникой по единым федеральным
                стандартам качества по всей России
            </p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-shield-check text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Федеральные стандарты</h4>
                    <p class="text-muted mb-0">Единое качество обслуживания во всех 85 регионах страны</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-people text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Прозрачность</h4>
                    <p class="text-muted mb-0">Честные цены, юридическая защита и безопасные сделки для обеих сторон</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-headset text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Поддержка 24/7</h4>
                    <p class="text-muted mb-0">Круглосуточная поддержка клиентов. Поможем в любой ситуации</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
    3. ИСТОРИЯ КОМПАНИИ (ТАЙМЛАЙН)
    ============================================================ --}}
<section class="page-section bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h2 class="page-section-title fw-bold mb-4">История компании</h2>
                <p class="text-muted mb-4">
                    Федеральная Арендная Платформа создана для обеспечения бизнеса надёжной
                    строительной техникой по единым стандартам качества по всей территории России.
                </p>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-year">2023</div>
                        <div class="timeline-content">Запуск федерального оператора аренды строительной техники</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-year">2024</div>
                        <div class="timeline-content">Покрытие 85 регионов России, запуск цифровой платформы</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-year">2025</div>
                        <div class="timeline-content">Внедрение единых стандартов качества, запуск B2B-решений</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-year">2026</div>
                        <div class="timeline-content">1000+ единиц техники, расширение сети партнёров</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-building text-primary" style="font-size: 5rem;"></i>
                        <h4 class="fw-bold mt-3">Федеральный охват</h4>
                        <p class="text-muted mb-0">Работаем во всех федеральных округах России</p>
                        <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                            <span class="badge bg-primary p-2 px-3">Центральный ФО</span>
                            <span class="badge bg-primary p-2 px-3">Северо-Западный ФО</span>
                            <span class="badge bg-primary p-2 px-3">Южный ФО</span>
                            <span class="badge bg-primary p-2 px-3">Приволжский ФО</span>
                            <span class="badge bg-primary p-2 px-3">Уральский ФО</span>
                            <span class="badge bg-primary p-2 px-3">Сибирский ФО</span>
                            <span class="badge bg-primary p-2 px-3">Дальневосточный ФО</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
    4. ЦИФРЫ И ФАКТЫ (СТАТИСТИКА)
    ============================================================ --}}
<section class="page-section cta-section text-white">
    <div class="container position-relative z-1">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-2">ФАП в цифрах</h2>
            <p class="text-white-50">Результаты нашей работы</p>
        </div>
        <div class="row g-4 text-center">
            <div class="col-6 col-lg-3">
                <div class="stat-item">
                    <div class="fs-1 fw-bold text-warning">85+</div>
                    <div class="text-white-50 fs-5">Регионов</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-item">
                    <div class="fs-1 fw-bold text-warning">1000+</div>
                    <div class="text-white-50 fs-5">Ед. техники</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-item">
                    <div class="fs-1 fw-bold text-warning">500+</div>
                    <div class="text-white-50 fs-5">Партнёров</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-item">
                    <div class="fs-1 fw-bold text-warning">24/7</div>
                    <div class="text-white-50 fs-5">Поддержка</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
    5. КОМАНДА
    ============================================================ --}}
<section class="page-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="page-section-title fw-bold">Наша команда</h2>
            <p class="page-section-subtitle mt-3">Профессионалы своего дела</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-6 col-lg-3">
                <div class="team-card card border-0 shadow-sm text-center p-4 h-100">
                    <div class="d-flex justify-content-center mb-3">
                        <div class="team-img rounded-circle d-flex align-items-center justify-content-center bg-primary text-white fw-bold" style="width: 120px; height: 120px; font-size: 2.5rem;">
                            Д
                        </div>
                    </div>
                    <h5 class="fw-bold mb-1">Команда ФАП</h5>
                    <p class="text-muted small mb-0">Руководство</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="team-card card border-0 shadow-sm text-center p-4 h-100">
                    <div class="d-flex justify-content-center mb-3">
                        <div class="team-img rounded-circle d-flex align-items-center justify-content-center bg-success text-white fw-bold" style="width: 120px; height: 120px; font-size: 2.5rem;">
                            О
                        </div>
                    </div>
                    <h5 class="fw-bold mb-1">Отдел развития</h5>
                    <p class="text-muted small mb-0">Развитие платформы</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="team-card card border-0 shadow-sm text-center p-4 h-100">
                    <div class="d-flex justify-content-center mb-3">
                        <div class="team-img rounded-circle d-flex align-items-center justify-content-center bg-warning text-dark fw-bold" style="width: 120px; height: 120px; font-size: 2.5rem;">
                            Т
                        </div>
                    </div>
                    <h5 class="fw-bold mb-1">Технический отдел</h5>
                    <p class="text-muted small mb-0">Поддержка и разработка</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="team-card card border-0 shadow-sm text-center p-4 h-100">
                    <div class="d-flex justify-content-center mb-3">
                        <div class="team-img rounded-circle d-flex align-items-center justify-content-center bg-info text-white fw-bold" style="width: 120px; height: 120px; font-size: 2.5rem;">
                            П
                        </div>
                    </div>
                    <h5 class="fw-bold mb-1">Служба поддержки</h5>
                    <p class="text-muted small mb-0">Поддержка клиентов 24/7</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
    6. CTA
    ============================================================ --}}
<section class="page-section bg-light">
    <div class="container">
        <div class="text-center">
            <h2 class="page-section-title fw-bold">Готовы начать?</h2>
            <p class="page-section-subtitle mt-3 mb-4">
                Присоединяйтесь к тысячам клиентов, которые уже пользуются Федеральной Арендной Платформой
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="{{ route('catalog.index') }}" class="btn btn-primary btn-lg px-5 py-3 fw-bold">
                    <i class="bi bi-search me-2"></i> Перейти в каталог
                </a>
                <a href="{{ route('contacts') }}" class="btn btn-outline-primary btn-lg px-5 py-3 fw-bold">
                    <i class="bi bi-envelope me-2"></i> Связаться с нами
                </a>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Анимация цифр статистики
    const statItems = document.querySelectorAll('.stat-item .fs-1');
    if (statItems.length) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        statItems.forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });
    }

    // Плавный скролл к якорям
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

    // Появление карточек при скролле
    const cards = document.querySelectorAll('.feature-card, .team-card');
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
