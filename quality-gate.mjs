import { chromium } from 'playwright';
import { existsSync, mkdirSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
const __dir = dirname(fileURLToPath(import.meta.url));
const shots = join(__dir, '.screenshots');
if (!existsSync(shots)) mkdirSync(shots, { recursive: true });
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const URL = 'http://parsian-music.test/login';
const ts = Date.now();

const browser = await chromium.launch({ headless:true, executablePath:CHROME });
for (const [w,h] of [[1920,1080],[1366,768],[390,844]]) {
  const page = await browser.newPage();
  await page.setViewportSize({ width:w, height:h });
  await page.goto(URL, { waitUntil:'networkidle' });
  await page.waitForTimeout(400);
  const shotPath = join(shots, `spacing-${w}x${h}-${ts}.png`);
  await page.screenshot({ path: shotPath });
  const d = await page.evaluate(() => {
    const card = document.querySelector('#login-card');
    const r = card.getBoundingClientRect();
    return {
      right: Math.round(r.right),
      left: Math.round(r.left),
      vw: window.innerWidth,
      spaceFromRight: Math.round(window.innerWidth - r.right),
      spaceFromLeft: Math.round(r.left),
    };
  });
  console.log(`${w}×${h}: cardLeft=${d.left} cardRight=${d.right} gapRight=${d.spaceFromRight}px gapLeft=${d.spaceFromLeft}px`);
  console.log(`  Screenshot: ${shotPath}`);
  await page.close();
}
await browser.close();
