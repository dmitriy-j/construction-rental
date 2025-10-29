// resources/js/pages/lessor-rental-requests.js
console.log('🚀 lessor-rental-requests.js: Начало загрузки ЛК арендодателя');

import { createApp } from 'vue';

// Ждем загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Поиск элемента lessor-rental-requests-app...');

    const appElement = document.getElementById('lessor-rental-requests-app');
    const fallbackElement = document.getElementById('lessor-html-fallback');

    if (!appElement) {
        console.error('❌ Элемент lessor-rental-requests-app не найден');
        if (fallbackElement) fallbackElement.style.display = 'block';
        return;
    }

    console.log('✅ Элемент найден, начинаем загрузку Vue компонентов...');

    // 🔥 ДИНАМИЧЕСКИ ИМПОРТИРУЕМ ВСЕ КОМПОНЕНТЫ ЛК АРЕНДОДАТЕЛЯ
    Promise.all([
        import('../components/Lessor/LessorRentalRequestList.vue'),
        import('../components/Lessor/AnalyticsDashboard.vue'),
        import('../components/Lessor/ProposalTemplates.vue'),
        import('../components/Lessor/RealTimeAnalytics.vue'),
        import('../components/Lessor/StrategicAnalytics.vue'),
        import('../components/Lessor/QuickActionCard.vue'),
        import('../components/Lessor/TemplateCard.vue'),
        import('../components/Lessor/RentalRequestDetail.vue') // 🔥 ДОБАВЛЕНО
    ])
    .then(([
        LessorRentalRequestListModule,
        AnalyticsDashboardModule,
        ProposalTemplatesModule,
        RealTimeAnalyticsModule,
        StrategicAnalyticsModule,
        QuickActionCardModule,
        TemplateCardModule,
        RentalRequestDetailModule // 🔥 ДОБАВЛЕНО
    ]) => {
        console.log('✅ Все компоненты ЛК арендодателя загружены');

        const app = createApp({});

        // 🔥 РЕГИСТРИРУЕМ ВСЕ КОМПОНЕНТЫ
        app.component('lessor-rental-request-list', LessorRentalRequestListModule.default);
        app.component('analytics-dashboard', AnalyticsDashboardModule.default);
        app.component('proposal-templates', ProposalTemplatesModule.default);
        app.component('real-time-analytics', RealTimeAnalyticsModule.default);
        app.component('strategic-analytics', StrategicAnalyticsModule.default);
        app.component('quick-action-card', QuickActionCardModule.default);
        app.component('template-card', TemplateCardModule.default);
        app.component('rental-request-detail', RentalRequestDetailModule.default); // 🔥 ДОБАВЛЕНО

        // Используем vue-manager для безопасного монтирования
        if (window.vueAppManager && window.vueAppManager.canInitialize('lessor-rental-requests-app')) {
            window.vueAppManager.initializeApp('lessor-rental-requests-app', app);

            // Показываем Vue приложение, скрываем fallback
            appElement.style.display = 'block';
            if (fallbackElement) fallbackElement.style.display = 'none';

            console.log('✅ Vue приложение ЛК арендодателя успешно инициализировано через vue-manager');
        } else {
            // Fallback: монтируем напрямую
            app.mount(appElement);
            appElement.style.display = 'block';
            if (fallbackElement) fallbackElement.style.display = 'none';
            console.log('✅ Vue приложение ЛК арендодателя успешно инициализировано напрямую');
        }

    })
    .catch(error => {
        console.error('❌ Ошибка загрузки компонентов ЛК арендодателя:', error);
        // Показываем HTML fallback
        if (fallbackElement) {
            fallbackElement.style.display = 'block';
            console.log('✅ Показан HTML fallback для ЛК арендодателя');
        }
    });
});
