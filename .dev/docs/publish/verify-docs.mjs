// Check the Unioncorp docs page: media, videos, contents links, gate, JS errors.
import { chromium } from 'playwright';
import { readFileSync } from 'fs';
const d = JSON.parse(readFileSync('preview.json', 'utf8'));
const url = process.argv[2] || d.preview_url;
const b = await chromium.launch();
setTimeout(() => { console.log('WATCHDOG'); process.exit(2); }, 240000).unref();
const ctx = await b.newContext({ viewport: { width: 1280, height: 900 } });
if (!process.argv[2]) await ctx.addCookies([{ name: d.cookie_name, value: d.cookie_value, domain: 'colorlib.com', path: '/', secure: true, httpOnly: true }]);
const p = await ctx.newPage();
const errors = []; p.on('pageerror', e => errors.push(String(e).slice(0, 140)));
const r = await p.goto(url + (url.includes('?') ? '&' : '?') + 'cb=' + Date.now(), { waitUntil: 'load', timeout: 120000 });
await p.evaluate(async () => { document.querySelectorAll('img').forEach(i => { i.loading = 'eager'; }); for (let y = 0; y < document.body.scrollHeight; y += 700) { scrollTo(0, y); await new Promise(r => setTimeout(r, 80)); } scrollTo(0, 0); await Promise.race([Promise.all([...document.images].map(i => i.decode().catch(() => {}))), new Promise(r => setTimeout(r, 15000))]); });
const info = await p.evaluate(() => {
  const figs = [...document.querySelectorAll('.uc-docs-fig img')];
  return {
    title: document.title,
    headings: [...document.querySelectorAll('.vcex-heading')].map(h => h.textContent.trim()),
    images: figs.length, brokenImages: figs.filter(i => !i.naturalWidth).map(i => i.src.split('/').pop()), missingAlt: figs.filter(i => !i.alt).length,
    videos: document.querySelectorAll('video.uc-docs-video').length,
    buttons: [...document.querySelectorAll('a.vc_btn3')].map(a => a.textContent.trim() + ' → ' + a.getAttribute('href')),
    gateTriggers: document.querySelectorAll('a[href^="https://updates.colorlib.com/download/theme/"]').length,
    tocLinks: document.querySelectorAll('.uc-docs-toc a').length,
    tocMissing: [...document.querySelectorAll('.uc-docs-toc a, .uc-docs a[href^="#"]')].map(a => a.getAttribute('href').slice(1)).filter(id => id && !document.getElementById(id)),
    overflowX: document.documentElement.scrollWidth > innerWidth,
  };
});
console.log('status', r.status()); console.log(JSON.stringify(info, null, 1));
// Each video should start once scrolled into view.
const vids = p.locator('video.uc-docs-video');
for (let i = 0; i < await vids.count(); i++) {
  await vids.nth(i).scrollIntoViewIfNeeded();
  await p.waitForTimeout(4500);
  console.log('video', i, JSON.stringify(await vids.nth(i).evaluate(v => ({ file: v.currentSrc.split('/').pop(), playing: !v.paused, t: +v.currentTime.toFixed(1), ready: v.readyState, w: v.videoWidth }))));
}
const dl = p.locator('a[href^="https://updates.colorlib.com/download/theme/"]').first();
await dl.scrollIntoViewIfNeeded(); await dl.click({ noWaitAfter: true }).catch(() => {}); await p.waitForTimeout(2500);
console.log('gate popup shown:', await p.evaluate(() => { const e = document.querySelector('.cfd-popup'); return !!e && getComputedStyle(e).display !== 'none'; }), '| js errors:', errors);
await b.close();
