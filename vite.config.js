import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
// Import path for node built-ins if needed for alias
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/main.jsx'],
            refresh: true,
        }),
        tailwindcss(),
        react(), // Ajoute le support de React et JSX
    ],
    define: {
        // 'process.env': process.env, // Example if needed, ensure process is available or handle appropriately
        global: 'window', // Fix for "global is not defined"
    },
    resolve: {
        alias: {
            // Add this alias
            util: 'util/',
            // If you were aliasing to a specific file, you might use:
            // util: path.resolve(__dirname, 'node_modules/util/util.js'),
            // However, for node built-ins polyfills, 'util/' often works with modern bundlers
            // or just 'util' if the polyfill package is correctly structured.
            // Let's try with 'util/' first as it's a common way for Vite.
        }
    }
});
