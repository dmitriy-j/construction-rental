import { createApp } from 'vue';
import RentalRequestList from '../components/RentalRequest/RentalRequestList.vue';

console.log('🟢 rental-requests.js - Инициализация Vue приложения для заявок');

// Ждем полной загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM готов для Vue приложения заявок');

    const appContainer = document.getElementById('rental-request-app');

    if (appContainer) {
        console.log('✅ Найден контейнер для Vue приложения заявок');

        try {
            const app = createApp(RentalRequestList);
            app.mount('#rental-request-app');
            console.log('🎉 Vue приложение заявок успешно смонтировано');
        } catch (error) {
            console.error('❌ Ошибка монтирования Vue приложения:', error);
        }
    } else {
        console.log('❌ Контейнер #rental-request-app не найден');
    }
});
