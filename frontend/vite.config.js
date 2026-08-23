import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'src')
        }
    },
    server: {
        port: 5173,
        proxy: {
            '/api': 'http://127.0.0.1:8000',
            '/auth': 'http://127.0.0.1:8000',
            '/sanctum': 'http://127.0.0.1:8000',
            '/avatar': 'http://127.0.0.1:8000',
            '/broadcasting': 'http://127.0.0.1:8000'
        }
    },
    build: {
        outDir: '../public',
        emptyOutDir: false,
        chunkSizeWarningLimit: 2000
    }
});
