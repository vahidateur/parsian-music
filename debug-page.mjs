import { chromium } from 'playwright';
const b = await chromium.launch({ headless:true, executablePath:'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe' });
const p = await b.newPage();
await p.setViewportSize({ width: 1920, height: 1080 });
await p.goto('http://parsian-music.test/login', { waitUntil:'networkidle', timeout:15000 });
await p.waitForTimeout(400);

const gaps = await p.evaluate(() => {
  const rc = el => el?.getBoundingClientRect();
  const px = n => Math.round(n);
  const sections = [...document.querySelectorAll('#login-card-content > *')];
  return sections.map(el => ({
    tag: el.tagName,
    class: el.className.slice(0,40),
    top: px(rc(el).top), bottom: px(rc(el).bottom),
    height: px(rc(el).height),
  }));
});
gaps.forEach(g => console.log(`${g.tag}.${g.class} h=${g.height} top=${g.top} bot=${g.bottom}`));

// Specific gaps
const formR    = await p.$eval('.login-form',    el => el.getBoundingClientRect()).catch(() => null);
const actionsR = await p.$eval('.login-actions', el => el.getBoundingClientRect()).catch(() => null);
const socialR  = await p.$eval('.login-social',  el => el.getBoundingClientRect()).catch(() => null);
if (formR && actionsR) console.log(`\nGap Form→Button: ${Math.round(actionsR.top - formR.bottom)}px`);
if (actionsR && socialR) console.log(`Gap Button→Divider: ${Math.round(socialR.top - actionsR.bottom)}px`);
await b.close();
