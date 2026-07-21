import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/js/pages/rental-request-create.js',
                'resources/js/pages/rental-request-show.js',
                'resources/js/pages/rental-request-edit.js',
                'resources/js/pages/rental-requests.js',
                'resources/js/pages/unified-requests.js',
                'resources/js/pages/lessor-rental-requests.js',
                'resources/js/pages/lessor-rental-request-detail.js',
                'resources/js/pages/public-rental-request-show.js',
                'resources/js/vue-manager.js',
                'resources/js/components.js',
                'resources/js/navbar.js',
                'resources/js/theme.js',
                'resources/js/ripple.js',
                'resources/js/cart/index.js',
                'resources/js/yandex-map-fallback.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: { base: null, includeAbsolute: false },
            },
        }),
    ],
    build: {
        target: 'es2015',
        outDir: 'public/build',
        emptyOutDir: true,
        sourcemap: false,
        minify: false,
        chunkSizeWarningLimit: 1000,
    },
});
