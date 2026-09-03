import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath, URL } from 'node:url';

function serveAdminSpaForHtml(req: { headers: Record<string, string | string[] | undefined> }) {
  const accept = req.headers.accept;

  return typeof accept === 'string' && accept.includes('text/html') ? '/index.html' : undefined;
}

export default defineConfig(({ command }) => ({
  base: command === 'build' ? '/admin/' : '/',
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 3000,
    allowedHosts: true,
    hmr: {
      protocol: 'ws',
      clientPort: 3000,
    },
    watch: {
      usePolling: true,
    },
    proxy: {
      '/__dev/reload': {
        target: 'http://dev-reload:35729',
        changeOrigin: true,
        rewrite: () => '/events',
      },
      '/auth': {
        target: process.env.VITE_PROXY_TARGET || 'http://127.0.0.1:5000',
        changeOrigin: true,
      },
      '/admin': {
        target: process.env.VITE_PROXY_TARGET || 'http://127.0.0.1:5000',
        changeOrigin: true,
        bypass: serveAdminSpaForHtml,
      },
      '/uploads': {
        target: process.env.VITE_PROXY_TARGET || 'http://127.0.0.1:5000',
        changeOrigin: true,
      },
      '/assets': {
        target: process.env.VITE_PROXY_TARGET || 'http://127.0.0.1:5000',
        changeOrigin: true,
      },
    },
  },
}));
