@extends('layouts.app')

@section('title', 'Вакансии — Федеральная Арендная Платформа')
@section('meta-description', 'Присоединяйтесь к команде Федеральной Арендной Платформы. Открытые вакансии, карьерный рост, конкурентная зарплата.')

@section('content')
{{-- ============================================================
    1. HERO-БЛОК
    ============================================================ --}}
<section class="page-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold text-white mb-3 animate__animated animate__fadeInUp">
                    Вакансии
                </h1>
                <p class="lead text-white-50 mb-0 animate__animated animate__fadeInUp animate__delay-1s">
                    Присоединяйтесь к команде федерального оператора аренды строительной техники.
                    Мы строим будущее отрасли по всей России!
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
    2. ПРЕИМУЩЕСТВА РАБОТЫ
    ============================================================ --}}
<section class="page-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="page-section-title fw-bold">Почему стоит работать в ФАП?</h2>
            <p class="page-section-subtitle mt-3">Мы создаём лучшие условия для наших сотрудников</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-cash-stack text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Конкурентная зарплата</h4>
                    <p class="text-muted mb-0">Достойная оплата труда + бонусы за результаты работы в федеральной компании</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-geo-alt text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Федеральный масштаб</h4>
                    <p class="text-muted mb-0">Работайте в компании с партнёрами во всех 85 регионах России</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-graph-up text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Карьерный рост</h4>
                    <p class="text-muted mb-0">Возможности профессионального и карьерного развития в динамичной компании</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-people text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Дружный коллектив</h4>
                    <p class="text-muted mb-0">Команда профессионалов, поддерживающая атмосфера и общие цели</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-laptop text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Современные технологии</h4>
                    <p class="text-muted mb-0">Работа с передовыми IT-решениями и цифровыми инструментами</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 p-4 text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-clock text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Гибкий график</h4>
                    <p class="text-muted mb-0">Возможность удалённой работы и гибкого графика для отдельных позиций</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
    3. ОТКРЫТЫЕ ВАКАНСИИ
    ============================================================ --}}
<section class="page-section bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="page-section-title fw-bold">Открытые вакансии</h2>
            <p class="page-section-subtitle mt-3">Актуальные предложения о работе</p>
        </div>

        {{-- Пока нет вакансий в БД — показываем заглушку --}}
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-people text-primary" style="font-size: 4rem;"></i>
                        <h3 class="fw-bold mt-4 mb-3">Нет открытых вакансий</h3>
                        <p class="text-muted mb-4">
                            В данный момент у нас нет открытых вакансий, но мы всегда рады
                            талантливым специалистам. Отправьте своё резюме, и мы рассмотрим
                            его при появлении подходящей позиции.
                        </p>
                        <a href="mailto:career@fap24.ru" class="btn btn-primary btn-lg px-5 py-3 fw-bold">
                            <i class="bi bi-envelope me-2"></i> Отправить резюме
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
    4. CTA
    ============================================================ --}}
<section class="page-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="page-section-title fw-bold">Не нашли подходящую вакансию?</h2>
                <p class="text-muted mt-3 mb-4">
                    Мы всегда открыты для новых талантов. Отправьте нам своё резюме,
                    и мы обязательно рассмотрим его при появлении подходящей позиции.
                </p>
                <div class="d-flex flex-column align-items-center">
                    <a href="mailto:career@fap24.ru" class="btn btn-outline-primary btn-lg px-5 py-3 fw-bold mb-3">
                        <i class="bi bi-envelope me-2"></i> career@fap24.ru
                    </a>
                    <p class="text-muted">
                        Или звоните: <a href="tel:+78001234567" class="text-decoration-none fw-bold">8 (800) 123-45-67</a>
                    </p>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge bg-primary p-2 px-3">Москва</span>
                        <span class="badge bg-primary p-2 px-3">Санкт-Петербург</span>
                        <span class="badge bg-primary p-2 px-3">Регионы России</span>
                        <span class="badge bg-primary p-2 px-3">Удалённо</span>
                    </div>
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
