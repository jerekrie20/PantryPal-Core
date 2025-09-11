import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import fullReload from 'vite-plugin-full-reload';
import fs from 'fs';
import path from 'path';

export default defineConfig({
    base: '/',                         // <— important for vhost

    build: {
        outDir: 'dist',
        manifest: true,
        rollupOptions: {
            input: { main: 'src/js/main.js' },
            output: {
                entryFileNames: 'assets/[name]-[hash].js',
                chunkFileNames: 'assets/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash].[ext]',
            },
        },
    },

    plugins: [
        tailwindcss(),
        fullReload([
            './**/*.php',
            './src/**/*.{css,js}',
            '!./node_modules/**',
            '!./**/.idea/**/*',
        ]),
    ],

    server: {
        https: {
            key: fs.readFileSync(path.resolve(__dirname, 'ssl/localhost+3-key.pem')),
            cert: fs.readFileSync(path.resolve(__dirname, 'ssl/localhost+3.pem')),
            ca: fs.readFileSync(path.resolve(__dirname, 'ssl/rootCA.pem')),
        },
        host: true,
        port: 5173,
        hmr: {
            protocol: 'wss',
            host: 'localhost',
            port: 5173,
            // no custom path needed
        },
        // Remove the proxy unless you truly need it
        watch: { usePolling: false, interval: 300 },
        headers: { 'Access-Control-Allow-Origin': '*' },
    },
});
