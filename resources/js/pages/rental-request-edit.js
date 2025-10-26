import { createApp } from 'vue';
import EditRentalRequestForm from '../components/RentalRequest/EditRentalRequestForm.vue';

console.log('🎯 rental-request-edit.js: Скрипт начал выполнение');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔄 DOM готов, инициализация Vue приложения...');

    const appElement = document.getElementById('rental-request-edit-app');
    if (!appElement) {
        console.error('❌ Элемент #rental-request-edit-app не найден');
        return;
    }

    try {
        const app = createApp(EditRentalRequestForm, {
            requestId: appElement.dataset.requestId,
            apiUrl: appElement.dataset.apiUrl,
            updateUrl: appElement.dataset.updateUrl,
            csrfToken: appElement.dataset.csrfToken,
            categories: JSON.parse(appElement.dataset.categories || '[]'),
            locations: JSON.parse(appElement.dataset.locations || '[]')
        });

        app.mount('#rental-request-edit-app');
        console.log('✅ Vue приложение смонтировано успешно');

        // Дополнительная проверка сайдбара после монтирования Vue
        setTimeout(() => {
            const sidebar = document.getElementById('sidebarContainer');
            if (sidebar) {
                console.log('📊 Проверка сайдбара после Vue:', {
                    height: sidebar.style.height,
                    computedHeight: window.getComputedStyle(sidebar).height
                });
            }
        }, 100);

    } catch (error) {
        console.error('❌ Ошибка при монтировании Vue:', error);
    }
});
