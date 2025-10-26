// resources/js/components.js
import { createApp } from 'vue';
import RequestItems from './components/RentalRequest/RequestItems.vue';
import RentalConditions from './components/RentalRequest/RentalConditions.vue';
import BudgetCalculator from './components/RentalRequest/BudgetCalculator.vue';
import CreateRentalRequestForm from './components/RentalRequest/CreateRentalRequestForm.vue';

// 🔥 ИСПРАВЛЕНИЕ: Безопасная инициализация с проверкой
function initializeComponents() {
    const rentalRequestApp = document.getElementById('rental-request-app');

    // 🔥 ПРОВЕРКА: Не инициализировать на странице публичной заявки
    const isPublicRequestPage = document.getElementById('public-rental-request-show-app');
    if (isPublicRequestPage) {
        console.log('⚠️ components.js: Пропускаем инициализацию на странице публичной заявки');
        return;
    }

    if (rentalRequestApp && !rentalRequestApp._vueApp) {
        console.log('🚀 components.js: Инициализация компонентов заявки');

        const app = createApp({});
        app.component('request-items', RequestItems);
        app.component('rental-conditions', RentalConditions);
        app.component('budget-calculator', BudgetCalculator);
        app.component('create-rental-request-form', CreateRentalRequestForm);

        // 🔥 Сохраняем ссылку на приложение
        rentalRequestApp._vueApp = app;
        app.mount('#rental-request-app');

        console.log('✅ components.js: Компоненты заявки смонтированы');
    } else if (rentalRequestApp && rentalRequestApp._vueApp) {
        console.log('⚠️ components.js: Приложение уже смонтировано');
    }
}

// 🔥 ИСПРАВЛЕНИЕ: Отложенная инициализация
document.addEventListener('DOMContentLoaded', function() {
    // Задержка для предотвращения конфликтов
    setTimeout(initializeComponents, 100);
});
