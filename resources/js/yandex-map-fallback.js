// УНИВЕРСАЛЬНОЕ РЕШЕНИЕ ДЛЯ ЯНДЕКС КАРТ
class YandexMapUniversal {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.options = options;
        this.map = null;
        this.attempts = 0;
        this.maxAttempts = 10;

        this.init();
    }

    init() {
        if (this.attempts >= this.maxAttempts) {
            console.error('❌ Превышено максимальное количество попыток инициализации карты');
            this.showFallback();
            return;
        }

        this.attempts++;
        console.log(`🔄 Попытка инициализации #${this.attempts}`);

        // Ждем полной загрузки страницы
        if (document.readyState !== 'complete') {
            setTimeout(() => this.init(), 100);
            return;
        }

        // Проверяем контейнер
        const container = document.getElementById(this.containerId);
        if (!container) {
            console.error('❌ Контейнер карты не найден');
            setTimeout(() => this.init(), 200);
            return;
        }

        // Принудительно устанавливаем размеры
        this.forceContainerStyles(container);

        // Проверяем Яндекс Карты
        if (typeof ymaps === 'undefined') {
            console.log('⏳ Ожидаем загрузку Яндекс Карт...');
            setTimeout(() => this.init(), 200);
            return;
        }

        // Инициализируем карту
        this.initializeMap();
    }

    forceContainerStyles(container) {
        // КРИТИЧЕСКИЕ СТИЛИ ДЛЯ КОНТЕЙНЕРА
        container.style.cssText = `
            width: 100% !important;
            height: 400px !important;
            min-height: 400px !important;
            position: relative !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 1 !important;
            background: #e9ecef !important;
        `;

        // Принудительно обновляем родительские элементы
        let parent = container.parentElement;
        while (parent && parent !== document.body) {
            parent.style.overflow = 'visible';
            parent.style.position = 'relative';
            parent = parent.parentElement;
        }
    }

    initializeMap() {
        try {
            console.log('🎯 Создаем карту...');

            ymaps.ready(() => {
                if (this.map) return;

                const container = document.getElementById(this.containerId);

                // ЯДЕРНЫЙ ФИКС: Полностью пересоздаем контейнер
                const oldContainer = container;
                const newContainer = document.createElement('div');
                newContainer.id = this.containerId;
                newContainer.style.cssText = oldContainer.style.cssText;
                oldContainer.parentNode.replaceChild(newContainer, oldContainer);

                // СОЗДАЕМ КАРТУ
                this.map = new ymaps.Map(this.containerId, {
                    center: this.options.center || [55.863631, 37.652714],
                    zoom: this.options.zoom || 16,
                    controls: this.options.controls || [
                        'zoomControl',
                        'fullscreenControl',
                        'typeSelector',
                        'searchControl'
                    ]
                }, {
                    suppressMapOpenBlock: true,
                    yandexMapDisablePoiInteractivity: false
                });

                // СОЗДАЕМ МЕТКУ
                if (this.options.placemark) {
                    const placemark = new ymaps.Placemark(
                        this.options.placemark.center || this.options.center || [55.863631, 37.652714],
                        this.options.placemark.properties || {
                            hintContent: 'Федеральная Арендная Платформа',
                            balloonContentHeader: 'Федеральная Арендная Платформа',
                            balloonContentBody: this.options.placemark.content || `
                                <div style="max-width: 250px;">
                                    <p style="margin: 8px 0; font-size: 14px;">
                                        <strong>Адрес:</strong><br>
                                        ${this.options.address || 'ул. Искры, 31, Москва'}
                                    </p>
                                    <p style="margin: 8px 0; font-size: 14px;">
                                        <strong>Телефон:</strong><br>
                                        ${this.options.phone || '+7 (929) 533-32-06'}
                                    </p>
                                </div>
                            `
                        },
                        this.options.placemark.options || {
                            preset: 'islands#blueBusinessIcon',
                            iconColor: '#0056b3'
                        }
                    );

                    this.map.geoObjects.add(placemark);

                    // Автоматически открываем балун
                    setTimeout(() => {
                        try {
                            placemark.balloon.open();
                        } catch (e) {
                            console.warn('Не удалось открыть балун:', e);
                        }
                    }, 2000);
                }

                // ОБРАБОТЧИКИ СОБЫТИЙ
                this.map.events.add('load', () => {
                    console.log('✅ Карта успешно загружена!');
                    this.onMapLoaded();
                });

                this.map.events.add('error', (error) => {
                    console.error('❌ Ошибка карты:', error);
                    this.showFallback();
                });

                // ПРИНУДИТЕЛЬНОЕ ОБНОВЛЕНИЕ РАЗМЕРОВ
                this.forceMapRedraw();

            });

        } catch (error) {
            console.error('💥 Критическая ошибка:', error);
            this.showFallback();
        }
    }

    onMapLoaded() {
        // Скрываем лоадер
        const loader = document.getElementById('map-loader');
        if (loader) {
            loader.style.display = 'none';
        }

        // Множественные обновления размеров
        this.forceMapRedraw();

        // Дополнительные обновления
        setTimeout(() => this.forceMapRedraw(), 100);
        setTimeout(() => this.forceMapRedraw(), 500);
        setTimeout(() => this.forceMapRedraw(), 1000);
    }

    forceMapRedraw() {
        if (!this.map) return;

        try {
            // Все возможные методы обновления размеров
            if (this.map.container && this.map.container.fitToViewport) {
                this.map.container.fitToViewport();
            }

            if (this.map.container && this.map.container.redraw) {
                this.map.container.redraw();
            }

            // Принудительный рефлоу
            const container = document.getElementById(this.containerId);
            if (container) {
                container.style.display = 'none';
                container.offsetHeight; // Принудительный reflow
                container.style.display = 'block';
            }

            console.log('🔄 Размеры карты обновлены');

        } catch (e) {
            console.warn('Не удалось обновить размеры карты:', e);
        }
    }

    showFallback() {
        console.log('🔄 Активируем fallback...');

        const container = document.getElementById(this.containerId);
        const loader = document.getElementById('map-loader');

        if (!container) return;

        // Создаем статическую карту
        const staticMap = document.createElement('div');
        staticMap.innerHTML = `
            <div style="width: 100%; height: 400px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                <div class="text-center">
                    <div style="font-size: 48px; color: #dc3545; margin-bottom: 16px;">🗺️</div>
                    <h5>Интерактивная карта недоступна</h5>
                    <p class="text-muted">Используется статическая карта</p>
                    <img src="https://static-maps.yandex.ru/1.x/?ll=37.652714,55.863631&z=16&size=650,400&l=map&pt=37.652714,55.863631,pm2dbl"
                         alt="Федеральная Арендная Платформа"
                         style="width: 100%; height: 300px; object-fit: cover; border-radius: 8px;">
                    <p class="mt-2">
                        <strong>Адрес:</strong> ${this.options.address || 'ул. Искры, 31, Москва'}<br>
                        <strong>Телефон:</strong> ${this.options.phone || '+7 (929) 533-32-06'}
                    </p>
                </div>
            </div>
        `;

        container.parentNode.replaceChild(staticMap, container);

        if (loader) {
            loader.style.display = 'none';
        }
    }

    destroy() {
        if (this.map) {
            this.map.destroy();
            this.map = null;
        }
    }
}

// Глобальная функция для инициализации
window.initYandexMap = function(containerId, options = {}) {
    return new YandexMapUniversal(containerId, options);
};
