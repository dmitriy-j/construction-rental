// Точка входа для каталога техники
import { createApp } from 'vue';
import CatalogApp from './catalog/CatalogApp.vue';

if (document.getElementById('catalog-app')) {
    createApp(CatalogApp).mount('#catalog-app');
}
