import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    server: {
        host: '127.0.0.1',  // Add this to force IPv4 only
    },
    plugins: [
        react(),
        laravel({
            input: ["resources/components/App.jsx"] ,
            buildDirectory:'tenancy/assets/build',
            refresh: true,
        }),
    ],
    build: {
        outDir: path.resolve(__dirname, 'public/tenancy/assets/build'), // Output to a folder named after the tenant
        target: 'esnext' //browsers can handle the latest ES features
      },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'public'), // Alias '@' to the 'resources' directory
        },
    },
    ssr: {
        external: ["@webassemblyjs/helper-api-error"]
      }
});
