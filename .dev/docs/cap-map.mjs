import { chromium } from 'playwright';
import { writeFileSync } from 'fs';
const b = await chromium.launch();
setTimeout(() => process.exit(2), 200000).unref();
const pg = await b.newPage({ viewport: { width: 1440, height: 900 } });
await pg.goto('https://colorlibhub.com/unioncorp/?cb=' + Date.now(), { waitUntil: 'load', timeout: 90000 });
await pg.addStyleTag({ content: '.unioncorp-reveal{opacity:1!important;transform:none!important;transition:none!important}' });
await pg.evaluate(async () => { document.querySelectorAll('img').forEach(i => { i.loading = 'eager'; }); for (let y = 0; y < document.body.scrollHeight; y += 700) { scrollTo(0, y); await new Promise(r => setTimeout(r, 120)); } scrollTo(0, 0); await Promise.race([Promise.all([...document.images].map(i => i.decode().catch(() => {}))), new Promise(r => setTimeout(r, 8000))]); if (document.fonts) await document.fonts.ready; });
await pg.waitForTimeout(3500); // counters finish
const names = [
  ['Hero', 'section pattern'], ['About: introduction', 'section pattern'], ['Services: eight cards', 'section pattern'],
  ['Why us', 'section pattern · video button'], ['Case studies: gallery', 'section pattern · lightbox'], ['Statistics', 'section pattern · counters'],
  ['Team: eight profiles', 'section pattern'], ['Testimonials: three quotes', 'section pattern'], ['Latest posts', 'section pattern'], ['Call to action band', 'section pattern'],
];
const result = await pg.evaluate((names) => {
  const st = document.createElement('style');
  st.textContent = '.docs-tag{position:absolute;z-index:9999;left:18px;display:flex;align-items:center;gap:10px;background:#f0b849;color:#1d2327;font:700 30px/1 Poppins,sans-serif;padding:10px 16px 10px 10px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.25)}.docs-tag b{display:inline-block;background:#1d2327;color:#fff;border-radius:50%;width:44px;height:44px;line-height:44px;text-align:center;font-size:24px}.docs-tag small{font:400 24px/1 Poppins,sans-serif}';
  document.head.appendChild(st);
  const content = document.querySelector('.wp-block-post-content');
  const sections = [...content.children].filter(e => e.offsetHeight > 80);
  const tag = (el, n, name, where, dy = 14, left = 18) => { const r = el.getBoundingClientRect(); const t = document.createElement('div'); t.className = 'docs-tag'; t.innerHTML = `<b>${n}</b><span>${name} <small>— ${where}</small></span>`; t.style.top = (r.top + scrollY + dy) + 'px'; t.style.left = left + 'px'; document.body.appendChild(t); };
  const header = document.querySelector('header.wp-block-template-part');
  tag(header, 1, 'Header', 'template part', 38, 330); document.querySelector('.docs-tag').style.transform = 'scale(.85)'; document.querySelector('.docs-tag').style.transformOrigin = '0 0';
  sections.forEach((s, i) => names[i] && tag(s, i + 2, names[i][0], names[i][1]));
  const footer = document.querySelector('footer.wp-block-template-part');
  tag(footer, sections.length + 2, 'Footer', 'template part');
  return { sections: sections.length, tops: [...sections.map(s => Math.round(s.getBoundingClientRect().top + scrollY)), Math.round(footer.getBoundingClientRect().top + scrollY)] };
}, names);
console.log(JSON.stringify(result));
writeFileSync('shots/map-tops.json', JSON.stringify(result.tops));
await pg.screenshot({ path: 'shots/map-full.png', fullPage: true });
await b.close();
