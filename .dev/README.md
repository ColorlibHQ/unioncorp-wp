# Build tooling

Nothing in `patterns/`, `theme.json` or `styles/` is written by hand. Edit the
generator, run it, and commit what it produces.

```bash
python3 .dev/build_theme.py        # theme.json + styles/colors/* + styles/typography/*
python3 .dev/build_patterns.py     # patterns/*.php
node    .dev/build-fonts.mjs       # assets/fonts/*.woff2 (only when the weights change)
```

## The order that matters

Generated block markup is a guess until the editor has seen it. Block comment
attributes must match what a block's `save()` writes, and when they do not, the
editor shows "this block contains unexpected or invalid content" — while the
front end looks perfect and nothing warns you at build time.

So, against a WordPress with this theme active:

```bash
export WP_URL=http://127.0.0.1:9481 WP_USER=admin WP_PASS=password
node .dev/normalize-blocks.mjs     # re-serialise every pattern as the editor would
node .dev/validate-blocks.mjs      # then fail on anything still invalid
```

`normalize-blocks.mjs` stashes the `<?php … ?>` snippets that carry image URLs
before parsing and puts them back afterwards. **Identical snippets must share a
token**: a cover block names the same `get_theme_file_uri()` call twice, and two
different tokens make the attribute and the markup disagree, which parses as
invalid.

A throwaway WordPress to run them against:

```bash
npx -y @wp-playground/cli@3.1.54 server --port=9481 --php=8.3 --wp=latest \
  --mount-before-install="$PWD:/wordpress/wp-content/themes/unioncorp" \
  --blueprint=.dev/blueprint.json --login
```

## The other checks

```bash
node .dev/contrast-rendered.mjs    # measured text contrast on a rendered page
node .dev/overflow-check.mjs       # elements wider than the box they live in
node .dev/screenshot.mjs           # page captures
bash .dev/build-zip.sh             # the distributable, without .dev or node_modules
```

`build_theme.py` audits every palette before writing and **refuses to emit one
that fails WCAG AA** on any foreground/background pair the design produces. It
also picks each palette's button label colour by measurement: on a dark palette
the button is bright and its label is the dark colour, which is the opposite of
the usual rule.

The template's own brand colours are decorative for this reason. `#4f86f9` is
3.44:1 on white and `#3bd381` is 1.94:1; both ship as `accent`, and deeper
shades carry links, buttons and anything else that has to be read.

## Spacing and colour are vocabularies, not values

`sp()` refuses any spacing step that is not on the registered scale, because an
undefined preset variable makes WordPress drop the whole declaration and the
element silently falls back to its inherited gap. The same applies to colour
slugs: patterns name `primary` or `surface`, never a hex value, which is what
lets all eight palettes restyle every section.
