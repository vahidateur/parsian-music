/**
 * Quality Gate Screenshot Script
 * Captures login page at 1920×1080 and saves to .screenshots/
 */
import { chromium } from 'playwright';
import { existsSync, mkdirSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __dirname = dirname(fileURLToPath(import.meta.url));
const outDir = join(__dirname, '.screenshots');

if (!existsSync(outDir)) mkdirSync(outDir);

const url = 'http://parsian-music.test/login';
const outFile = join(outDir, `login-${Date.now()}.png`);

const browser = await chromium.launch({
  headless: true,
  executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
});
const page = await browser.newPage();
await page.setViewportSize({ width: 1920, height: 1080 });

console.log(`Opening ${url} …`);
await page.goto(url, { waitUntil: 'networkidle' });

// Wait for fonts and backdrop-filter to settle
await page.waitForTimeout(800);

await page.screenshot({ path: outFile, fullPage: false });
console.log(`Screenshot saved: ${outFile}`);

await browser.close();
