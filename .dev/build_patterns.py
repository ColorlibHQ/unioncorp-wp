#!/usr/bin/env python3
"""Generate Unioncorp's patterns.

Run from the theme root:

    python3 .dev/build_patterns.py
    node .dev/normalize-blocks.mjs      # then let the editor re-serialise them
    node .dev/validate-blocks.mjs       # and refuse anything it calls invalid

Every pattern file is committed as generated. Edit this file, never
patterns/*.php.
"""

import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from patternlib import (  # noqa: E402
    button, buttons, column, columns, cover, group, heading, image,
    paragraph, shortcode, spacer,
)

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PATTERNS = os.path.join(ROOT, "patterns")
WRITTEN = []

SECTIONS = ["unioncorp-sections"]
PAGES = ["unioncorp-pages"]


def write(slug, title, content, categories=None, keywords=None,
          description=None, inserter=True, block_types=None):
    header = ["Title: " + title, "Slug: unioncorp/" + slug]
    if categories:
        header.append("Categories: " + ", ".join(categories))
    if keywords:
        header.append("Keywords: " + ", ".join(keywords))
    if block_types:
        header.append("Block Types: " + ", ".join(block_types))
    if description:
        header.append("Description: " + description)
    if not inserter:
        header.append("Inserter: no")

    body = (
        "<?php\n/**\n * " + "\n * ".join(header) + "\n *\n * @package Unioncorp\n */\n\n"
        "defined( 'ABSPATH' ) || exit;\n?>\n" + content.strip() + "\n"
    )
    with open(os.path.join(PATTERNS, slug + ".php"), "w") as fh:
        fh.write(body)
    WRITTEN.append(slug)


def eyebrow(text, align="center", color="primary"):
    """The small caps line above a section title, as the template draws it.

    The colour is a parameter because this line also appears on the primary
    band, where `primary` text on a `primary` ground is 1:1 — invisible, and
    measured as such by .dev/contrast-rendered.mjs.
    """
    return paragraph(text, align=align, color=color, size="small",
                     extra_class="unioncorp-eyebrow")


def section_head(label, title, blurb=None, align="center"):
    parts = [eyebrow(label, align), heading(title, level=2, align=align)]
    if blurb:
        parts.append(paragraph(blurb, align=align, color="muted", size="large"))
    return group("\n".join(parts), layout="constrained", content_size="760px", gap="30")


def icon_card(icon, title, blurb):
    """One service card: Flaticon glyph, heading, one line of copy."""
    inner = "\n".join([
        paragraph('<span class="flaticon-%s"></span>' % icon, color="primary",
                  size="x-large", extra_class="unioncorp-card__icon"),
        heading(title, level=3, size="large"),
        paragraph(blurb, color="muted"),
    ])
    return column(group(inner, layout="constrained", gap="20", padding={"top": "40", "bottom": "40", "left": "40", "right": "40"},
                        background="base", style="unioncorp-card", radius="8px"))


def stat(number, label):
    inner = "\n".join([
        heading(number, level=3, align="center", color="overlay", size="display"),
        paragraph(label, align="center", color="overlay", size="small"),
    ])
    return column(group(inner, layout="constrained", gap="20"))


def person(slug, name, role):
    inner = "\n".join([
        image(slug, "%s, %s" % (name, role), ratio="1/1", rounded="50%"),
        heading(name, level=3, size="large"),
        paragraph(role, color="muted", size="small"),
    ])
    return column(group(inner, layout="constrained", gap="20"))


def quote(text, name, role):
    inner = "\n".join([
        paragraph(text, color="contrast"),
        paragraph("<strong>%s</strong><br>%s" % (name, role), color="muted", size="small"),
    ])
    return column(group(inner, layout="constrained", gap="20", background="base",
                        padding={"top": "40", "bottom": "40", "left": "40", "right": "40"},
                        style="unioncorp-card", radius="8px"))


def plan(name, price, features, featured=False):
    items = "".join("<li>%s</li>" % f for f in features)
    inner = "\n".join([
        paragraph(name, align="center", color="muted", size="small",
                  extra_class="unioncorp-eyebrow"),
        heading("$%s" % price, level=3, align="center", size="display"),
        paragraph("per month", align="center", color="muted", size="small"),
        '<!-- wp:list {"className":"is-style-unioncorp-ticks"} -->\n'
        '<ul class="wp-block-list is-style-unioncorp-ticks">%s</ul>\n'
        '<!-- /wp:list -->' % items,
        buttons([button("Get started", "#", style=None if featured else "unioncorp-ghost")], align="center"),
    ])
    return column(group(inner, layout="constrained", gap="20",
                        padding={"top": "50", "bottom": "50", "left": "40", "right": "40"},
                        background="base" if not featured else "surface",
                        style="unioncorp-card", radius="8px"))


# ---------------------------------------------------------------------------
# Parts
# ---------------------------------------------------------------------------
def build_header():
    top = group(
        columns([
            column(paragraph('<span class="flaticon-accountant"></span> Monday – Friday 8:00AM–8:00PM',
                             color="overlay", size="small"), width="60%"),
            column(paragraph('info@yourdomain.com', color="overlay", size="small"), width="40%"),
        ], gap="30"),
        align="full", background="dark", padding_y="20", layout="constrained",
        extra_class="unioncorp-topbar",
    )
    nav = group(
        columns([
            column('<!-- wp:site-logo {"width":180} /-->\n<!-- wp:site-title {"level":0} /-->', width="30%", vertical="center"),
            column('<!-- wp:navigation {"overlayMenu":"mobile","layout":{"type":"flex","justifyContent":"right"}} /-->',
                   width="50%", vertical="center"),
            column(buttons([button("Get started", "#")], align="right"), width="20%", vertical="center"),
        ], gap="30", vertical="center"),
        align="full", background="base", padding_y="30", layout="constrained",
        extra_class="unioncorp-header",
    )
    write("header", "Header", top + "\n" + nav, keywords=["header", "navigation"],
          description="Top bar with contact details, then the logo, navigation and a call to action.",
          block_types=["core/template-part/header"])


def build_footer():
    services = ["Financial Planning", "Investments Management", "Business Loan", "Taxes Consulting"]
    col_services = column("\n".join([
        heading("Services", level=3, color="overlay", size="large"),
        '<!-- wp:list {"className":"is-style-unioncorp-ticks"} -->\n<ul class="wp-block-list is-style-unioncorp-ticks">%s</ul>\n<!-- /wp:list -->'
        % "".join("<li>%s</li>" % s for s in services),
    ]))
    col_brand = column("\n".join([
        '<!-- wp:site-title {"level":0,"style":{"color":{"text":"var(--wp--preset--color--overlay)"}}} /-->',
        paragraph("Financial planning and consulting for businesses and the people who run them.",
                  color="on-dark", size="small"),
    ]))
    col_contact = column("\n".join([
        heading("Have a question?", level=3, color="overlay", size="large"),
        paragraph("203 Fake St. Mountain View, San Francisco, California, USA", color="on-dark", size="small"),
        paragraph("+2 392 3929 210", color="on-dark", size="small"),
        paragraph("info@yourdomain.com", color="on-dark", size="small"),
    ]))
    col_posts = column("\n".join([
        heading("Recent posts", level=3, color="overlay", size="large"),
        '<!-- wp:latest-posts {"postsToShow":2,"displayPostDate":true} /-->',
    ]))
    body = group(
        columns([col_brand, col_services, col_posts, col_contact], gap="40"),
        align="full", background="dark", text="overlay", padding_y="70", layout="constrained",
    )
    legal = group(
        paragraph('Copyright © <a href="https://colorlib.com" rel="nofollow">Colorlib</a>. All rights reserved.',
                  align="center", color="on-dark", size="small"),
        align="full", background="dark", padding_y="30", layout="constrained",
    )
    write("footer", "Footer", body + "\n" + legal, keywords=["footer"],
          description="Four columns on the dark ground, with a copyright line beneath.",
          block_types=["core/template-part/footer"])


def build_sidebar():
    inner = "\n".join([
        '<!-- wp:search {"label":"Search","buttonText":"Search"} /-->',
        heading("Categories", level=3, size="large"),
        '<!-- wp:categories /-->',
        heading("Recent posts", level=3, size="large"),
        '<!-- wp:latest-posts {"postsToShow":4,"displayPostDate":true} /-->',
    ])
    write("sidebar", "Sidebar", group(inner, layout="constrained", gap="40"),
          keywords=["sidebar"], inserter=False,
          description="Search, categories and recent posts.")


# ---------------------------------------------------------------------------
# Sections
# ---------------------------------------------------------------------------
def build_hero():
    inner = "\n".join([
        eyebrow("Finance &amp; consultation", color="overlay"),
        heading("We're always here to give financial help", level=1, align="center",
                color="overlay", size="colossal"),
        paragraph("Planning, investment and risk management for businesses that would rather "
                  "spend their time on the business.", align="center", color="overlay", size="large"),
        buttons([button("Get started", "#"), button("Our services", "#", style="unioncorp-ghost")],
                align="center"),
    ])
    write("hero", "Hero", cover(group(inner, layout="constrained", gap="30"), "bg_1",
                                dim=60, min_height=70, min_height_unit="vh"),
          categories=SECTIONS, keywords=["hero", "banner"],
          description="Full-width opening banner with a headline and two buttons.")


def build_page_banner():
    inner = "\n".join([
        '<!-- wp:post-title {"level":1,"textAlign":"center","textColor":"overlay"} /-->',
    ])
    write("hidden-page-banner", "Page banner", cover(group(inner, layout="constrained"), "bg_2",
                                              dim=60, min_height=34, min_height_unit="vh"),
          inserter=False, description="The photograph banner a page or post title sits on.")


def build_welcome():
    text = "\n".join([
        eyebrow("About Union Corporation", align="left"),
        heading("More than 40M+ trusted our financial &amp; consultation institution",
                level=2, align="left"),
        paragraph("We have been advising businesses for twenty-eight years: what to invest, "
                  "what to insure, and what to leave alone.", color="muted"),
        paragraph("Our consultants work with founders, finance directors and family businesses "
                  "across the country.", color="muted"),
        buttons([button("Learn more", "#")]),
    ])
    write("welcome", "About: introduction",
          group(columns([
              column(image("about", "Two advisers going over figures at a meeting table", ratio="4/3", rounded="8px"), width="50%"),
              column(group(text, layout="constrained", gap="30"), width="50%", vertical="center"),
          ], gap="60", vertical="center"), align="full", padding_y="80", layout="constrained"),
          categories=SECTIONS, keywords=["about", "intro"],
          description="A photograph beside an introduction and a button.")


def build_services():
    cards = [
        ("accounting", "Financial Planning", "A plan for the next five years that survives contact with the first one."),
        ("financial", "Investments Management", "Portfolios built around what the business actually needs to do."),
        ("recession", "Business Loan", "Lending arranged and negotiated, with the terms explained in plain words."),
        ("tax", "Taxes Consulting", "Returns, planning and the correspondence nobody wants to open."),
        ("insurance", "Insurance Consulting", "Cover that matches the risk, without the parts you will never claim."),
        ("retirement-plan", "Retirement Planning", "Pensions and succession, for you and for the people who work for you."),
        ("risk", "Risk Management", "Finding what would hurt most, then making it less likely."),
        ("technology", "Technology Consulting", "The systems behind the numbers: what to buy, and what to retire."),
    ]
    rows = [columns([icon_card(*c) for c in cards[i:i + 4]], gap="40") for i in (0, 4)]
    inner = section_head("Our services", "Our exclusive services we offer for you",
                         "Eight practices, one team, and a single point of contact.") + "\n" + spacer("50") + "\n" + "\n".join(rows)
    write("services", "Services: eight cards",
          group(inner, align="full", background="surface", padding_y="80", layout="constrained", anchor="services"),
          categories=SECTIONS, keywords=["services", "cards"],
          description="Eight service cards with icons, in two rows of four.")


def build_quality():
    text = "\n".join([
        eyebrow("Why us", align="left"),
        heading("Quality makes the belief for customers", level=2, align="left", color="overlay"),
        paragraph("Advice you can check: every recommendation comes with the workings, the "
                  "assumptions and what would have to be true for it to be wrong.",
                  color="overlay"),
        buttons([button("Read our case studies", "#", style="unioncorp-ghost")]),
    ])
    write("quality", "Why us: photograph and statement",
          cover(group(text, layout="constrained", content_size="620px", gap="30"), "image_5",
                dim=70, min_height=48, min_height_unit="vh"),
          categories=SECTIONS, keywords=["about", "quality"],
          description="A statement over a photograph, with a button.")


def build_case_studies():
    shots = ["gallery-1", "gallery-2", "gallery-3", "gallery-4", "gallery-5", "gallery-6", "gallery-7", "gallery-8"]
    rows = [columns([column(image(s, "Consulting work in progress", ratio="4/3", rounded="6px"))
                     for s in shots[i:i + 4]], gap="30") for i in (0, 4)]
    inner = section_head("Case studies", "We take every case study very seriously") + "\n" + spacer("50") + "\n" + "\n".join(rows)
    write("case-studies", "Case studies: gallery",
          group(inner, align="full", padding_y="80", layout="constrained", anchor="work"),
          categories=SECTIONS, keywords=["gallery", "portfolio", "work"],
          description="Eight photographs in two rows, for case studies or a portfolio.")


def build_counters():
    stats = [("60", "Years of experience"), ("9,200", "Satisfied customers"),
             ("5,800", "Projects completed"), ("100", "Awards won")]
    write("counters", "Statistics",
          cover(columns([stat(n, l) for n, l in stats], gap="40"), "bg_4", dim=80, min_height=None),
          categories=SECTIONS, keywords=["statistics", "counters", "numbers"],
          description="Four figures over a photograph.")


def build_team():
    people = [("staff-1", "Jason Smith", "Managing partner"), ("staff-2", "Jeffrey Rockenson", "Head of investments"),
              ("staff-3", "Maria Alvarez", "Tax director"), ("staff-4", "Peter Nowak", "Risk consultant"),
              ("staff-5", "Hannah Byrne", "Financial planner"), ("staff-6", "Daniel Osei", "Technology consultant"),
              ("staff-7", "Claire Dubois", "Insurance specialist"), ("staff-8", "Marcus Hale", "Investment analyst")]
    rows = [columns([person(*p) for p in people[i:i + 4]], gap="40") for i in (0, 4)]
    inner = section_head("Our team", "The people you will actually speak to") + "\n" + spacer("50") + "\n" + "\n".join(rows)
    write("team", "Team: six profiles",
          group(inner, align="full", background="surface", padding_y="80", layout="constrained", anchor="team"),
          categories=SECTIONS, keywords=["team", "staff", "people"],
          description="Six team members with photographs and roles.")


def build_testimonials():
    quotes = [
        ("They found a tax position we had been missing for four years, and then explained it "
         "in a way our board could follow.", "Roger Scott", "Managing director"),
        ("The plan survived a bad year, which is the only test that matters.", "Alice Mensah", "Founder"),
        ("Straight answers, including the ones we did not want.", "Tom Reilly", "Finance director"),
    ]
    inner = section_head("Testimonials", "What our customers say") + "\n" + spacer("50") + "\n" + columns([quote(*q) for q in quotes], gap="40")
    write("testimonials", "Testimonials: three quotes",
          group(inner, align="full", padding_y="80", layout="constrained"),
          categories=SECTIONS, keywords=["testimonials", "reviews", "quotes"],
          description="Three customer quotes in cards.")


def build_pricing():
    feats = ["Live chat support", "Minimum of 10 users", "Easily track payments",
             "Web conference support", "Group management of users", "Remote monitoring"]
    plans = [plan("Free", "50", feats), plan("Basic plan", "79", feats, featured=True),
             plan("Professional", "89", feats), plan("Startup", "99", feats)]
    inner = section_head("Pricing", "Find the plan that is right for you") + "\n" + spacer("50") + "\n" + columns(plans, gap="40")
    write("pricing", "Pricing: four plans",
          group(inner, align="full", background="surface", padding_y="80", layout="constrained", anchor="pricing"),
          categories=SECTIONS, keywords=["pricing", "plans"],
          description="Four pricing plans with feature lists.")


def build_blog_latest():
    query = (
        '<!-- wp:query {"queryId":1,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post",'
        '"order":"desc","orderBy":"date","inherit":false},"align":"wide","layout":{"type":"default"}} -->\n'
        '<div class="wp-block-query alignwide"><!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->\n'
        '<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3","style":{"border":{"radius":"6px"}}} /-->\n'
        '<!-- wp:post-date {"fontSize":"small","textColor":"muted"} /-->\n'
        '<!-- wp:post-title {"isLink":true,"level":3,"fontSize":"large"} /-->\n'
        '<!-- wp:post-excerpt {"excerptLength":18,"textColor":"muted"} /-->\n'
        '<!-- /wp:post-template --></div>\n'
        '<!-- /wp:query -->'
    )
    inner = section_head("From the blog", "Recent from our desk") + "\n" + spacer("50") + "\n" + query
    write("blog-latest", "Latest posts",
          group(inner, align="full", padding_y="80", layout="constrained"),
          categories=SECTIONS, keywords=["blog", "posts", "news"],
          description="The three most recent posts in a grid.")


def build_cta():
    # The band is `primary`, and a plain button is also `primary` — so it sat
    # 1:1 on its own ground and read as loose text. Inverting it to an
    # `on-primary` fill with a `primary` label uses the one pair the audit
    # guarantees in every palette and in dark mode, where the same pair flips.
    inner = columns([
        column(group("\n".join([
            eyebrow("Prepare for takeoff", align="left", color="on-primary"),
            heading("Looking for a business opportunity?", level=2, align="left", color="on-primary"),
        ]), layout="constrained", gap="20"), width="70%", vertical="center"),
        column(buttons([button("Get started", "#", background="on-primary", text_color="primary")],
                       align="right"), width="30%", vertical="center"),
    ], gap="40", vertical="center")
    write("cta", "Call to action band",
          group(inner, align="full", background="primary", text="on-primary", padding_y="60", layout="constrained"),
          categories=SECTIONS, keywords=["cta", "call to action"],
          description="A full-width band with a headline and a button.")


def build_contact():
    details = group("\n".join([
        heading("Contact us", level=2, align="left"),
        paragraph("We are open for questions, second opinions and new work.", color="muted"),
        paragraph("<strong>Address</strong><br>198 West 21th Street, Suite 721, New York NY 10016", color="muted"),
        paragraph("<strong>Email</strong><br>info@yourdomain.com", color="muted"),
        paragraph("<strong>Phone</strong><br>+1 235 2355 98", color="muted"),
    ]), layout="constrained", gap="30")
    form = group(shortcode("[unioncorp_enquiry_form]"), layout="constrained")
    map_block = (
        '<!-- wp:html -->\n'
        '<iframe class="unioncorp-map" title="Map showing where Unioncorp is" loading="lazy" '
        'src="https://www.openstreetmap.org/export/embed.html?bbox=-74.0170%2C40.6990%2C-74.0070%2C40.7100&amp;layer=mapnik&amp;marker=40.704644%2C-74.011987" '
        'style="width:100%;height:420px;border:0"></iframe>\n'
        '<!-- /wp:html -->'
    )
    inner = columns([column(details, width="45%"), column(form, width="55%")], gap="60")
    write("contact", "Contact: details, form and map",
          group(inner + "\n" + spacer("60") + "\n" + map_block, align="full",
                padding_y="80", layout="constrained", anchor="contact"),
          categories=SECTIONS, keywords=["contact", "form", "map"],
          description="Contact details beside the enquiry form, with a map beneath.")


# ---------------------------------------------------------------------------
# Hidden patterns: the pieces templates are built from. Not in the inserter,
# because inserting "the comments area" into a page is never what anyone means.
# ---------------------------------------------------------------------------
def build_hidden():
    write("hidden-archive-banner", "Archive banner",
          cover(group('<!-- wp:query-title {"type":"archive","textAlign":"center","textColor":"overlay"} /-->',
                      layout="constrained"), "bg_3", dim=60, min_height=34, min_height_unit="vh"),
          inserter=False, description="The banner an archive title sits on.")

    write("hidden-search-banner", "Search banner",
          cover(group('<!-- wp:query-title {"type":"search","textAlign":"center","textColor":"overlay"} /-->',
                      layout="constrained"), "bg_3", dim=60, min_height=34, min_height_unit="vh"),
          inserter=False, description="The banner search results sit under.")

    write("hidden-blog-heading", "Blog heading",
          cover(group(heading("From the blog", level=1, align="center", color="overlay"),
                      layout="constrained"), "bg_2", dim=60, min_height=34, min_height_unit="vh"),
          inserter=False, description="The heading for the posts page.")

    posts = (
        '<!-- wp:query {"queryId":0,"query":{"perPage":9,"pages":0,"offset":0,"postType":"post",'
        '"order":"desc","orderBy":"date","inherit":true},"layout":{"type":"default"}} -->\n'
        '<div class="wp-block-query"><!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->\n'
        '<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3","style":{"border":{"radius":"6px"}}} /-->\n'
        '<!-- wp:post-date {"fontSize":"small","textColor":"muted"} /-->\n'
        '<!-- wp:post-title {"isLink":true,"level":3,"fontSize":"large"} /-->\n'
        '<!-- wp:post-excerpt {"excerptLength":22,"textColor":"muted"} /-->\n'
        '<!-- /wp:post-template -->\n'
        '<!-- wp:query-no-results -->\n'
        + paragraph("Nothing here yet.", color="muted") + '\n'
        '<!-- /wp:query-no-results -->\n'
        '<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->\n'
        '<!-- wp:query-pagination-previous /-->\n'
        '<!-- wp:query-pagination-numbers /-->\n'
        '<!-- wp:query-pagination-next /-->\n'
        '<!-- /wp:query-pagination --></div>\n'
        '<!-- /wp:query -->'
    )
    write("hidden-posts-grid", "Posts grid",
          group(posts, align="full", padding_y="80", layout="constrained"),
          inserter=False, description="The post list used by the blog and every archive.")

    meta = (
        '<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} -->\n'
        '<div class="wp-block-group">\n'
        '<!-- wp:post-date {"fontSize":"small","textColor":"muted"} /-->\n'
        '<!-- wp:post-author-name {"fontSize":"small","textColor":"muted"} /-->\n'
        '<!-- wp:post-terms {"term":"category","fontSize":"small","textColor":"muted"} /-->\n'
        '</div>\n'
        '<!-- /wp:group -->'
    )
    write("hidden-post-meta", "Post meta", meta, inserter=False,
          description="Date, author and categories for a single post.")

    comments = (
        '<!-- wp:comments -->\n'
        '<div class="wp-block-comments">\n'
        '<!-- wp:comments-title /-->\n'
        '<!-- wp:comment-template -->\n'
        '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->\n'
        '<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)">\n'
        '<!-- wp:comment-author-name {"fontSize":"small"} /-->\n'
        '<!-- wp:comment-date {"fontSize":"small"} /-->\n'
        '<!-- wp:comment-content /-->\n'
        '<!-- wp:comment-reply-link {"fontSize":"small"} /-->\n'
        '</div>\n'
        '<!-- /wp:group -->\n'
        '<!-- /wp:comment-template -->\n'
        '<!-- wp:comments-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->\n'
        '<!-- wp:comments-pagination-previous /-->\n'
        '<!-- wp:comments-pagination-numbers /-->\n'
        '<!-- wp:comments-pagination-next /-->\n'
        '<!-- /wp:comments-pagination -->\n'
        '<!-- wp:post-comments-form /-->\n'
        '</div>\n'
        '<!-- /wp:comments -->'
    )
    write("hidden-comments", "Comments",
          group(comments, align="full", background="surface", padding_y="70", layout="constrained"),
          inserter=False, description="The comments area for a single post.")

    notfound = "\n".join([
        heading("That page has moved on", level=1, align="center"),
        paragraph("The address may be old, or the page may have been renamed. "
                  "Try a search, or start again from the home page.", align="center", color="muted"),
        '<!-- wp:search {"label":"Search","buttonText":"Search","align":"center"} /-->',
        buttons([button("Back to the home page", "/")], align="center"),
    ])
    write("hidden-404", "404 content",
          group(notfound, align="full", padding_y="80", layout="constrained", content_size="620px", gap="30"),
          inserter=False, description="What a visitor sees when nothing is there.")


# ---------------------------------------------------------------------------
# Whole pages
# ---------------------------------------------------------------------------
def ref(slug):
    return '<!-- wp:pattern {"slug":"unioncorp/%s"} /-->' % slug


def build_pages():
    pages = {
        "page-home": ("Page: home", ["hero", "welcome", "services", "quality", "case-studies",
                                     "counters", "team", "testimonials", "blog-latest", "cta"]),
        "page-about": ("Page: about", ["welcome", "quality", "counters", "team", "testimonials", "cta"]),
        "page-services": ("Page: services", ["services", "quality", "testimonials", "cta"]),
        "page-work": ("Page: case studies", ["case-studies", "counters", "cta"]),
        "page-pricing": ("Page: pricing", ["pricing", "testimonials", "cta"]),
        "page-contact": ("Page: contact", ["contact", "cta"]),
    }
    for slug, (title, refs) in pages.items():
        write(slug, title, "\n".join(ref(r) for r in refs), categories=PAGES,
              description="A complete %s page, built from the theme's sections." % title.split(": ")[1])


def main():
    build_header()
    build_footer()
    build_sidebar()
    build_hidden()
    build_page_banner()
    build_hero()
    build_welcome()
    build_services()
    build_quality()
    build_case_studies()
    build_counters()
    build_team()
    build_testimonials()
    build_pricing()
    build_blog_latest()
    build_cta()
    build_contact()
    build_pages()

    for slug in sorted(WRITTEN):
        print("  patterns/%s.php" % slug)
    print("\n%d patterns" % len(WRITTEN))


if __name__ == "__main__":
    main()
