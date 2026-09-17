import { openEditor, BASE, watchdog } from './docs-lib.mjs';
watchdog(200000);
const { b, pg } = await openEditor(BASE + '/wp-admin/post.php?post=5&action=edit', { width: 1440, height: 900, scale: 2 });
await pg.evaluate(() => { window.onbeforeunload = null; });
const settings = pg.locator('button[aria-label="Settings"]').first();
if ((await settings.getAttribute('aria-pressed')) !== 'true') { await settings.click(); await pg.waitForTimeout(900); }
const tab = pg.locator('[role="tab"]', { hasText: /^Page$/ });
if (await tab.count()) { await tab.first().click(); await pg.waitForTimeout(800); }
console.log('rows:', JSON.stringify(await pg.$$eval('.editor-post-panel__row', r => r.map(x => x.innerText.replace(/\s+/g, ' ').trim()))));
const row = pg.locator('.editor-post-panel__row', { hasText: 'Template' }).first();
await row.locator('button').first().click(); await pg.waitForTimeout(1500);
console.log('menu:', JSON.stringify(await pg.$$eval('[role="menuitem"], [role="menuitemradio"], .components-popover button', m => m.map(x => x.textContent.trim()).filter(Boolean))));
const change = pg.getByRole('menuitem', { name: /Change template|Swap template/ }).first();
if (await change.count()) {
  await change.click(); await pg.waitForTimeout(5000);
  for (const fr of pg.frames()) await fr.evaluate(async () => { if (document.fonts) await document.fonts.ready; }).catch(() => {});
  await pg.waitForTimeout(1500);
  console.log('choices:', JSON.stringify(await pg.$$eval('.components-modal__frame [role="option"], .components-modal__frame .block-editor-block-patterns-list__item', o => o.map(x => x.getAttribute('aria-label') || x.textContent.trim()))));
  await pg.screenshot({ path: 'final/unioncorp-docs-template-choice.jpg', type: 'jpeg', quality: 86 });
} else {
  await pg.screenshot({ path: 'final/unioncorp-docs-template-choice.jpg', type: 'jpeg', quality: 86 });
}
await b.close();
