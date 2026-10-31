# KOVOCER.eu

One-page marketing site for **Kovocer** (Prečín, Slovakia) – forged gates, fences and railings made of steel and stainless steel.
Built with [Astro](https://astro.build) and [Tailwind CSS v4](https://tailwindcss.com). The output is a fully static site; the only server-side piece is an optional PHP mailer.

## Requirements

- Node.js `>= 22.12.0`
- npm
- (optional) PHP 8+ on the host, only if the contact form runs in `smtp` mode

## Getting started

```sh
git clone https://github.com/majko96/kovocer.eu.git
cd kovocer.eu
npm install
npm run dev
```

The dev server runs at <http://localhost:4321>.

| Command           | What it does                                   |
| ----------------- | ---------------------------------------------- |
| `npm run dev`     | Start the dev server with hot reload           |
| `npm run build`   | Build the static site into `dist/`             |
| `npm run preview` | Serve the production build locally             |

> Note: `public/sendEmail.php` is plain PHP, so the SMTP form mode does **not** work under `npm run dev`. Use the default `link` mode locally (see below).

## Project structure

```
src/
  assets/photos/     Gallery and hero photos (optimised by astro:assets at build time)
  components/        One component per page section (Hero, About, Gates, Fences, Faq, ...)
  data/
    site.ts          Business details (phone, e-mail, address, map), button styles, form mode
    faqs.ts          FAQ content (used both for the page and for FAQPage JSON-LD)
  layouts/Layout.astro   <head>: SEO meta, Open Graph, JSON-LD (LocalBusiness + FAQPage)
  pages/index.astro  Page composition (the order of the sections)
  pages/robots.txt.ts, llms.txt.ts   Generated from the configured SITE_URL
  scripts/           reveal.js (scroll animations), lightbox.js (PhotoSwipe setup)
  styles/global.css  Tailwind theme tokens (colours, fonts) and global styles
public/
  og-image.png       Open Graph / Twitter preview image (1200x630)
  favicon.svg
  sendEmail.php, PHPMailer/, smtp-config.example.php   Optional SMTP mailer
design/
  logos/             Logo concepts (SVG)
  og/                Source SVG and generator script for og-image.png
```

## Editing content

- **Business details** (phone, e-mail, address, map): `src/data/site.ts`. They are used across the page, the footer, the mobile menu and the structured data.
- **FAQ**: `src/data/faqs.ts`.
- **Section copy**: edit the matching component in `src/components/`.
- **Photos**: put files into `src/assets/photos/` and import them in the component (`Gates.astro`, `Fences.astro`, ...). Keep meaningful file names and `alt` texts – they matter for SEO. Lightbox captions are intentionally not shown.
- **Colours / fonts**: `src/styles/global.css` (`@theme` block). The main accent is `--color-accent`.

## Contact form modes

The "Napíšte nám" button is controlled by one constant in `src/data/site.ts`:

```ts
export const formMode: "link" | "mailto" | "smtp" = "link";
```

| Mode     | Behaviour                                                                                         |
| -------- | ------------------------------------------------------------------------------------------------- |
| `link`   | Plain `mailto:` link, no form (default, needs no backend)                                          |
| `mailto` | Modal form; on submit it opens the visitor's mail client with the message pre-filled               |
| `smtp`   | Modal form; submits to `public/sendEmail.php`, which sends the e-mail through PHPMailer over SMTP |

### SMTP configuration (`smtp` mode only)

`sendEmail.php` reads its settings from, in order of priority: real environment variables, a `.env` file next to or above the web root, and `smtp-config.php`.

Environment variables:

```
SMTP_HOST=
SMTP_PORT=465
SMTP_SECURE=ssl          # ssl or tls
SMTP_USERNAME=
SMTP_PASSWORD=
SMTP_FROM_EMAIL=
SMTP_FROM_NAME="KOVOCER.eu — web"
SMTP_TO_EMAIL=
```

Alternatively copy `public/smtp-config.example.php` to `public/smtp-config.php` and fill it in.
**Never commit real credentials** – `.env` and `public/smtp-config.php` are git-ignored.

## SEO

- The public origin is read at **build time** from the `SITE_URL` environment variable (default `https://www.kovocer.eu`, see `astro.config.mjs`). Canonical links, the sitemap, `robots.txt`, `llms.txt`, Open Graph URLs and JSON-LD all derive from it.
- Set `NOINDEX=true` on staging/test deployments: it adds `noindex, nofollow` to every page and makes `robots.txt` disallow everything.

  ```sh
  # example: staging build
  SITE_URL=https://kovocer.mariobabinec.sk NOINDEX=true npm run build
  ```

  Both variables can also be placed in a `.env` file in the project root (git-ignored). On HostCreators, put them into the app's `.env` content.
- `@astrojs/sitemap` generates `sitemap-index.xml` on every build; `public/robots.txt` points to it.
- `public/llms.txt` is a short machine-readable summary of the business.
- Structured data (LocalBusiness and FAQPage) lives in `src/layouts/Layout.astro`.

### Regenerating the Open Graph image

`public/og-image.png` is rendered from `design/og/og.svg`, with text converted to outlines so it does not depend on installed fonts.

```sh
npm i --no-save fontkit
# download the variable font "RobotoCondensed[wght].ttf" from github.com/google/fonts (ofl/robotocondensed)
OG_FONT="/path/to/RobotoCondensed[wght].ttf" node design/og/generate.cjs
```

After deploying, you can check the preview with Facebook's Sharing Debugger or the LinkedIn Post Inspector.

## Deployment (HostCreators, Git webhook)

The site is deployed from the `main` branch through a HostCreators "Git application":

1. Add a Git application for the subdomain/domain and select this repository and the `main` branch.
2. Set **Relative public path to the web** to `dist` (without it the server serves the repository root and returns `403`).
3. Build & deploy steps (working directory `/`):
   ```sh
   npm install
   npm run build
   ```
4. Add environment variables in the app's `.env` section: `SITE_URL` (and `NOINDEX=true` for a staging subdomain). Add the `SMTP_*` variables **only** if you switched `formMode` to `smtp`.
5. Push to `main` (or press "Run application") to deploy. The very first request after a deploy can still return the old state for a few minutes while the web server configuration refreshes.

## Conventions

- Commit messages are written in English.
- Do not commit secrets, `.env` files or `public/smtp-config.php`.
- `design/backups/` is git-ignored and used only for local colour-variant snapshots.
