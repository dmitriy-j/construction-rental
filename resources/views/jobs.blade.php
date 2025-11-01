@section('meta')
<title>Карьера в Федеральной Арендной Платформе | Вакансии ФАП</title>
<meta name="description" content="Присоединяйтесь к команде федерального оператора аренды строительной техники. Карьерный рост, конкурентная зарплата, работа по всей России.">
@endsection

@extends('layouts.app')

@section('content')
<div class="jobs-page">
    <!-- Герой-секция -->
    <section class="hero-federal text-white py-5 position-relative">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-5 fw-bold mb-4">Карьера в Федеральной Арендной Платформе</h1>
                    <p class="lead mb-4">Станьте частью команды федерального оператора аренды строительной техники. Мы строим будущее отрасли по всей России!</p>
                    <div class="d-flex gap-3">
                        <a href="#contact-block" class="btn btn-warning btn-lg px-4">Откликнуться</a>
                        <a href="#benefits" class="btn btn-outline-light btn-lg px-4">Преимущества</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1521791136064-7986c2920216?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80"
                         class="img-fluid rounded-3 shadow"
                         alt="Команда ФАП">
                </div>
            </div>
        </div>
    </section>

    <!-- Преимущества -->
    <section id="benefits" class="py-5 bg-light">
        <div class="container py-4">
            <h2 class="text-center mb-5">Почему стоит работать в ФАП?</h2>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 text-center">
                            <div class="icon-circle bg-primary mb-3 mx-auto">
                                <i class="bi bi-cash-stack fs-2 text-white"></i>
                            </div>
                            <h3>Конкурентная зарплата</h3>
                            <p>Достойная оплата труда + бонусы за результаты работы в федеральной компании</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 text-center">
                            <div class="icon-circle bg-success mb-3 mx-auto">
                                <i class="bi bi-geo-alt fs-2 text-white"></i>
                            </div>
                            <h3>Федеральный масштаб</h3>
                            <p>Работайте в компании с филиалами по всей России - 85 регионов</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 text-center">
                            <div class="icon-circle bg-warning mb-3 mx-auto">
                                <i class="bi bi-graph-up fs-2 text-white"></i>
                            </div>
                            <h3>Карьерный рост</h3>
                            <p>Возможности роста в федеральной компании с перспективой работы в разных регионах</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Контактный блок -->
    <section id="contact-block" class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow">
                        <div class="card-body p-5 text-center">
                            <h2 class="mb-4">Присоединяйтесь к нашей команде!</h2>
                            <p class="lead mb-4">Федеральная Арендная Платформа ищет талантливых специалистов для работы в динамично развивающейся компании федерального масштаба.</p>

                            <div class="d-flex flex-column align-items-center">
                                <a href="mailto:career@fap24.ru" class="btn btn-primary btn-lg mb-3 px-4">
                                    <i class="bi bi-envelope me-2"></i> career@fap24.ru
                                </a>

                                <p class="text-muted mt-3">Или звоните: <a href="tel:+78001234567" class="text-decoration-none">8 (800) 123-45-67</a></p>

                                <div class="mt-4">
                                    <span class="federal-badge badge p-2 me-2">Москва</span>
                                    <span class="federal-badge badge p-2 me-2">Санкт-Петербург</span>
                                    <span class="federal-badge badge p-2">Регионы России</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
