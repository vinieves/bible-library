import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/pdf-reader.js',
                'resources/js/pdf-reader-legacy.js',
                'resources/js/pdf-preview.js',
                'resources/js/audio-player.js',
                'resources/js/video-player.js',
                'resources/js/forum.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '127.0.0.1',
        hmr: {
            host: '127.0.0.1',
        },
    },
});
