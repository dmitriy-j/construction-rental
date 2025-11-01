@extends('layouts.app')

@section('title', 'Контакты - Федеральная Арендная Платформа')
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

                            <!-- УДАЛЕНО: Свидетельство о регистрации -->
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
                                <div class="text-muted">+7 (929) 533-32-06</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-envelope-fill text-primary me-3 fs-5"></i>
                            <div>
                                <strong>Email</strong>
                                <div class="text-muted">
                                    <a href="mailto:office@fap24.ru" class="text-decoration-none">
                                        office@fap24.ru
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

                        <!-- УДАЛЕНО: Главный бухгалтер -->
                    @else
                    <div class="text-center py-3">
                        <p class="text-muted mb-0">Информация о руководстве временно недоступна</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- ЗАМЕНА: Федеральный статус на информацию о компании -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>О компании
                    </h5>
                </div>
                <div class="card-body text-center">
                    <div class="company-badge badge mb-3 p-2 fs-6">
                        <i class="bi bi-building me-1"></i>Федеральная Арендная Платформа
                    </div>
                    <p class="text-muted small mb-2">
                        <i class="bi bi-check-circle text-success me-1"></i>
                        Платформа аренды строительной техники
                    </p>
                    <p class="text-muted small mb-2">
                        <i class="bi bi-check-circle text-success me-1"></i>
                        B2B решения для бизнеса
                    </p>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-check-circle text-success me-1"></i>
                        Работаем с 2024 года
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Карта -->
    @if($platform && $platform->exists && $platform->physical_address)
    <div class="card mt-4">
        <div class="card-header bg-dark text-white">
            <h5 class="card-title mb-0">
                <i class="bi bi-map me-2"></i>Мы на карте
            </h5>
        </div>
        <div class="card-body p-0 position-relative">
            <!-- КОНТЕЙНЕР ДЛЯ КАРТЫ -->
            <div id="contacts-map" style="width: 100%; height: 400px;"></div>
            <div id="contacts-map-loader" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.95); display: flex; align-items: center; justify-content: center; z-index: 1000;">
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Загрузка карты...</span>
                    </div>
                    <p class="text-muted">Загрузка карты...</p>
                </div>
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

.company-badge {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    font-weight: 600;
}

/* КРИТИЧЕСКИЕ СТИЛИ ДЛЯ КАРТЫ */
#contacts-map {
    width: 100% !important;
    height: 400px !important;
    min-height: 400px !important;
    position: relative !important;
    display: block !important;
    background: #e9ecef !important;
}

/* ГАРАНТИЯ ОТОБРАЖЕНИЯ ЯНДЕКС КАРТ */
.ymaps-2-1-79-map,
.ymaps-2-1-79-inner-panes,
.ymaps-2-1-79-ground-pane {
    width: 100% !important;
    height: 100% !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
}

.ymaps-2-1-79-map {
    border-radius: 0 0 0.375rem 0.375rem !important;
}

/* Улучшение для мобильных устройств */
@media (max-width: 768px) {
    #contacts-map {
        height: 350px !important;
        min-height: 350px !important;
    }
}
</style>
@endsection

@push('scripts')
@if($platform && $platform->exists && $platform->physical_address)
<script src="https://api-maps.yandex.ru/2.1/?apikey=bd8c3925-2d3e-448a-b6d3-c2c53a112615&lang=ru_RU" type="text/javascript"></script>
<script>
// ГАРАНТИРОВАННО РАБОТАЮЩАЯ ИНТЕРАКТИВНАЯ КАРТА
(function() {
    'use strict';

    console.log('🗺️ Инициализация гарантированно работающей карты...');

    let map = null;
    let mapInitialized = false;
    const mapContainer = document.getElementById('contacts-map');
    const mapLoader = document.getElementById('contacts-map-loader');

    if (!mapContainer) {
        console.error('❌ Контейнер карты не найден');
        return;
    }

    // 1. ПРИНУДИТЕЛЬНАЯ УСТАНОВКА СТИЛЕЙ ПЕРЕД ИНИЦИАЛИЗАЦИЕЙ
    function enforceContainerStyles() {
        console.log('📏 Устанавливаем принудительные стили контейнера...');

        // Жестко фиксируем размеры
        mapContainer.style.cssText = `
            width: 100% !important;
            height: 400px !important;
            min-height: 400px !important;
            position: relative !important;
            display: block !important;
            background: #e9ecef !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 1 !important;
            overflow: hidden !important;
        `;

        // Принудительный reflow
        void mapContainer.offsetHeight;
    }

    // 2. ФУНКЦИЯ ДЛЯ СТАТИЧЕСКОЙ КАРТЫ (запасной вариант)
    function showStaticMap() {
        console.log('🔄 Активируем запасной вариант - статическую карту...');

        if (mapContainer) {
            const containerWidth = Math.max(mapContainer.offsetWidth, 600);
            const containerHeight = 400;

            const staticMapUrl = `https://static-maps.yandex.ru/1.x/?ll=37.652714,55.863631&z=16&size=${containerWidth},${containerHeight}&l=map&pt=37.652714,55.863631,pm2dbl`;

            mapContainer.innerHTML = `
                <div style="width: 100%; height: 400px; background: #f8f9fa; border-radius: 0 0 0.375rem 0.375rem; overflow: hidden; position: relative;">
                    <img src="${staticMapUrl}"
                         alt="Федеральная Арендная Платформа - {{ $platform->physical_address }}"
                         style="width: 100%; height: 100%; object-fit: cover; display: block;">
                    <div style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0, 0, 0, 0.7); color: white; padding: 0.75rem; font-size: 14px;">
                        <p class="mb-1"><strong>Адрес:</strong> {{ $platform->physical_address }}</p>
                        <p class="mb-0"><strong>Телефон:</strong> {{ $platform->phone ?? '+7 (929) 533-32-06' }}</p>
                    </div>
                </div>
            `;
        }

        if (mapLoader) {
            mapLoader.style.display = 'none';
        }
    }

    // 3. ОСНОВНАЯ ФУНКЦИЯ ИНИЦИАЛИЗАЦИИ ИНТЕРАКТИВНОЙ КАРТЫ
    function initializeInteractiveMap() {
        if (mapInitialized) return;

        // Проверяем Яндекс Карты
        if (typeof ymaps === 'undefined') {
            console.log('⏳ Ожидаем загрузку Яндекс Карт...');
            setTimeout(initializeInteractiveMap, 100);
            return;
        }

        ymaps.ready(() => {
            if (mapInitialized) return;

            try {
                console.log('🎯 Создаем интерактивную карту...');

                // Убеждаемся что контейнер готов
                enforceContainerStyles();

                // Даем время на применение стилей
                setTimeout(() => {
                    if (mapInitialized) return;

                    try {
                        // СОЗДАЕМ КАРТУ
                        map = new ymaps.Map('contacts-map', {
                            center: [55.863631, 37.652714],
                            zoom: 16,
                            controls: ['zoomControl', 'fullscreenControl', 'typeSelector', 'searchControl']
                        }, {
                            suppressMapOpenBlock: true,
                            yandexMapDisablePoiInteractivity: false
                        });

                        // СОЗДАЕМ МЕТКУ
                        const placemark = new ymaps.Placemark([55.863631, 37.652714], {
                            hintContent: 'Федеральная Арендная Платформа',
                            balloonContentHeader: 'Федеральная Арендная Платформа',
                            balloonContentBody: `
                                <div style="max-width: 250px;">
                                    <p style="margin: 8px 0; font-size: 14px;">
                                        <strong>Адрес:</strong><br>
                                        {{ $platform->physical_address }}
                                    </p>
                                    <p style="margin: 8px 0; font-size: 14px;">
                                        <strong>Телефон:</strong><br>
                                        {{ $platform->phone ?? '+7 (929) 533-32-06' }}
                                    </p>
                                </div>
                            `
                        }, {
                            preset: 'islands#blueBusinessIcon',
                            iconColor: '#0056b3'
                        });

                        map.geoObjects.add(placemark);

                        // КРИТИЧЕСКИЙ ФИКС: Используем таймаут вместо ненадежного события 'load'
                        setTimeout(() => {
                            console.log('✅ Карта создана (таймаут)');
                            mapInitialized = true;

                            if (mapLoader) {
                                mapLoader.style.display = 'none';
                            }

                            // Принудительно обновляем карту
                            if (map) {
                                // Используем стандартные методы API
                                try {
                                    // Этот метод существует в официальном API
                                    if (map.behaviors && map.behaviors.get('drag')) {
                                        // Просто обновляем визуализацию
                                        map.setCenter([55.863631, 37.652714]);
                                    }
                                } catch (e) {
                                    console.log('ℹ️ Стандартный метод обновления не сработал:', e);
                                }
                            }
                        }, 1000);

                        // Обработчик ошибок
                        map.events.add('error', (error) => {
                            console.error('❌ Ошибка интерактивной карты:', error);
                            showStaticMap();
                        });

                    } catch (error) {
                        console.error('💥 Ошибка при создании карты:', error);
                        showStaticMap();
                    }
                }, 100);

            } catch (error) {
                console.error('💥 Критическая ошибка в ymaps.ready:', error);
                showStaticMap();
            }
        });
    }

    // 4. УМНАЯ ИНИЦИАЛИЗАЦИЯ С ПРОВЕРКОЙ ВИДИМОСТИ
    function smartInitialization() {
        console.log('🚀 Запуск умной инициализации...');

        // Сначала устанавливаем стили
        enforceContainerStyles();

        // Проверяем видимость контейнера
        function isContainerVisible() {
            const rect = mapContainer.getBoundingClientRect();
            return rect.width > 0 && rect.height > 0;
        }

        if (!isContainerVisible()) {
            console.log('👀 Контейнер не видим, ждем...');
            // Ждем пока контейнер станет видимым
            const checkVisibility = setInterval(() => {
                if (isContainerVisible()) {
                    clearInterval(checkVisibility);
                    console.log('✅ Контейнер стал видимым, инициализируем карту');
                    initializeInteractiveMap();
                }
            }, 100);

            // Таймаут проверки видимости
            setTimeout(() => {
                clearInterval(checkVisibility);
                if (!mapInitialized) {
                    console.log('⏰ Таймаут видимости, пробуем инициализировать');
                    initializeInteractiveMap();
                }
            }, 3000);
        } else {
            console.log('✅ Контейнер видим, инициализируем сразу');
            initializeInteractiveMap();
        }

        // Дополнительные попытки
        setTimeout(() => !mapInitialized && initializeInteractiveMap(), 2000);
        setTimeout(() => !mapInitialized && initializeInteractiveMap(), 4000);

        // Финальный fallback
        setTimeout(() => {
            if (!mapInitialized) {
                console.log('🆘 Финальный таймаут - показываем статическую карту');
                showStaticMap();
            }
        }, 8000);
    }

    // 5. ЗАПУСК
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            console.log('📄 DOM загружен, запускаем умную инициализацию');
            smartInitialization();
        });
    } else {
        console.log('⚡ DOM уже загружен, запускаем умную инициализацию');
        smartInitialization();
    }

})();
</script>

<!-- КРИТИЧЕСКИЕ СТИЛИ ДЛЯ ГАРАНТИИ -->
<style>
    /* АБСОЛЮТНАЯ ГАРАНТИЯ РАЗМЕРОВ */
    #contacts-map {
        width: 100% !important;
        height: 400px !important;
        min-height: 400px !important;
        position: relative !important;
        display: block !important;
        background: #e9ecef !important;
        visibility: visible !important;
        opacity: 1 !important;
        z-index: 1 !important;
        overflow: hidden !important;
    }

    /* ГАРАНТИЯ ДЛЯ YANDEX MAPS */
    .ymaps-2-1-79-map,
    .ymaps-2-1-79-inner-panes,
    .ymaps-2-1-79-ground-pane {
        width: 100% !important;
        height: 100% !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
    }

    .ymaps-2-1-79-map {
        border-radius: 0 0 0.375rem 0.375rem !important;
    }
</style>
@endif
@endpush
