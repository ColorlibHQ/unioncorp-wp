<?php
/**
 * Build the Unioncorp documentation page, a child of the product page.
 *
 * Written for someone who has just installed the theme and wants their firm's
 * site ready this week. Every step that happens on a screen has a screenshot, a
 * short animation or a video, captured from WordPress Playground with Unioncorp
 * 1.1.2 (setup and editor) and from the live demo (the front end).
 *
 * Same machinery as the Pato and Bonkers documentation: media is found in the
 * library by file name and carries the alt text the importer set from
 * media/manifest.json; the run stops before saving if anything is missing
 * (UNIONCORP_DOCS_ALLOW_MISSING=1 saves anyway). An .mp4 becomes a <video> that
 * plays only while it is on screen, and never for a reader who asked their
 * system for reduced motion.
 *
 * Idempotent. Keeps the page's current status; a new page starts as a draft.
 *
 *   wp --url=https://colorlib.com/wp/ eval-file colorlib-docs-page.php
 */

defined( 'ABSPATH' ) || exit;

$slug     = 'documentation';
$parent   = 381648;                    // the Unioncorp product page
$demo     = 'https://colorlibhub.com/unioncorp/';
$download = 'https://updates.colorlib.com/download/theme/unioncorp.zip';
$support  = 'https://colorlibsupport.com/';

$demo_enc     = rawurlencode( $demo );
$download_enc = rawurlencode( $download );
$btn_css      = 'display:inline-block !important;vertical-align:middle !important;margin-right:12px !important;margin-bottom:10px !important;';

// Slug AND parent, and explicit statuses ('any' leaves drafts out).
$found    = get_posts(
	array(
		'post_type'   => 'page',
		'name'        => $slug,
		'post_parent' => $parent,
		'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'numberposts' => 1,
	)
);
$existing = $found ? $found[0] : null;
$page_id  = $existing ? $existing->ID : 0;

// Token => [file in the media library, caption (HTML), max width in px or 0].
$GLOBALS['uc_docs_missing'] = array();
$GLOBALS['uc_docs_figures'] = array(
	'upload'     => array( 'unioncorp-docs-upload-theme.png', 'Appearance → Themes → Add New Theme → <strong>Upload Theme</strong>.', 0 ),
	'activate'   => array( 'unioncorp-docs-activation.mp4', 'Activating Unioncorp on a new site: the pages and menu are built, and the home page is ready.', 0 ),
	'pages'      => array( 'unioncorp-docs-pages-created.png', 'Straight after activation: seven new pages, with Home as the front page and Blog as the posts page.', 0 ),
	'map'        => array( 'unioncorp-docs-front-page-map.jpg', 'The home page, top to bottom. Sections are page content; the header and footer are template parts shared by every page. Click to see it full size.', 0 ),
	'figure'     => array( 'unioncorp-docs-edit-figure.gif', 'Changing a figure: click it, select it, type, then Save.', 0 ),
	'listview'   => array( 'unioncorp-docs-list-view.jpg', 'List View (left) with the services section opened up. Clicking a row selects that block on the page.', 0 ),
	'inserter'   => array( 'unioncorp-docs-pattern-inserter.jpg', 'The block inserter’s Patterns tab with <em>Unioncorp: sections</em> open.', 0 ),
	'icon'       => array( 'unioncorp-docs-change-icon.gif', 'Changing a card’s icon from calculator to briefcase in Advanced → Additional CSS class(es).', 0 ),
	'icons'      => array( 'unioncorp-docs-icons.png', 'The seventeen icons and the name to use for each.', 0 ),
	'tour'       => array( 'unioncorp-docs-scroll-tour.mp4', 'The home page scrolled at reading pace: sections fade in and the statistics count up.', 0 ),
	'popup'      => array( 'unioncorp-docs-video-popup.gif', '<strong>Watch the video</strong> opens the video over the page. Press Esc, click × or click outside it to close.', 0 ),
	'contact'    => array( 'unioncorp-docs-contact.jpg', 'The contact section on the Contact page: your details on the left, the enquiry form on the right.', 0 ),
	'parts'      => array( 'unioncorp-docs-template-parts.jpg', 'Appearance → Editor → Patterns → <em>All template parts</em>.', 0 ),
	'header'     => array( 'unioncorp-docs-edit-header.jpg', 'The Header template part. Click the grey placeholder to add your logo.', 0 ),
	'nav'        => array( 'unioncorp-docs-navigation.jpg', 'Appearance → Editor → Navigation, with the Primary menu open.', 0 ),
	'styles'     => array( 'unioncorp-docs-styles.jpg', 'Appearance → Editor → <strong>Styles</strong>.', 0 ),
	'palettes'   => array( 'unioncorp-docs-palettes.gif', 'Browse styles: each click previews a palette. Your live site does not change until you click Save.', 0 ),
	'browse'     => array( 'unioncorp-docs-browse-styles.jpg', 'Browse styles: the eight palettes, then the five font pairings plus the default.', 0 ),
	'dark'       => array( 'unioncorp-docs-dark-mode.gif', 'The moon in the top bar switches a visitor to dark mode, and back again.', 0 ),
	'darkstill'  => array( 'unioncorp-docs-dark-mode.jpg', 'The same section in light and in dark mode. The palette stays; the grounds and text turn over.', 0 ),
	'template'   => array( 'unioncorp-docs-template-choice.jpg', 'Page tab → Template → <strong>Change template</strong>.', 0 ),
);

/**
 * Attachment ID for a file name. colorlib.com stores uploads with no month
 * folder, so match the bare name as well as a `YYYY/MM/name` path.
 *
 * @param string $file File name.
 * @return int
 */
function uc_docs_attachment_id( $file ) {
	global $wpdb;
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND ( meta_value = %s OR meta_value LIKE %s ) ORDER BY post_id ASC LIMIT 1",
			$file,
			'%/' . $wpdb->esc_like( $file )
		)
	);
}

/**
 * Replace a {{fig:token}} with a figure, on one line so wpautop leaves it alone.
 *
 * @param array $m Regex match.
 * @return string
 */
function uc_docs_figure( $m ) {
	$figures = $GLOBALS['uc_docs_figures'];
	if ( ! isset( $figures[ $m[1] ] ) ) {
		$GLOBALS['uc_docs_missing'][] = 'unknown token ' . $m[1];
		return '';
	}
	list( $file, $caption, $max ) = $figures[ $m[1] ];
	$id = uc_docs_attachment_id( $file );
	if ( ! $id ) {
		$GLOBALS['uc_docs_missing'][] = $file;
		return '';
	}
	$url  = wp_get_attachment_url( $id );
	$alt  = get_post_meta( $id, '_wp_attachment_image_alt', true );
	$open = '<figure class="uc-docs-fig"' . ( $max ? ' style="max-width:' . (int) $max . 'px"' : '' ) . '>';
	$cap  = $caption ? '<figcaption>' . $caption . '</figcaption>' : '';

	if ( '.mp4' === substr( $file, -4 ) ) {
		$poster_id = uc_docs_attachment_id( substr( $file, 0, -4 ) . '-poster.jpg' );
		if ( ! $poster_id ) {
			$GLOBALS['uc_docs_missing'][] = substr( $file, 0, -4 ) . '-poster.jpg';
			return '';
		}
		// No autoplay attribute: the script below starts it once it is on screen.
		return $open
			. '<video class="uc-docs-video" src="' . esc_url( $url ) . '" poster="' . esc_url( wp_get_attachment_url( $poster_id ) ) . '" width="1280" height="800" muted loop playsinline controls preload="none" aria-label="' . esc_attr( $alt ) . '"></video>'
			. $cap . '</figure>';
	}

	$meta = wp_get_attachment_metadata( $id );
	$size = ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) ? ' width="' . (int) $meta['width'] . '" height="' . (int) $meta['height'] . '"' : '';

	return $open
		. '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener"><img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '"' . $size . ' loading="lazy" decoding="async"></a>'
		. $cap . '</figure>';
}

/**
 * One documentation section: an anchored row with a heading and its body.
 *
 * @param string $n      Section number, for a distinct css= class per row.
 * @param string $anchor Row id, the target of the contents links.
 * @param string $head   Heading.
 * @param string $body   HTML body, with {{fig:token}} placeholders.
 * @return string
 */
function uc_docs_section( $n, $anchor, $head, $body ) {
	$body = preg_replace_callback( '/\{\{fig:([a-z0-9-]+)\}\}/', 'uc_docs_figure', $body );

	return '[vc_row el_id="' . $anchor . '" el_class="uc-docs" css=".vc_custom_ucdoc' . $n . '{padding-top:34px !important;padding-bottom:6px !important;}"][vc_column width="1/1"]'
		. '[vcex_heading text="' . esc_attr( $head ) . '" tag="h2" font_size="28px" bottom_margin="14px" font_weight="700"]'
		. '[vc_column_text css=".vc_custom_ucdoct' . $n . '{max-width:860px !important;}"]' . $body . '[/vc_column_text]'
		. '[/vc_column][/vc_row]';
}

$css = '<style>'
	. 'html{scroll-behavior:smooth}.uc-docs{scroll-margin-top:110px}'
	. '.uc-docs h3{font-size:20px;margin:30px 0 10px}'
	. '.uc-docs-fig{margin:22px 0 30px}.uc-docs-fig img,.uc-docs-fig video{display:block;width:100%;height:auto;border:1px solid #e3e8f2;border-radius:8px;background:#f4f7fc}'
	. '.uc-docs-fig figcaption{font-size:14px;line-height:1.55;color:#55677a;margin-top:9px}'
	. '.uc-docs-tip{background:#f4f7fc;border-left:4px solid #2c62d6;border-radius:4px;padding:14px 18px 2px;margin:22px 0}'
	. '.uc-docs kbd{display:inline-block;font:600 13px/1.5 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;background:#fff;border:1px solid #d4dbe6;border-bottom-width:2px;border-radius:4px;padding:0 6px;color:#0b2033;white-space:nowrap}'
	. '.uc-docs code{font-size:.9em;background:#f4f7fc;padding:1px 5px;border-radius:3px;word-break:break-word}'
	. '.uc-docs-steps li{margin-bottom:8px}'
	. '.uc-docs-table{overflow-x:auto;margin:18px 0 24px}.uc-docs-table table{border-collapse:collapse;width:100%;min-width:560px;font-size:15px}'
	. '.uc-docs-table th,.uc-docs-table td{border-bottom:1px solid #e3e8f2;padding:9px 10px;text-align:left;vertical-align:top}.uc-docs-table th{background:#f4f7fc}'
	. '.uc-docs pre{background:#f4f7fc;padding:16px 18px;border-radius:6px;overflow-x:auto;font-size:14px;line-height:1.6;white-space:pre}'
	. '.uc-docs-swatch{display:inline-block;width:14px;height:14px;border-radius:3px;vertical-align:-2px;margin-right:6px;border:1px solid rgba(0,0,0,.15)}'
	. '.uc-docs-toc{columns:2;column-gap:40px;max-width:720px;margin:0 auto;text-align:left;padding-left:22px}.uc-docs-toc li{margin-bottom:6px;break-inside:avoid}'
	. '@media (max-width:640px){.uc-docs-toc{columns:1}}'
	. '</style>'
	// Videos play while on screen and pause when scrolled away; never for reduced motion.
	. '<script>(function(){if(!("IntersectionObserver" in window))return;if(window.matchMedia&&matchMedia("(prefers-reduced-motion: reduce)").matches)return;'
	. 'document.addEventListener("DOMContentLoaded",function(){var io=new IntersectionObserver(function(es){es.forEach(function(e){var v=e.target;if(e.isIntersecting){v.play().catch(function(){});}else{v.pause();}});},{threshold:0.45});'
	. 'document.querySelectorAll("video.uc-docs-video").forEach(function(v){io.observe(v);});});})();</script>';

$sections = '';

$sections .= uc_docs_section( '01', 'quick-start', 'The short version', <<<'HTML'
<p>If you read one section, read this one. Each step links to the full explanation further down.</p>
<ol class="uc-docs-steps">
<li><strong>Install and activate Unioncorp.</strong> It builds your pages and your menu on its own. <a href="#install">Install Unioncorp</a></li>
<li><strong>Put in your firm’s details</strong>: the phone number and hours in the header, the address and email in the footer and on the Contact page. <a href="#contact-details">Contact details and map</a></li>
<li><strong>Rewrite the words and figures, and replace the photographs.</strong> <a href="#editing">Change words, figures and photos</a></li>
<li><strong>Point every button somewhere.</strong> They all start out going nowhere. <a href="#buttons">Buttons and links</a></li>
<li><strong>Add your logo.</strong> <a href="#header">Logo, header, footer and navigation</a></li>
<li><strong>Send yourself a test enquiry</strong> and make sure the email arrives. <a href="#enquiry">The enquiry form</a></li>
<li><strong>Pick a palette and fonts</strong> if the blue is not yours. <a href="#styles">Colours and fonts</a></li>
</ol>
<p>None of it needs code, a page builder or a plugin.</p>
HTML
);

$sections .= uc_docs_section( '02', 'install', 'Install Unioncorp', <<<'HTML'
<p>Download the zip with the button at the top of this page. <strong>Do not unzip it</strong> — WordPress installs the zip file itself.</p>
<ol class="uc-docs-steps">
<li>In your dashboard, go to <strong>Appearance → Themes</strong> and click <strong>Add New Theme</strong>.</li>
<li>Click <strong>Upload Theme</strong> at the top of the screen.</li>
<li>Click <strong>Choose File</strong>, pick <code>unioncorp.zip</code>, then click <strong>Install Now</strong>.</li>
<li>When WordPress reports the theme is installed, click <strong>Activate</strong>.</li>
</ol>
{{fig:upload}}
<p>Unioncorp needs <strong>WordPress 6.6 or newer</strong> and <strong>PHP 7.4 or newer</strong>, and is tested up to WordPress 7.1. <strong>Tools → Site Health → Info</strong> shows both versions.</p>
<h3>What happens when you activate it</h3>
<p>On a site that has not been built yet, Unioncorp creates seven pages — <strong>Home, About, Services, Case studies, Pricing, Contact and Blog</strong> — and fills six of them with real content. It makes Home the front page and Blog the posts page, and builds a navigation menu that links them all.</p>
{{fig:activate}}
{{fig:pages}}
<p><em>Privacy Policy</em> and <em>Sample Page</em> come with WordPress, not with Unioncorp. Delete Sample Page whenever you like.</p>
<div class="uc-docs-tip"><p><strong>Already had pages?</strong> If the site has more than one page of its own, Unioncorp builds nothing, so it can never overwrite a site you have been working on. Build pages from the theme’s <a href="#sections">page layouts</a> instead, and choose your front page in <strong>Settings → Reading</strong>.</p></div>
<h3>Where Unioncorp lives in the dashboard</h3>
<p>Unioncorp is a block theme, so there is no Customizer and no theme options screen. Your words and photographs are on <strong>Pages</strong>. Everything shared by every page — the header, the footer, the menu, colours, fonts and templates — is in <strong>Appearance → Editor</strong>.</p>
HTML
);

$sections .= uc_docs_section( '03', 'layout', 'How the site is put together', <<<'HTML'
<p>Every page Unioncorp builds is a stack of <strong>sections</strong> — a hero, the services, the statistics, the team — and every section is made of ordinary blocks. The sections were copied into your pages when the theme was activated, so they are yours to rewrite, reorder or delete.</p>
<p>Above and below the sections sit the <strong>header</strong> and <strong>footer</strong>. They are template parts: shared by every page, and edited once in <strong>Appearance → Editor</strong>.</p>
{{fig:map}}
<div class="uc-docs-table"><table><thead><tr><th>Page</th><th>Sections, in order</th></tr></thead><tbody>
<tr><td><strong>Home</strong></td><td>Hero, About, Services, Why us, Case studies, Statistics, Team, Testimonials, Latest posts, Call to action</td></tr>
<tr><td><strong>About</strong></td><td>About, Why us, Statistics, Team, Testimonials, Call to action</td></tr>
<tr><td><strong>Services</strong></td><td>Services, Why us, Testimonials, Call to action</td></tr>
<tr><td><strong>Case studies</strong></td><td>Case studies, Statistics, Call to action</td></tr>
<tr><td><strong>Pricing</strong></td><td>Pricing, Testimonials, Call to action</td></tr>
<tr><td><strong>Contact</strong></td><td>Contact details, form and map, Call to action</td></tr>
<tr><td><strong>Blog</strong></td><td>Your posts, laid out by the theme</td></tr>
</tbody></table></div>
<p>Every page except Home opens with a photograph banner carrying the page’s title. Home has its own hero instead — see <a href="#templates">Page templates</a>.</p>
HTML
);

$sections .= uc_docs_section( '04', 'editing', 'Change words, figures and photos', <<<'HTML'
<p>Open the page: from the front of your site click <strong>Edit Page</strong> in the black bar at the top, or go to <strong>Pages</strong> and click its title.</p>
<ol class="uc-docs-steps">
<li>Click the words you want to change. A small toolbar appears above them.</li>
<li>Select the old text — double-click it, or press <kbd>⌘ A</kbd> (<kbd>Ctrl A</kbd> on Windows), which selects only that block — and type the new text.</li>
<li>Click <strong>Save</strong> in the top-right corner, or press <kbd>⌘ S</kbd> / <kbd>Ctrl S</kbd>.</li>
</ol>
{{fig:figure}}
<h3>List View: the outline of the page</h3>
<p>Click <strong>Document Overview</strong> (the three stacked lines near the top left) or press <kbd>⌃ ⌥ O</kbd> (<kbd>Shift Alt O</kbd>). Every block on the page is listed on the left: open a section with its arrow, and click a row to select that block — the easy way to reach text on top of a photograph.</p>
{{fig:listview}}
<p>Drag a section up or down in List View to move it, and use its <strong>⋮</strong> menu to duplicate or delete it. Some sections carry a small label — <em>services</em>, <em>work</em>, <em>team</em>, <em>pricing</em>, <em>contact</em>. That is the section’s anchor, set under <strong>Advanced → HTML anchor</strong>: a link to <code>#services</code> scrolls straight to it.</p>
<h3>Change a photograph</h3>
<p>Click the photograph and choose <strong>Replace</strong> on the toolbar, then <strong>Open Media Library</strong> or <strong>Upload</strong>. On a banner with text over it (a Cover block), the <strong>Focal point picker</strong> in the sidebar decides which part stays in view on a phone. The case studies gallery opens each photograph larger when it is clicked; a replacement keeps that. Fill in <strong>Alternative text</strong> for every photograph: a short description for people who cannot see it, and for search engines.</p>
<h3 id="buttons">Buttons and links</h3>
<p>Every button Unioncorp builds — <em>Get started</em>, <em>Our services</em>, <em>Learn more</em>, <em>Read our case studies</em> — starts out linked to <code>#</code>, which goes nowhere. Point each one before you launch:</p>
<ol class="uc-docs-steps">
<li>Click the button, then the <strong>Link</strong> icon on its toolbar.</li>
<li>Search for a page — <em>Contact</em> for “Get started”, say — or paste an address. A section anchor such as <code>#services</code> scrolls the same page.</li>
<li>Save. The <em>Get started</em> button in the header is in the Header template part: change it there once, as described under <a href="#header">Logo, header, footer and navigation</a>.</li>
</ol>
HTML
);

$sections .= uc_docs_section( '05', 'sections', 'Add a section or a whole page', <<<'HTML'
<p>Every section of the demo is also a <strong>pattern</strong> you can insert on any page, and every page layout is one too.</p>
<ol class="uc-docs-steps">
<li>Click where the new section should go, then the blue <strong>+</strong> in the top-left corner.</li>
<li>Open the <strong>Patterns</strong> tab and choose <strong>Unioncorp: sections</strong> or <strong>Unioncorp: pages</strong>.</li>
<li>Click a preview to insert it, or drag it into place, then edit it like anything else.</li>
</ol>
{{fig:inserter}}
<div class="uc-docs-table"><table><thead><tr><th>Unioncorp: sections</th><th>Unioncorp: pages</th></tr></thead><tbody>
<tr><td>Hero · About: introduction · Services: eight cards · Why us: photograph and statement · Case studies: gallery · Statistics · Team: eight profiles · Testimonials: three quotes · Pricing: four plans · Latest posts · Contact: details, form and map · Call to action band</td><td>Page: home · Page: about · Page: services · Page: case studies · Page: pricing · Page: contact</td></tr>
</tbody></table></div>
<p>An inserted pattern is a copy: it keeps working however much you change it, and later theme updates do not rewrite it.</p>
HTML
);

$sections .= uc_docs_section( '06', 'icons', 'Change an icon', <<<'HTML'
<p>Each service, about and testimonial card has an icon in a tile, and each contact line has one beside it. The icon comes from a class name on the block, so changing it takes a few seconds:</p>
<ol class="uc-docs-steps">
<li>Click the icon tile (or the contact line) to select it.</li>
<li>In the sidebar, open the <strong>Block</strong> tab, then <strong>Advanced</strong>.</li>
<li>In <strong>Additional CSS class(es)</strong>, replace the name after <code>unioncorp-icon--</code> with another from the list below — for example <code>unioncorp-icon--calculator</code> becomes <code>unioncorp-icon--briefcase</code>. Leave the other class, such as <code>unioncorp-card__icon</code>, as it is.</li>
<li>Save.</li>
</ol>
{{fig:icon}}
{{fig:icons}}
<p>Icons take the palette’s colours, turn over with their tile when a card is hovered, and follow <a href="#dark-mode">dark mode</a>. They come from <a href="https://tabler.io/icons" target="_blank" rel="noopener">Tabler Icons</a> and ship with the theme.</p>
<div class="uc-docs-tip"><p><strong>Pages built by Unioncorp 1.1.1 or earlier</strong> keep the icon inside the paragraph instead, and the editor shows those tiles without an icon, often with the words “Type / to choose a block”. To change one, select it, choose <strong>Edit as HTML</strong> from the toolbar’s <strong>⋮</strong> menu, change the name in <code>unioncorp-icon--…</code>, then choose <strong>Edit visually</strong>. A section inserted from the patterns today uses the newer form.</p></div>
HTML
);

$sections .= uc_docs_section( '07', 'motion', 'Counters, animations and the video button', <<<'HTML'
<p>As a visitor scrolls, sections fade in and the statistics count up from zero. It is all automatic.</p>
{{fig:tour}}
<h3>Statistics that count up</h3>
<p>Any heading with the class <code>unioncorp-count</code> counts up the first time it scrolls into view — the four figures in the Statistics section have it already. Change the number and the animation follows. Write it the way it should end up — <code>9,200</code>, <code>98%</code> or <code>$2.4m</code>: one number, with any symbols before or after it. A heading with two numbers in it, such as <code>24/7</code>, simply does not animate. A figure already on screen when the page opens does not count, and screen readers are given the final figure straight away.</p>
<h3>The video button</h3>
<p><strong>Watch the video</strong> in the Why us section opens a YouTube video over the page instead of leaving the site.</p>
{{fig:popup}}
<ol class="uc-docs-steps">
<li>Click the button, then the <strong>Link</strong> icon, and paste your video’s YouTube address — a normal link, a <code>youtu.be</code> link or a Shorts link.</li>
<li>Save.</li>
</ol>
<p>Any button can do this: give the Button block the class <code>unioncorp-video</code> under <strong>Advanced → Additional CSS class(es)</strong> and link it to YouTube. The video plays from <code>youtube-nocookie.com</code>, YouTube’s privacy-enhanced mode. A link that is not YouTube behaves like an ordinary link.</p>
<div class="uc-docs-tip"><p><strong>Replace the demo video.</strong> It is there to show the button working and is not about your firm.</p></div>
<h3>Turning the animations off</h3>
<p>Visitors who have asked their phone or computer to reduce motion never see the animations. To switch them off for everyone, add this to a child theme or a small plugin — the video button keeps working:</p>
<pre>add_filter( 'unioncorp_enable_scroll_animations', '__return_false' );</pre>
HTML
);

$sections .= uc_docs_section( '08', 'enquiry', 'The enquiry form', <<<'HTML'
<p>The enquiry form is built into Unioncorp — no plugin — and is already on the Contact page.</p>
{{fig:contact}}
<p>It asks for a name, an email address, a phone number, a company and a message; the name, email and message are required. It is checked on your server, protected from spam bots by a hidden trap field and a security token, and works with JavaScript turned off. The button reads <em>Request a consultation</em>.</p>
<h3>Where enquiries go</h3>
<p>Each enquiry is emailed to the <strong>Administration Email Address</strong> in <strong>Settings → General</strong>, with the subject <em>[Your site name] Website enquiry</em>. Its <em>reply-to</em> is the visitor, so pressing Reply answers them.</p>
<h3>Test it before you launch</h3>
<ol class="uc-docs-steps">
<li>Open your Contact page in a private browser window, so you see it as a visitor does.</li>
<li>Fill in the form with your own email address and click <strong>Request a consultation</strong>.</li>
<li>Check the page thanks you, then check the email arrives — look in the spam folder too.</li>
</ol>
<div class="uc-docs-tip"><p><strong>No email?</strong> Many hosts do not send WordPress email reliably. Install an SMTP plugin, such as FluentSMTP or WP Mail SMTP, and connect it to your email provider. The form sends through WordPress’s own <code>wp_mail()</code>, so whatever fixes a missing password-reset email fixes enquiries too.</p></div>
<h3>Put the form on another page</h3>
<p>Add a <strong>Shortcode</strong> block and type <code>&#91;unioncorp_enquiry_form&#93;</code>. To change the button’s words, give it a <code>button</code>: <code>&#91;unioncorp_enquiry_form button="Book a call"&#93;</code>. The form is a shortcode on purpose: it is built fresh each time the page is shown, so it keeps working however the page around it is edited.</p>
<h3>For developers: send enquiries somewhere else</h3>
<p>Enquiries can go to a CRM, a helpdesk or a webhook instead of email. Add this to a child theme’s <code>functions.php</code> or a small plugin:</p>
<pre>add_filter( 'unioncorp_enquiry_handlers', function ( $handled, $enquiry ) {
	// $enquiry has: name, email, phone, company, message
	my_crm_create_lead( $enquiry );
	return true; // handled, so Unioncorp sends no email of its own
}, 10, 2 );</pre>
<div class="uc-docs-table"><table><thead><tr><th>Filter</th><th>Use it to</th></tr></thead><tbody>
<tr><td><code>unioncorp_enquiry_fields</code></td><td>Add, remove, relabel or reorder the form’s fields</td></tr>
<tr><td><code>unioncorp_enquiry_handlers</code></td><td>Send the enquiry elsewhere; return <code>true</code> to skip the email</td></tr>
<tr><td><code>unioncorp_enquiry_email_to</code></td><td>Send enquiries to an address other than the site administrator’s</td></tr>
<tr><td><code>unioncorp_enquiry_email_subject</code></td><td>Change the email’s subject line</td></tr>
<tr><td><code>unioncorp_enquiry_email_body</code></td><td>Change the email’s text</td></tr>
</tbody></table></div>
HTML
);

$sections .= uc_docs_section( '09', 'contact-details', 'Contact details, social links and map', <<<'HTML'
<p>Your phone number, address and email appear in three places. Update all three:</p>
<ul>
<li><strong>The top bar</strong> of the header — phone number and opening hours — on every page. Edit the Header template part (<a href="#header">see below</a>) and type over them.</li>
<li><strong>The footer’s “Have a question?” column</strong> — address, phone and email — on every page. Edit the Footer template part the same way.</li>
<li><strong>The contact section</strong> on the Contact page — address, email and phone beside the form. Edit the page.</li>
</ul>
<h3>Social links</h3>
<p>The icons in the top bar and the footer are a <strong>Social Icons</strong> block, and they link to <code>#</code> until you set them. Click an icon and paste your profile’s address; to remove a network, select its icon and press <kbd>⌃ ⌥ Z</kbd> (<kbd>Shift Alt Z</kbd>); to add one, click the block’s <strong>+</strong>.</p>
<h3>The map</h3>
<p>The map under the enquiry form is an OpenStreetMap embed, so it needs no API key and no billing account. It is a <strong>Custom HTML</strong> block. To show your own address:</p>
<ol class="uc-docs-steps">
<li>Find your office on <a href="https://www.openstreetmap.org/" target="_blank" rel="noopener">openstreetmap.org</a>, right-click it and choose <strong>Show address</strong>. The latitude and longitude appear at the top of the panel on the left.</li>
<li>Click the map block to see its code. In the <code>src</code> address, replace the two numbers after <code>marker=</code> with your latitude and longitude, keeping the <code>%2C</code> between them.</li>
<li>The four numbers after <code>bbox=</code> are the edges of the map: west, south, east, north. About 0.005 either side of your point gives a street-level view.</li>
</ol>
<pre>…embed.html?bbox=-74.0170%2C40.6990%2C-74.0070%2C40.7100&amp;layer=mapnik&amp;marker=40.704644%2C-74.011987</pre>
<p>Prefer Google Maps? Delete the block, add a new Custom HTML block, and paste the code from Google Maps’ <strong>Share → Embed a map</strong>.</p>
HTML
);

$sections .= uc_docs_section( '10', 'header', 'Logo, header, footer and navigation', <<<'HTML'
<p>The header, the footer and the blog’s sidebar are <strong>template parts</strong>: edit one once and every page follows.</p>
{{fig:parts}}
<h3>Add your logo</h3>
<ol class="uc-docs-steps">
<li>Go to <strong>Appearance → Editor → Patterns</strong> and choose <strong>Header</strong>.</li>
<li>Click the grey logo placeholder beside the site name, and upload your logo or pick it from the Media Library.</li>
<li>Drag the handle on the logo’s corner to resize it, then click <strong>Save</strong>.</li>
</ol>
{{fig:header}}
<p>The name beside the logo is your <strong>Site Title</strong> from <strong>Settings → General</strong>. If your logo already spells out the name, select the title in the header and delete it. Until you add a logo, the placeholder shows only in the editor, never to visitors.</p>
<h3>Change the navigation menu</h3>
<p>Go to <strong>Appearance → Editor → Navigation</strong> and open the <strong>Primary</strong> menu.</p>
{{fig:nav}}
<ul>
<li><strong>Reorder links</strong> with <strong>Move up</strong> and <strong>Move down</strong> in the <strong>⋮</strong> menu beside each link.</li>
<li><strong>Add a link</strong> with the <strong>+</strong> under the list: search for a page, or paste any web address.</li>
<li><strong>Remove a link</strong> from its <strong>⋮</strong> menu. The page itself is not deleted.</li>
</ul>
<p>On a phone the menu folds into a single button that opens it full screen. That happens by itself.</p>
<h3>The header button and the footer</h3>
<p><strong>Get started</strong> in the header is an ordinary Button block: click it in the Header template part to change its words, and use the <strong>Link</strong> icon to point it at your Contact page or a booking service. The footer’s description, services list and contact column are ordinary text in the Footer template part; its <em>Recent posts</em> column lists your newest posts by itself.</p>
HTML
);

$sections .= uc_docs_section( '11', 'styles', 'Colours and fonts', <<<'HTML'
<p>Colours and fonts are set once for the whole site in <strong>Appearance → Editor → Styles</strong>. Click <strong>Styles</strong>, then <strong>Browse styles</strong>.</p>
{{fig:styles}}
<p>Click a palette and the preview changes immediately. Your live site does not change until you click <strong>Save</strong>, so try as many as you like.</p>
{{fig:palettes}}
{{fig:browse}}
<h3>The eight palettes</h3>
<div class="uc-docs-table"><table><thead><tr><th>Palette</th><th>Page</th><th>Buttons and links</th><th>Dark bands</th></tr></thead><tbody>
<tr><td><strong>Azure</strong> (the theme’s own)</td><td>Light</td><td><span class="uc-docs-swatch" style="background:#2c62d6"></span>Blue</td><td><span class="uc-docs-swatch" style="background:#052c43"></span>Deep navy</td></tr>
<tr><td><strong>Emerald</strong></td><td>Light</td><td><span class="uc-docs-swatch" style="background:#127a4b"></span>Green</td><td><span class="uc-docs-swatch" style="background:#06301f"></span>Forest</td></tr>
<tr><td><strong>Navy</strong></td><td>Light</td><td><span class="uc-docs-swatch" style="background:#1b4f8f"></span>Navy blue</td><td><span class="uc-docs-swatch" style="background:#04182b"></span>Midnight blue</td></tr>
<tr><td><strong>Slate</strong></td><td>Light</td><td><span class="uc-docs-swatch" style="background:#3a4750"></span>Slate grey</td><td><span class="uc-docs-swatch" style="background:#12161a"></span>Charcoal</td></tr>
<tr><td><strong>Teal</strong></td><td>Light</td><td><span class="uc-docs-swatch" style="background:#0f6f7f"></span>Teal</td><td><span class="uc-docs-swatch" style="background:#04252c"></span>Deep teal</td></tr>
<tr><td><strong>Plum</strong></td><td>Light</td><td><span class="uc-docs-swatch" style="background:#7b2d8e"></span>Plum</td><td><span class="uc-docs-swatch" style="background:#1d0f23"></span>Aubergine</td></tr>
<tr><td><strong>Midnight</strong></td><td>Dark</td><td><span class="uc-docs-swatch" style="background:#7fb0ff"></span>Light blue</td><td><span class="uc-docs-swatch" style="background:#080f18"></span>Near black</td></tr>
<tr><td><strong>Graphite</strong></td><td>Dark</td><td><span class="uc-docs-swatch" style="background:#9fb6c6"></span>Steel</td><td><span class="uc-docs-swatch" style="background:#0e1011"></span>Near black</td></tr>
</tbody></table></div>
<p>Every palette was checked before release: body text, secondary text, links and button labels meet WCAG AA contrast on every background the design uses, in light mode and in dark mode.</p>
<h3>Why the brand blue is not the link colour</h3>
<p>Unioncorp’s bright blue, <code>#4f86f9</code>, is only 3.4:1 against white — too light for text anyone has to read. It is kept as the <em>Accent</em> for decoration, and a deeper blue, <em>Primary</em>, carries buttons, links and icons. The other palettes follow the same rule.</p>
<h3>The five font pairings</h3>
<p>Unioncorp uses <strong>Poppins</strong> and <strong>Inter</strong>, both served from your own site rather than from Google. The typography variations rearrange them:</p>
<ul>
<li><strong>Poppins throughout</strong> — the default.</li>
<li><strong>Poppins headings, Inter text</strong> — easier on long pages.</li>
<li><strong>Inter throughout</strong> — plainer and more compact.</li>
<li><strong>Inter headings, Poppins text</strong>.</li>
<li><strong>System fonts</strong> — each device’s own fonts, and no font files to download at all.</li>
</ul>
<h3>Change one colour, or undo</h3>
<p>To adjust a single colour, open <strong>Styles → Colors → Edit palette</strong> and click a swatch. Colours are named by job — <em>Primary</em> for buttons and links, <em>Dark</em> for the dark bands, <em>On primary</em> and <em>On dark</em> for text sitting on those grounds — so one change applies everywhere that colour is used. If you darken or lighten <em>Primary</em> or <em>Dark</em>, check the matching <em>On …</em> colour still reads clearly against it.</p>
<p>To undo, click <strong>Revisions</strong> at the bottom of the Styles panel and go back to any earlier save, or open the <strong>⋮</strong> menu at the top of Styles and choose <strong>Reset styles</strong>.</p>
HTML
);

$sections .= uc_docs_section( '12', 'dark-mode', 'Dark mode', <<<'HTML'
<p>The moon at the right of the top bar lets each visitor choose light or dark. It is separate from your palette: dark mode turns the palette you chose over rather than replacing it, so an Emerald site stays green.</p>
{{fig:dark}}
{{fig:darkstill}}
<ul>
<li>Until a visitor clicks it, it follows their phone or computer’s own light or dark setting.</li>
<li>Once they click, it remembers their choice on that device.</li>
<li>It is applied before the page is drawn, so a phone in dark mode never flashes white.</li>
</ul>
<p><strong>To remove the switch</strong>, add <code>add_filter( 'unioncorp_enable_dark_mode', '__return_false' );</code> to a child theme or a small plugin. <strong>To move it</strong>, edit the Header template part and drag the button: any Button block with the class <code>unioncorp-scheme-toggle</code> becomes the switch. Put the class on the button itself, not on the Buttons group around it.</p>
HTML
);

$sections .= uc_docs_section( '13', 'templates', 'Page templates', <<<'HTML'
<p>A template decides what surrounds a page’s content: the header, a banner, a sidebar, the footer. For a page you choose between three:</p>
<ul>
<li><strong>Pages</strong> (the default) — a photograph banner carrying the page’s title, above the content.</li>
<li><strong>Page without title</strong> — no banner and no title. Home uses it, because it opens with its own hero.</li>
<li><strong>Page with sidebar</strong> — the content, with the sidebar beside it.</li>
</ul>
<p>To switch, open the page, click the <strong>Page</strong> tab in the sidebar, click the template’s name next to <strong>Template</strong>, and choose <strong>Change template</strong>.</p>
{{fig:template}}
<p>Posts can use <strong>Post with sidebar</strong> the same way. The blog, archives, categories, tags, author pages, search results and the 404 page each have their own template — fourteen in all — which you can change in <strong>Appearance → Editor → Templates</strong>.</p>
HTML
);

$sections .= uc_docs_section( '14', 'block-styles', 'Block styles', <<<'HTML'
<p>Some core blocks gain extra looks. Select the block, open the <strong>Styles</strong> panel in its settings, and click one.</p>
<div class="uc-docs-table"><table><thead><tr><th>Block</th><th>Style</th><th>Looks like</th></tr></thead><tbody>
<tr><td>Heading</td><td><strong>Eyebrow</strong></td><td>Small, spaced capitals in the primary colour — the line above each section title</td></tr>
<tr><td>Group</td><td><strong>Card</strong></td><td>A bordered card with a soft shadow that lifts on hover, as the service cards do</td></tr>
<tr><td>Group</td><td><strong>Panel</strong></td><td>A tinted panel with a primary-coloured bar down its left edge</td></tr>
<tr><td>Button</td><td><strong>Ghost</strong></td><td>An outlined button that fills on hover</td></tr>
<tr><td>List</td><td><strong>Tick list</strong></td><td>Ticks in the primary colour instead of bullets</td></tr>
</tbody></table></div>
HTML
);

$sections .= uc_docs_section( '15', 'plugins', 'WooCommerce and form plugins', <<<'HTML'
<p><strong>WooCommerce.</strong> Install it and Unioncorp styles it — shop, product pages, cart and checkout take the theme’s colours, fonts and buttons, three products to a row, in light and dark mode. The shop styles load only on shop pages, and nothing at all loads without WooCommerce.</p>
<p><strong>Form plugins.</strong> Unioncorp’s own enquiry form needs none, but if you use one it is styled to match: Contact Form 7, WPForms, Gravity Forms, Fluent Forms, Forminator or Ninja Forms. Their fields, buttons and messages take the theme’s look, and the styles load only on a site that has one of them.</p>
HTML
);

$sections .= uc_docs_section( '16', 'updates', 'Updates', <<<'HTML'
<p>Unioncorp is not in the WordPress.org theme directory, so it checks <code>updates.colorlib.com</code> for new versions twice a day. A new version then appears in <strong>Dashboard → Updates</strong> and on <strong>Appearance → Themes</strong>, like any other theme, and updates with one click.</p>
<p>The check sends the theme’s version, your WordPress and PHP versions, your site’s language, whether it is a multisite, and an identifier made from your site address with your site’s own secret key. It sends no personal data and no site name, and the identifier cannot be turned back into your address. Two filters control it:</p>
<pre>// Stop checking altogether (you will not hear about new versions).
add_filter( 'unioncorp_check_for_updates', '__return_false' );

// Or send only the theme name and version.
add_filter( 'unioncorp_update_payload', function ( $payload ) {
	return array( 'theme' =&gt; $payload['theme'], 'version' =&gt; $payload['version'] );
} );</pre>
<p><strong>Updates change the theme, not your pages.</strong> The sections in your pages were copied from the theme when it was activated, so an update improves the header, footer, templates and styles straight away, while changes to the section patterns reach only sections inserted after the update.</p>
HTML
);

$sections .= uc_docs_section( '17', 'shortcuts', 'Keyboard shortcuts worth knowing', <<<'HTML'
<div class="uc-docs-table"><table><thead><tr><th>To</th><th>Mac</th><th>Windows</th></tr></thead><tbody>
<tr><td>Save</td><td><kbd>⌘ S</kbd></td><td><kbd>Ctrl S</kbd></td></tr>
<tr><td>Undo / redo</td><td><kbd>⌘ Z</kbd> / <kbd>⇧ ⌘ Z</kbd></td><td><kbd>Ctrl Z</kbd> / <kbd>Ctrl Shift Z</kbd></td></tr>
<tr><td>Select the text in a block (press again for the whole page)</td><td><kbd>⌘ A</kbd></td><td><kbd>Ctrl A</kbd></td></tr>
<tr><td>Duplicate the selected block</td><td><kbd>⇧ ⌘ D</kbd></td><td><kbd>Ctrl Shift D</kbd></td></tr>
<tr><td>Delete the selected block</td><td><kbd>⌃ ⌥ Z</kbd></td><td><kbd>Shift Alt Z</kbd></td></tr>
<tr><td>Open List View</td><td><kbd>⌃ ⌥ O</kbd></td><td><kbd>Shift Alt O</kbd></td></tr>
<tr><td>Show every shortcut</td><td><kbd>⌃ ⌥ H</kbd></td><td><kbd>Shift Alt H</kbd></td></tr>
<tr><td>Add a block by name</td><td colspan="2">Type <kbd>/</kbd> on an empty line, then the block’s name</td></tr>
</tbody></table></div>
HTML
);

$sections .= uc_docs_section( '18', 'troubleshooting', 'If something is not where you expect', <<<'HTML'
<p><strong>Activation built no pages.</strong> The site already had more than one page of its own, so Unioncorp left it alone. Insert the <em>Unioncorp: pages</em> layouts into pages of your own, then choose your front page in <strong>Settings → Reading</strong>.</p>
<p><strong>My front page shows the latest blog posts.</strong> Go to <strong>Settings → Reading</strong>, choose <em>A static page</em>, and pick Home as the homepage.</p>
<p><strong>A button does nothing when it is clicked.</strong> It still links to <code>#</code>. Point it at a page — see <a href="#buttons">Buttons and links</a>.</p>
<p><strong>An icon tile is empty, or says “Type / to choose a block”, in the editor.</strong> The page was built by Unioncorp 1.1.1 or earlier, where the icon sits inside the paragraph. It still shows on the site; to change it, use <strong>Edit as HTML</strong> as described in <a href="#icons">Change an icon</a>, or insert the section again from the patterns.</p>
<p><strong>The statistics do not count up.</strong> They were already on screen when the page opened, the visitor has reduced motion turned on, or animations are switched off with the <code>unioncorp_enable_scroll_animations</code> filter. Otherwise, check the heading still has the <code>unioncorp-count</code> class and holds a single number.</p>
<p><strong>The video button leaves the site instead of opening a window.</strong> The link is not a YouTube address, or the button has lost its <code>unioncorp-video</code> class.</p>
<p><strong>Enquiries are not arriving.</strong> Check the address in Settings → General and your spam folder, then test whether WordPress can send mail at all — most hosts need an SMTP plugin. If WordPress cannot send a password reset, it cannot send an enquiry.</p>
<p><strong>I edited a pattern file in the theme and my page did not change.</strong> Pages hold copies of the sections, made when they were built. Edit the page, not the pattern.</p>
<p><strong>I saved, but visitors still see the old version.</strong> A caching plugin or your host’s cache is serving a stored copy. Clear it from the plugin’s or host’s settings.</p>
HTML
);

$toc = '<ol class="uc-docs-toc">'
	. '<li><a href="#quick-start">The short version</a></li>'
	. '<li><a href="#install">Install Unioncorp</a></li>'
	. '<li><a href="#layout">How the site is put together</a></li>'
	. '<li><a href="#editing">Change words, figures and photos</a></li>'
	. '<li><a href="#sections">Add a section or a whole page</a></li>'
	. '<li><a href="#icons">Change an icon</a></li>'
	. '<li><a href="#motion">Counters, animations and the video button</a></li>'
	. '<li><a href="#enquiry">The enquiry form</a></li>'
	. '<li><a href="#contact-details">Contact details, social links and map</a></li>'
	. '<li><a href="#header">Logo, header, footer and navigation</a></li>'
	. '<li><a href="#styles">Colours and fonts</a></li>'
	. '<li><a href="#dark-mode">Dark mode</a></li>'
	. '<li><a href="#templates">Page templates</a></li>'
	. '<li><a href="#block-styles">Block styles</a></li>'
	. '<li><a href="#plugins">WooCommerce and form plugins</a></li>'
	. '<li><a href="#updates">Updates</a></li>'
	. '<li><a href="#shortcuts">Keyboard shortcuts</a></li>'
	. '<li><a href="#troubleshooting">If something is not where you expect</a></li>'
	. '</ol>';

$content = '[vc_row el_id="top" css=".vc_custom_ucdoc00{padding-top:44px !important;padding-bottom:30px !important;background-color:#f4f7fc !important;}"][vc_column width="1/1"]'
	. '[vcex_heading text="Unioncorp documentation" tag="h2" font_size="40px" text_align="center" bottom_margin="16px" font_weight="700"]'
	. '[vc_column_text css=".vc_custom_ucdoc00t{text-align:center !important;font-size:18px !important;max-width:760px !important;margin-left:auto !important;margin-right:auto !important;}"]'
	. $css
	. 'Everything you need to take Unioncorp from install to a finished site for your firm, in the order you will need it, with a screenshot, animation or video for every step. Unioncorp is a free block theme for financial advisers, consultants and professional services.'
	. '[/vc_column_text]'
	. '[vc_column_text css=".vc_custom_ucdoc00b{text-align:center !important;margin-top:22px !important;}"]'
	. '[vc_btn title="Download Unioncorp" style="flat" color="green" link="url:' . $download_enc . '|title:Download%20Unioncorp|target:_blank" css=".vc_custom_ucdoc00c{' . $btn_css . '}" i_icon_fontawesome="fa fa-download" add_icon="true"]'
	. '[vc_btn title="Live demo" style="flat" color="grey" link="url:' . $demo_enc . '|title:Live%20demo|target:_blank" css=".vc_custom_ucdoc00d{' . $btn_css . '}" i_icon_fontawesome="fa fa-eye" add_icon="true"]'
	. '[/vc_column_text]'
	. '[vc_column_text css=".vc_custom_ucdoc00m{text-align:center !important;font-size:14px !important;color:#55677a !important;margin-top:6px !important;}"]Written for Unioncorp 1.1.2 on WordPress 7.1. Every setup screenshot is from a fresh install.[/vc_column_text]'
	. '[/vc_column][/vc_row]'
	. '[vc_row el_id="contents" el_class="uc-docs" css=".vc_custom_ucdoc0c{padding-top:34px !important;padding-bottom:4px !important;}"][vc_column width="1/1"]'
	. '[vcex_heading text="On this page" tag="h2" font_size="24px" text_align="center" bottom_margin="16px" font_weight="700"]'
	. '[vc_column_text]' . $toc . '[/vc_column_text]'
	. '[/vc_column][/vc_row]'
	. $sections
	. '[vc_row el_class="uc-docs" css=".vc_custom_ucdoc99{padding-top:34px !important;padding-bottom:56px !important;}"][vc_column width="1/1"]'
	. '[vcex_heading text="Still stuck?" tag="h2" font_size="28px" bottom_margin="14px" font_weight="700"]'
	. '[vc_column_text css=".vc_custom_ucdoc99t{max-width:860px !important;}"]'
	. '<p>Ask on the <a href="' . esc_url( $support ) . '" target="_blank" rel="noopener">Colorlib support forum</a>. Include your Unioncorp, WordPress and PHP versions, what you did, and what you expected to happen — a screenshot helps.</p>'
	. '[/vc_column_text][/vc_column][/vc_row]';

// Refuse to save a page with a hole in it.
$problems = $GLOBALS['uc_docs_missing'];
if ( preg_match_all( '/%%[A-Z_]+%%|\{\{fig:[a-z0-9-]+\}\}/', $content, $left ) ) {
	$problems = array_merge( $problems, array_unique( $left[0] ) );
}
if ( $problems && ! getenv( 'UNIONCORP_DOCS_ALLOW_MISSING' ) ) {
	echo 'NOT SAVED. Missing: ' . implode( ', ', $problems ) . "\n";
	return;
}

$kses = has_filter( 'content_save_pre', 'wp_filter_post_kses' );
if ( $kses ) {
	kses_remove_filters();
}

$args = array(
	'post_title'   => 'Unioncorp documentation',
	'post_name'    => $slug,
	'post_content' => wp_slash( $content ),
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

update_post_meta( $page_id, '_wp_page_template', 'templates/no-sidebar.php' );
update_post_meta( $page_id, '_wpb_vc_js_status', 'true' );

if ( function_exists( 'visual_composer' ) ) {
	$vc = visual_composer();
	if ( method_exists( $vc, 'buildShortcodesCss' ) ) {
		$vc->buildShortcodesCss( $page_id, 'custom' );
		$vc->buildShortcodesCss( $page_id, 'default' );
		echo "custom css rebuilt\n";
	}
}

$saved    = get_post_field( 'post_content', $page_id );
$rendered = do_shortcode( $saved );

echo 'page: ' . $page_id . ' (' . get_post_status( $page_id ) . '), parent ' . wp_get_post_parent_id( $page_id ) . "\n";
echo 'length: ' . strlen( $saved ) . ', saved intact: ' . ( $saved === $content ? 'yes' : 'NO' ) . "\n";
echo 'sections: ' . substr_count( $saved, '[vcex_heading' ) . ', figures: ' . substr_count( $saved, '<figure' ) . ', videos: ' . substr_count( $saved, '<video' ) . "\n";
echo 'unbalanced rows: ' . ( substr_count( $saved, '[vc_row' ) - substr_count( $saved, '[/vc_row]' ) ) . "\n";

preg_match_all( '#<a[^>]+href="([^"]*)"#', $rendered, $hrefs );
$dead = array_filter(
	array_unique( $hrefs[1] ),
	function ( $h ) {
		return '' === $h || '#' === $h || false !== strpos( $h, 'http://https' ) || 0 === strpos( $h, 'url:' );
	}
);
echo 'rendered links: ' . count( array_unique( $hrefs[1] ) ) . ', dead: ' . count( $dead ) . "\n";

// Every in-page link needs a target: a row id, or an id inside the content.
preg_match_all( '/href="#([a-z0-9-]+)"/', $saved, $anchors );
preg_match_all( '/(?:el_id|id)="([a-z0-9-]+)"/', $saved, $ids );
$orphans = array_diff( array_unique( $anchors[1] ), $ids[1] );
echo 'anchor links: ' . count( array_unique( $anchors[1] ) ) . ', without a target: ' . ( $orphans ? implode( ', ', $orphans ) : 'none' ) . "\n";
