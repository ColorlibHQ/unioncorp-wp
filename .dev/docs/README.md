# Documentation page, and every screenshot, animation and video on it

Builds https://colorlib.com/wp/themes/unioncorp/documentation/ (a child of the
product page). Nothing here ships in the theme zip: `.dev` is excluded by
`build-zip.sh`.

## Where each figure comes from

| Source | What |
| --- | --- |
| Playground, theme **not** activated (`blueprint-inactive.json`) | `vid-activate.mjs` — the activation video |
| Playground, theme activated (`blueprint.json`) | `cap-admin.mjs` (upload screen, pages), `cap-editor.mjs` (List View, inserter, figure GIF), `gif-icon2.mjs`, `cap-site-editor.mjs`, `gif-palettes.mjs`, `cap-template.mjs` |
| The live demo | `vid-scroll.mjs` (scroll tour), `cap-front.mjs` (dark mode, video popup, contact), `cap-map.mjs` (labelled home page) |
| The theme's own files | `build-icon-sheet.py` then `cap-icons.mjs` |

```bash
npx -y @wp-playground/cli@3.1.54 server --port=9493 --php=8.3 --wp=latest \
  --mount-before-install="$PWD:/wordpress/wp-content/themes/unioncorp" \
  --blueprint=.dev/docs/blueprint.json --login
WPBASE=http://127.0.0.1:9493 node .dev/docs/cap-site-editor.mjs
```

Run the scripts from a scratch directory with Playwright installed; they write to
`shots/`, `gif/`, `frames/` and `final/`. `gif.py out.gif WIDTH frame.png:ms …`
assembles GIF frames on one palette.

**Videos** are recorded by `record()` in `docs-lib.mjs`: Chromium's screencast
frames with their real timestamps, encoded to H.264 by ffmpeg (a static build
from `pip install imageio-ffmpeg`, path in `.ffmpeg`). Screenshots in a loop run
slower than the page, so reveals and count-ups would play back sped up.
Encode `yuv420p` in TV range: JPEG frames come out `yuvj420p`, which some
browsers show washed out.

## Things that bit

- `docs-lib.mjs` reads `WPBASE` **when it is imported**. Set it on the command
  line; set inside a script it is too late, and the script logs in to the
  wrong site without complaining.
- Playground's `--login` logs in every visitor, so front-end shots carry the
  admin bar. Shoot the front end on the demo instead.
- `img.decode()` never settles for a lazy image below the fold: race it against
  a timeout or a capture hangs.
- WordPress 7.1 draws an empty paragraph's placeholder in every card tile
  whose icon is an inline span — that is what 1.1.2 fixed. Check the editor
  after any change to how patterns hold content.
- The demo's "Watch the video" pointed at a video that had gone private. Open
  every external link a figure depends on before capturing it.
- colorlib.com renames an upload over 2560px to `-scaled`, which breaks the
  by-file-name lookup: keep every figure under that.

## Publishing

Copy `publish/media/*`, `publish/import.php` and `publish/colorlib-docs-page.php`
to the server under a **unique** name, then from the WordPress root, as the user
PHP runs as:

```bash
UNIONCORP_DOCS_DIR=/path/to/copied/files wp --url=https://colorlib.com/wp/ eval-file import.php
wp --url=https://colorlib.com/wp/ eval-file colorlib-docs-page.php
```

The page script refuses to save while any media is missing, keeps the page's
status, and reports dead links and contents links without a target. The page
needs its own entry in the download gate map, or its Download button hands the
zip out without the email step. Publish with `wp_publish_post()`, not
`wp post update --post_status`, which runs kses over the content.

Never stage a builder at a fixed path such as `/tmp/colorlib-product-page.php`:
another theme's builder can already be sitting there, a failed copy leaves it in
place, and running it rewrites the wrong product page.
