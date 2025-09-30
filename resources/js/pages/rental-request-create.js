import { createApp } from 'vue';
import CreateRentalRequestForm from '../components/RentalRequest/CreateRentalRequestForm.vue';
import RequestItems from '../components/RentalRequest/RequestItems.vue';
import RentalConditions from '../components/RentalRequest/RentalConditions.vue';
import BudgetCalculator from '../components/RentalRequest/BudgetCalculator.vue';

// Ждем полной загрузки DOM
document.addEventListener('DOMContentLoaded', () => {
    const appElement = document.getElementById('rental-request-app');

    // Проверяем, существует ли элемент и не смонтировано ли на нем приложение
    if (appElement && !appElement._vueApp) {
        const categories = JSON.parse(appElement.dataset.categories || '[]');
        const locations = JSON.parse(appElement.dataset.locations || '[]');
        const storeUrl = appElement.dataset.storeUrl;
        const csrfToken = appElement.dataset.csrfToken;

        console.log('Vue app initialization:', {
            categoriesCount: categories?.length,
            locationsCount: locations?.length,
            storeUrl: storeUrl
        });

        const app = createApp(CreateRentalRequestForm, {
            categories: categories,
            locations: locations,
            storeUrl: storeUrl,
            csrfToken: csrfToken
        });

        app.component('RequestItems', RequestItems);
        app.component('RentalConditions', RentalConditions);
        app.component('BudgetCalculator', BudgetCalculator);

        // Сохраняем ссылку на приложение, чтобы предотвратить повторное монтирование
        appElement._vueApp = app;
        app.mount('#rental-request-app');
        console.log('Vue app mounted successfully with all components');
    } else {
        console.log('Vue app already mounted or container not found.');
    }
});
