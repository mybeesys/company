import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';
import fs from 'fs';

/** Append brand-theme after React style chunks (bundle resets :root to blue). */
function appendBrandTheme() {
    const brandThemePath = path.resolve(__dirname, 'public/assets/css/brand-theme.css');
  return {
    name: 'append-brand-theme',
    enforce: 'post',
    generateBundle(_options, bundle) {
      if (!fs.existsSync(brandThemePath)) {
        return;
      }
      const brandTheme = fs.readFileSync(brandThemePath, 'utf8');
      for (const item of Object.values(bundle)) {
        if (
          item.type === 'asset' &&
          item.fileName?.startsWith('assets/style-') &&
          item.fileName.endsWith('.css') &&
          typeof item.source === 'string' &&
          !item.source.includes('Brand primary theme — logo gold')
        ) {
          item.source = `${item.source}\n${brandTheme}`;
        }
      }
    },
  };
}

export default defineConfig({
    server: {
        host: '127.0.0.1',  // Add this to force IPv4 only
    },
    plugins: [
        react(),
        appendBrandTheme(),
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
      },
    assetsInclude: ['**/*.node'],
    optimizeDeps: {
        include: ['@swc/wasm']
      }
});