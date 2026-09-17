// Block editor figures on the fresh install (9491). Nothing is saved.
import { openEditor, BASE, watchdog } from './docs-lib.mjs';
watchdog(600000);
const only = process.argv.slice(2);
const step = async (n, fn) => { if (only.length && !only.includes(n)) return; try { await fn(); console.log('ok  ', n); } catch (e) { console.log('FAIL', n, e.message.split('\n')[0]); for (const br of globalThis.__browsers || []) await br.close().catch(() => {}); } };
const edit = id => BASE + `/wp-admin/post.php?post=${id}&action=edit`;
const HOME = 4, ABOUT = 5; // same ids on 9491 and 9493
const ringCss = '.docs-ring{outline:3px solid #f0b849 !important;outline-offset:2px !important}';
const noSave = pg => pg.evaluate(() => { wp.data.dispatch('core/editor').updateEditorSettings({ autosaveInterval: 100000 }); window.onbeforeunload = null; });

await step('list-view', async () => {
  const { b, pg, f } = await openEditor(edit(HOME), { width: 1440, height: 900, scale: 2 });
  await noSave(pg);
  await pg.locator('button[aria-label="Document Overview"]').first().click();
  await pg.waitForTimeout(1500);
  const labels = await pg.$$eval('.block-editor-list-view-leaf', r => r.map(x => x.getAttribute('aria-level') + ' ' + x.textContent.trim().slice(0, 40)));
  console.log('     top rows:', JSON.stringify(labels.slice(0, 12)));
  // Open the services section and select its heading.
  const rows = pg.locator('.block-editor-list-view-leaf[aria-level="1"]');
  const n = await rows.count();
  let target = -1;
  for (let i = 0; i < n; i++) { if (/services/i.test(await rows.nth(i).textContent())) { target = i; break; } }
  if (target < 0) target = 2;
  const ex = rows.nth(target).locator('.block-editor-list-view__expander');
  if (await ex.count()) { await ex.click({ force: true }); await pg.waitForTimeout(800); }
  await rows.nth(target).locator('a, button.block-editor-list-view-block-select-button').first().click();
  await pg.waitForTimeout(1500);
  await pg.mouse.move(1430, 890);
  await pg.screenshot({ path: 'final/unioncorp-docs-list-view.jpg', type: 'jpeg', quality: 86 });
  await b.close();
});

await step('pattern-inserter', async () => {
  const { b, pg } = await openEditor(edit(ABOUT), { width: 1440, height: 900, scale: 2 });
  await noSave(pg);
  await pg.locator('button[aria-label="Block Inserter"]').first().click(); await pg.waitForTimeout(1500);
  await pg.locator('[role="tab"]', { hasText: 'Patterns' }).first().click(); await pg.waitForTimeout(1500);
  await pg.locator('button, [role="tab"]').filter({ hasText: /^Unioncorp: sections$/ }).first().click();
  await pg.waitForTimeout(5000);
  for (const fr of pg.frames()) await fr.evaluate(async () => { document.querySelectorAll('img').forEach(i => { i.loading = 'eager'; }); await Promise.race([Promise.all([...document.images].map(i => i.decode().catch(() => {}))), new Promise(r => setTimeout(r, 4000))]); }).catch(() => {});
  await pg.waitForTimeout(1500);
  console.log('     patterns:', await pg.$$eval('[role="option"]', o => o.map(x => x.getAttribute('aria-label') || x.textContent.trim()).filter(Boolean).join(' | ')));
  await pg.mouse.move(1430, 890);
  await pg.screenshot({ path: 'final/unioncorp-docs-pattern-inserter.jpg', type: 'jpeg', quality: 86 });
  await b.close();
});

await step('gif-counter', async () => {
  const { b, pg, f } = await openEditor(edit(HOME), { width: 1100, height: 760, scale: 1 });
  await noSave(pg);
  await pg.addStyleTag({ content: ringCss });
  const settings = pg.locator('button[aria-label="Settings"]').first();
  if ((await settings.getAttribute('aria-pressed')) === 'true') { await settings.click(); await pg.waitForTimeout(800); }
  const fig = f.locator('.unioncorp-count').nth(1);
  await fig.scrollIntoViewIfNeeded();
  await f.evaluate(() => document.querySelectorAll('.unioncorp-count')[1].scrollIntoView({ block: 'center' }));
  await pg.waitForTimeout(1200);
  let k = 0; const shot = async (ms = 200) => { await pg.waitForTimeout(ms); await pg.screenshot({ path: `gif/counter-${String(++k).padStart(2, '0')}.png` }); };
  console.log('     before:', await fig.textContent());
  await pg.mouse.move(1090, 750); await shot(400);
  await fig.click(); await shot(500);
  await pg.keyboard.press('ControlOrMeta+A'); await shot(300);
  for (const ch of '12,500') { await pg.keyboard.type(ch, { delay: 40 }); await shot(120); }
  await pg.mouse.move(1090, 750); await shot(500);
  await pg.locator('.editor-post-publish-button, .editor-post-save-draft, button:has-text("Save")').first().evaluate(e => e.classList.add('docs-ring'));
  await shot(300);
  console.log('     after:', await fig.textContent(), '| frames', k);
  await b.close();
});

await step('gif-icon', async () => {
  const { b, pg, f } = await openEditor(edit(HOME), { width: 1100, height: 760, scale: 1 });
  await noSave(pg);
  await pg.addStyleTag({ content: ringCss });
  const settings = pg.locator('button[aria-label="Settings"]').first();
  if ((await settings.getAttribute('aria-pressed')) === 'true') { await settings.click(); await pg.waitForTimeout(800); }
  // The editor does not draw the empty icon span, so find the block by its content.
  const clientId = await pg.evaluate(() => {
    let id = null; const walk = bs => bs.forEach(x => { if (!id && String(x.attributes.content || '').includes('unioncorp-icon--calculator')) id = x.clientId; walk(x.innerBlocks); });
    walk(wp.data.select('core/block-editor').getBlocks()); return id;
  });
  await f.evaluate(id => document.querySelector(`[data-block="${id}"]`).closest('.wp-block-columns').scrollIntoView({ block: 'center' }), clientId);
  await pg.waitForTimeout(1200);
  let k = 0; const shot = async (ms = 250) => { await pg.waitForTimeout(ms); await pg.screenshot({ path: `gif/icon-${String(++k).padStart(2, '0')}.png` }); };
  await pg.mouse.move(1090, 750); await shot(500);
  await pg.evaluate(id => wp.data.dispatch('core/block-editor').selectBlock(id), clientId);
  await f.evaluate(id => document.querySelector(`[data-block="${id}"]`).classList.add('docs-ring'), clientId).catch(() => {});
  await f.evaluate(css => { const st = document.createElement('style'); st.textContent = css; document.head.appendChild(st); }, ringCss);
  await shot(700);
  const options = pg.locator('.block-editor-block-toolbar button[aria-label="Options"]').first();
  await options.click(); await pg.waitForTimeout(600);
  const asHtml = pg.getByRole('menuitem', { name: 'Edit as HTML' });
  await asHtml.evaluate(e => e.classList.add('docs-ring')); await shot(900);
  await asHtml.click(); await pg.waitForTimeout(900);
  const ta = f.locator('textarea').first();
  await ta.evaluate(e => e.scrollIntoView({ block: 'center' }));
  await shot(1100);
  const value = await ta.inputValue();
  const start = value.indexOf('calculator'), end = start + 'calculator'.length;
  await ta.evaluate((e, [s, t]) => { e.focus(); e.setSelectionRange(s, t); }, [start, end]);
  await shot(900);
  for (const ch of 'briefcase') { await pg.keyboard.type(ch, { delay: 40 }); if ('bfe'.includes(ch)) await shot(90); }
  await shot(1400);
  console.log('     textarea now:', (await ta.inputValue()).slice(0, 120), '| frames', k);
  await b.close();
});
