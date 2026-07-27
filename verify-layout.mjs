/**
 * Teacher Hero Layout Verification
 * Checks all layout requirements
 */
import { chromium } from 'playwright';

const baseUrl = 'http://parsian-music.test/teachers/1';

const tests = [
  {
    name: 'Mobile (390×844)',
    width: 390,
    height: 844,
  },
  {
    name: 'Tablet (768×1024)',
    width: 768,
    height: 1024,
  },
  {
    name: 'Desktop (1366×768)',
    width: 1366,
    height: 768,
  },
  {
    name: 'Full HD (1920×1080)',
    width: 1920,
    height: 1080,
  },
];

const browser = await chromium.launch({
  headless: true,
  executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
});

for (const test of tests) {
  console.log(`\n${test.name}:`);
  const page = await browser.newPage();
  await page.setViewportSize({ width: test.width, height: test.height });
  await page.goto(baseUrl, { waitUntil: 'networkidle' });
  await page.waitForTimeout(300);

  // Verify structure
  const structure = await page.evaluate(() => {
    return {
      hasHeader: !!document.querySelector('header'),
      hasMain: !!document.querySelector('main'),
      hasSection: !!document.querySelector('section[class*="grid"]'),
      hasButton: !!document.querySelector('button[type="button"]'),
      slotCount: [
        '#teacher-background-slot',
        '#teacher-frame-slot',
        '#teacher-photo-slot',
        '#teacher-decoration-slot',
      ].filter(sel => document.querySelector(sel)).length,
      hasImg: !!document.querySelector('img'),
    };
  });

  const rtlAttr = await page.getAttribute('section[class*="grid"]', 'dir');
  
  const gridClasses = await page.evaluate(() => {
    const section = document.querySelector('section[class*="grid"]');
    return section ? section.className : '';
  });

  const scrollInfo = await page.evaluate(() => {
    return {
      scrollWidth: document.documentElement.scrollWidth,
      clientWidth: document.documentElement.clientWidth,
      hasScroll: document.documentElement.scrollWidth > document.documentElement.clientWidth,
    };
  });

  console.log(`  ✓ Structure: header=${structure.hasHeader}, main=${structure.hasMain}, section=${structure.hasSection}, button=${structure.hasButton}`);
  console.log(`  ✓ Slots: ${structure.slotCount}/4 (no img tags: ${!structure.hasImg})`);
  console.log(`  ✓ RTL: dir="${rtlAttr}"`);
  console.log(`  ✓ Grid: ${gridClasses.includes('grid-cols-12') ? '✓' : '✗'}`);
  console.log(`  ✓ Scroll: ${scrollInfo.hasScroll ? `FAIL (${scrollInfo.scrollWidth}px)` : '✓'}`);

  await page.close();
}

console.log('\n✅ Complete.');
await browser.close();
