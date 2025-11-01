@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Герой-секция Федерального оператора -->
    <div class="hero-section bg-primary text-white rounded-3 p-5 mb-5 position-relative overflow-hidden">
        <div class="position-absolute top-0 end-0 w-50 h-100 opacity-10">
            <div class="w-100 h-100" style="background: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><text x=\"20\" y=\"50\" font-size=\"40\" fill=\"white\">ФАП</text></svg>') no-repeat center center;"></div>
        </div>
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">Федеральная Арендная Платформа</h1>
                <p class="lead mb-4">Ваш надежный партнер в аренде строительной техники по всей России</p>
                <div class="d-flex flex-wrap gap-3">
                    <span class="badge bg-light text-primary fs-6 p-2">85 регионов России</span>
                    <span class="badge bg-light text-primary fs-6 p-2">Единые стандарты качества</span>
                    <span class="badge bg-light text-primary fs-6 p-2">Федеральный оператор</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Карточки для бизнеса и партнеров -->
    <div class="row mt-5">
        <div class="col-md-6 mb-4">
            <div class="card h-100 border-primary">
                <div class="card-body text-center p-4">
                    <i class="bi bi-building text-primary" style="font-size: 3rem;"></i>
                    <h3 class="my-3">Для бизнеса</h3>
                    <p class="text-muted">
                        Аренда строительной техники для ваших проектов по всей России
                    </p>
                    <a href="#" class="btn btn-primary btn-lg">
                        <i class="bi bi-search me-2"></i> Найти технику
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card h-100 border-warning">
                <div class="card-body text-center p-4">
                    <i class="bi bi-handshake text-warning" style="font-size: 3rem;"></i>
                    <h3 class="my-3">Партнерам</h3>
                    <p class="text-muted">
                        Сотрудничество с федеральным оператором аренды
                    </p>
                    <a href="{{ route('cooperation') }}" class="btn btn-warning btn-lg">
                        <i class="bi bi-plus-circle me-2"></i> Стать партнером
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- География покрытия -->
    <div class="card shadow-sm mt-5">
        <div class="card-body">
            <h3 class="text-center mb-4">Федеральное покрытие</h3>
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="border rounded p-3">
                        <h4 class="text-primary">85</h4>
                        <p class="mb-0">регионов</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3">
                        <h4 class="text-primary">1000+</h4>
                        <p class="mb-0">городов</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3">
                        <h4 class="text-primary">24/7</h4>
                        <p class="mb-0">поддержка</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3">
                        <h4 class="text-primary">Единые</h4>
                        <p class="mb-0">стандарты</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
