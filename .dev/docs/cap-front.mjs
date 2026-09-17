// Front-end figures from the live demo, logged out.
import { browser, watchdog } from './docs-lib.mjs';
watchdog(700000);
const DEMO = 'https://colorlibhub.com/unioncorp/';
const only = process.argv.slice(2);
const step = async (n, fn) => { if (only.length && !only.includes(n)) return; let br; try { await fn(r => (br = r)); console.log('ok  ', n); } catch (e) { console.log('FAIL', n, e.message.split('\n')[0]); } finally { if (br) await br.close().catch(() => {}); } };
const ringCss = '.docs-ring{outline:3px solid #f0b849 !important;outline-offset:4px !important;border-radius:6px !important}';
async function ready(pg, url) {
  await pg.goto(url + (url.includes('?') ? '&' : '?') + 'cb=' + Date.now(), { waitUntil: 'load', timeout: 90000 });
  await pg.addStyleTag({ content: ringCss + ' .unioncorp-reveal{opacity:1!important;transform:none!important;transition:none!important}' });
  await pg.evaluate(async () => { document.querySelectorAll('img').forEach(i => { i.loading = 'eager'; }); await Promise.race([Promise.all([...document.images].map(i => i.decode().catch(() => {}))), new Promise(r => setTimeout(r, 8000))]); if (document.fonts) await document.fonts.ready; });
  await pg.waitForTimeout(800);
}

await step('dark-mode', async keep => {
  const r = await browser({ width: 1200, height: 800, scale: 1 }); keep(r.b); const { pg } = r;
  await ready(pg, DEMO);
  const toggle = pg.locator('.unioncorp-scheme-toggle a').first();
  const ring = on => toggle.evaluate((e, on) => e.closest('.wp-block-button').classList.toggle('docs-ring', on), on);
  let k = 0; const shot = async (ms = 250) => { await pg.waitForTimeout(ms); await pg.screenshot({ path: `gif/dark-${++k}.png` }); };
  await pg.evaluate(() => scrollTo(0, 560)); await pg.waitForTimeout(300);
  await pg.evaluate(() => scrollTo(0, 0)); await shot(400);
  await ring(true); await shot();
  await toggle.click(); await pg.waitForTimeout(900); await shot();
  await ring(false);
  await pg.evaluate(async () => { for (let y = 0; y <= 700; y += 70) { scrollTo(0, y); await new Promise(r => setTimeout(r, 30)); } }); await shot(500);
  await pg.evaluate(() => scrollTo(0, 0)); await ring(true); await shot(400);
  await toggle.click(); await pg.waitForTimeout(900); await ring(false); await shot();
  console.log('     scheme classes now:', await pg.evaluate(() => document.documentElement.className));
  // Still: the same view light and dark, side by side.
  await pg.evaluate(() => scrollTo(0, 620)); await pg.waitForTimeout(500);
  await pg.screenshot({ path: 'shots/still-light.png' });
  await toggle.evaluate(e => e.click()); await pg.waitForTimeout(900);
  await pg.screenshot({ path: 'shots/still-dark.png' });
  await toggle.evaluate(e => e.click());
});

await step('video-popup', async keep => {
  const r = await browser({ width: 1200, height: 800, scale: 1 }); keep(r.b); const { pg } = r;
  await ready(pg, DEMO);
  const btn = pg.locator('.unioncorp-video a').first();
  // The demo still has 1.1.1's private video; show what 1.1.2 links to.
  // Swap in a listener-free clone, then run the script again so only the new link is bound.
  await btn.evaluate((e, vid) => { const c = e.cloneNode(true); c.href = 'https://www.youtube.com/watch?v=' + vid; e.replaceWith(c); }, process.env.VID || 'FkJT-6Ta60s');
  await pg.evaluate(() => { const s = document.createElement('script'); s.src = document.querySelector('script[src*="interactions.js"]').src + '&rebind=' + Date.now(); document.body.appendChild(s); });
  await pg.waitForTimeout(1500);
  await btn.evaluate(e => e.closest('.wp-block-group.alignfull, .wp-block-cover').scrollIntoView({ block: 'center' }));
  await pg.waitForTimeout(800);
  let k = 0; const shot = async (ms = 250) => { await pg.waitForTimeout(ms); await pg.screenshot({ path: `gif/video-${++k}.png` }); };
  await pg.mouse.move(1190, 790); await shot(400);
  await btn.evaluate(e => e.closest('.wp-block-button').classList.add('docs-ring')); await shot();
  await btn.click(); await pg.waitForTimeout(1200); await shot();
  await pg.waitForTimeout(3500); await shot();
  console.log('     dialog open:', await pg.locator('dialog.unioncorp-video-dialog[open]').count(), '| iframe:', await pg.locator('dialog iframe').getAttribute('src').catch(() => 'none'));
  await pg.keyboard.press('Escape'); await pg.waitForTimeout(700);
  await btn.evaluate(e => e.closest('.wp-block-button').classList.remove('docs-ring')); await shot(400);
});

await step('contact', async keep => {
  const r = await browser({ width: 1280, height: 900, scale: 2 }); keep(r.b); const { pg } = r;
  await ready(pg, DEMO + 'contact/');
  await pg.waitForTimeout(3000); // map tiles
  const form = pg.locator('.unioncorp-enquiry').first();
  const section = form.locator('xpath=ancestor::div[contains(@class,"alignfull") or contains(@class,"wp-block-columns")][1]');
  await section.scrollIntoViewIfNeeded(); await pg.waitForTimeout(1200);
  await section.screenshot({ path: 'final/unioncorp-docs-contact.jpg', type: 'jpeg', quality: 86 });
  const map = pg.locator('iframe.unioncorp-map').first();
  console.log('     map:', await map.count(), '| form fields:', await pg.$$eval('.unioncorp-enquiry label', l => l.map(x => x.textContent.trim()).join(' | ')));
  await map.scrollIntoViewIfNeeded(); await pg.waitForTimeout(2500);
  await map.screenshot({ path: 'shots/contact-map.png' });
});
