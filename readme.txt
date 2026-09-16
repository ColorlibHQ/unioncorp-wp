=== Unioncorp ===

Contributors: colorlib
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: business, portfolio, blog, full-site-editing, block-patterns, block-styles, template-editing, wide-blocks, accessibility-ready, translation-ready, custom-colors, custom-menu, custom-logo, featured-images, threaded-comments, one-column, two-columns, right-sidebar, rtl-language-support, sticky-post, theme-options

A block theme for finance, consulting and professional services.

== Description ==

Unioncorp is a full site editing theme for finance and consulting businesses:
services, case studies, pricing plans, team profiles, testimonials, a blog and
an enquiry form that needs no plugin.

Eight colour palettes and five type pairings, each checked for contrast before
release rather than by eye. Visitor-facing dark mode that follows the reader's
system setting until they choose for themselves. WooCommerce is styled if you
install it and loads nothing if you do not.

Everything is editable in the Site Editor: the header, the footer, every
template and every section. Nothing here depends on a page builder.

== Installation ==

1. In WordPress, go to Appearance → Themes → Add New Theme → Upload Theme.
2. Choose unioncorp.zip and click Install Now, then Activate.
3. Appearance → Editor is where the header, footer, colours and templates live.

== Frequently Asked Questions ==

= Do I need a plugin for the enquiry form? =

No. The form is part of the theme and sends with WordPress's own wp_mail(). If
your host cannot send mail, install any SMTP plugin — whatever fixes a lost
password-reset email fixes the form too.

= How do I change the colours? =

Appearance → Editor → Styles → Browse styles. Eight palettes are included, and
each one restyles every section. To change a single colour, open Styles →
Colors → Edit palette.

= Why is the brand blue not used for links? =

Unioncorp's blue, #4f86f9, is 3.4:1 against white, which fails WCAG AA for text
and button labels. It ships as the decorative accent, and a deeper blue carries
links, buttons and anything else you have to read.

= Can I turn dark mode off? =

Yes: add add_filter( 'unioncorp_enable_dark_mode', '__return_false' ); to a
child theme or a small plugin.

== Theme Check ==

Theme Check reports four REQUIRED findings and no warnings. All four are
deliberate, and each is the price of something the theme does on purpose.

1. **add_shortcode() in inc/enquiry.php.** The enquiry form has to keep working
   after a pattern is expanded into a page's content, where PHP never runs. A
   shortcode is the only mechanism WordPress offers for that. Moving it to a
   plugin would mean the form stops working the moment the plugin is disabled,
   on a page the theme built.
2. **Unsplash photographs.** The demo images are Unsplash-licensed, which is not
   GPL-compatible. Replace them with your own and the finding goes with them.
3. **The Flaticon icon font**, used by the service cards, is CC BY 3.0:
   attribution is required and the licence is not GPL-compatible. Credited
   below. To remove it entirely, delete the @font-face and glyph rules from
   style.css and replace the icons in patterns/services.php with inline SVG.
4. **Update URI in style.css.** This theme is distributed outside the
   WordPress.org directory and checks colorlib.com for its own updates. A theme
   inside the directory must not carry this header.

== Copyright ==

Unioncorp WordPress Theme, (C) 2026 Colorlib.
Unioncorp is distributed under the terms of the GNU GPL v2 or later.

Poppins and Inter
License: SIL Open Font License 1.1
Source: https://fontsource.org/

Flaticon icon font
License: CC BY 3.0
Source: https://www.flaticon.com/

Photographs
License: Unsplash License, https://unsplash.com/license
Source: https://unsplash.com/

== Changelog ==

= 1.0.0 =
* Initial release.
