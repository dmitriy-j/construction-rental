import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/sass/sidebar.scss',
                'resources/sass/footer.scss',
                'resources/sass/navbar.scss',
                'resources/js/app.js',
                'resources/js/sidebar.js',
                'resources/js/theme.js',
                'resources/js/ripple.js',
                'resources/js/cart/index.js'
                /*'resources/js/catalog/show.js'*/
            ],
            refresh: true,
        }),
    ],
});
