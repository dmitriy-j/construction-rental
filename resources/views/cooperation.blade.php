@extends('layouts.app')

@section('content')
<div class="cooperation-page">
    <!-- Герой-секция -->
    <section class="hero-section bg-primary text-white py-5">
        <div class="container text-center">
            <h1 class="display-5 fw-bold mb-4">Сотрудничество с RentTech</h1>
            <p class="lead">Зарабатывайте на своей технике с нами!</p>
        </div>
    </section>

    <!-- Условия -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <!-- Карточка 1 -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="bg-primary rounded-circle p-3 mb-3 mx-auto" style="width: 70px; height: 70px;">
                                <i class="bi bi-currency-dollar fs-3 text-white"></i>
                            </div>
                            <h3>Выгодные ставки</h3>
                            <p>До 85% от стоимости аренды в вашем кармане</p>
                        </div>
                    </div>
                </div>
                
                <!-- Карточка 2 -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="bg-success rounded-circle p-3 mb-3 mx-auto" style="width: 70px; height: 70px;">
                                <i class="bi bi-shield-check fs-3 text-white"></i>
                            </div>
                            <h3>Гарантии</h3>
                            <p>Полное страхование и юридическая защита</p>
                        </div>
                    </div>
                </div>
                
                <!-- Карточка 3 -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="bg-info rounded-circle p-3 mb-3 mx-auto" style="width: 70px; height: 70px;">
                                <i class="bi bi-graph-up fs-3 text-white"></i>
                            </div>
                            <h3>Аналитика</h3>
                            <p>Детальные отчеты по загрузке техники</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Форма заявки -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow">
                        <div class="card-body p-4">
                            <h2 class="text-center mb-4">Стать партнером</h2>
                            
                            <form>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Ваше имя*</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Телефон*</label>
                                        <input type="tel" class="form-control" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Тип техники*</label>
                                        <select class="form-select" required>
                                            <option value="">Выберите...</option>
                                            <option>Экскаваторы</option>
                                            <option>Бульдозеры</option>
                                            <option>Краны</option>
                                            <option>Грузовики</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Количество единиц</label>
                                        <input type="number" class="form-control" min="1">
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="agree">
                                            <label class="form-check-label" for="agree">
                                                Я согласен на обработку данных
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-3">
                                        <button type="submit" class="btn btn-primary w-100 py-2">
                                            Отправить заявку
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection