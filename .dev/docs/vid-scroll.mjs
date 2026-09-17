// Front page, top to bottom, at reading pace: sections fade in, figures count up.
import { browser, record, watchdog } from './docs-lib.mjs';
watchdog(240000);
const { b, pg } = await browser({ width: 1280, height: 800, scale: 1 });
const DEMO = 'https://colorlibhub.com/unioncorp/';
await pg.goto(DEMO + '?cb=' + Date.now(), { waitUntil: 'load', timeout: 90000 });
await pg.evaluate(async () => { if (document.fonts) await document.fonts.ready; });
// Photos above and below the fold decoded before recording, so none pop in late.
await pg.evaluate(async () => { document.querySelectorAll('img').forEach(i => { i.loading = 'eager'; }); await Promise.race([Promise.all([...document.images].map(i => i.decode().catch(() => {}))), new Promise(r => setTimeout(r, 8000))]); });
await pg.evaluate(async () => { for (let y = 0; y < document.documentElement.scrollHeight; y += 600) { scrollTo(0, y); await new Promise(r => setTimeout(r, 150)); } });
await pg.waitForTimeout(1500);
await pg.goto(DEMO + '?cb=' + Date.now(), { waitUntil: 'load', timeout: 90000 });   // fresh page: reveals and counters reset
await pg.evaluate(async () => { if (document.fonts) await document.fonts.ready; });
await pg.waitForTimeout(1200);
const res = await record(pg, 'unioncorp-docs-scroll-tour', async () => {
  await pg.waitForTimeout(1600);
  // Glide down, easing, with pauses on sections worth a look.
  await pg.evaluate(async () => {
    const stops = ['.is-style-unioncorp-card', 'h2', '.unioncorp-video', '.unioncorp-count', '.wp-block-post-template, h2'];
    const target = document.documentElement.scrollHeight - innerHeight;
    const glide = (to, ms) => new Promise(done => {
      const from = scrollY, t0 = performance.now();
      const step = now => { const k = Math.min(1, (now - t0) / ms); const e = k < .5 ? 2 * k * k : 1 - Math.pow(-2 * k + 2, 2) / 2; scrollTo(0, from + (to - from) * e); k < 1 ? requestAnimationFrame(step) : done(); };
      requestAnimationFrame(step);
    });
    const counts = document.querySelector('.unioncorp-count');
    const countsY = counts ? counts.getBoundingClientRect().top + scrollY - innerHeight * 0.45 : target * 0.55;
    await glide(Math.min(countsY, target), 9000);
    await new Promise(r => setTimeout(r, 2600));   // let the figures count up
    await glide(target, 7000);
    await new Promise(r => setTimeout(r, 1200));
  });
}, { width: 1280, height: 800, crf: 27 });
console.log(JSON.stringify(res));
await b.close();
