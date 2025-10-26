// resources/js/app.js

import { createApp } from 'vue';
import RentalRequests from '/resources/js/Views/RentalRequests.vue';
import { initRipple } from './ripple';
import { initTheme } from './theme';
import { initSmartNavbar } from './navbar';
import Chart from 'chart.js/auto';
import './bootstrap';
import Alpine from 'alpinejs';

// 🔥 ИМПОРТИРУЕМ МЕНЕДЖЕР
import './vue-manager';

window.Alpine = Alpine;
Alpine.start();
window.Chart = Chart;

console.log('🟢 app.js - ВЕРСИЯ С ЦЕНТРАЛИЗОВАННЫМ УПРАВЛЕНИЕМ VUE');

// УНИВЕРСАЛЬНАЯ ИНИЦИАЛИЗАЦИЯ ВСЕХ МОДУЛЕЙ
document.addEventListener('DOMContentLoaded', function() {
  try {
    initTheme();
    initSmartNavbar();
    initRipple();
    console.log('✅ Все модули инициализированы');
  } catch (error) {
    console.error('❌ Ошибка инициализации модулей:', error);
  }
});

// УЛУЧШЕННАЯ ИНИЦИАЛИЗАЦИЯ VUE ПРИЛОЖЕНИЙ
function initializeVueApps() {
  // 🔥 ИСПРАВЛЕНИЕ: Используем менеджер для проверки
  const appManager = window.vueAppManager;

  // Приложение для заявок
  const rentalRequestsAppElement = document.getElementById('rental-requests-app');
  if (rentalRequestsAppElement && appManager.canInitialize('rental-requests-app')) {
    try {
      const rentalRequestsApp = createApp({});
      rentalRequestsApp.component('rental-requests', RentalRequests);
      rentalRequestsApp.mount('#rental-requests-app');
      appManager.registerApp('rental-requests-app', rentalRequestsApp);
      console.log('✅ Rental Requests App mounted');
    } catch (error) {
      console.error('❌ Ошибка монтирования Rental Requests App:', error);
    }
  }

  // 🔥 УБИРАЕМ ВСЕ ДУБЛИРУЮЩИЕСЯ ПРИЛОЖЕНИЯ
  // Приложение для публичной страницы заявки теперь монтируется ТОЛЬКО в public-rental-request-show.js

  // Приложение для редактирования заявки
  const rentalRequestEditAppElement = document.getElementById('rental-request-edit-app');
  if (rentalRequestEditAppElement) {
    console.log('🔄 App for rental request edit detected - handled in separate file');
  }
}

// УЛУЧШЕННАЯ ИНИЦИАЛИЗАЦИЯ САЙДБАРА
function initializeAdaptiveSidebar() {
  const sidebar = document.getElementById('sidebarContainer');
  if (!sidebar) {
    console.log('ℹ️ Сайдбар не найден на этой странице');
    return;
  }

  console.log('✅ Инициализация адаптивного сайдбара');

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

  // Минимизация сайдбара
  const minifyBtn = document.getElementById('sidebarMinify');
  if (minifyBtn) {
    minifyBtn.addEventListener('click', function() {
      document.body.classList.toggle('sidebar-mini');
      localStorage.setItem('sidebarMini', document.body.classList.contains('sidebar-mini'));
    });
  }

  // Слушатель изменений
  window.addEventListener('resize', updateSidebarDimensions);
  updateSidebarDimensions();
}

// ОСНОВНАЯ ИНИЦИАЛИЗАЦИЯ ПРИ ЗАГРУЗКЕ
window.addEventListener('load', function() {
  console.log('🎯 Загрузка завершена - инициализация компонентов');

  // 🔥 ИСПРАВЛЕНИЕ: Инициализируем Vue приложения только если нет публичной заявки
  const isPublicRequestPage = document.getElementById('public-rental-request-show-app');

  if (!isPublicRequestPage) {
    initializeVueApps();
    initializeAdaptiveSidebar();
  } else {
    console.log('⚠️ Страница публичной заявки - пропускаем инициализацию в app.js');
  }
});

// ГЛОБАЛЬНЫЕ ОБРАБОТЧИКИ ОШИБОК
window.addEventListener('error', function(e) {
  console.error('🚨 Глобальная ошибка:', e.error);
});

window.addEventListener('unhandledrejection', function(e) {
  console.error('🚨 Необработанный Promise rejection:', e.reason);
});
