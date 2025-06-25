@extends('layouts.app')

@section('content')
<div class="jobs-page">
    <!-- Герой-секция -->
    <section class="hero-section bg-dark text-white py-5 position-relative">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-5 fw-bold mb-4">Стань частью команды RentTech!</h1>
                    <p class="lead mb-4">Мы строим будущее аренды строительной техники. Если ты хочешь работать в динамичной компании с крутыми проектами — ты наш человек!</p>
                    <div class="d-flex gap-3">
                        <a href="#contact-block" class="btn btn-primary btn-lg px-4">Откликнуться</a>
                        <a href="#benefits" class="btn btn-outline-light btn-lg px-4">Преимущества</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1521791136064-7986c2920216?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80" 
                         class="img-fluid rounded-3 shadow" 
                         alt="Рабочая команда">
                </div>
            </div>
        </div>
        <div class="custom-shape-divider-bottom">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M1200 0L0 0 598.97 114.72 1200 0z" class="shape-fill"></path>
            </svg>
        </div>
    </section>

    <!-- Преимущества -->
    <section id="benefits" class="py-5 bg-light">
        <div class="container py-4">
            <h2 class="text-center mb-5">Почему стоит работать у нас?</h2>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 text-center">
                            <div class="icon-circle bg-primary mb-3 mx-auto">
                                <i class="bi bi-cash-stack fs-2 text-white"></i>
                            </div>
                            <h3>Конкурентная зарплата</h3>
                            <p>Достойная оплата труда + бонусы за результаты</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 text-center">
                            <div class="icon-circle bg-success mb-3 mx-auto">
                                <i class="bi bi-people fs-2 text-white"></i>
                            </div>
                            <h3>Крутой коллектив</h3>
                            <p>Работай с профессионалами в дружеской атмосфере</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 text-center">
                            <div class="icon-circle bg-warning mb-3 mx-auto">
                                <i class="bi bi-lightning-charge fs-2 text-white"></i>
                            </div>
                            <h3>Развитие</h3>
                            <p>Обучение за счет компании и карьерный рост</p>
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
                            <h2 class="mb-4">Отправь нам свое резюме!</h2>
                            <p class="lead mb-4">Мы всегда рады талантливым людям. Даже если сейчас нет подходящей вакансии — оставь свои данные, и мы свяжемся с тобой!</p>
                            
                            <div class="d-flex flex-column align-items-center">
                                <a href="mailto:career@renttech.ru" class="btn btn-primary btn-lg mb-3 px-4">
                                    <i class="bi bi-envelope me-2"></i> career@renttech.ru
                                </a>
                                
                                <p class="text-muted mt-3">Или звони: <a href="tel:+78001234567" class="text-decoration-none">8 (800) 123-45-67</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection