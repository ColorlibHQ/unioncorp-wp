// Changing an icon in 1.1.2: select the tile, Advanced → Additional CSS class(es).
import { admin, openEditor, BASE, watchdog } from './docs-lib.mjs';
watchdog(300000);
if (!/9493/.test(BASE)) { console.log('run with WPBASE=http://127.0.0.1:9493'); process.exit(1); }
const ids = await (async () => { const { b, pg } = await admin({ width: 1200, height: 800, scale: 1 }); await pg.goto(BASE + '/wp-admin/edit.php?post_type=page', { waitUntil: 'domcontentloaded' }); const r = await pg.$$eval('#the-list tr', rows => Object.fromEntries(rows.map(x => [x.querySelector('.row-title')?.textContent.trim(), x.id.replace('post-', '')]))); await b.close(); return r; })();
console.log('ids', JSON.stringify(ids));
const { b, pg, f } = await openEditor(BASE + `/wp-admin/post.php?post=${ids.Home}&action=edit`, { width: 1180, height: 780, scale: 1 });
await pg.evaluate(() => { wp.data.dispatch('core/editor').updateEditorSettings({ autosaveInterval: 100000 }); window.onbeforeunload = null; });
const ringCss = '.docs-ring{outline:3px solid #f0b849 !important;outline-offset:2px !important}';
await pg.addStyleTag({ content: ringCss });
// Placeholder check across every icon tile.
const tiles = await f.$$eval('.unioncorp-card__icon', els => els.map(e => ({ text: e.textContent.trim(), ph: e.getAttribute('aria-label') || e.dataset.placeholder || '', before: getComputedStyle(e, '::before').maskImage || getComputedStyle(e, '::before').webkitMaskImage })));
console.log('tiles:', tiles.length, '| with visible text:', tiles.filter(t => t.text).length, '| drawing an icon:', tiles.filter(t => /url\(/.test(String(t.before))).length);
const tile = f.locator('.unioncorp-card__icon.unioncorp-icon--calculator').first();
await tile.evaluate(e => e.closest('.wp-block-columns').scrollIntoView({ block: 'center' }));
await pg.waitForTimeout(1200);
await pg.screenshot({ path: 'shots/editor-tiles-fixed.png' });
let k = 0; const shot = async (ms = 250) => { await pg.waitForTimeout(ms); await pg.screenshot({ path: `gif/icon2-${String(++k).padStart(2, '0')}.png` }); };
await pg.mouse.move(1170, 770); await shot(500);
await tile.click({ position: { x: 30, y: 30 } }); await shot(700);
const settings = pg.locator('button[aria-label="Settings"]').first();
if ((await settings.getAttribute('aria-pressed')) !== 'true') { await settings.click(); await pg.waitForTimeout(900); }
const blockTab = pg.locator('[role="tab"]', { hasText: /^Block$/ });
if (await blockTab.count()) { await blockTab.first().click(); await pg.waitForTimeout(500); }
await tile.evaluate(e => e.closest('.wp-block-columns').scrollIntoView({ block: 'center' }));
await shot(700);
const adv = pg.locator('button', { hasText: /^Advanced$/ }).first();
await adv.scrollIntoViewIfNeeded(); await adv.click(); await pg.waitForTimeout(700);
const field = pg.getByLabel('Additional CSS class(es)').first();
await field.scrollIntoViewIfNeeded();
await field.evaluate(e => e.classList.add('docs-ring'));
await shot(900);
const val = await field.inputValue(); console.log('class field:', val);
const s0 = val.indexOf('calculator');
await field.evaluate((e, [a, z]) => { e.focus(); e.setSelectionRange(a, z); }, [s0, s0 + 'calculator'.length]);
await shot(800);
for (const ch of 'briefcase') { await pg.keyboard.type(ch, { delay: 45 }); if ('bfe'.includes(ch)) await shot(90); }
await field.evaluate(e => e.classList.remove('docs-ring'));
await pg.mouse.move(700, 770);
await pg.waitForTimeout(700);
await shot(1600);
const now = f.locator('.unioncorp-card__icon.unioncorp-icon--briefcase').first();
console.log('frames', k, '| tile now briefcase:', await now.count());
await b.close();
