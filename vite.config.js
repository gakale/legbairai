import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

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
});
