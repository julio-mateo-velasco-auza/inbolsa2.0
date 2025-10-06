// astro.config.mjs
import { defineConfig } from 'astro/config';
import tailwind from '@astrojs/tailwind';
import compress from 'astro-compress'; // opcional

export default defineConfig({
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
  },
  integrations: [
    tailwind({
      // aplica la config por defecto de Tailwind para Astro
      applyBaseStyles: true,
    }),
    compress(), // opcional
  ],
    
  vite: {
    server: {
      // API PHP (docker php-apache en :8088)
      proxy: {
        '/api': 'http://localhost:8088',
      },
    },
  },
});
