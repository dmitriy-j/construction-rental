import { createApp } from 'vue';
import RentalRequests from '/resources/js/Views/RentalRequests.vue';
import PublicRentalRequestShow from './Views/PublicRentalRequestShow.vue';
import { initRipple } from './ripple';
import { initTheme } from './theme';
import { initSmartNavbar } from './navbar';
import Chart from 'chart.js/auto';
import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
window.Chart = Chart;

console.log('🟢 app.js - УПРОЩЕННАЯ ВЕРСИЯ С АДАПТИВНЫМ САЙДБАРОМ');

// Функция для инициализации адаптивного сайдбара
function initStableSidebar() {
    const sidebar = document.getElementById('sidebarContainer');
    if (!sidebar) {
        console.log('❌ Сайдбар не найден на этой странице');
        return;
    }

    console.log('✅ Инициализация адаптивного сайдбара');

    // Функция для обновления высоты сайдбара при изменении состояния навбара
    function updateSidebarHeight() {
        const navbar = document.querySelector('.navbar');
        const isNavbarHidden = document.body.classList.contains('navbar--hidden');

        if (isNavbarHidden) {
            // Навбар скрыт - сайдбар на всю высоту
            sidebar.style.top = '0';
            sidebar.style.height = '100vh';
        } else {
            // Навбар виден - учитываем его высоту
            const navbarHeight = navbar ? navbar.offsetHeight : 80;
            sidebar.style.top = `${navbarHeight}px`;
            sidebar.style.height = `calc(100vh - ${navbarHeight}px)`;
        }
    }

    // Минимизация сайдбара
    const minifyBtn = document.getElementById('sidebarMinify');
    if (minifyBtn) {
        function updateMinifyIcon(isMini) {
            const icon = minifyBtn.querySelector('i');
            if (icon) {
                icon.style.transition = 'transform 0.3s ease';
                icon.style.transform = isMini ? 'rotate(180deg)' : 'rotate(0deg)';
            }
        }

        const isMini = localStorage.getItem('sidebarMini') === 'true';
        if (isMini) {
            document.body.classList.add('sidebar-mini');
            updateMinifyIcon(true);
        }

        minifyBtn.addEventListener('click', () => {
            const isNowMini = !document.body.classList.contains('sidebar-mini');
            document.body.classList.toggle('sidebar-mini', isNowMini);
            localStorage.setItem('sidebarMini', isNowMini);
            updateMinifyIcon(isNowMini);
        });
    }

    // Слушаем изменения состояния навбара
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                updateSidebarHeight();
            }
        });
    });

    // Начинаем наблюдать за body на предмет изменения классов
    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });

    // Также обновляем при ресайзе окна
    window.addEventListener('resize', updateSidebarHeight);

    // Изначальная настройка высоты
    updateSidebarHeight();

    console.log('🎉 Адаптивный сайдбар инициализирован');
}

// УМНАЯ ИНИЦИАЛИЗАЦИЯ: проверяем, нужно ли монтировать Vue приложения
function initializePageSpecificApps() {
    const path = window.location.pathname;
    console.log('🎯 Текущий путь:', path);

    // ТОЛЬКО для страниц создания и просмотра заявок пропускаем Blade сайдбар
    // Страница редактирования ДОЛЖНА использовать Blade сайдбар
    const vueOnlyPages = [
        '/rental-requests/create',
        '/rental-requests/show' // если есть отдельная страница просмотра
    ];

    const shouldSkipSidebar = vueOnlyPages.some(vuePath => path.includes(vuePath));

    if (shouldSkipSidebar) {
        console.log('⏭️ Vue-страница - пропускаем Blade сайдбар');
        return;
    }

    // На ВСЕХ остальных страницах, включая редактирование, инициализируем сайдбар
    console.log('✅ Инициализируем Blade сайдбар для:', path);
    initStableSidebar();
}

window.addEventListener('load', () => {
    console.log('📦 Window loaded - завершено');
});

const rentalRequestsApp = createApp({});
rentalRequestsApp.component('rental-requests', RentalRequests);

// Монтируем приложение только если на странице есть соответствующий элемент
if (document.getElementById('rental-requests-app')) {
    rentalRequestsApp.mount('#rental-requests-app');
}

// Регистрируем компонент для публичной страницы заявки
const publicRentalRequestShowApp = createApp({});
publicRentalRequestShowApp.component('public-rental-request-show', PublicRentalRequestShow);

// Монтируем приложение только если на странице есть соответствующий элемент
if (document.getElementById('public-rental-request-show-app')) {
    publicRentalRequestShowApp.mount('#public-rental-request-show-app');
    console.log('✅ Public Rental Request Show App mounted');
}
