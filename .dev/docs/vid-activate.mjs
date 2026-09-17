// Activation, recorded: Themes → Activate → Pages → the finished home page.
import { admin, record, BASE, watchdog } from './docs-lib.mjs';
watchdog(300000);
if (!/9492/.test(BASE)) { console.log('run with WPBASE=http://127.0.0.1:9492'); process.exit(1); }
const { b, pg } = await admin({ width: 1280, height: 800, scale: 1 });
await pg.goto(BASE + '/wp-admin/edit.php?post_type=page', { waitUntil: 'domcontentloaded' });
console.log('pages before:', await pg.$$eval('#the-list .row-title', r => r.map(x => x.textContent.trim())));
await pg.goto(BASE + '/wp-admin/themes.php', { waitUntil: 'domcontentloaded' });
await pg.waitForTimeout(1500);
// Warm the front page's images so the last shot does not load on camera.
const warm = await b.contexts()[0].newPage(); await warm.goto(BASE + '/', { waitUntil: 'load' }).catch(() => {}); await warm.close();
await pg.addStyleTag({ content: '.docs-ring{outline:3px solid #f0b849 !important;outline-offset:3px !important;border-radius:4px}' });
const res = await record(pg, 'unioncorp-docs-activation', async () => {
  await pg.waitForTimeout(1200);
  const card = pg.locator('.theme[data-slug="unioncorp"]');
  await card.scrollIntoViewIfNeeded();
  await card.hover(); await pg.waitForTimeout(900);
  const activate = card.locator('a.activate');
  await activate.evaluate(e => e.classList.add('docs-ring')); await pg.waitForTimeout(900);
  await Promise.all([pg.waitForNavigation({ waitUntil: 'domcontentloaded' }), activate.click()]);
  await pg.waitForTimeout(1800);
  // admin_init also runs the setup, so opening Pages is enough to see the result.
  await pg.goto(BASE + '/wp-admin/edit.php?post_type=page&orderby=title&order=asc', { waitUntil: 'domcontentloaded' });
  await pg.waitForTimeout(2600);
  await pg.goto(BASE + '/', { waitUntil: 'load' });
  await pg.evaluate(() => { document.getElementById('wpadminbar')?.remove(); document.documentElement.style.setProperty('margin-top', '0', 'important'); });
  await pg.waitForTimeout(2200);
  await pg.evaluate(async () => { const glide = (to, ms) => new Promise(done => { const from = scrollY, t0 = performance.now(); const s = n => { const k = Math.min(1, (n - t0) / ms); const e = k < .5 ? 2*k*k : 1 - Math.pow(-2*k+2, 2)/2; scrollTo(0, from + (to - from) * e); k < 1 ? requestAnimationFrame(s) : done(); }; requestAnimationFrame(s); }); await glide(1500, 3500); await new Promise(r => setTimeout(r, 1500)); });
}, { width: 1280, height: 800, crf: 27 });
console.log(JSON.stringify(res));
await pg.goto(BASE + '/wp-admin/edit.php?post_type=page', { waitUntil: 'domcontentloaded' });
console.log('pages after:', await pg.$$eval('#the-list .row-title', r => r.map(x => x.textContent.trim())));
await b.close();
