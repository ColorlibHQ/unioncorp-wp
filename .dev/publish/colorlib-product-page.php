<?php
/**
 * Build the Unioncorp product page on colorlib.com/wp, as a child of the themes
 * listing (5091).
 *
 * Same shape as the Pato, Academia, Unapp and Philosophy pages: what the theme
 * is, what it looks like, what you get, and the questions people ask. Unioncorp
 * leads with the thing a firm's site needs most and normally buys a plugin for:
 * an enquiry form.
 *
 * Every factual claim below was checked against this theme's own code rather
 * than carried over from Pato's page, and three of Pato's would have been wrong
 * here: Unioncorp styles six form plugins, not eight (no Formidable, no
 * HappyForms); its update check also sends a multisite flag; and its site
 * identifier is an HMAC keyed with the site's own salt, not a plain hash. There
 * is no documentation page yet, so nothing links one.
 *
 * Idempotent: creates the page the first time, rewrites it after that, and
 * leaves it a DRAFT. colorlib.com's convention is draft first, publish
 * separately.
 *
 * Images are found by file name, not by attachment ID, and the script refuses
 * to save while any is missing — a product page with a broken hero is worse
 * than no page.
 *
 *   Copy this file to the server, then run it with WP-CLI from the WordPress
 *   root, as the user PHP runs as:
 *     wp --url=https://colorlib.com/wp/ eval "require '/tmp/colorlib-product-page.php';"
 *
 * Use `wp eval "require …"`, not `wp eval-file`, which runs in a function scope.
 */

defined( 'ABSPATH' ) || exit;

$slug   = 'unioncorp';
$parent = 5091;

$download = 'https://updates.colorlib.com/download/theme/unioncorp.zip';
$demo     = 'https://colorlibhub.com/unioncorp/';

// ---------------------------------------------------------------------------
// Images, by file name
// ---------------------------------------------------------------------------

/*
 * colorlib.com's uploads have no year/month folder, so `_wp_attached_file` is
 * the bare file name and `LIKE '%/name'` alone never matches. A file over the
 * size threshold is stored as `name-scaled.jpg`, so accept that too.
 */
$find_image = static function ( $file ) {
	global $wpdb;
	$scaled = preg_replace( '/\.(jpe?g|png)$/i', '-scaled.$1', $file );
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta}
			 WHERE meta_key = '_wp_attached_file'
			   AND ( meta_value = %s OR meta_value = %s OR meta_value LIKE %s OR meta_value LIKE %s )
			 ORDER BY post_id DESC LIMIT 1",
			$file,
			$scaled,
			'%/' . $wpdb->esc_like( $file ),
			'%/' . $wpdb->esc_like( $scaled )
		)
	);
};

$wanted = array(
	'card'     => 'unioncorp-free-business-wordpress-theme.jpg',
	'home'     => 'unioncorp-wordpress-theme-home.jpg',
	'services' => 'unioncorp-wordpress-theme-services.jpg',
	'enquiry'  => 'unioncorp-wordpress-theme-enquiry.jpg',
	'palettes' => 'unioncorp-wordpress-theme-palettes.jpg',
	'dark'     => 'unioncorp-wordpress-theme-dark-mode.jpg',
	'blog'     => 'unioncorp-wordpress-theme-blog.jpg',
);

$img     = array();
$missing = array();
foreach ( $wanted as $key => $file ) {
	$img[ $key ] = $find_image( $file );
	if ( ! $img[ $key ] ) {
		$missing[] = $file;
	}
}

if ( $missing ) {
	echo "ERROR: not in the media library yet, refusing to build a page with gaps:\n  " . implode( "\n  ", $missing ) . "\n";
	return;
}

// vc_btn's `link` attribute is WPBakery's own "url:…|title:…|target:…" encoding.
// It splits on "|" then on the first ":", so a raw URL is cut off at "https:"
// and the button renders href="http://https". Percent-encode anything in a link.
$download_enc = rawurlencode( $download );
$demo_enc     = rawurlencode( $demo );

$btn_css = 'display:inline-block !important;vertical-align:middle !important;margin-right:12px !important;margin-bottom:10px !important;';
$tint    = '#f9faff';
$accent  = '#2c62d6';

// Look up by slug AND parent: get_page_by_path( 'unioncorp' ) finds only a
// top-level page, and this one is a child of 5091, so that lookup would miss it
// and create a duplicate every run.
$found = get_posts(
	array(
		'post_type'   => 'page',
		'name'        => $slug,
		'post_parent' => $parent,
		// An explicit list, not 'any': in WP_Query 'any' leaves drafts out, so an
		// idempotent script would keep re-creating its own draft.
		'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'numberposts' => 1,
	)
);

$existing = $found ? $found[0] : null;
$page_id  = $existing ? $existing->ID : 0;

// ---------------------------------------------------------------------------
// Content
// ---------------------------------------------------------------------------

$features = array(
	array( 'envelope', 'An enquiry form without a plugin', 'Validated on the server, protected by a nonce and a honeypot, and it works with JavaScript turned off. One filter hands each enquiry to a CRM, a webhook or a plugin instead.' ),
	array( 'th-large', 'Sections a firm needs', 'Services, case studies, pricing plans, counters, testimonials and a team. Each is a pattern you insert and then edit like any other block.' ),
	array( 'paint-brush', 'Eight palettes, five type pairings', 'Every palette is measured against WCAG AA before the theme is built: text, links and button labels, resting and hovered, in light mode and in dark.' ),
	array( 'moon-o', 'Dark mode', 'A switch for the visitor, separate from the palette you chose. It lifts the palette rather than replacing it, so an Emerald site stays green.' ),
	array( 'plug', 'Your form plugin, styled', 'Contact Form 7, WPForms, Gravity Forms, Fluent Forms, Forminator and Ninja Forms take on the theme’s colours and spacing instead of looking like another website.' ),
	array( 'shopping-cart', 'WooCommerce ready', 'Styled if you install WooCommerce, and it loads nothing at all if you do not.' ),
);

$feature_boxes = '';
foreach ( $features as $f ) {
	list( $icon, $heading, $body ) = $f;
	$feature_boxes .= '[vc_column width="1/3"][vcex_icon_box style="two" heading="' . esc_attr( $heading ) . '" heading_type="h3"'
		. ' icon="fa fa-' . $icon . '" icon_color="' . $accent . '" icon_size="28px" heading_size="20px"'
		. ' content_font_size="15px" css=".vc_custom_uc_f_' . sanitize_key( $icon ) . '{margin-bottom:26px !important;}"]'
		. $body . '[/vcex_icon_box][/vc_column]';
}

$faqs = array(
	array( 'Do I need a plugin?', 'No. The enquiry form and dark mode are part of the theme. WooCommerce is styled if you add it, and is not required.' ),
	array( 'Where do the enquiries go?', 'To the site’s admin email address by default. If you already use a CRM, a webhook or a form plugin, the <code>unioncorp_enquiry_handlers</code> filter hands each enquiry to it instead, and Unioncorp sends nothing of its own.' ),
	array( 'Which form plugins does it style?', 'Contact Form 7, WPForms, Gravity Forms, Fluent Forms, Forminator and Ninja Forms are mapped onto the theme’s own colours and spacing, so a form block does not look like a different website.' ),
	array( 'Can I change the colours?', 'Eight palettes and five type pairings ship with the theme, and each is a one-click choice in the Site Editor under Styles. Beyond that, every colour in the theme is a palette entry you can edit there.' ),
	array( 'Can I turn dark mode off?', 'Yes. Add <code>add_filter( \'unioncorp_enable_dark_mode\', \'__return_false\' );</code> to a child theme or a small plugin.' ),
	array( 'Is it translation ready?', 'Yes. Every string is translatable and <code>languages/unioncorp.pot</code> is included.' ),
	array( 'Does it check for updates?', 'Yes, twice a day, because it is distributed outside the WordPress.org theme directory. It sends the theme, WordPress and PHP versions, the locale, whether the site is a multisite, and an identifier derived from the site’s address with the site’s own secret key, so it cannot be turned back into the address. The <code>unioncorp_check_for_updates</code> filter switches it off.' ),
);

$toggles = '';
foreach ( $faqs as $i => $faq ) {
	// vcex_toggle takes `heading`. With `title` every toggle renders the
	// shortcode's own placeholder, "Lorem ipsum dolor sit amet?".
	$toggles .= '[vcex_toggle heading="' . esc_attr( $faq[0] ) . '" heading_type="h3" css=".vc_custom_uc_q' . $i . '{margin-bottom:10px !important;}"]'
		. $faq[1] . '[/vcex_toggle]';
}

$specs = array(
	'Requires'     => 'WordPress 6.6 or newer',
	'PHP'          => '7.4 or newer',
	'Tested up to' => 'WordPress 7.1',
	'Licence'      => 'GNU General Public License v2 or later',
	'Patterns'     => '21 to insert, plus 8 the templates use',
	'Templates'    => '14, plus 3 template parts',
	'Styles'       => '8 colour palettes × 5 type pairings',
	'Fonts'        => 'Poppins and Inter, self-hosted',
	'Form plugins' => 'Contact Form 7, WPForms, Gravity Forms, Fluent Forms, Forminator, Ninja Forms',
	'Build step'   => 'None — no npm, no SCSS',
);

$spec_rows = '';
foreach ( $specs as $label => $value ) {
	$spec_rows .= '<tr><th style="text-align:left;padding:10px 18px 10px 0;border-bottom:1px solid #e3e8f2;font-weight:600;white-space:nowrap;vertical-align:top;">'
		. esc_html( $label ) . '</th><td style="padding:10px 0;border-bottom:1px solid #e3e8f2;">' . $value . '</td></tr>';
}

$buttons = '[vc_btn title="Download Unioncorp" style="flat" color="blue" link="url:{$download_enc}|title:Download%20Unioncorp|target:_blank" css=".vc_custom_uc{N}a{{$btn_css}}" i_icon_fontawesome="fa fa-download" add_icon="true"]'
	. '[vc_btn title="Live demo" style="flat" color="grey" link="url:{$demo_enc}|title:Live%20demo|target:_blank" css=".vc_custom_uc{N}b{{$btn_css}}" i_icon_fontawesome="fa fa-eye" add_icon="true"]';
$buttons = str_replace( array( '{$download_enc}', '{$demo_enc}', '{$btn_css}' ), array( $download_enc, $demo_enc, $btn_css ), $buttons );
$top_buttons    = str_replace( '{N}', '004', $buttons );
$bottom_buttons = str_replace( '{N}', '083', $buttons );

$section = static function ( $n, $bg, $heading, $text, $image ) use ( $tint ) {
	$background = $bg ? "background-color:{$tint} !important;" : '';
	return "[vc_row css=\".vc_custom_uc{$n}0{padding-top:56px !important;padding-bottom:20px !important;{$background}}\"][vc_column width=\"1/1\"]"
		. "[vcex_heading text=\"" . esc_attr( $heading ) . "\" tag=\"h2\" font_size=\"34px\" text_align=\"center\" bottom_margin=\"14px\" font_weight=\"700\"]"
		. "[vc_column_text css=\".vc_custom_uc{$n}1{text-align:center !important;max-width:790px !important;margin-left:auto !important;margin-right:auto !important;}\"]{$text}[/vc_column_text]"
		. "[/vc_column][/vc_row][vc_row css=\".vc_custom_uc{$n}2{padding-bottom:56px !important;{$background}}\"][vc_column width=\"1/1\"]"
		. "[vcex_image image_id=\"{$image}\" align=\"center\" border_radius=\"12px\" bottom_margin=\"0px\"][/vc_column][/vc_row]";
};

$content = "[vc_row css=\".vc_custom_uc001{padding-top:64px !important;padding-bottom:40px !important;background-color:{$tint} !important;}\"][vc_column width=\"1/1\"]"
	. '[vcex_heading text="A finance and consulting theme with the enquiry form built in" tag="h2" font_size="46px" text_align="center" bottom_margin="20px" font_weight="700"]'
	. '[vc_column_text css=".vc_custom_uc002{text-align:center !important;font-size:18px !important;max-width:820px !important;margin-left:auto !important;margin-right:auto !important;}"]'
	. 'Unioncorp is a free block theme for financial advisers, consultancies and professional-services firms. It ships the page a firm needs most and normally buys a plugin for — an enquiry form that works without one — along with services, case studies, pricing and team sections, eight colour palettes and full site editing throughout.'
	. '[/vc_column_text][vc_column_text css=".vc_custom_uc003{text-align:center !important;margin-top:26px !important;}"]' . $top_buttons . '[/vc_column_text]'
	. "[/vc_column][/vc_row][vc_row css=\".vc_custom_uc006{padding-top:0px !important;padding-bottom:64px !important;background-color:{$tint} !important;}\"][vc_column width=\"1/1\"]"
	. "[vcex_image image_id=\"{$img['home']}\" align=\"center\" border_radius=\"14px\" bottom_margin=\"0px\"][/vc_column][/vc_row]\n\n"

	. $section( '01', false, 'An enquiry form without a plugin',
		'The contact form is part of the theme. It validates on the server, carries a nonce and a honeypot, and works with JavaScript turned off. Enquiries go to the site’s admin address — or, with one filter, to whatever CRM, webhook or form plugin you already use.',
		$img['enquiry'] ) . "\n\n"

	. $section( '02', true, 'Services, case studies and a team, as blocks',
		'Eight service cards, a case-study grid, pricing plans, counters, testimonials and a team four across. Each one is a pattern: insert it, change the words and the photographs, and it stays exactly as editable as a paragraph.',
		$img['services'] ) . "\n\n"

	. $section( '03', false, 'Eight palettes, checked before release',
		'Azure, Emerald, Navy, Slate, Teal and Plum, and two dark palettes, Midnight and Graphite. Every one is measured against WCAG AA before the theme is built — text, links and button labels, resting and hovered, in light mode and in dark — and a palette that fails is not written.',
		$img['palettes'] ) . "\n\n"

	. $section( '04', true, 'Dark mode the visitor controls',
		'The switch is the visitor’s, separate from the palette you chose. It follows the reader’s system setting until they decide for themselves, and it lifts your palette rather than replacing it, so an Emerald site stays green.',
		$img['dark'] ) . "\n\n"

	. "[vc_row css=\".vc_custom_uc050{padding-top:56px !important;padding-bottom:16px !important;}\"][vc_column width=\"1/1\"]"
	. '[vcex_heading text="What you get" tag="h2" font_size="34px" text_align="center" bottom_margin="34px" font_weight="700"][/vc_column][/vc_row]'
	. "[vc_row css=\".vc_custom_uc051{padding-bottom:40px !important;}\"]{$feature_boxes}[/vc_row]\n\n"

	. $section( '06', true, 'It has a blog, too',
		'Advice, market notes, news from the firm. Archives, single posts, categories, tags, author pages, search and a 404 are all designed rather than inherited, with an optional sidebar layout for posts and pages.',
		$img['blog'] ) . "\n\n"

	. "[vc_row css=\".vc_custom_uc070{padding-top:56px !important;padding-bottom:18px !important;}\"][vc_column width=\"1/1\"]"
	. '[vcex_heading text="Questions" tag="h2" font_size="34px" text_align="center" bottom_margin="28px" font_weight="700"][/vc_column][/vc_row]'
	. "[vc_row css=\".vc_custom_uc071{padding-bottom:48px !important;}\"][vc_column width=\"1/1\"]{$toggles}[/vc_column][/vc_row]\n\n"

	. "[vc_row css=\".vc_custom_uc080{padding-top:48px !important;padding-bottom:56px !important;background-color:{$tint} !important;}\"][vc_column width=\"1/1\"]"
	. '[vcex_heading text="The details" tag="h2" font_size="34px" text_align="center" bottom_margin="26px" font_weight="700"]'
	. '[vc_column_text css=".vc_custom_uc081{max-width:680px !important;margin-left:auto !important;margin-right:auto !important;}"]<table style="width:100%;border-collapse:collapse;">' . $spec_rows . '</table>[/vc_column_text]'
	. '[vc_column_text css=".vc_custom_uc082{text-align:center !important;margin-top:34px !important;}"]' . $bottom_buttons . '[/vc_column_text]'
	. '[/vc_column][/vc_row]';

// ---------------------------------------------------------------------------
// Save
// ---------------------------------------------------------------------------

// kses strips the shortcode attributes this page is made of.
$kses = has_filter( 'content_save_pre', 'wp_filter_post_kses' );
if ( $kses ) {
	kses_remove_filters();
}

$args = array(
	'post_title'   => 'Unioncorp',
	'post_name'    => $slug,
	'post_content' => $content,
	// Draft on creation, but never demote a page that is already published:
	// rebuilding the copy of a live page must not take it off the site.
	'post_status'  => $existing ? $existing->post_status : 'draft',
	'post_type'    => 'page',
	'post_parent'  => $parent,
);

if ( $page_id ) {
	$args['ID'] = $page_id;
	$result     = wp_update_post( $args, true );
} else {
	$result  = wp_insert_post( $args, true );
	$page_id = is_wp_error( $result ) ? 0 : $result;
}

if ( $kses ) {
	kses_init_filters();
}

if ( is_wp_error( $result ) ) {
	echo 'ERROR: ' . $result->get_error_message() . "\n";
	return;
}

set_post_thumbnail( $page_id, $img['card'] );

// WPBakery keeps every css="…" rule in _wpb_shortcodes_custom_css and only
// regenerates it when the page is saved through the builder UI. Updating
// post_content programmatically leaves that meta stale, so every css= edit
// after the first save is silently inert.
if ( function_exists( 'visual_composer' ) && method_exists( visual_composer(), 'buildShortcodesCss' ) ) {
	visual_composer()->buildShortcodesCss( $page_id, 'custom' );
	visual_composer()->buildShortcodesCss( $page_id, 'default' );
	echo "custom css rebuilt\n";
} else {
	echo "WARNING: could not rebuild the WPBakery custom css\n";
}

$saved = get_post_field( 'post_content', $page_id );

echo 'page: ' . $page_id . ' (' . get_post_status( $page_id ) . '), parent ' . wp_get_post_parent_id( $page_id ) . "\n";
echo 'images: ' . wp_json_encode( $img ) . "\n";
echo 'length: ' . strlen( $saved ) . "\n";
echo 'icon boxes: ' . substr_count( $saved, '[vcex_icon_box' ) . "\n";
echo 'toggles: ' . substr_count( $saved, '[vcex_toggle' ) . ' (with heading=: ' . substr_count( $saved, '[vcex_toggle heading=' ) . ")\n";
echo 'section images: ' . substr_count( $saved, '[vcex_image' ) . "\n";
echo 'buttons: ' . substr_count( $saved, '[vc_btn' ) . "\n";
echo 'unbalanced rows: ' . ( substr_count( $saved, '[vc_row' ) - substr_count( $saved, '[/vc_row]' ) ) . "\n";
echo 'documentation links: ' . substr_count( $saved, 'documentation' ) . " (must be 0 — there is no docs page)\n";
