import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import fullReload from 'vite-plugin-full-reload';
import fs from 'fs';
import path from 'path';

export default defineConfig({
  // Base path for the application
  base: '/pantrypal_core/',
  build: {
    // Output directory for production build
    outDir: 'dist',
    // Generate manifest.json in the build output directory
    manifest: true,
    rollupOptions: {
      input: {
        main: 'src/js/main.js',
      },
      output: {
        entryFileNames: 'assets/[name]-[hash].js',
        chunkFileNames: 'assets/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash].[ext]'
      }
    }
  },

  plugins: [
    tailwindcss(),
    fullReload([
      './**/*.php',  // Watch all PHP files in the project
      './src/**/*.{css,js}',  // Watch CSS and JS files in src directory
      '!./node_modules/**',  // Ignore node_modules
      '!./**/.idea/**/*',
    ]),
  ],

  server: {
    // Use HTTPS with mkcert-generated certificates
    https: {
      key: fs.readFileSync(path.resolve(__dirname, 'ssl/localhost+3-key.pem')),
      cert: fs.readFileSync(path.resolve(__dirname, 'ssl/localhost+3.pem')),
      ca: fs.readFileSync(path.resolve(__dirname, 'ssl/rootCA.pem')),
    },

    // Ensure Vite responds to all hosts
    host: true,
    // Set port to match what's expected in index.php
    port: 5173,

    // Configure HMR to work with PHP
    hmr: {
      protocol: 'wss',
      host: 'localhost',
      port: 5173,
      path: '/pantrypal_core/socket',
    },
    // Proxy PHP requests to your PHP server
    proxy: {
      // Proxy all PHP files to the PHP server
      '^/pantrypal_core/(?!(@vite|src|node_modules|assets)/|.*\\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)).*$': {
        target: 'http://localhost:80',
        changeOrigin: true,
        secure: true,
        // ws: true,
      }
    },
    watch: {
      usePolling: false, // Helps detect file changes reliably
      interval: 300,
    },
    headers: {
      'Access-Control-Allow-Origin': '*',
    },
  }
});
