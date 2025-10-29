// resources/js/pages/lessor-rental-request-detail.js
console.log('🚀 lessor-rental-request-detail.js: Начало загрузки детальной страницы заявки');

import { createApp } from 'vue';

// Ждем загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Поиск элемента lessor-rental-request-detail...');

    const appElement = document.getElementById('lessor-rental-request-detail');

    if (!appElement) {
        console.error('❌ Элемент lessor-rental-request-detail не найден');
        return;
    }

    console.log('✅ Элемент найден, начинаем загрузку Vue компонентов...');

    // 🔥 ИСПРАВЛЕННЫЙ ПУТЬ ИМПОРТА - используем относительные пути от pages/
    Promise.all([
        import('../components/Lessor/RentalRequestDetail.vue'),
        import('../components/Lessor/ProposalTemplates.vue')
    ])
    .then(([
        RentalRequestDetailModule,
        ProposalTemplatesModule
    ]) => {
        console.log('✅ Все компоненты детальной страницы загружены');

        const app = createApp({});

        // 🔥 РЕГИСТРИРУЕМ КОМПОНЕНТЫ
        app.component('rental-request-detail', RentalRequestDetailModule.default);
        app.component('proposal-templates', ProposalTemplatesModule.default);

        // Используем vue-manager для безопасного монтирования
        if (window.vueAppManager && window.vueAppManager.canInitialize('lessor-rental-request-detail')) {
            window.vueAppManager.initializeApp('lessor-rental-request-detail', app);
            console.log('✅ Vue приложение детальной страницы успешно инициализировано через vue-manager');
        } else {
            // Fallback: монтируем напрямую
            app.mount(appElement);
            console.log('✅ Vue приложение детальной страницы успешно инициализировано напрямую');
        }

    })
    .catch(error => {
        console.error('❌ Ошибка загрузки компонентов детальной страницы:', error);
        console.error('📋 Детали ошибки:', error.message);
        console.error('🔄 Stack trace:', error.stack);
    });
});
