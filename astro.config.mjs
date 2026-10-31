// @ts-check
import { defineConfig } from 'astro/config';
import { loadEnv } from 'vite';

import tailwindcss from '@tailwindcss/vite';

import sitemap from '@astrojs/sitemap';

// Build-time settings come from real environment variables or from a .env file in the project root:
//   SITE_URL  public origin of this deployment (default: the production domain)
//   NOINDEX   "true" on staging/test deployments to keep them out of search engines
const env = { ...loadEnv(process.env.NODE_ENV ?? 'production', process.cwd(), ''), ...process.env };
const siteUrl = (env.SITE_URL || 'https://www.kovocer.eu').replace(/\/+$/, '');
const noindex = env.NOINDEX === 'true';

// https://astro.build/config
export default defineConfig({
  site: siteUrl,

  vite: {
    plugins: [tailwindcss()],
    define: {
      'import.meta.env.NOINDEX': JSON.stringify(noindex)
    }
  },

  integrations: [sitemap()]
});
