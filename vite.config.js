import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    base: process.env.NODE_ENV === 'production' ? '/stg/' : '/',
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/email-template-editor.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        host: process.env.VITE_HOST || '127.0.0.1',
        port: 5173,
    },
});
