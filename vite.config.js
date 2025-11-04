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
                'resources/sass/mobile-navbar.scss',
                'resources/js/app.js',
                'resources/js/vue-manager.js',
                'resources/js/components/SidebarComponent.js',
                'resources/js/stores/sidebarStore.js',
                'resources/js/theme.js',
                'resources/js/ripple.js',
                'resources/js/cart/index.js',
                'resources/js/navbar.js',
                'resources/js/components.js',
                'resources/js/pages/rental-request-create.js',
                'resources/js/pages/rental-request-show.js',
                'resources/js/pages/public-rental-request-show.js',
                'resources/js/pages/rental-request-edit.js',
                'resources/js/pages/rental-requests.js',
                'resources/js/pages/lessor-rental-requests.js',
                'resources/js/pages/lessor-rental-request-detail.js',
                'resources/js/yandex-map-fallback.js'
            ],
            refresh: false, // На продакшене отключаем refresh
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
        assetsDir: 'assets',
        emptyOutDir: true,
        sourcemap: false,
        minify: 'terser',
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['vue', 'axios', 'bootstrap'],
                    charts: ['chart.js'],
                },
            },
        },
        chunkSizeWarningLimit: 800,
    },
    
    // Критически важно для продакшена
    base: '/build/',
    
    optimizeDeps: {
        include: ['vue', 'axios', 'bootstrap']
    },
});