/**
 * Component-Level Verification
 */
import { chromium } from 'playwright';

const baseUrl = 'http://parsian-music.test/teachers/1';

const browser = await chromium.launch({
  headless: true,
  executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
});

const page = await browser.newPage();
await page.setViewportSize({ width: 1920, height: 1080 });
await page.goto(baseUrl, { waitUntil: 'networkidle' });
await page.waitForTimeout(300);

const verification = await page.evaluate(() => {
  const results = {};

  // 1. Semantic HTML check
  results.semantic = {
    header: !!document.querySelector('header'),
    main: !!document.querySelector('main'),
    section: !!document.querySelector('section'),
    figure: !!document.querySelector('figure'),
    figcaption: !!document.querySelector('figcaption'),
    button: !!document.querySelector('button[type="button"]'),
  };

  // 2. ARIA labels
  results.aria = {
    backgroundSlot: document.querySelector('#teacher-background-slot')?.getAttribute('aria-label'),
    frameSlot: document.querySelector('#teacher-frame-slot')?.getAttribute('aria-label'),
    photoSlot: document.querySelector('#teacher-photo-slot')?.getAttribute('aria-label'),
    decorationSlot: document.querySelector('#teacher-decoration-slot')?.getAttribute('aria-label'),
    decorationAriaHidden: document.querySelector('[aria-hidden="true"]') ? 'true' : 'false',
  };

  // 3. Content verification
  results.content = {
    name: document.querySelector('h1')?.textContent?.trim(),
    role: document.querySelector('h1')?.nextElementSibling?.textContent?.trim(),
    badge: document.querySelector('[role="text"]')?.textContent?.trim(),
    instruments: Array.from(document.querySelectorAll('span')).map(s => s.textContent.trim()).filter(t => ['ویولن', 'سلفژ', 'موسیقی کلاسیک'].includes(t)),
    ctaLabel: document.querySelector('button[type="button"]')?.getAttribute('aria-label'),
  };

  // 4. Z-index checks
  results.zindex = {
    heroStyles: document.querySelector('[style*="z-index"]')?.style.zIndex || 'none',
  };

  // 5. No inline styles (except z-index)
  results.styles = {
    hasInlineStyles: Array.from(document.querySelectorAll('[style]')).map(el => ({
      tag: el.tagName,
      style: el.getAttribute('style'),
    })),
  };

  // 6. Grid layout
  results.grid = {
    sectionClass: document.querySelector('section')?.className,
    overflowHidden: document.querySelector('section')?.classList.contains('overflow-x-hidden'),
  };

  // 7. No images
  results.assets = {
    imgTags: document.querySelectorAll('img').length,
    svgTags: document.querySelectorAll('svg').length,
    backgroundImages: Array.from(document.querySelectorAll('[style*="background"]')).length,
  };

  return results;
});

console.log('\n=== COMPONENT VERIFICATION ===\n');

console.log('1. SEMANTIC HTML:');
Object.entries(verification.semantic).forEach(([key, val]) => {
  console.log(`   ${key}: ${val ? '✓' : '✗'}`);
});

console.log('\n2. ARIA LABELS:');
Object.entries(verification.aria).forEach(([key, val]) => {
  console.log(`   ${key}: ${val || '(empty)'}`);
});

console.log('\n3. CONTENT:');
Object.entries(verification.content).forEach(([key, val]) => {
  if (Array.isArray(val)) {
    console.log(`   ${key}: ${val.length > 0 ? val.join(', ') : '(empty)'}`);
  } else {
    console.log(`   ${key}: ${val || '(empty)'}`);
  }
});

console.log('\n4. Z-INDEX (CSS Variables):');
console.log(`   Using var(--z-hero-*): ${verification.zindex.heroStyles !== 'none' ? '✓' : '?'}`);

console.log('\n5. INLINE STYLES (should only be z-index):');
if (verification.styles.hasInlineStyles.length > 0) {
  verification.styles.hasInlineStyles.forEach(el => {
    console.log(`   ${el.tag}: ${el.style}`);
  });
} else {
  console.log('   None (good)');
}

console.log('\n6. GRID LAYOUT:');
console.log(`   Has grid-cols-12: ${verification.grid.sectionClass?.includes('grid-cols-12') ? '✓' : '✗'}`);
console.log(`   Has overflow-x-hidden: ${verification.grid.overflowHidden ? '✓' : '✗'}`);

console.log('\n7. NO IMAGES/SVG:');
console.log(`   img tags: ${verification.assets.imgTags} (should be 0)`);
console.log(`   svg tags: ${verification.assets.svgTags} (should be 0)`);
console.log(`   background-image styles: ${verification.assets.backgroundImages} (should be 0)`);

console.log('\n✅ Verification complete.\n');

await browser.close();
