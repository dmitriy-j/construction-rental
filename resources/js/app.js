// resources/js/app.js - ПРОДАКШЕН ВЕРСИЯ БЕЗ ОТЛАДКИ

import { createApp } from 'vue';
import RentalRequests from '/resources/js/Views/RentalRequests.vue';
import { initRipple } from './ripple';
import { initTheme } from './theme';
//import { initSmartNavbar } from './navbar';
import Chart from 'chart.js/auto';
import './bootstrap';
import Alpine from 'alpinejs';

// Импортируем менеджер
import './vue-manager';

window.Alpine = Alpine;
Alpine.start();
window.Chart = Chart;

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
