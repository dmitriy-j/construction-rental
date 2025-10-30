// vite.config.js - ОПТИМИЗИРОВАННАЯ ВЕРСИЯ
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
            '~pages': '/resources/js/pages',
            '~lessor': '/resources/js/components/Lessor',
        },
    },
    build: {
        target: 'es2015',
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['vue', 'axios', 'bootstrap'],
                    charts: ['chart.js'],
                    manager: ['resources/js/vue-manager.js'],
                    'public-requests': [
                        'resources/js/views/PublicRentalRequestShow.vue',
                        'resources/js/components/Public/PublicRentalConditionsDisplay.vue',
                        'resources/js/components/Public/PublicCategoryGroup.vue'
                    ],
                    'lessor-components': [
                        'resources/js/components/Lessor/RentalRequestDetail.vue',
                        'resources/js/components/Lessor/ProposalTemplates.vue'
                    ]
                },
            },
        },
        chunkSizeWarningLimit: 600,
        sourcemap: false,
        minify: 'esbuild',
    },
});
