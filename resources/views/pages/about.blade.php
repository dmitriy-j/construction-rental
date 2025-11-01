@extends('layouts.app')

@section('content')
<div class="about-page">
    <!-- Герой-баннер -->
    <div class="hero-banner bg-primary text-white py-5">
        <div class="container py-5">
            <h1 class="display-4 fw-bold">Федеральная Арендная Платформа</h1>
            <p class="fs-5">Федеральный оператор аренды строительной техники с 2023 года</p>
        </div>
    </div>

    <!-- История компании -->
    <div class="container my-5">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="mb-4">Федеральный оператор</h2>
                <p>Федеральная Арендная Платформа создана для обеспечения бизнеса надежной строительной техникой по единым стандартам качества по всей территории России.</p>

                <div class="timeline mt-4">
                    <div class="timeline-item">
                        <div class="timeline-year">2023</div>
                        <div class="timeline-content">Запуск федерального оператора аренды</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-year">2024</div>
                        <div class="timeline-content">Покрытие 85 регионов России</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-year">2024</div>
                        <div class="timeline-content">Внедрение единых стандартов качества</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="https://via.placeholder.com/600x400?text=Federal+Coverage" class="img-fluid rounded-3 shadow" alt="Федеральное покрытие">
            </div>
        </div>
    </div>

    <!-- Миссия -->
    <div class="bg-light py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Наша миссия</h2>
                <p class="lead">Обеспечивать бизнес надежной строительной техникой по единым федеральным стандартам по всей России</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-shield-check text-primary fs-1"></i>
                            <h5 class="mt-3">Федеральные стандарты</h5>
                            <p>Единое качество обслуживания во всех регионах</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-geo-alt text-primary fs-1"></i>
                            <h5 class="mt-3">Вся Россия</h5>
                            <p>Работаем в 85 регионах страны</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-headset text-primary fs-1"></i>
                            <h5 class="mt-3">Поддержка 24/7</h5>
                            <p>Круглосуточная поддержка для бизнеса</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
