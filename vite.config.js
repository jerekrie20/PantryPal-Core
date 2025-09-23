import { defineConfig, loadEnv } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import fullReload from 'vite-plugin-full-reload';
import fs from 'fs';
import path from 'path';

// Use a function export so we can tailor config for dev vs. build (production)
export default defineConfig(({ command, mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    // For Hostinger on a subdomain, assets should generally be rooted at "/".
    // If you deploy to a subfolder instead, set VITE_BASE="/your-subfolder/" in your .env.production
    const base = env.VITE_BASE || '/';

    const isDev = command === 'serve';

    return {
        base,

        build: {
            outDir: 'public/dist',
            manifest: true,
            sourcemap: false,
            copyPublicDir: false,
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

        // Only configure the HTTPS dev server locally. This avoids reading SSL files in production.
        server: isDev
            ? {
                  https: {
                      key: fs.readFileSync(path.resolve(__dirname, 'ssl/localhost+3-key.pem')),
                      cert: fs.readFileSync(path.resolve(__dirname, 'ssl/localhost+3.pem')),
                      ca: fs.readFileSync(path.resolve(__dirname, 'ssl/rootCA.crt')),
                  },
                  host: true,
                  port: Number(env.VITE_DEV_PORT || 5173),
                  hmr: {
                      protocol: env.VITE_HMR_PROTOCOL || 'wss',
                      host: env.VITE_HMR_HOST || 'localhost',
                      port: Number(env.VITE_HMR_PORT || 5173),
                  },
                  watch: { usePolling: false, interval: 300 },
                  headers: { 'Access-Control-Allow-Origin': '*' },
              }
            : undefined,
    };
});
