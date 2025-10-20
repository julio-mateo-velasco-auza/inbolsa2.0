// astro.config.mjs
import { defineConfig } from 'astro/config';

export default defineConfig({
  output: 'static',
  base: '/inbolsaNeo/',   // servimos bajo /inbolsaNeo
  trailingSlash: 'always',
});
