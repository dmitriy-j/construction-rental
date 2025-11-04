import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

// Упрощенная версия БЕЗ glob для надежности
export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Основные файлы
                'resources/sass/app.scss',
                'resources/js/app.js',

                // Все страницы с Vue компонентами
                'resources/js/pages/rental-request-create.js',
                'resources/js/pages/rental-request-show.js',
                'resources/js/pages/rental-request-edit.js',
                'resources/js/pages/rental-requests.js',
                'resources/js/pages/lessor-rental-requests.js',
                'resources/js/pages/lessor-rental-request-detail.js',
                'resources/js/pages/public-rental-request-show.js',

                // Дополнительные скрипты
                'resources/js/vue-manager.js',
                'resources/js/components.js',
                'resources/js/navbar.js',
                'resources/js/theme.js',
                'resources/js/ripple.js',
                'resources/js/cart/index.js',
                'resources/js/yandex-map-fallback.js'
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],

    build: {
        target: 'es2015',
        outDir: 'public/build',
        emptyOutDir: true,
        sourcemap: false,
        minify: false, // Отключаем минификацию для экономии памяти
        rollupOptions: {
            output: {
                manualChunks: undefined, // Отключаем разделение на чанки
            },
        },
        chunkSizeWarningLimit: 1000,
    },
});
