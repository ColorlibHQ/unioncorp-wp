=== Unioncorp ===

Contributors: colorlib
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.1
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

= Can I turn the scroll animations off? =

Yes: add add_filter( 'unioncorp_enable_scroll_animations', '__return_false' );
to a child theme or a small plugin. That stops sections fading in and figures
counting up; the video popup keeps working. Visitors who have asked their
system for reduced motion never see the animations either way.

== Theme Check ==

Theme Check reports three REQUIRED findings and no warnings. All three are
deliberate, and each is the price of something the theme does on purpose.

1. **add_shortcode() in inc/enquiry.php.** The enquiry form has to keep working
   after a pattern is expanded into a page's content, where PHP never runs. A
   shortcode is the only mechanism WordPress offers for that. Moving it to a
   plugin would mean the form stops working the moment the plugin is disabled,
   on a page the theme built.
2. **Unsplash photographs.** The demo images are Unsplash-licensed, which is not
   GPL-compatible. Replace them with your own and the finding goes with them.
3. **Update URI in style.css.** This theme is distributed outside the
   WordPress.org directory and checks colorlib.com for its own updates. A theme
   inside the directory must not carry this header.

== Copyright ==

Unioncorp WordPress Theme, (C) 2026 Colorlib.
Unioncorp is distributed under the terms of the GNU GPL v2 or later.

Poppins and Inter
License: SIL Open Font License 1.1
Source: https://fontsource.org/

Tabler Icons
License: MIT, https://github.com/tabler/tabler-icons/blob/main/LICENSE
Source: https://tabler.io/icons

Photographs
License: Unsplash License, https://unsplash.com/license
Source: https://unsplash.com/

== Changelog ==

= 1.1.1 =
* Fixed: the map on the contact page sat flush left instead of centred beneath the form, leaving an empty band beside it on wide screens. It had been like this since 1.0.

= 1.1.0 =
* Added: sections, columns and blog posts fade and rise into view as you scroll. Nothing on the first screen is hidden, and nothing is hidden at all without JavaScript or for visitors who prefer reduced motion. A filter switches it off (see the FAQ).
* Added: the figures in the statistics band count up when they come into view. Screen readers are given the final figure.
* Added: "Watch the video" plays in a popup, using YouTube's privacy-enhanced embed. Without JavaScript it is an ordinary link to the video.
* Added: the case study photographs open in a lightbox, and gallery, team and blog photographs zoom slightly on hover.
* Added: icons on the service cards, the four new feature cards in the welcome section, the top bar, the contact details, the footer, the testimonials and post dates. They take their colour from the palette, so they follow every style variation and dark mode.
* Added: social links in the top bar and the footer.
* Changed: cards lift on hover and their icon tile fills with the brand colour. The ghost button has a real outline, the tick list has ticks and eyebrow labels are set in spaced capitals.
* Fixed: the Card, Panel, Ghost, Tick list and Eyebrow block styles had no styling behind them, so choosing one in the editor changed nothing.
* Fixed: the header navigation wrapped onto two lines at laptop widths. It now stays on one line and switches to the menu button below 960px.
* Fixed: uneven blank bands between sections. WordPress's gap between blocks was being added between full-width sections, and above and below the content on some templates but not others.
* Fixed: on the hero and "Why us" photographs, the main button is now white with a dark label. The brand blue measured 1.06:1 against the darkened photograph, which left the most important button on the page nearly invisible. The "Why us" label, at 2.27:1, is white too, and the hero photograph is darkened a little more.
* Fixed: team and testimonial names now match the people in the photographs.
* Removed: the icon font 1.0 used, which was licensed CC BY 3.0 and is not GPL-compatible. Pages built with 1.0 keep their icons: the old icon classes now draw the icons that replaced them.

= 1.0.1 =
* Fixed: the enquiry form's anti-spam field was visible above the form, labelled "Leave this field empty". Its hiding rule targeted a class name the form never used.
* Fixed: for the same reason the form's two-column layout, the spacing above its button, and its styled success and error messages never applied. Name and email, and phone and company, now sit side by side, with the message across the full width.
* Changed: the success message is set in the text colour with a green edge instead of green text. Measured across all eight palettes in light and dark mode, green text failed WCAG AA contrast in most of them.

= 1.0.0 =
* Initial release.
