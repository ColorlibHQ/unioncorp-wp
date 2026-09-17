import { admin, declutter, BASE, watchdog } from './docs-lib.mjs';
watchdog(200000);
const step = async (n, fn) => { try { await fn(); console.log('ok  ', n); } catch (e) { console.log('FAIL', n, e.message.split('\n')[0]); } };
const { b, pg } = await admin({ width: 1280, height: 800, scale: 2 });
const pad = () => pg.evaluate(() => { const w = document.querySelector('.wrap'); if (w) { w.style.padding = '18px 24px 28px'; w.style.margin = '0'; } });

await step('upload-theme', async () => {
  await pg.goto(BASE + '/wp-admin/theme-install.php?browse=upload', { waitUntil: 'domcontentloaded' });
  await pg.waitForTimeout(1500);
  await declutter(pg);
  await pg.evaluate(() => {
    const u = document.querySelector('.upload-theme'); if (u) u.style.display = 'block';
    document.querySelectorAll('.wp-filter, .theme-browser, .no-themes, .theme-install-overlay, .error, .notice').forEach(e => e.remove());
  });
  await pad(); await pg.waitForTimeout(300);
  await (await pg.$('.wrap')).screenshot({ path: 'final/unioncorp-docs-upload-theme.png' });
});

await step('pages-created', async () => {
  await pg.goto(BASE + '/wp-admin/edit.php?post_type=page&orderby=title&order=asc', { waitUntil: 'domcontentloaded' });
  await pg.waitForTimeout(1000);
  await declutter(pg);
  await pg.evaluate(() => document.querySelectorAll('.tablenav.bottom, #screen-options-link-wrap, .search-box, .subsubsub, .tablenav.top').forEach(e => e.remove()));
  // Author and comments columns are noise in a docs shot.
  await pg.evaluate(() => document.querySelectorAll('.column-author, .column-comments').forEach(e => e.remove()));
  await pad(); await pg.waitForTimeout(300);
  await (await pg.$('.wrap')).screenshot({ path: 'final/unioncorp-docs-pages-created.png' });
});
await b.close();
