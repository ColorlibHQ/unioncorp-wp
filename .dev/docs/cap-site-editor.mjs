// Site Editor figures on the fixed-theme install (9493). Nothing is saved.
import { openEditor, BASE, watchdog } from './docs-lib.mjs';
watchdog(900000);
if (!/9493/.test(BASE)) { console.log('run with WPBASE=http://127.0.0.1:9493'); process.exit(1); }
const only = process.argv.slice(2);
const step = async (n, fn) => { if (only.length && !only.includes(n)) return; let br; try { await fn(r => (br = r)); console.log('ok  ', n); } catch (e) { console.log('FAIL', n, e.message.split('\n')[0]); } finally { if (br) await br.close().catch(() => {}); } };
const nav = (pg, label) => pg.locator('.edit-site-sidebar-navigation-item, [class*="sidebar-navigation-item"], a, button').filter({ hasText: new RegExp('^\\s*' + label + '\\s*$') }).first();
async function dismissGuide(pg) { for (let i = 0; i < 3; i++) { await pg.waitForTimeout(1200); if (!(await pg.$('.components-guide, .components-modal__frame'))) return; await pg.keyboard.press('Escape'); } }
const canvas = pg => pg.frames().find(fr => fr.name() === 'editor-canvas');
async function decode(pg) { const f = canvas(pg); if (f) await f.evaluate(async () => { document.querySelectorAll('img').forEach(i => { i.loading = 'eager'; }); await Promise.race([Promise.all([...document.images].map(i => i.decode().catch(() => {}))), new Promise(r => setTimeout(r, 5000))]); if (document.fonts) await document.fonts.ready; }).catch(() => {}); }

await step('template-parts', async keep => {
  const r = await openEditor(BASE + '/wp-admin/site-editor.php?p=%2Fpattern&categoryId=uncategorized&postType=wp_template_part', { width: 1440, height: 900, scale: 2 }); keep(r.b);
  const { pg } = r; await dismissGuide(pg);
  const all = pg.getByText('All template parts', { exact: true }).first();
  if (await all.count()) { await all.click(); await pg.waitForTimeout(3500); }
  await pg.waitForTimeout(3000);
  for (const fr of pg.frames()) await fr.evaluate(async () => { if (document.fonts) await document.fonts.ready; }).catch(() => {});
  console.log('     url', pg.url());
  await pg.screenshot({ path: 'final/unioncorp-docs-template-parts.jpg', type: 'jpeg', quality: 86 });
});

await step('edit-header', async keep => {
  const r = await openEditor(BASE + '/wp-admin/site-editor.php?p=%2Fwp_template_part%2Funioncorp%2F%2Fheader&canvas=edit', { width: 1440, height: 900, scale: 2 }); keep(r.b);
  const { pg } = r; await dismissGuide(pg); await decode(pg); await pg.waitForTimeout(2000);
  console.log('     url', pg.url(), '| blocks', await canvas(pg)?.evaluate(() => document.querySelectorAll('.wp-block').length));
  await pg.screenshot({ path: 'final/unioncorp-docs-edit-header.jpg', type: 'jpeg', quality: 86 });
});

await step('navigation', async keep => {
  const r = await openEditor(BASE + '/wp-admin/site-editor.php', { width: 1440, height: 900, scale: 2 }); keep(r.b);
  const { pg } = r; await dismissGuide(pg);
  await nav(pg, 'Navigation').click(); await pg.waitForTimeout(3500);
  const primary = pg.getByText('Primary', { exact: true }).first();
  if (await primary.count()) { await primary.click(); await pg.waitForTimeout(3500); }
  await decode(pg); await pg.waitForTimeout(1200);
  console.log('     url', pg.url());
  await pg.screenshot({ path: 'final/unioncorp-docs-navigation.jpg', type: 'jpeg', quality: 86 });
});

await step('styles', async keep => {
  const r = await openEditor(BASE + '/wp-admin/site-editor.php', { width: 1440, height: 900, scale: 2 }); keep(r.b);
  const { pg } = r; await dismissGuide(pg);
  await nav(pg, 'Styles').click(); await pg.waitForTimeout(4000);
  await decode(pg); await pg.waitForTimeout(1200);
  await pg.screenshot({ path: 'final/unioncorp-docs-styles.jpg', type: 'jpeg', quality: 86 });
  const browse = pg.getByText('Browse styles', { exact: true }).first();
  if (await browse.count()) { await browse.click(); await pg.waitForTimeout(4500); }
  else console.log('     no Browse styles button');
  await decode(pg);
  for (const fr of pg.frames()) await fr.evaluate(async () => { if (document.fonts) await document.fonts.ready; }).catch(() => {});
  await pg.waitForTimeout(2000);
  console.log('     variation labels:', await pg.$$eval('.edit-site-global-styles-variations_item, [class*="variations"] [role="button"]', e => e.map(x => x.getAttribute('aria-label')).join(' | ')));
  await pg.screenshot({ path: 'final/unioncorp-docs-browse-styles.jpg', type: 'jpeg', quality: 86 });
});
