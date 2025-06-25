import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { nodePolyfills } from 'vite-plugin-node-polyfills';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/main.jsx'],
            refresh: true,
        }),
        tailwindcss(),
        react(),
        nodePolyfills({
            include: ['util', 'stream', 'events', 'process', 'buffer'],
            globals: {
                process: true,
                Buffer: true,
                global: true,
            },
        }),
    ],
    define: {
        global: 'window',
    },
    resolve: {
        alias: {
            util: 'util/',
        },
    },
    server: {
        host: true,
        hmr: {
            protocol: 'wss',
            host: 'https://ylnbb73qg7nr.share.zrok.io', // ← adapte ici avec ton URL Zrok du port 5173
            port: 443,
        },
    },
});