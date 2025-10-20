// astro.config.mjs
import { defineConfig } from 'astro/config';
import tailwind from '@astrojs/tailwind';
import compress from 'astro-compress'; // opcional

export default defineConfig({
  // ✅ Vive bajo /inbolsaNeo tanto en dev como en build
  base: '/inbolsaNeo',

  output: 'static',
  server: { port: 4321, host: true },

  build: {
    format: 'file',
    inlineStylesheets: 'auto',
  },

  vite: {
    build: {
      target: ['es2020', 'edge88', 'firefox79', 'chrome87', 'safari14'],
      cssMinify: true,
      sourcemap: false,
    },
    server: {
      // ✅ DEV: proxy para /api y también /inbolsaNeo/api
      proxy: {
        '/api': {
          target: 'http://localhost:8088',
          changeOrigin: true,
          secure: false,
        },
        '/inbolsaNeo/api': {
          target: 'http://localhost:8088',
          changeOrigin: true,
          secure: false,
          // manda /inbolsaNeo/api/... -> /api/... en el backend local
          rewrite: (path) => path.replace(/^\/inbolsaNeo\/api/, '/api'),
        },
      },
    },
  },

  integrations: [
    tailwind({ applyBaseStyles: true }),
    compress(), // opcional
  ],
});
