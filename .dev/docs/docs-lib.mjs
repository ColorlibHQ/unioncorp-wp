import { chromium } from 'playwright';

// Isolated Playground by default: local-wp is shared with other sessions.
export const BASE = process.env.WPBASE || 'http://127.0.0.1:9491';
const USER = process.env.WPUSER || 'admin';
const PASS = process.env.WPPASS || 'password';

/** Kill the process if a run hangs — a stuck editor must never stall the session. */
export function watchdog(ms = 100000) {
  const t = setTimeout(() => { console.error('WATCHDOG: exceeded ' + ms + 'ms'); process.exit(2); }, ms);
  t.unref();
}

export async function browser({ width = 1440, height = 900, scale = 2 } = {}) {
  const b = await chromium.launch();
  const c = await b.newContext({ viewport: { width, height }, deviceScaleFactor: scale });
  const pg = await c.newPage();
  pg.setDefaultTimeout(20000);
  return { b, c, pg };
}

export async function admin(opts = {}) {
  const r = await browser(opts);
  await r.pg.goto(BASE + '/wp-admin/', { waitUntil: 'domcontentloaded' });
  if (await r.pg.$('#user_login')) {
    await r.pg.fill('#user_login', USER);
    await r.pg.fill('#user_pass', PASS);
    await Promise.all([r.pg.waitForNavigation({ waitUntil: 'domcontentloaded' }), r.pg.click('#wp-submit')]);
  }
  return r;
}

/** Strip admin chrome that is noise in a documentation shot. */
export async function declutter(pg, { keepMenu = false } = {}) {
  await pg.evaluate((keepMenu) => {
    document.querySelectorAll('.notice, .update-nag, #screen-meta, #screen-meta-links, #wpfooter, #wpadminbar').forEach(e => e.remove());
    if (!keepMenu) {
      document.querySelectorAll('#adminmenuback, #adminmenuwrap').forEach(e => e.remove());
      const w = document.querySelector('#wpcontent');
      if (w) { w.style.marginLeft = '0'; w.style.paddingLeft = '20px'; }
    }
    document.documentElement.style.setProperty('margin-top', '0', 'important');
    document.querySelector('#wpbody-content')?.style.setProperty('padding-bottom', '0');
  }, keepMenu);
  await pg.waitForTimeout(250);
}

/** Make every image eager and decoded — full-page shots photograph lazy images as blank. */
export async function eager(pg) {
  await pg.evaluate(async () => {
    document.querySelectorAll('img').forEach(i => { i.loading = 'eager'; i.setAttribute('decoding', 'sync'); });
    document.querySelector('#wpadminbar')?.remove();
    document.documentElement.style.setProperty('margin-top', '0', 'important');
    window.scrollTo(0, document.body.scrollHeight); await new Promise(r => setTimeout(r, 900));
    window.scrollTo(0, 0); await new Promise(r => setTimeout(r, 400));
    await Promise.all([...document.images].map(i => i.decode().catch(() => {})));
    if (document.fonts) await document.fonts.ready;
  });
  await pg.waitForTimeout(500);
}

/** Open the block editor or Site Editor and wait until its canvas has painted. */
export async function openEditor(url, opts = {}) {
  const r = await admin(opts);
  const { pg } = r;
  await pg.goto(url, { waitUntil: 'domcontentloaded' });
  await pg.waitForSelector('iframe[name="editor-canvas"]', { timeout: 45000 }).catch(() => {});
  // Close only real modals (welcome guide) — a bare "Close" selector also matches the sidebar.
  for (let i = 0; i < 3; i++) {
    await pg.waitForTimeout(900);
    const close = await pg.$('.components-modal__frame button[aria-label="Close"]');
    if (!close) break;
    await close.click().catch(() => {});
  }
  const f = pg.frames().find(fr => fr.name() === 'editor-canvas');
  if (f) {
    await f.waitForSelector('.wp-block', { timeout: 30000 }).catch(() => {});
    await f.evaluate(async () => {
      document.querySelectorAll('img').forEach(i => { i.loading = 'eager'; });
      await Promise.all([...document.images].map(i => i.decode().catch(() => {})));
    }).catch(() => {});
  }
  await pg.evaluate(() => {
    document.querySelector('#wpadminbar')?.remove();
    document.documentElement.style.setProperty('margin-top', '0', 'important');
    document.body.classList.remove('admin-bar');
  });
  await pg.waitForTimeout(1500);
  return { ...r, f };
}

/** Thumbnail an image for visual review. */
export async function preview() {}

/** Open the Customizer, optionally at a panel or section, with the preview painted. */
export async function customizer(opts = {}, focus = null) {
  const r = await admin(opts);
  const { pg } = r;
  const q = focus ? `?autofocus[${focus.type}]=${focus.id}` : '';
  await pg.goto(BASE + '/wp-admin/customize.php' + q, { waitUntil: 'domcontentloaded' });
  await pg.waitForFunction(() => window.wp && wp.customize && wp.customize.previewer && wp.customize.state && wp.customize.state('processing').get() === 0 && document.querySelector('#customize-preview iframe'), null, { timeout: 90000 });
  await pg.waitForTimeout(2500);
  await settlePreview(pg);
  return r;
}

/** Wait for the preview iframe to finish loading and decode its images. */
export async function settlePreview(pg) {
  const f = pg.frameLocator('#customize-preview iframe');
  await f.locator('body').waitFor({ timeout: 60000 });
  await pg.evaluate(async () => {
    const doc = document.querySelector('#customize-preview iframe')?.contentDocument;
    if (!doc) return;
    doc.querySelectorAll('img').forEach(i => { i.loading = 'eager'; });
    await Promise.all([...doc.images].map(i => i.decode().catch(() => {})));
    if (doc.fonts) await doc.fonts.ready;
  }).catch(() => {});
  await pg.waitForTimeout(800);
}

/** Scroll the Customizer preview so an element sits near the top. */
export async function previewScrollTo(pg, selector, offset = 0) {
  await pg.evaluate(([sel, off]) => {
    const doc = document.querySelector('#customize-preview iframe').contentDocument;
    const el = doc.querySelector(sel);
    if (el) doc.defaultView.scrollTo(0, el.getBoundingClientRect().top + doc.defaultView.scrollY - off);
  }, [selector, offset]);
  await pg.waitForTimeout(700);
}

/**
 * Render the Customizer preview at a desktop width and scale it into the pane,
 * so a 1280px window still shows the site's desktop layout beside readable
 * controls. Hides the edit-shortcut pencils unless asked to keep them.
 */
export async function desktopPreview(pg, { width = 1320, pencils = false } = {}) {
  const pane = await pg.evaluate(() => document.querySelector('#customize-preview').getBoundingClientRect().width);
  const s = pane / width;
  await pg.addStyleTag({ content: `#customize-preview{overflow:hidden}#customize-preview iframe{width:${100 / s}% !important;height:${100 / s}% !important;transform:scale(${s});transform-origin:0 0}` });
  if (!pencils) {
    const hide = () => pg.evaluate(() => { const d = document.querySelector('#customize-preview iframe')?.contentDocument; if (d && !d.getElementById('docs-no-pencils')) { const st = d.createElement('style'); st.id = 'docs-no-pencils'; st.textContent = '.customize-partial-edit-shortcut{display:none !important}'; d.head.appendChild(st); } }).catch(() => {});
    await hide();
    pg.__hidePencils = hide;
  }
  await pg.waitForTimeout(600);
}

/** After a preview refresh: wait, decode, and re-hide the pencils. */
export async function afterRefresh(pg) {
  await pg.waitForFunction(() => wp.customize.state('processing').get() === 0, null, { timeout: 60000 }).catch(() => {});
  await pg.waitForTimeout(2500);
  await settlePreview(pg);
  if (pg.__hidePencils) await pg.__hidePencils();
}

/**
 * Record the page in real time with Chromium's screencast and encode an MP4.
 *
 * Screenshots in a loop run slower than the page, so scroll reveals and count-up
 * animations would play back sped up and uneven. Screencast frames carry their
 * own timestamps; ffmpeg's concat demuxer turns those into per-frame durations.
 */
export async function record(pg, name, fn, { width = 1280, height = 800, crf = 26, fps = 30 } = {}) {
  const { mkdirSync, writeFileSync, rmSync } = await import('fs');
  const { execFileSync } = await import('child_process');
  const dir = `frames/${name}`;
  rmSync(dir, { recursive: true, force: true });
  mkdirSync(dir, { recursive: true });
  const cdp = await pg.context().newCDPSession(pg);
  const frames = [];
  cdp.on('Page.screencastFrame', async ({ data, metadata, sessionId }) => {
    const file = `${dir}/${String(frames.length).padStart(5, '0')}.jpg`;
    writeFileSync(file, Buffer.from(data, 'base64'));
    frames.push({ file, t: metadata.timestamp });
    await cdp.send('Page.screencastFrameAck', { sessionId }).catch(() => {});
  });
  await cdp.send('Page.startScreencast', { format: 'jpeg', quality: 92, maxWidth: width, maxHeight: height, everyNthFrame: 1 });
  const started = Date.now() / 1000;
  await fn();
  await pg.waitForTimeout(400);
  await cdp.send('Page.stopScreencast');
  const ended = Date.now() / 1000;
  if (frames.length < 2) throw new Error('screencast produced ' + frames.length + ' frames');
  // A static screen sends no frames, so the last frame holds until the end.
  let list = '';
  frames.forEach((f, i) => {
    const next = i + 1 < frames.length ? frames[i + 1].t : f.t + Math.max(0.04, ended - started - (f.t - frames[0].t));
    list += `file '${f.file.split('/').pop()}'\nduration ${Math.max(0.01, next - f.t).toFixed(4)}\n`;
  });
  list += `file '${frames[frames.length - 1].file.split('/').pop()}'\n`;
  writeFileSync(`${dir}/list.txt`, list);
  const ff = (await import('fs')).readFileSync('.ffmpeg', 'utf8').trim();
  mkdirSync('final', { recursive: true });
  execFileSync(ff, ['-y', '-loglevel', 'error', '-f', 'concat', '-safe', '0', '-i', `${dir}/list.txt`,
    '-vf', `fps=${fps},scale=${width}:-2:flags=lanczos:in_range=full:out_range=tv,format=yuv420p`, '-pix_fmt', 'yuv420p', '-color_range', 'tv',
    '-c:v', 'libx264', '-preset', 'slow', '-crf', String(crf), '-movflags', '+faststart', '-an', `final/${name}.mp4`]);
  // Poster: the first frame, for the <video> element before it loads.
  execFileSync(ff, ['-y', '-loglevel', 'error', '-i', frames[0].file, '-q:v', '3', `final/${name}-poster.jpg`]);
  return { frames: frames.length, seconds: +(frames[frames.length - 1].t - frames[0].t).toFixed(2) };
}
