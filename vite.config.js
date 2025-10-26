// vite.config.js - добавляем менеджер
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
                'resources/js/vue-manager.js', // 🔥 ДОБАВЛЯЕМ МЕНЕДЖЕР
                'resources/js/components/SidebarComponent.js',
                'resources/js/stores/sidebarStore.js',
                'resources/js/theme.js',
                'resources/js/ripple.js',
                'resources/js/cart/index.js',
                'resources/js/navbar.js',
                'resources/js/components.js', // 🔥 ОБНОВЛЕННЫЙ КОМПОНЕНТЫ
                'resources/js/pages/rental-request-create.js',
                'resources/js/pages/rental-request-show.js',
                'resources/js/pages/public-rental-request-show.js',
                'resources/js/pages/rental-request-edit.js',
                'resources/js/pages/rental-requests.js',
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
    resolve: {
        alias: {
            '@': '/resources/js',
            'vue': 'vue/dist/vue.esm-bundler.js',
            '~components': '/resources/js/components',
            '~views': '/resources/js/views',
            '~pages': '/resources/js/pages'
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
            protocol: 'ws'
        },
        cors: {
            origin: '*',
            methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            allowedHeaders: ['*'],
        },
        proxy: {
            '/api': {
                target: 'http://cr.loc',
                changeOrigin: true,
                secure: false,
            }
        }
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['vue', 'axios', 'bootstrap'],
                    charts: ['chart.js'],
                    manager: ['resources/js/vue-manager.js'], // 🔥 ОТДЕЛЬНЫЙ ЧАНК
                    'public-requests': [
                        'resources/js/views/PublicRentalRequestShow.vue',
                        'resources/js/components/Public/PublicRentalConditionsDisplay.vue',
                        'resources/js/components/Public/PublicCategoryGroup.vue'
                    ]
                },
            },
        },
        chunkSizeWarningLimit: 600,
    },
});
