import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/sass/sidebar.scss',
                'resources/sass/footer.scss',
                'resources/sass/navbar.scss',
                'resources/js/app.js',
                'resources/js/components/SidebarComponent.js',
                'resources/js/stores/sidebarStore.js',
                'resources/js/theme.js',
                'resources/js/ripple.js',
                'resources/js/cart/index.js',
                'resources/js/navbar.js',
                'resources/js/pages/rental-request-create.js', // Добавляем новую точку входа
                'resources/js/pages/rental-request-show.js',
                'resources/js/pages/rental-request-edit.js',
                'resources/js/pages/rental-requests.js',

            ],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            'vue': 'vue/dist/vue.esm-bundler.js'
        },
    },
    server: {
        hmr: {
            host: 'localhost',
        },
    },
});
