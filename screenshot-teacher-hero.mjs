/**
 * Teacher Hero Phase 1 — Screenshot Verification Script
 * Captures teacher profile at 4 breakpoints with full-page scrolling
 */
import { chromium } from 'playwright';
import { existsSync, mkdirSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __dirname = dirname(fileURLToPath(import.meta.url));
const outDir = join(__dirname, '.screenshots');

if (!existsSync(outDir)) mkdirSync(outDir);

const baseUrl = 'http://parsian-music.test/teachers/1';

// Breakpoints to verify
const breakpoints = [
  { width: 390, height: 844, name: '390x844-mobile' },
  { width: 768, height: 1024, name: '768x1024-tablet' },
  { width: 1366, height: 768, name: '1366x768-desktop' },
  { width: 1920, height: 1080, name: '1920x1080-fullhd' },
];

const browser = await chromium.launch({
  headless: true,
  executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
});

for (const bp of breakpoints) {
  const page = await browser.newPage();
  await page.setViewportSize({ width: bp.width, height: bp.height });

  console.log(`\n📸 Capturing ${bp.name}...`);
  await page.goto(baseUrl, { waitUntil: 'networkidle' });

  // Wait for fonts and CSS to settle
  await page.waitForTimeout(500);

  const outFile = join(outDir, `hero-${bp.name}-${Date.now()}.png`);
  await page.screenshot({ path: outFile, fullPage: true });
  console.log(`✓ Saved: ${outFile}`);

  // Verify no horizontal scroll
  const scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
  const clientWidth = await page.evaluate(() => document.documentElement.clientWidth);
  const hasHorizontalScroll = scrollWidth > clientWidth;

  if (hasHorizontalScroll) {
    console.warn(`⚠ WARNING: Horizontal scroll detected! scrollWidth=${scrollWidth}, clientWidth=${clientWidth}`);
  } else {
    console.log(`✓ No horizontal scroll`);
  }

  // Check for 4 named slots
  const slots = await page.evaluate(() => {
    return {
      backgroundSlot: !!document.querySelector('#teacher-background-slot'),
      frameSlot: !!document.querySelector('#teacher-frame-slot'),
      photoSlot: !!document.querySelector('#teacher-photo-slot'),
      decorationSlot: !!document.querySelector('#teacher-decoration-slot'),
    };
  });

  console.log(`✓ Slots present: bg=${slots.backgroundSlot}, frame=${slots.frameSlot}, photo=${slots.photoSlot}, deco=${slots.decorationSlot}`);

  // Check for CTA button inside info section
  const ctaPresent = await page.evaluate(() => {
    const button = document.querySelector('button[type="button"]');
    return button ? { text: button.textContent, ariaLabel: button.getAttribute('aria-label') } : null;
  });

  if (ctaPresent) {
    console.log(`✓ CTA button found: "${ctaPresent.text.trim()}" (aria-label: "${ctaPresent.ariaLabel}")`);
  } else {
    console.warn(`⚠ WARNING: CTA button not found`);
  }

  await page.close();
}

console.log('\n✅ Screenshot verification complete.');
await browser.close();
