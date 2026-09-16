#!/usr/bin/env python3
"""
Fail on any `unioncorp-*` CSS selector that nothing in the theme emits.

A selector that matches nothing is not an error anywhere: the browser ignores
it, the build passes, Theme Check passes, and every rendered check passes too,
because the element it was meant to style still renders — just unstyled.

That is how the enquiry form shipped. This theme was ported from Pato, whose
form is a *reservation* form; the port renamed `pato-` to `unioncorp-` but kept
the word, while inc/enquiry.php emits `unioncorp-enquiry__*`. So six rules in
forms.css — the wrapper, the two-column grid, the actions row, the success and
error notice, and the honeypot — matched nothing. The fields still stacked and
looked plausible, which is what hid it, and the honeypot rendered in full view:
"Leave this field empty", above the form, for every visitor.

Class names built at runtime count as emitted. PHP such as
`'unioncorp-field--' . esc_attr( $name )` or JavaScript such as
`'unioncorp-' + name` registers its literal prefix, and any selector starting
with that prefix is treated as live. Without that, `.unioncorp-field--message`
would be reported, although inc/enquiry.php builds it for the message field.

    python3 .dev/dead-selectors.py      # exits 1 and lists them, or 0
"""

import glob
import os
import re
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
os.chdir(ROOT)

CSS = sorted(glob.glob("assets/css/*.css"))
EMITTERS = sorted(
    glob.glob("inc/*.php") + glob.glob("functions.php") + glob.glob("patterns/*.php")
    + glob.glob("templates/*.html") + glob.glob("parts/*.html") + glob.glob("assets/js/*.js")
    + glob.glob("theme.json") + glob.glob("styles/**/*.json", recursive=True)
)

TOKEN = re.compile(r"unioncorp-[a-z0-9_-]+")
SELECTOR = re.compile(r"\.(unioncorp-[a-z0-9_-]+)")
# A runtime prefix is the unioncorp- token that ENDS a string literal and is then
# concatenated with a variable. It must not be required to START the literal:
#
#   class="unioncorp-field unioncorp-field--' . esc_attr( $name ) . '"
#
# begins at `class="`, so the first version of this pattern — which wanted a
# quote directly before the prefix — missed `unioncorp-field--` and reported the
# live `.unioncorp-field--message` as dead. Its own first run caught that.
PHP_PREFIX = re.compile(r"""(unioncorp-[a-z0-9_-]*)['"]\s*\.\s*(?:[a-z_]+\s*\(\s*)?\$""")
# 'unioncorp-' + name   /   `unioncorp-${name}`
JS_PREFIX = re.compile(r"""(unioncorp-[a-z0-9_-]*)(?:['"]\s*\+|\$\{)""")


def strip_comments(css):
    return re.sub(r"/\*.*?\*/", "", css, flags=re.S)


def main():
    selectors = {}
    for path in CSS:
        for name in SELECTOR.findall(strip_comments(open(path, encoding="utf-8").read())):
            selectors.setdefault(name, set()).add(path)

    literal, prefixes = set(), set()
    for path in EMITTERS:
        text = open(path, encoding="utf-8").read()
        literal.update(TOKEN.findall(text))
        prefixes.update(p for p in PHP_PREFIX.findall(text) + JS_PREFIX.findall(text) if p)

    def live(name):
        return name in literal or any(name.startswith(p) for p in prefixes)

    dead = sorted(name for name in selectors if not live(name))

    print("%d unioncorp-* selectors, %d emitted literally, dynamic prefixes: %s"
          % (len(selectors), len(literal), ", ".join(sorted(prefixes)) or "none"))
    if dead:
        print("\n%d selector(s) match nothing the theme emits:" % len(dead))
        for name in dead:
            print("  .%-40s %s" % (name, ", ".join(sorted(selectors[name]))))
        sys.exit(1)
    print("every unioncorp-* selector matches something the theme emits")


if __name__ == "__main__":
    main()
