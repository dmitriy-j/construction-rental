@extends('layouts.app')

@section('title', 'Контакты - ConstructionRental')
@section('page-title', 'Контакты')
@section('background-text', 'Свяжитесь с нами')

@section('content')
<div class="container">
    @if(!$platform || !$platform->exists)
        <div class="alert alert-warning">
            <h5>Информация о компании временно недоступна</h5>
            <p class="mb-0">Пожалуйста, свяжитесь с нами по телефону или email для получения актуальной информации.</p>
        </div>
    @endif

    <div class="row">
        <!-- Основная контактная информация -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-building me-2"></i>Реквизиты компании
                    </h5>
                </div>
                <div class="card-body">
                    @if($platform && $platform->exists)
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Основная информация</h6>
                            @if($platform->legal_name)
                            <div class="mb-3">
                                <strong>Название компании:</strong>
                                <div class="text-muted">{{ $platform->legal_name }}</div>
                            </div>
                            @endif

                            @if($platform->short_name)
                            <div class="mb-3">
                                <strong>Краткое название:</strong>
                                <div class="text-muted">{{ $platform->short_name }}</div>
                            </div>
                            @endif

                            @if($platform->inn)
                            <div class="mb-3">
                                <strong>ИНН:</strong>
                                <div class="text-muted">{{ $platform->inn }}</div>
                            </div>
                            @endif

                            @if($platform->kpp)
                            <div class="mb-3">
                                <strong>КПП:</strong>
                                <div class="text-muted">{{ $platform->kpp }}</div>
                            </div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Регистрационные данные</h6>
                            @if($platform->ogrn)
                            <div class="mb-3">
                                <strong>ОГРН:</strong>
                                <div class="text-muted">{{ $platform->ogrn }}</div>
                            </div>
                            @endif

                            @if($platform->okpo)
                            <div class="mb-3">
                                <strong>ОКПО:</strong>
                                <div class="text-muted">{{ $platform->okpo }}</div>
                            </div>
                            @endif

                            @if($platform->certificate_number)
                            <div class="mb-3">
                                <strong>Свидетельство:</strong>
                                <div class="text-muted">{{ $platform->certificate_number }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="bi bi-building fs-1 text-muted mb-3"></i>
                        <p class="text-muted">Информация о компании загружается...</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Адреса -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-geo-alt me-2"></i>Адреса
                    </h5>
                </div>
                <div class="card-body">
                    @if($platform && $platform->exists)
                    <div class="row">
                        @if($platform->legal_address)
                        <div class="col-md-6 mb-3">
                            <strong>Юридический адрес:</strong>
                            <div class="text-muted">{{ $platform->legal_address }}</div>
                        </div>
                        @endif

                        @if($platform->physical_address)
                        <div class="col-md-6 mb-3">
                            <strong>Фактический адрес:</strong>
                            <div class="text-muted">{{ $platform->physical_address }}</div>
                        </div>
                        @endif

                        @if($platform->post_address)
                        <div class="col-md-6 mb-3">
                            <strong>Почтовый адрес:</strong>
                            <div class="text-muted">{{ $platform->post_address }}</div>
                        </div>
                        @endif
                    </div>
                    @else
                    <div class="text-center py-3">
                        <p class="text-muted mb-0">Адресная информация временно недоступна</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Боковая панель с контактами -->
        <div class="col-lg-4">
            <!-- Контактная информация -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-telephone me-2"></i>Контакты
                    </h5>
                </div>
                <div class="card-body">
                    @if($platform && $platform->exists)
                        @if($platform->phone)
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-telephone-fill text-primary me-3 fs-5"></i>
                            <div>
                                <strong>Телефон</strong>
                                <div class="text-muted">{{ $platform->phone }}</div>
                            </div>
                        </div>
                        @endif

                        @if($platform->email)
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-envelope-fill text-primary me-3 fs-5"></i>
                            <div>
                                <strong>Email</strong>
                                <div class="text-muted">
                                    <a href="mailto:{{ $platform->email }}" class="text-decoration-none">
                                        {{ $platform->email }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($platform->website)
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-globe text-primary me-3 fs-5"></i>
                            <div>
                                <strong>Сайт</strong>
                                <div class="text-muted">
                                    <a href="{{ $platform->website }}" target="_blank" class="text-decoration-none">
                                        {{ $platform->website }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Дополнительные телефоны -->
                        @if($platform->additional_phones && count($platform->additional_phones) > 0)
                        <div class="mb-3">
                            <strong>Дополнительные телефоны:</strong>
                            @foreach($platform->additional_phones as $phone)
                            <div class="text-muted small">{{ $phone }}</div>
                            @endforeach
                        </div>
                        @endif
                    @else
                    <div class="text-center py-3">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-telephone-fill text-primary me-3 fs-5"></i>
                            <div>
                                <strong>Телефон</strong>
                                <div class="text-muted">+7 (999) 123-45-67</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-envelope-fill text-primary me-3 fs-5"></i>
                            <div>
                                <strong>Email</strong>
                                <div class="text-muted">
                                    <a href="mailto:info@constructionrental.ru" class="text-decoration-none">
                                        info@constructionrental.ru
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Руководство -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person-badge me-2"></i>Руководство
                    </h5>
                </div>
                <div class="card-body">
                    @if($platform && $platform->exists)
                        @if($platform->ceo_name)
                        <div class="mb-3">
                            <strong>Генеральный директор:</strong>
                            <div class="text-muted">{{ $platform->ceo_name }}</div>
                            @if($platform->ceo_position)
                            <small class="text-muted">({{ $platform->ceo_position }})</small>
                            @endif
                        </div>
                        @endif

                        @if($platform->accountant_name)
                        <div class="mb-3">
                            <strong>Главный бухгалтер:</strong>
                            <div class="text-muted">{{ $platform->accountant_name }}</div>
                            @if($platform->accountant_position)
                            <small class="text-muted">({{ $platform->accountant_position }})</small>
                            @endif
                        </div>
                        @endif
                    @else
                    <div class="text-center py-3">
                        <p class="text-muted mb-0">Информация о руководстве временно недоступна</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Банковские реквизиты -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-bank me-2"></i>Банковские реквизиты
                    </h5>
                </div>
                <div class="card-body">
                    @if($platform && $platform->exists)
                        @if($platform->bank_name)
                        <div class="mb-2">
                            <strong>Банк:</strong>
                            <div class="text-muted small">{{ $platform->bank_name }}</div>
                        </div>
                        @endif

                        @if($platform->bic)
                        <div class="mb-2">
                            <strong>БИК:</strong>
                            <div class="text-muted small">{{ $platform->bic }}</div>
                        </div>
                        @endif

                        @if($platform->settlement_account)
                        <div class="mb-2">
                            <strong>Расчетный счет:</strong>
                            <div class="text-muted small">{{ $platform->settlement_account }}</div>
                        </div>
                        @endif

                        @if($platform->correspondent_account)
                        <div class="mb-2">
                            <strong>Корреспондентский счет:</strong>
                            <div class="text-muted small">{{ $platform->correspondent_account }}</div>
                        </div>
                        @endif
                    @else
                    <div class="text-center py-3">
                        <p class="text-muted mb-0">Банковские реквизиты временно недоступны</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Карта (опционально) -->
    @if($platform && $platform->exists && $platform->physical_address)
    <div class="card mt-4">
        <div class="card-header bg-dark text-white">
            <h5 class="card-title mb-0">
                <i class="bi bi-map me-2"></i>Мы на карте
            </h5>
        </div>
        <div class="card-body">
            <div class="bg-light rounded p-4 text-center">
                <i class="bi bi-map fs-1 text-muted mb-3"></i>
                <p class="text-muted mb-0">
                    Карта будет отображена здесь<br>
                    <small>Адрес: {{ $platform->physical_address }}</small>
                </p>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: box-shadow 0.15s ease-in-out;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.text-muted {
    color: #6c757d !important;
}
</style>
@endsection
