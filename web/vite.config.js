import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';

const watching = process.argv.includes('--watch');

export default defineConfig({
  base: '/build/',
  publicDir: false,
  plugins: [tailwindcss()],
  experimental: {
    renderBuiltUrl(filename) {
      return '/build/' + filename.replace(/^\//, '');
    },
  },
  build: {
    outDir: 'public/build',
    emptyOutDir: !watching,
    cssCodeSplit: false,
    ...(watching
      ? {
          watch: {
            exclude: ['public/build/**', 'node_modules/**'],
            chokidar: {
              ignored: ['**/public/build/**', '**/node_modules/**'],
            },
          },
        }
      : {}),
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
});
