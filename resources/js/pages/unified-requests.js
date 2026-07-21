import { createApp } from 'vue';
import UnifiedRequests from '../components/RentalRequest/UnifiedRequests.vue';

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('unified-requests-app');
    if (!container) {
        console.error('❌ Контейнер #unified-requests-app не найден');
        return;
    }

    try {
        const userRole = container.dataset.userRole || 'guest';
        let authUser = null;
        try {
            authUser = container.dataset.authUser ? JSON.parse(container.dataset.authUser) : null;
        } catch (e) {}

        let categories = [];
        try {
            categories = container.dataset.categories ? JSON.parse(container.dataset.categories) : [];
        } catch (e) {}

        let locations = [];
        try {
            locations = container.dataset.locations ? JSON.parse(container.dataset.locations) : [];
        } catch (e) {}

        const app = createApp(UnifiedRequests, {
            userRole: userRole,
            authUser: authUser,
            categories: categories,
            locations: locations
        });

        // Используем vueAppManager если доступен
        if (window.vueAppManager && window.vueAppManager.canInitialize('unified-requests-app')) {
            window.vueAppManager.initializeApp('unified-requests-app', app);
        } else {
            app.mount('#unified-requests-app');
        }

        console.log('✅ UnifiedRequests смонтирован, роль:', userRole);
    } catch (error) {
        console.error('❌ Ошибка монтирования UnifiedRequests:', error);
    }
});
