import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // Tambahkan path ke assets Sneat
                'public/sneat-template/assets/vendor/js/menu.js',
                'public/sneat-template/assets/vendor/libs/jquery/jquery.js',
                // ... asset lainnya
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            // Definisikan alias untuk path asset
            '@': '/resources',
            '@sneat': '/public/sneat-template/assets',
        }
    }
});