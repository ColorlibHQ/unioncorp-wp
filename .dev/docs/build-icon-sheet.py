"""Write shots/icons.html: every icon that has a `.unioncorp-icon--<name>` class,
in a primary-coloured tile with its name. cap-icons.mjs photographs it."""
import html, pathlib, re
root = pathlib.Path(__file__).resolve().parents[2]
css = (root / "style.css").read_text()
names = sorted(set(re.findall(r"\.unioncorp-icon--([a-z-]+) \{ --unioncorp-icon", css)))
cells = []
for n in names:
    svg = (root / "assets/icons" / f"{n}.svg").read_text().replace("\n", " ").replace("#", "%23")
    cells.append(f'<div class="c"><div class="tile" style="--u:url(\'data:image/svg+xml;utf8,{html.escape(svg, quote=True)}\')"></div><code>{n}</code></div>')
page = ("<!doctype html><html><head><style>body{margin:0;padding:26px;background:#fff;font-family:Poppins,system-ui,sans-serif;width:1100px}"
        ".g{display:grid;grid-template-columns:repeat(6,1fr);gap:22px 14px}.c{display:flex;flex-direction:column;align-items:center;gap:10px}"
        ".tile{width:64px;height:64px;border-radius:14px;background:color-mix(in srgb,#2c62d6 11%,transparent);position:relative}"
        ".tile::before{content:'';position:absolute;inset:18px;background:#2c62d6;-webkit-mask:var(--u) center/contain no-repeat;mask:var(--u) center/contain no-repeat}"
        "code{font:500 13px/1.3 ui-monospace,Menlo,monospace;color:#0b2033;text-align:center}</style></head><body><div class=\"g\">"
        + "".join(cells) + "</div></body></html>")
pathlib.Path("shots").mkdir(exist_ok=True)
pathlib.Path("shots/icons.html").write_text(page)
print(len(names), "icons")
