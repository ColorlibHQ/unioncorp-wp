"""Assemble PNG frames into a documentation GIF.

Usage: python3 gif.py out.gif width frame1.png:ms frame2.png:ms ...
Frames are resized to `width`, quantised to one shared palette (so colours do
not shimmer between frames) and looped forever.
"""
import sys
from PIL import Image

out, width, specs = sys.argv[1], int(sys.argv[2]), sys.argv[3:]
frames, durations = [], []
for spec in specs:
    path, ms = spec.rsplit(':', 1)
    im = Image.open(path).convert('RGB')
    h = round(im.height * width / im.width)
    frames.append(im.resize((width, h), Image.LANCZOS))
    durations.append(int(ms))

# One palette built from a strip of every frame keeps colours stable.
strip = Image.new('RGB', (width, sum(f.height for f in frames)))
y = 0
for f in frames:
    strip.paste(f, (0, y)); y += f.height
palette = strip.quantize(colors=255, method=Image.MEDIANCUT)
q = [f.quantize(palette=palette, dither=Image.FLOYDSTEINBERG) for f in frames]
q[0].save(out, save_all=True, append_images=q[1:], duration=durations, loop=0, optimize=True, disposal=1)
import os
print(out, f'{len(q)} frames', f'{os.path.getsize(out)/1024:.0f} KB', q[0].size)
