import { chromium } from 'playwright';

const browser = await chromium.launch({
  headless: true,
  executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
});

const page = await browser.newPage();
await page.goto('http://parsian-music.test/teachers/1', { waitUntil: 'networkidle' });

const bgImages = await page.evaluate(() => {
  return Array.from(document.querySelectorAll('[style*="background"]')).map(el => ({
    tag: el.tagName,
    id: el.id,
    class: el.className,
    style: el.getAttribute('style'),
    text: el.textContent?.substring(0, 50),
  }));
});

console.log('Background-image elements:');
bgImages.forEach(el => {
  console.log(`  ${el.tag}#${el.id}.${el.class} = ${el.style}`);
});

await browser.close();
