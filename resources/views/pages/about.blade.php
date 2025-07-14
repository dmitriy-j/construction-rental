@extends('layouts.app')

@section('content')
<div class="about-page">
    <!-- Герой-баннер -->
    <div class="hero-banner bg-primary text-white py-5">
        <div class="container py-5">
            <h1 class="display-4 fw-bold">О компании</h1>
            <p class="fs-5">RentTech — лидер в аренде спецтехники с 2010 года</p>
        </div>
    </div>

    <!-- История компании -->
    <div class="container my-5">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="mb-4">Наша история</h2>
                <p>Основанная в 2010 году, компания начинала с парка из 5 экскаваторов. Сегодня у нас более 200 единиц техники в 15 городах России.</p>
                <div class="timeline mt-4">
                    <div class="timeline-item">
                        <div class="timeline-year">2010</div>
                        <div class="timeline-content">Открытие первого офиса в Москве</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-year">2015</div>
                        <div class="timeline-content">Расширение парка до 50 машин</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-year">2023</div>
                        <div class="timeline-content">Запуск онлайн-бронирования</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="https://via.placeholder.com/600x400?text=Our+Team" class="img-fluid rounded-3 shadow" alt="История">
            </div>
        </div>
    </div>

    <!-- Миссия -->
    <div class="bg-light py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Наша миссия</h2>
                <p class="lead">Обеспечивать клиентов надежной техникой для любых задач</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-shield-check text-primary fs-1"></i>
                            <h5 class="mt-3">Надежность</h5>
                            <p>Вся техника проходит регулярное ТО</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-coin text-primary fs-1"></i>
                            <h5 class="mt-3">Выгодные цены</h5>
                            <p>Скидки при долгосрочной аренде</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-headset text-primary fs-1"></i>
                            <h5 class="mt-3">Поддержка 24/7</h5>
                            <p>Консультации по любым вопросам</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Команда -->
    <div class="container my-5">
        <h2 class="text-center mb-5">Наша команда</h2>
        <div class="row g-4">
            @php
                // Определяем фото для каждого сотрудника
                $teamPhotos = [
                    'Вячеслав Алёшин (Директор)' => asset('storage/team/slava.jpg'), // Путь к реальному фото
                    'Мария Иванова (Менеджер)' => 'https://via.placeholder.com/300x300?text=Photo',
                    'Дмитрий Сидоров (Технический отдел)' => 'https://via.placeholder.com/300x300?text=Photo'
                ];
            @endphp

            @foreach(['Вячеслав Алёшин (Директор)', 'Мария Иванова (Менеджер)', 'Дмитрий Сидоров (Технический отдел)'] as $member)
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <img
                        src="{{ $teamPhotos[$member] }}"
                        class="card-img-top object-fit-cover"
                        alt="{{ explode(' ', $member)[0] }} {{ explode(' ', $member)[1] }}"
                        style="height: 300px;"
                    >
                    <div class="card-body text-center">
                        <h5 class="card-title">{{ $member }}</h5>
                        <p class="text-muted">10+ лет опыта</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
