import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    plugins: [
        react(),
        laravel({
            input: "resources/js/app.jsx",
            buildDirectory:'public/tenancy/assets/build',
            refresh: true,
        }),
    ],
    build: {
        outDir: path.resolve(__dirname, 'public/tenancy/assets/build'), // Output to a folder named after the tenant
      },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'public'), // Alias '@' to the 'resources' directory
        },
    },
});
