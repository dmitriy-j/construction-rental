import { createApp } from 'vue';
import RentalRequests from '../Views/RentalRequests.vue';
import RentalRequestList from '../components/RentalRequest/RentalRequestList.vue';

console.log('🟢 rental-requests.js - Инициализация Vue приложений');

document.addEventListener('DOMContentLoaded', function() {
    // 1. Публичная страница /requests
    const publicContainer = document.getElementById('rental-requests-app');
    if (publicContainer) {
        console.log('✅ Найден контейнер #rental-requests-app');
        try {
            const userRole = publicContainer.dataset.userRole || 'guest';
            let authUser = null;
            try {
                authUser = publicContainer.dataset.authUser ? JSON.parse(publicContainer.dataset.authUser) : null;
            } catch (e) {}

            const app = createApp(RentalRequests, {
                userRole: userRole,
                authUser: authUser,
            });
            app.mount('#rental-requests-app');
            console.log('🎉 Публичные заявки смонтированы');
        } catch (error) {
            console.error('❌ Ошибка монтирования публичных заявок:', error);
        }
        return; // Не проверяем другие контейнеры
    }

    // 2. ЛК арендатора /lessee/rental-requests
    const lesseeContainer = document.getElementById('rental-request-list-app');
    if (lesseeContainer) {
        console.log('✅ Найден контейнер #rental-request-list-app');
        try {
            const app = createApp(RentalRequestList);
            app.mount('#rental-request-list-app');
            console.log('🎉 Список заявок арендатора смонтирован');
        } catch (error) {
            console.error('❌ Ошибка монтирования списка заявок:', error);
        }
        return;
    }

    console.log('❌ Ни один контейнер не найден');
});
