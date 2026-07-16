import { createApp } from 'vue';
import RentalRequests from '../Views/RentalRequests.vue';

console.log('🟢 rental-requests.js - Инициализация Vue приложения для публичных заявок');

document.addEventListener('DOMContentLoaded', function() {
    const appContainer = document.getElementById('rental-requests-app');

    if (appContainer) {
        console.log('✅ Найден контейнер #rental-requests-app');

        try {
            // Получаем props из data-атрибутов контейнера
            const userRole = appContainer.dataset.userRole || 'guest';
            let authUser = null;
            try {
                authUser = appContainer.dataset.authUser ? JSON.parse(appContainer.dataset.authUser) : null;
            } catch (e) {}

            const app = createApp(RentalRequests, {
                userRole: userRole,
                authUser: authUser,
            });
            app.mount('#rental-requests-app');
            console.log('🎉 Vue приложение публичных заявок успешно смонтировано');
        } catch (error) {
            console.error('❌ Ошибка монтирования Vue приложения:', error);
        }
    } else {
        console.log('❌ Контейнер #rental-requests-app не найден, ищем альтернативный...');
        const altContainer = document.getElementById('rental-request-app');
        if (altContainer) {
            try {
                const app = createApp(RentalRequests);
                app.mount('#rental-request-app');
                console.log('🎉 Vue смонтирован на #rental-request-app (альтернативный)');
            } catch (error) {
                console.error('❌ Ошибка монтирования альтернативного приложения:', error);
            }
        }
    }
});
