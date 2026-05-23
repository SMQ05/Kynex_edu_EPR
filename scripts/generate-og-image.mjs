/**
 * Generate the 1200×630 social/OG share image (PNG) from the editable SVG.
 *
 * Why: og:image must be a PNG/JPG for Facebook, WhatsApp, LinkedIn and X.
 * The brand source of truth is public/images/og-kynexedu.svg — edit that,
 * then re-run this to refresh the PNG that <meta property="og:image"> points at.
 *
 * Run inside the app container (Playwright/Chromium is already in the image):
 *   node scripts/generate-og-image.mjs
 *
 * Output: public/images/og-kynexedu.png
 */
import { chromium } from 'playwright';
import { readFileSync, mkdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const svgPath = path.join(root, 'public/images/og-kynexedu.svg');
const outPath = path.join(root, 'public/images/og-kynexedu.png');

const svg = readFileSync(svgPath, 'utf8');
const html = `<!doctype html><html><head><meta charset="utf-8">
<style>html,body{margin:0;padding:0}svg{display:block}</style></head>
<body>${svg}</body></html>`;

const browser = await chromium.launch({ args: ['--no-sandbox'] });
const page = await browser.newPage({
  viewport: { width: 1200, height: 630 },
  deviceScaleFactor: 2, // crisp on retina / when platforms downscale
});
await page.setContent(html, { waitUntil: 'networkidle' });
mkdirSync(path.dirname(outPath), { recursive: true });
await page.screenshot({ path: outPath, clip: { x: 0, y: 0, width: 1200, height: 630 } });
await browser.close();
console.log('✓ wrote', outPath);
