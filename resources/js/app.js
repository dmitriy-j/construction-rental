// resources/js/app.js

import { createApp } from 'vue';
import RentalRequests from '/resources/js/Views/RentalRequests.vue';
import CatalogApp from '/resources/js/catalog/CatalogApp.vue';
import CatalogDetail from '/resources/js/catalog/CatalogDetail.vue';
import { CartIcon } from '/resources/js/catalog/CartComponents.js';
import { initRipple } from './ripple';
import { initTheme } from './theme';
import { initSmartNavbar } from './navbar';
import './eventBus.js';
import Chart from 'chart.js/auto';
import './bootstrap';
import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import './vue-manager';

window.Alpine = Alpine;
Alpine.start();
window.Chart = Chart;
window.Swal = Swal;
window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// Монтирование каталога (список)
if (document.getElementById('catalog-app')) {
    createApp(CatalogApp).mount('#catalog-app');
}

// Монтирование детальной страницы каталога
if (document.getElementById('catalog-detail-app') && window.__EQUIPMENT_ID__) {
    const detailApp = createApp(CatalogDetail, {
        equipmentId: window.__EQUIPMENT_ID__,
    });
    detailApp.mount('#catalog-detail-app');
}

// Монтирование иконки корзины глобально
if (document.getElementById('cart-icon')) {
    const cartApp = createApp({ components: { CartIcon }, template: '<CartIcon />' });
    cartApp.component('CartIcon', CartIcon);
    cartApp.mount('#cart-icon');
}

// Слушаем EventBus для обновления корзины
window.addEventListener('DOMContentLoaded', function() {
    if (window.cartBus) {
        window.cartBus.on('cart-updated', function() {
            // Перезагружаем корзину через кастомное событие на #cart-icon
            const event = new CustomEvent('cart-refresh');
            document.dispatchEvent(event);
        });
    }
});

// Инициализация модулей
document.addEventListener('DOMContentLoaded', function() {
    try { initTheme(); initSmartNavbar(); initRipple(); } catch (e) { console.error(e); }
});

window.addEventListener('error', function(e) { console.error('Global error:', e.error); });
window.addEventListener('unhandledrejection', function(e) { console.error('Unhandled rejection:', e.reason); });
