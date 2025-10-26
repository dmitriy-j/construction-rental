import { createApp } from 'vue';
import RentalRequestShow from '../components/RentalRequest/RentalRequestShow.vue';
import ProposalsList from '../components/RentalRequest/ProposalsList.vue';
import RequestStats from '../components/RentalRequest/RequestStats.vue';
import RequestActions from '../components/RentalRequest/RequestActions.vue';
import QuickActions from '../components/RentalRequest/QuickActions.vue';
import PauseRequestModal from '../components/RentalRequest/PauseRequestModal.vue';
import CancelRequestModal from '../components/RentalRequest/CancelRequestModal.vue';
import RentalConditionsDisplay from '../components/RentalRequest/RentalConditionsDisplay.vue';
import SpecificationsDisplay from '../components/RentalRequest/SpecificationsDisplay.vue';
import PositionCard from '../components/RentalRequest/PositionCard.vue';
import CategoryGroup from '../components/RentalRequest/CategoryGroup.vue';

// Ждем полной загрузки DOM
document.addEventListener('DOMContentLoaded', () => {
    const appElement = document.getElementById('rental-request-show-app');

    if (appElement) {
        // Извлекаем данные из data-атрибутов
        const requestId = appElement.dataset.requestId;
        const apiUrl = appElement.dataset.apiUrl;
        const pauseUrl = appElement.dataset.pauseUrl || `/api/lessee/rental-requests/${requestId}/pause`;
        const cancelUrl = appElement.dataset.cancelUrl || `/api/lessee/rental-requests/${requestId}/cancel`;
        const csrfToken = appElement.dataset.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content;

        console.log('RentalRequestShow app initialization:', {
            requestId: requestId,
            apiUrl: apiUrl,
            pauseUrl: pauseUrl,
            cancelUrl: cancelUrl,
            hasCsrfToken: !!csrfToken
        });

        // Создаем приложение с корневым компонентом
        const app = createApp(RentalRequestShow, {
            requestId: parseInt(requestId),
            apiUrl: apiUrl,
            pauseUrl: pauseUrl,
            cancelUrl: cancelUrl,
            csrfToken: csrfToken
        });

        // Регистрируем компоненты только для этого приложения
        app.component('ProposalsList', ProposalsList);
        app.component('RequestStats', RequestStats);
        app.component('RequestActions', RequestActions);
        app.component('QuickActions', QuickActions);
        app.component('PauseRequestModal', PauseRequestModal);
        app.component('CancelRequestModal', CancelRequestModal);
        app.component('RentalConditionsDisplay', RentalConditionsDisplay);
        app.component('SpecificationsDisplay', SpecificationsDisplay);
        app.component('PositionCard', PositionCard);
        app.component('CategoryGroup', CategoryGroup);

        app.mount('#rental-request-show-app');
        console.log('RentalRequestShow app mounted successfully with all components');
    } else {
        console.log('Element #rental-request-show-app not found - this is normal on other pages');
    }
});
