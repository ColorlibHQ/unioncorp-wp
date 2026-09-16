#!/usr/bin/env python3
"""
Generates theme.json and every style variation from one table of colours.

Unioncorp's brand blue, #4f86f9, is 3.08:1 on white. That is fine for a large
heading or a decorative rule and fails AA for body text, links and button
labels — so the palette keeps it as `accent`, for decoration only, and derives
`primary` (a deeper blue) for everything a reader has to read. The same applies
to the template's green, #3bd381, which is 1.9:1 on white.

Nothing here is eyeballed: audit() computes every pair the design actually
produces and refuses to write a palette that fails, and button_text() picks a
label colour by measurement rather than by the "dark background, light text"
rule of thumb, which is wrong as often as it is right.

Usage:  python3 .dev/build_theme.py
"""

import collections
import json
import os

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
os.chdir(ROOT)

# ---------------------------------------------------------------------------
# Palette
# ---------------------------------------------------------------------------
# Eleven slugs, the same in every variation, so a pattern written against them
# works under all of them. `overlay` is separate from `base` on purpose: text on
# a dimmed photograph must stay near-white even when the palette is dark, and
# writing it as `base` is what turns a dark palette's covers black-on-black.
PALETTE = [
    ("Base",         "base",         "#ffffff"),
    ("Surface",      "surface",      "#f9faff"),   # the template's own $light
    ("Contrast",     "contrast",     "#0b2033"),
    ("Muted",        "muted",        "#55677a"),
    ("Primary",      "primary",      "#2c62d6"),   # readable blue: links, buttons
    ("Primary deep", "primary-deep", "#1f4aa8"),
    ("Accent",       "accent",       "#4f86f9"),   # the brand blue, decorative
    ("Success",      "success",      "#1f9d5b"),   # readable green
    ("Dark",         "dark",         "#052c43"),   # the template's own $darken
    ("Divider",      "divider",      "#dbe3ef"),
    ("Overlay",      "overlay",      "#ffffff"),
]

COLOR_SETS = {
    "colors-1-azure": ("Azure", {
        "base": "#ffffff", "surface": "#f9faff", "contrast": "#0b2033", "muted": "#55677a",
        "primary": "#2c62d6", "primary-deep": "#1f4aa8", "accent": "#4f86f9",
        "success": "#1f9d5b", "dark": "#052c43", "divider": "#dbe3ef", "overlay": "#ffffff",
    }),
    "colors-2-emerald": ("Emerald", {
        "base": "#ffffff", "surface": "#f5fbf7", "contrast": "#0a2418", "muted": "#4f6659",
        "primary": "#127a4b", "primary-deep": "#0c5c38", "accent": "#3bd381",
        "success": "#127a4b", "dark": "#06301f", "divider": "#d5e8dd", "overlay": "#ffffff",
    }),
    "colors-3-navy": ("Navy", {
        "base": "#ffffff", "surface": "#f6f8fb", "contrast": "#0a1b2e", "muted": "#51637a",
        "primary": "#1b4f8f", "primary-deep": "#12396b", "accent": "#3f7fd4",
        "success": "#1f7a52", "dark": "#04182b", "divider": "#d9e1ec", "overlay": "#ffffff",
    }),
    "colors-4-slate": ("Slate", {
        "base": "#ffffff", "surface": "#f7f8f9", "contrast": "#16191d", "muted": "#5a6068",
        "primary": "#3a4750", "primary-deep": "#262f36", "accent": "#6b7c8c",
        "success": "#1f7a52", "dark": "#12161a", "divider": "#dfe2e6", "overlay": "#ffffff",
    }),
    "colors-5-teal": ("Teal", {
        "base": "#ffffff", "surface": "#f4fafb", "contrast": "#07242a", "muted": "#4d6970",
        "primary": "#0f6f7f", "primary-deep": "#0a5460", "accent": "#26a5b8",
        "success": "#1f8a5b", "dark": "#04252c", "divider": "#d3e7ea", "overlay": "#ffffff",
    }),
    "colors-6-plum": ("Plum", {
        "base": "#ffffff", "surface": "#faf6fb", "contrast": "#24122b", "muted": "#66546e",
        "primary": "#7b2d8e", "primary-deep": "#5d1f6c", "accent": "#a855bd",
        "success": "#1f8a5b", "dark": "#1d0f23", "divider": "#e7d9ec", "overlay": "#ffffff",
    }),
    # Dark palettes: `base` is the page, so it is dark here. A button is
    # bright-on-dark, which means its label is the DARK colour — the opposite of
    # the usual rule, and the reason button_text() measures instead of assuming.
    "colors-7-midnight": ("Midnight", {
        "base": "#0d1622", "surface": "#142033", "contrast": "#eef3f9", "muted": "#a3b1c2",
        "primary": "#7fb0ff", "primary-deep": "#a8c9ff", "accent": "#4f86f9",
        "success": "#57d693", "dark": "#080f18", "divider": "#24334a", "overlay": "#ffffff",
    }),
    "colors-8-graphite": ("Graphite", {
        "base": "#141618", "surface": "#1d2124", "contrast": "#f1f3f4", "muted": "#a8aeb4",
        "primary": "#9fb6c6", "primary-deep": "#c3d3de", "accent": "#7f97a8",
        "success": "#5fd19a", "dark": "#0e1011", "divider": "#2c3236", "overlay": "#ffffff",
    }),
}

# Typography. Two self-hosted families and the system stack; no third display
# face, because the template's eyebrows are small caps of the body font.
TYPE_SETS = {
    "type-1-poppins": ("Poppins throughout", "poppins", "poppins"),
    "type-2-poppins-inter": ("Poppins headings, Inter text", "poppins", "inter"),
    "type-3-inter": ("Inter throughout", "inter", "inter"),
    "type-4-inter-poppins": ("Inter headings, Poppins text", "inter", "poppins"),
    "type-5-system": ("System fonts", "system", "system"),
}

FAMILIES = collections.OrderedDict([
    ("poppins", ("Poppins", "Poppins, system-ui, -apple-system, 'Segoe UI', sans-serif",
                 [("300", "poppins-latin-300-normal.woff2"), ("400", "poppins-latin-400-normal.woff2"),
                  ("500", "poppins-latin-500-normal.woff2"), ("600", "poppins-latin-600-normal.woff2"),
                  ("700", "poppins-latin-700-normal.woff2")])),
    ("inter", ("Inter", "Inter, system-ui, -apple-system, 'Segoe UI', sans-serif",
               [("400", "inter-latin-400-normal.woff2"), ("500", "inter-latin-500-normal.woff2"),
                ("600", "inter-latin-600-normal.woff2"), ("700", "inter-latin-700-normal.woff2")])),
    ("system", ("System", "system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif", [])),
])


def fluid(minimum, maximum):
    return collections.OrderedDict([("min", minimum), ("max", maximum)])


FONT_SIZES = [
    ("Small",    "small",    "0.875rem", None),
    ("Medium",   "medium",   "1rem",     None),
    ("Large",    "large",    "1.125rem", fluid("1.0625rem", "1.1875rem")),
    ("X Large",  "x-large",  "1.5rem",   fluid("1.25rem", "1.5rem")),
    ("Heading",  "heading",  "2rem",     fluid("1.625rem", "2.125rem")),
    ("Display",  "display",  "2.75rem",  fluid("2.125rem", "3rem")),
    ("Colossal", "colossal", "3.75rem",  fluid("2.5rem", "4rem")),
]

SPACING = [
    ("20", "0.5rem"),
    ("30", "1rem"),
    ("40", "1.5rem"),
    ("50", "clamp(2rem, 4vw, 2.5rem)"),
    ("60", "clamp(2.5rem, 6vw, 4rem)"),
    ("70", "clamp(3.5rem, 8vw, 6rem)"),
    ("80", "clamp(5rem, 11vw, 8.5rem)"),
]

# Every foreground/background pair the design actually puts together.
CONTRAST_CHECKS = [
    ("contrast", "base"), ("contrast", "surface"),
    ("muted", "base"), ("muted", "surface"),
    ("primary", "base"), ("primary", "surface"),
    ("primary-deep", "base"),
    ("overlay", "dark"),
]


# ---------------------------------------------------------------------------
# Contrast
# ---------------------------------------------------------------------------
def _channel(value):
    value = value / 255
    return value / 12.92 if value <= 0.04045 else ((value + 0.055) / 1.055) ** 2.4


def luminance(hex_colour):
    r, g, b = (int(hex_colour[i:i + 2], 16) for i in (1, 3, 5))
    return 0.2126 * _channel(r) + 0.7152 * _channel(g) + 0.0722 * _channel(b)


def contrast_ratio(a, b):
    la, lb = luminance(a), luminance(b)
    lighter, darker = max(la, lb), min(la, lb)
    return (lighter + 0.05) / (darker + 0.05)


def button_text(colors):
    """The label colour for a button, chosen by measurement."""
    best, best_ratio = None, 0
    for slug in ("overlay", "base", "contrast", "dark"):
        ratio = min(contrast_ratio(colors[slug], colors["primary"]),
                    contrast_ratio(colors[slug], colors["primary-deep"]))
        if ratio > best_ratio:
            best, best_ratio = slug, ratio
    return best if best_ratio >= 4.5 else None


# ---------------------------------------------------------------------------
# theme.json
# ---------------------------------------------------------------------------
def od(*pairs):
    return collections.OrderedDict(pairs)


def var(slug):
    return "var(--wp--preset--color--%s)" % slug


def fs(slug):
    return "var(--wp--preset--font-size--%s)" % slug


def ff(slug):
    return "var(--wp--preset--font-family--%s)" % slug


def sp(slug):
    valid = {s for s, _ in SPACING}
    if slug not in valid:
        raise SystemExit("spacing %r is not on the scale %s" % (slug, sorted(valid)))
    return "var(--wp--preset--spacing--%s)" % slug


def palette(colors):
    return [od(("name", name), ("slug", slug), ("color", colors.get(slug, default)))
            for name, slug, default in PALETTE]


def font_families():
    out = []
    for key, (name, stack, faces) in FAMILIES.items():
        entry = od(("name", name), ("slug", key), ("fontFamily", stack))
        if faces:
            entry["fontFace"] = [od(
                ("fontFamily", name), ("fontStyle", "normal"), ("fontWeight", weight),
                ("src", ["file:./assets/fonts/%s" % filename]),
            ) for weight, filename in faces]
        out.append(entry)
    return out


def build_settings():
    return od(
        ("appearanceTools", True),
        ("useRootPaddingAwareAlignments", True),
        ("layout", od(("contentSize", "760px"), ("wideSize", "1200px"))),
        ("color", od(("custom", True), ("defaultPalette", False), ("defaultGradients", False),
                     ("palette", palette(COLOR_SETS["colors-1-azure"][1])))),
        ("typography", od(
            ("fluid", True), ("customFontSize", True), ("defaultFontSizes", False),
            ("fontFamilies", font_families()),
            ("fontSizes", [od(("name", name), ("slug", slug), ("size", size)) if not f else
                           od(("name", name), ("slug", slug), ("size", size), ("fluid", f))
                           for name, slug, size, f in FONT_SIZES]),
        )),
        ("spacing", od(("units", ["px", "em", "rem", "vh", "vw", "%"]),
                       ("padding", True), ("margin", True), ("blockGap", True),
                       ("defaultSpacingSizes", False),
                       ("spacingSizes", [od(("name", name), ("slug", name), ("size", size))
                                         for name, size in SPACING]))),
        ("border", od(("color", True), ("radius", True), ("style", True), ("width", True))),
        ("shadow", od(("defaultPresets", False), ("presets", [
            od(("name", "Card"), ("slug", "card"), ("shadow", "0 2px 12px rgba(5, 44, 67, 0.08)")),
            od(("name", "Lifted"), ("slug", "lifted"), ("shadow", "0 12px 32px rgba(5, 44, 67, 0.14)")),
        ]))),
    )


def build_styles():
    return od(
        ("color", od(("background", var("base")), ("text", var("contrast")))),
        ("typography", od(("fontFamily", ff("poppins")), ("fontSize", fs("medium")),
                          ("fontWeight", "400"), ("lineHeight", "1.7"))),
        ("spacing", od(("blockGap", sp("40")),
                       ("padding", od(("left", sp("40")), ("right", sp("40")))))),
        ("elements", od(
            ("heading", od(("typography", od(("fontFamily", ff("poppins")), ("fontWeight", "600"),
                                             ("lineHeight", "1.25"))),
                           ("color", od(("text", var("contrast")))))),
            ("h1", od(("typography", od(("fontSize", fs("colossal")))))),
            ("h2", od(("typography", od(("fontSize", fs("display")))))),
            ("h3", od(("typography", od(("fontSize", fs("heading")))))),
            ("link", od(("color", od(("text", var("primary")))),
                        (":hover", od(("color", od(("text", var("primary-deep")))))))),
            ("button", od(
                ("color", od(("background", var("primary")), ("text", var("overlay")))),
                ("typography", od(("fontWeight", "600"), ("fontSize", fs("small")))),
                ("border", od(("radius", "4px"))),
                ("spacing", od(("padding", od(("top", "0.9rem"), ("bottom", "0.9rem"),
                                              ("left", "1.75rem"), ("right", "1.75rem"))))),
                (":hover", od(("color", od(("background", var("primary-deep")), ("text", var("overlay")))))),
            )),
        )),
        ("blocks", od(
            ("core/separator", od(("color", od(("text", var("divider")))))),
            ("core/site-title", od(("typography", od(("fontWeight", "700"), ("fontSize", fs("x-large")))))),
        )),
    )


def build_theme():
    return od(
        ("$schema", "https://schemas.wp.org/trunk/theme.json"),
        ("version", 3),
        ("settings", build_settings()),
        ("styles", build_styles()),
        ("customTemplates", [
            od(("name", "page-no-title"), ("title", "Page without title"), ("postTypes", ["page"])),
            od(("name", "page-with-sidebar"), ("title", "Page with sidebar"), ("postTypes", ["page"])),
            od(("name", "single-with-sidebar"), ("title", "Post with sidebar"), ("postTypes", ["post"])),
        ]),
        ("templateParts", [
            od(("name", "header"), ("title", "Header"), ("area", "header")),
            od(("name", "footer"), ("title", "Footer"), ("area", "footer")),
            od(("name", "sidebar"), ("title", "Sidebar"), ("area", "uncategorized")),
        ]),
    )


def build_color_variation(slug, name, colors):
    label = button_text(colors)
    return od(
        ("$schema", "https://schemas.wp.org/trunk/theme.json"),
        ("version", 3), ("title", name),
        ("settings", od(("color", od(("palette", palette(colors)))))),
        ("styles", od(("elements", od(("button", od(
            ("color", od(("background", var("primary")), ("text", var(label)))),
            (":hover", od(("color", od(("background", var("primary-deep")), ("text", var(label)))))),
        )))))),
    )


def build_type_variation(slug, name, heading, body):
    return od(
        ("$schema", "https://schemas.wp.org/trunk/theme.json"),
        ("version", 3), ("title", name),
        ("styles", od(
            ("typography", od(("fontFamily", ff(body)))),
            ("elements", od(("heading", od(("typography", od(("fontFamily", ff(heading)))))))),
        )),
    )


def write(path, data):
    os.makedirs(os.path.dirname(path) or ".", exist_ok=True)
    with open(path, "w", encoding="utf-8") as handle:
        json.dump(data, handle, indent="\t", ensure_ascii=False)
        handle.write("\n")
    return path


def audit():
    problems = []
    print("  palette      button label   worst ratio")
    for slug, (name, colors) in sorted(COLOR_SETS.items()):
        for fg, bg in CONTRAST_CHECKS:
            ratio = contrast_ratio(colors[fg], colors[bg])
            if ratio < 4.5:
                problems.append("%s: %s on %s is %.2f" % (name, fg, bg, ratio))
        label = button_text(colors)
        if label is None:
            problems.append("%s: no readable button label" % name)
        else:
            worst = min(contrast_ratio(colors[label], colors[g]) for g in ("primary", "primary-deep"))
            print("  %-12s %-14s %.2f" % (name, label, worst))
    # The brand colours are decorative here, and that is a deliberate decision:
    # say so rather than let someone rediscover it.
    print("\n  for the record: the template's #4f86f9 is %.2f:1 on white and #3bd381 is %.2f:1 —"
          % (contrast_ratio("#4f86f9", "#ffffff"), contrast_ratio("#3bd381", "#ffffff")))
    print("  both ship as `accent`, for decoration, never as text or a button label.")
    if problems:
        raise SystemExit("\nContrast failures:\n  " + "\n  ".join(problems))


def main():
    audit()
    written = [write("theme.json", build_theme())]
    for slug, (name, colors) in sorted(COLOR_SETS.items()):
        written.append(write("styles/colors/%s.json" % slug, build_color_variation(slug, name, colors)))
    for slug, (name, heading, body) in sorted(TYPE_SETS.items()):
        written.append(write("styles/typography/%s.json" % slug, build_type_variation(slug, name, heading, body)))
    print("\n  %d files written" % len(written))
    for path in written:
        print("    " + path)


if __name__ == "__main__":
    main()
