import { createApp } from 'vue';
import RequestItems from './components/RentalRequest/RequestItems.vue';
import RentalConditions from './components/RentalRequest/RentalConditions.vue';
import BudgetCalculator from './components/RentalRequest/BudgetCalculator.vue';
import CreateRentalRequestForm from './components/RentalRequest/CreateRentalRequestForm.vue';

// Автоматическая регистрация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    const rentalRequestApp = document.getElementById('rental-request-app');
    if (rentalRequestApp) {
        const app = createApp({});

        app.component('request-items', RequestItems);
        app.component('rental-conditions', RentalConditions);
        app.component('budget-calculator', BudgetCalculator);
        app.component('create-rental-request-form', CreateRentalRequestForm);

        app.mount('#rental-request-app');
    }
});
