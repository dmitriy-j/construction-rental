@section('meta')
<title>Партнерство с Федеральной Арендной Платформой | Сотрудничество ФАП</title>
<meta name="description" content="Станьте партнером федерального оператора аренды строительной техники. Выгодные условия сотрудничества, работа по всей России.">
@endsection

@extends('layouts.app')

@section('content')
<div class="cooperation-page">
    <!-- Герой-секция -->
    <section class="hero-federal text-white py-5">
        <div class="container text-center">
            <h1 class="display-5 fw-bold mb-4">Партнерство с Федеральной Арендной Платформой</h1>
            <p class="lead">Сотрудничайте с федеральным оператором аренды строительной техники</p>
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
                            <h3>Выгодные условия</h3>
                            <p>Привлекательные финансовые условия для партнеров федерального оператора</p>
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
                            <h3>Надежность</h3>
                            <p>Работа с федеральным оператором - гарантия стабильности и выполнения обязательств</p>
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
                            <h3>Федеральный охват</h3>
                            <p>Доступ к клиентам по всей России через федеральную платформу</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Форма заявки -->
    <!-- Форма заявки -->
<section class="bg-light py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Стать партнером ФАП</h2>

                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('cooperation.submit') }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Ваше имя*</label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Телефон*</label>
                                    <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required>
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Компания</label>
                                    <input type="text" name="company" class="form-control" value="{{ old('company') }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Направление сотрудничества*</label>
                                    <select name="direction" class="form-select @error('direction') is-invalid @enderror" required>
                                        <option value="">Выберите...</option>
                                        <option value="Поставка техники" @selected(old('direction') == 'Поставка техники')>Поставка техники</option>
                                        <option value="Сервисное обслуживание" @selected(old('direction') == 'Сервисное обслуживание')>Сервисное обслуживание</option>
                                        <option value="Логистические услуги" @selected(old('direction') == 'Логистические услуги')>Логистические услуги</option>
                                        <option value="Другое" @selected(old('direction') == 'Другое')>Другое</option>
                                    </select>
                                    @error('direction')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Регион деятельности</label>
                                    <select name="region" class="form-select">
                                        <option value="">Не указан</option>
                                        <option value="Москва и МО" @selected(old('region') == 'Москва и МО')>Москва и МО</option>
                                        <option value="Санкт-Петербург и ЛО" @selected(old('region') == 'Санкт-Петербург и ЛО')>Санкт-Петербург и ЛО</option>
                                        <option value="Центральный федеральный округ" @selected(old('region') == 'Центральный федеральный округ')>Центральный федеральный округ</option>
                                        <option value="Северо-Западный федеральный округ" @selected(old('region') == 'Северо-Западный федеральный округ')>Северо-Западный федеральный округ</option>
                                        <option value="Все регионы России" @selected(old('region') == 'Все регионы России')>Все регионы России</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input @error('agree') is-invalid @enderror" type="checkbox" name="agree" id="agree" value="1" @checked(old('agree'))>
                                        <label class="form-check-label" for="agree">
                                            Я согласен на обработку персональных данных
                                        </label>
                                        @error('agree')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-12 mt-3">
                                    <button type="submit" class="btn btn-primary w-100 py-2">
                                        Отправить заявку на партнерство
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
