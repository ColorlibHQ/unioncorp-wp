import { chromium } from 'playwright';
const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 1152, height: 400 }, deviceScaleFactor: 2 });
await p.goto('file://' + process.cwd() + '/shots/icons.html'); await p.waitForTimeout(800);
await (await p.$('body')).screenshot({ path: 'final/unioncorp-docs-icons.png' });
await b.close();
