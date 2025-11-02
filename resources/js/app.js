// resources/js/app.js - ПРОДАКШЕН ВЕРСИЯ БЕЗ ОТЛАДКИ

import { createApp } from 'vue';
import RentalRequests from '/resources/js/Views/RentalRequests.vue';
import { initRipple } from './ripple';
import { initTheme } from './theme';
import { initSmartNavbar } from './navbar';
import Chart from 'chart.js/auto';
import './bootstrap';
import Alpine from 'alpinejs';

// ⚠️ ИСПРАВЛЕНИЕ: Добавляем импорт SweetAlert2
import Swal from 'sweetalert2';

// Импортируем менеджер
import './vue-manager';

window.Alpine = Alpine;
Alpine.start();
window.Chart = Chart;

// ⚠️ ИСПРАВЛЕНИЕ: Регистрируем SweetAlert2 глобально
window.Swal = Swal;

// ⚠️ ДОБАВЛЕНА ГЛОБАЛЬНАЯ ОБРАБОТКА ОШИБОК VUE
const initVueApp = (elementId, component, props = {}) => {
  try {
    const app = createApp(component, props);

    // ⚠️ ИСПРАВЛЕНИЕ: Регистрируем SweetAlert2 как глобальное свойство
    app.config.globalProperties.$swal = Swal;

    // Глобальная обработка ошибок
    app.config.errorHandler = (err, vm, info) => {
      console.error(`Vue Error in ${elementId}:`, err);
      console.error('Component:', vm);
      console.error('Info:', info);
    };

    // Обработчик предупреждений
    app.config.warnHandler = (msg, vm, trace) => {
      console.warn(`Vue Warning in ${elementId}:`, msg);
      console.warn('Trace:', trace);
    };

    app.mount(`#${elementId}`);
    return app;
  } catch (error) {
    console.error(`Failed to init Vue app ${elementId}:`, error);
  }
};

// УНИВЕРСАЛЬНАЯ ИНИЦИАЛИЗАЦИЯ ВСЕХ МОДУЛЕЙ
document.addEventListener('DOMContentLoaded', function() {
    try {
        initTheme();
        initSmartNavbar();
        initRipple();
    } catch (error) {
        console.error('Ошибка инициализации модулей:', error);
    }
});

// ИНИЦИАЛИЗАЦИЯ VUE ПРИЛОЖЕНИЙ
function initializeVueApps() {
    const appManager = window.vueAppManager;

    // Приложение для заявок арендатора
    const rentalRequestsAppElement = document.getElementById('rental-requests-app');
    if (rentalRequestsAppElement && appManager && appManager.canInitialize('rental-requests-app')) {
        try {
            const rentalRequestsApp = createApp({});

            // ⚠️ ИСПРАВЛЕНИЕ: Регистрируем SweetAlert2 для этого приложения
            rentalRequestsApp.config.globalProperties.$swal = Swal;

            // ⚠️ ДОБАВЛЕНА ГЛОБАЛЬНАЯ ОБРАБОТКА ОШИБОК
            rentalRequestsApp.config.errorHandler = (err, vm, info) => {
                console.error('Глобальная ошибка Vue:', err);
                console.error('Компонент:', vm);
                console.error('Информация:', info);
            };

            rentalRequestsApp.component('rental-requests', RentalRequests);
            rentalRequestsApp.mount('#rental-requests-app');
            appManager.registerApp('rental-requests-app', rentalRequestsApp);
        } catch (error) {
            console.error('Ошибка монтирования Rental Requests App:', error);
        }
    }

    // Приложение для просмотра заявки арендатора
    const rentalRequestShowAppElement = document.getElementById('rental-request-show-app');
    if (rentalRequestShowAppElement) {
        // Обрабатывается в отдельном файле
    }

    // Приложение для списка заявок арендодателя
    const lessorRentalRequestsAppElement = document.getElementById('lessor-rental-requests-app');
    if (lessorRentalRequestsAppElement) {
        // Обрабатывается в отдельном файле lessor-rental-requests.js
    }
}

// ИНИЦИАЛИЗАЦИЯ АДАПТИВНОГО САЙДБАРА
function initializeAdaptiveSidebar() {
    const sidebar = document.getElementById('sidebarContainer');
    if (!sidebar) return;

    function updateSidebarDimensions() {
        const navbar = document.querySelector('.navbar');
        const isMobile = window.innerWidth < 992;

        if (isMobile) {
            sidebar.style.top = '0';
            sidebar.style.height = '100vh';
        } else {
            const navbarHeight = navbar ? navbar.offsetHeight : 80;
            sidebar.style.top = `${navbarHeight}px`;
            sidebar.style.height = `calc(100vh - ${navbarHeight}px)`;
        }
    }

    const minifyBtn = document.getElementById('sidebarMinify');
    if (minifyBtn) {
        minifyBtn.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-mini');
            localStorage.setItem('sidebarMini', document.body.classList.contains('sidebar-mini'));
        });
    }

    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(updateSidebarDimensions, 250);
    });
    updateSidebarDimensions();
}

// ОСНОВНАЯ ИНИЦИАЛИЗАЦИЯ ПРИ ЗАГРУЗКЕ
window.addEventListener('load', function() {
    const isPublicRequestPage = document.getElementById('public-rental-request-show-app');

    if (!isPublicRequestPage) {
        initializeVueApps();
        initializeAdaptiveSidebar();
    }
});

// ГЛОБАЛЬНЫЕ ОБРАБОТЧИКИ ОШИБОК
window.addEventListener('error', function(e) {
    console.error('Глобальная ошибка:', e.error);
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Необработанный Promise rejection:', e.reason);
});
