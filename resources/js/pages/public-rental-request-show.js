// resources/js/pages/public-rental-request-show.js
import { createApp } from 'vue';
import PublicRentalRequestShow from '../Views/PublicRentalRequestShow.vue';
import PublicRentalConditionsDisplay from '../components/Public/PublicRentalConditionsDisplay.vue';
import PublicCategoryGroup from '../components/Public/PublicCategoryGroup.vue';
import PublicProposalModal from '../components/Public/PublicProposalModal.vue';
import ConditionItem from '../components/Public/ConditionItem.vue';

// 🔥 ИСПРАВЛЕНИЕ: Используем менеджер для предотвращения конфликтов
function initPublicRentalRequestShowApp() {
    const appManager = window.vueAppManager;
    const appElement = document.getElementById('public-rental-request-show-app');

    if (appElement && appManager.canInitialize('public-rental-request-show-app')) {
        console.log('🚀 Initializing PublicRentalRequestShow app...');

        const app = createApp(PublicRentalRequestShow);

        // Регистрируем компоненты для публичной заявки
        app.component('PublicRentalConditionsDisplay', PublicRentalConditionsDisplay);
        app.component('PublicCategoryGroup', PublicCategoryGroup);
        app.component('PublicProposalModal', PublicProposalModal);
        app.component('ConditionItem', ConditionItem);

        // 🔥 ОБРАБОТЧИК ОШИБОК
        app.config.errorHandler = (err, instance, info) => {
            console.error('🚨 Vue Error:', err, 'Info:', info);
        };

        try {
            app.mount('#public-rental-request-show-app');
            appManager.registerApp('public-rental-request-show-app', app);
            console.log('✅ PublicRentalRequestShow app mounted successfully');
        } catch (error) {
            console.error('❌ Ошибка монтирования:', error);
        }
    } else {
        console.log('⚠️ PublicRentalRequestShow app initialization skipped:', {
            elementExists: !!appElement,
            canInitialize: appManager.canInitialize('public-rental-request-show-app'),
            hasApp: appManager.hasApp('public-rental-request-show-app')
        });
    }
}

// 🔥 УЛУЧШЕННАЯ ИНИЦИАЛИЗАЦИЯ С ПРИОРИТЕТОМ
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Задержка для гарантии, что другие скрипты не помешают
        setTimeout(initPublicRentalRequestShowApp, 50);
    });
} else {
    setTimeout(initPublicRentalRequestShowApp, 100);
}
