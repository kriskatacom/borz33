import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';

const root = fileURLToPath(new URL('.', import.meta.url));

function phpFullReload() {
  return {
    name: 'php-full-reload',
    handleHotUpdate({ file, server }) {
      const relative = path.relative(root, file).replaceAll('\\', '/');

      if (relative.startsWith('public/build/') || relative.startsWith('node_modules/')) {
        return;
      }

      if (file.endsWith('.php')) {
        server.ws.send({ type: 'full-reload', path: '*' });
        return [];
      }
    },
  };
}

export default defineConfig(({ command }) => {
  const isBuild = command === 'build';

  return {
    base: isBuild ? '/build/' : '/',
    publicDir: false,
    plugins: [vue(), tailwindcss(), phpFullReload()],
    experimental: {
      renderBuiltUrl(filename) {
        return '/build/' + filename.replace(/^\//, '');
      },
    },
    server: {
      host: '0.0.0.0',
      port: 5174,
      strictPort: true,
      cors: true,
      allowedHosts: true,
      hmr: {
        protocol: 'ws',
        clientPort: 4000,
        path: '/__vite_hmr',
      },
      watch: {
        usePolling: true,
        interval: 300,
      },
    },
    build: {
      outDir: 'public/build',
      emptyOutDir: true,
      cssCodeSplit: false,
      rollupOptions: {
        input: 'src/app.js',
        output: {
          entryFileNames: 'app.js',
          assetFileNames: (asset) => {
            const name = asset.names?.[0] ?? '';

            if (name.endsWith('.css')) {
              return 'app.css';
            }

            return 'assets/[name][extname]';
          },
        },
      },
    },
  };
});
