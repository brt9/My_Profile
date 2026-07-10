import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/js/app.jsx',
      ],
      refresh: true,
    }),
    tailwindcss(),
    react(),
  ],
  server: {
    host: true,
    port: 5137,
    strictPort: true,
    cors: true,
    origin: 'http://127.0.0.1:5137',
    watch: {
      usePolling: true,
      interval: 100,
      ignored: ['**/node_modules/**', '**/vendor/**', '**/storage/**'],
    },
    hmr: {
      protocol: 'ws',
      host: '127.0.0.1',
      port: 5137,
      clientPort: 5137,
    },
  },
  build: { sourcemap: false },
})
