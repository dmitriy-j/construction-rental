@extends('layouts.app')

@section('title', 'Создание заявки на аренду')

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title">Создание заявки на аренду</h1>
                <div>
                    <a href="{{ route('lessee.rental-requests.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Назад к списку
                    </a>
                    <button type="button" class="btn btn-info" data-bs-toggle="collapse" data-bs-target="#helpGuide">
                        <i class="fas fa-question-circle me-2"></i>Шпаргалка
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Шпаргалка для арендатора -->
    <div class="collapse mb-4" id="helpGuide">
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-lightbulb me-2"></i>Шпаргалка по заполнению заявки
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="help-item mb-3">
                            <h6 class="text-primary">📝 Название и описание</h6>
                            <ul class="small">
                                <li><strong>Будьте конкретны:</strong> "Аренда экскаватора для котлована" вместо "Нужна техника"</li>
                                <li><strong>Укажите тип работ:</strong> земляные работы, демонтаж, погрузка и т.д.</li>
                                <li><strong>Описание проекта:</strong> площадь участка, объем работ, особые условия</li>
                            </ul>
                        </div>

                        <div class="help-item mb-3">
                            <h6 class="text-primary">📅 Период аренды</h6>
                            <ul class="small">
                                <li><strong>Запас по времени:</strong> добавляйте 1-2 дня на непредвиденные обстоятельства</li>
                                <li><strong>Учитывайте доставку:</strong> если техника нужна с конкретного числа, укажите дату начала на день раньше</li>
                                <li><strong>Сезонность:</strong> в высокий сезон бронируйте технику заранее</li>
                            </ul>
                        </div>

                        <div class="help-item mb-3">
                            <h6 class="text-primary">📍 Локация</h6>
                            <ul class="small">
                                <li><strong>Точный адрес:</strong> необходим для расчета стоимости доставки</li>
                                <li><strong>Особенности подъезда:</strong> отметьте если есть ограничения по габаритам</li>
                                <li><strong>Несколько площадок?</strong> Создайте отдельные заявки для разных адресов</li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="help-item mb-3">
                            <h6 class="text-primary">💰 Стоимость часа</h6>
                            <ul class="small">
                                <li><strong>Рыночные цены:</strong> экскаватор - 1,500-2,500 ₽/час, бульдозер - 2,000-3,000 ₽/час</li>
                                <li><strong>Не уверены?</strong> Оставьте поле пустым - арендодатели предложат свои цены</li>
                                <li><strong>Оптовые скидки:</strong> при аренде нескольких единиц техники можно указать желаемую скидку</li>
                            </ul>
                        </div>

                        <div class="help-item mb-3">
                            <h6 class="text-primary">🛠 Позиции оборудования</h6>
                            <ul class="small">
                                <li><strong>Добавляйте по одной:</strong> каждая позиция - отдельный тип техники</li>
                                <li><strong>Количество:</strong> укажите точное число необходимых единиц</li>
                                <li><strong>Индивидуальные условия:</strong> используйте для техники со специальными требованиями</li>
                            </ul>
                        </div>

                        <div class="help-item mb-3">
                            <h6 class="text-primary">⚙️ Условия аренды</h6>
                            <ul class="small">
                                <li><strong>Сменный график:</strong> 1 смена = 8 часов, 2 смены = 16 часов в сутки</li>
                                <li><strong>Оператор:</strong> включите если нужен специалист для работы с техникой</li>
                                <li><strong>ГСМ:</strong> "включено" - проще, "отдельно" - может быть выгоднее при большом пробеге</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning mt-3">
                    <small>
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Совет:</strong> Чем подробнее заявка, тем быстрее вы получите подходящие предложения от арендодателей.
                        Рекомендуем заполнять все поля максимально точно.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Быстрые подсказки (появляются при наведении) -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-light text-dark" data-bs-toggle="tooltip" title="Указывайте реальные сроки работ с запасом 10-15%">
                    <i class="fas fa-clock me-1"></i>Сроки
                </span>
                <span class="badge bg-light text-dark" data-bs-toggle="tooltip" title="Точный адрес помогает арендодателям рассчитать доставку">
                    <i class="fas fa-map-marker-alt me-1"></i>Адрес
                </span>
                <span class="badge bg-light text-dark" data-bs-toggle="tooltip" title="Можно указать ориентировочную стоимость для привлечения предложений">
                    <i class="fas fa-ruble-sign me-1"></i>Бюджет
                </span>
                <span class="badge bg-light text-dark" data-bs-toggle="tooltip" title="Добавляйте отдельные позиции для каждого типа техники">
                    <i class="fas fa-truck me-1"></i>Позиции
                </span>
            </div>
        </div>
    </div>

    <!-- Vue приложение для создания заявки -->
    <div id="rental-request-app"
         data-categories='@json($categories)'
         data-locations='@json($locations)'
         data-store-url="{{ route('lessee.rental-requests.store') }}"
         data-csrf-token="{{ csrf_token() }}">
    </div>
</div>
@endsection

@push('styles')
<style>
    .rental-request-app {
        min-height: 80vh;
    }

    .item-card {
        transition: all 0.3s ease;
        border-left: 4px solid #0d6efd;
    }

    .item-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .individual-conditions {
        border-left: 3px solid #20c997;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    .help-item {
        padding: 10px;
        border-left: 3px solid #0dcaf0;
        background-color: #f8f9fa;
        border-radius: 5px;
    }

    .help-item h6 {
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .help-item ul {
        margin-bottom: 0;
    }

    .help-item li {
        margin-bottom: 0.25rem;
    }

    @media (max-width: 768px) {
        .item-card .card-body .row {
            flex-direction: column;
        }

        .item-card .col-md-4,
        .item-card .col-md-3,
        .item-card .col-md-2,
        .item-card .col-md-1 {
            width: 100%;
            margin-bottom: 1rem;
        }
    }
</style>
@endpush

@push('scripts')
<!-- Подключаем через Vite -->
@vite(['resources/js/pages/rental-request-create.js'])

<!-- Инициализация подсказок -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Сохранение состояния шпаргалки в localStorage
    const helpGuide = document.getElementById('helpGuide');
    const helpState = localStorage.getItem('helpGuideExpanded');

    if (helpState === 'true') {
        new bootstrap.Collapse(helpGuide, { show: true });
    }

    helpGuide.addEventListener('show.bs.collapse', function () {
        localStorage.setItem('helpGuideExpanded', 'true');
    });

    helpGuide.addEventListener('hide.bs.collapse', function () {
        localStorage.setItem('helpGuideExpanded', 'false');
    });
});
</script>
@endpush
