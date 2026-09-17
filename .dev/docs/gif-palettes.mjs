// Browse styles, clicking through palettes, preview on the About section. Nothing saved.
import { openEditor, BASE, watchdog } from './docs-lib.mjs';
watchdog(400000);
if (!/9493/.test(BASE)) { console.log('run with WPBASE=http://127.0.0.1:9493'); process.exit(1); }
const { b, pg } = await openEditor(BASE + '/wp-admin/site-editor.php', { width: 1280, height: 800, scale: 1 });
const nav = label => pg.locator('.edit-site-sidebar-navigation-item, [class*="sidebar-navigation-item"], a, button').filter({ hasText: new RegExp('^\\s*' + label + '\\s*$') }).first();
await nav('Styles').click(); await pg.waitForTimeout(3000);
await pg.getByText('Browse styles', { exact: true }).first().click(); await pg.waitForTimeout(3000);
const cards = pg.locator('.edit-site-global-styles-variations_item, [class*="variations"] [role="button"]');
const labels = await cards.evaluateAll(els => els.map(e => e.getAttribute('aria-label')));
const canvas = () => pg.frames().find(fr => fr.name() === 'editor-canvas');
async function settle() {
  await pg.waitForTimeout(2300);
  const f = canvas(); if (!f) return 'no canvas';
  return f.evaluate(async () => {
    const tile = document.querySelector('.unioncorp-card__icon');
    const section = tile && tile.closest('.wp-block-group.alignfull, .alignfull');
    const t = section || tile;
    if (!t) return 'no target';
    window.scrollTo(0, t.getBoundingClientRect().top + window.scrollY - 20);
    await new Promise(r => setTimeout(r, 500));
    if (document.fonts) await document.fonts.ready;
    return Math.round(window.scrollY);
  });
}
let k = 0;
const shot = async () => { await pg.mouse.move(1270, 790); await pg.waitForTimeout(300); await pg.screenshot({ path: `gif/pal-${String(++k).padStart(2, '0')}.png` }); };
await cards.nth(0).click(); console.log('frame 1', labels[0], await settle()); await shot();
for (const name of ['Emerald', 'Plum', 'Teal', 'Midnight', 'Graphite', 'Default']) {
  const i = labels.indexOf(name);
  await cards.nth(i).click();
  const y = await settle(); await shot();
  console.log('frame', k, name, 'scrollY', y);
}
await b.close();
