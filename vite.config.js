import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/blog.js',
                'resources/js/components.js',
                'resources/css/filament/admin/theme.css',
                'resources/css/filament/dashboard/theme.css',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
