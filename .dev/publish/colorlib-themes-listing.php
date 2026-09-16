<?php
/**
 * Add Unioncorp to the /wp/themes/ listing (page 5091).
 *
 * Three edits, each idempotent:
 *
 *  - a card in the free themes grid, just before Pato's, following the
 *    precedent Pato set of placing the newest block theme ahead of the last;
 *  - a line in the "what are you building" picker. There was no line for a
 *    consultancy or a financial firm, and the nearest existing one, "A one-page
 *    business site", would be wrong: Unioncorp is a multi-page theme;
 *  - the intro's list of themes that download from colorlib.com rather than
 *    WordPress.org. It named only Unapp and Academia, and was already out of
 *    date — Philosophy and Pato are self-hosted too — before this theme joined.
 *
 * Safe to re-run: if Unioncorp is already listed, only the card image is
 * refreshed.
 */

defined( 'ABSPATH' ) || exit;

$page_id  = 5091;
$page_url = 'https://colorlib.com/wp/themes/unioncorp/';

// Derive the card image from the attachment, found by file name, never from a
// literal URL: `wp media import` does not overwrite, so a name that already
// exists becomes `-1.jpg` and a hardcoded URL quietly points at the wrong file.
global $wpdb;
$card_file = 'unioncorp-free-business-wordpress-theme.jpg';
$card_att  = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta}
		 WHERE meta_key = '_wp_attached_file' AND ( meta_value = %s OR meta_value LIKE %s )
		 ORDER BY post_id DESC LIMIT 1",
		$card_file,
		'%/' . $wpdb->esc_like( $card_file )
	)
);
$card_img = $card_att ? wp_get_attachment_url( $card_att ) : '';
$card_dim = $card_att ? wp_get_attachment_image_src( $card_att, 'full' ) : false;

$content = get_post_field( 'post_content', $page_id );

if ( ! $card_img || ! $card_dim ) {
	echo "ERROR: $card_file is not in the media library yet\n";
	return;
}

if ( '' === $content ) {
	echo "ERROR: page $page_id has no content\n";
	return;
}

if ( false !== stripos( $content, 'id="theme-unioncorp"' ) ) {
	if ( false !== strpos( $content, $card_img ) ) {
		echo "already listed, card image current — nothing to do\n";
		return;
	}
	$updated = preg_replace(
		'~(<li class="clt-theme" id="theme-unioncorp">.*?<img src=")[^"]+~s',
		'$1' . $card_img,
		$content,
		1
	);
	if ( null === $updated || $updated === $content ) {
		echo "ERROR: listed, but the card image could not be replaced\n";
		return;
	}
	$content = $updated;
	echo "card image updated to $card_img\n";
} else {
	$anchor = '<li class="clt-theme" id="theme-pato">';
	if ( false === strpos( $content, $anchor ) ) {
		echo "ERROR: could not find the Pato card to insert before\n";
		return;
	}

	$card = '<li class="clt-theme" id="theme-unioncorp">'
		. '<span class="clt-theme__shot">'
		. '<img src="' . esc_url( $card_img ) . '"'
		. ' alt="Unioncorp WordPress theme home page, with a team of consultants behind the headline and two buttons"'
		. ' width="' . (int) $card_dim[1] . '" height="' . (int) $card_dim[2] . '"'
		. ' loading="lazy" decoding="async" />'
		. '</span>'
		. '<span class="clt-theme__body">'
		. '<span class="clt-theme__kind">Block theme</span>'
		. '<h3 class="clt-theme__name"><a href="' . esc_url( $page_url ) . '">Unioncorp</a></h3>'
		. '<span class="clt-theme__desc">A finance and consulting theme with an enquiry form built in, services, case studies and pricing sections, and eight colour palettes.</span>'
		. '<span class="clt-theme__foot"><span class="clt-theme__installs"></span><span class="clt-theme__cta">View theme &rarr;</span></span>'
		. '</span></li>';

	$content = str_replace( $anchor, $card . $anchor, $content );
	echo "card added before Pato\n";

	$pick_anchor = '<li class="clt-pick">';
	if ( false !== strpos( $content, $pick_anchor ) ) {
		$pick = '<li class="clt-pick"><span class="clt-ico">'
			. '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
			. '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 13h18"/>'
			. '</svg></span>'
			. '<span><span class="clt-pick__lab">A consultancy or financial firm</span>'
			. '<span class="clt-pick__themes"><a href="#theme-unioncorp">Unioncorp</a></span></span></li>';
		$content = preg_replace( '~' . preg_quote( $pick_anchor, '~' ) . '~', $pick . $pick_anchor, $content, 1 );
		echo "picker entry added\n";
	} else {
		echo "NOTE: picker anchor not found — card added, picker left alone\n";
	}
}

// The intro's self-hosted list. Matched exactly and left alone if it has been
// reworded, rather than guessed at.
$stale = 'Unapp and Academia download from us.';
$fresh = 'Unapp, Philosophy, Academia, Pato and Unioncorp download from us.';
if ( false !== strpos( $content, $stale ) ) {
	$content = str_replace( $stale, $fresh, $content );
	echo "intro updated: self-hosted themes now listed in full\n";
} elseif ( false !== strpos( $content, $fresh ) ) {
	echo "intro already current\n";
} else {
	echo "NOTE: intro sentence not found as expected — left unchanged\n";
}

$kses = has_filter( 'content_save_pre', 'wp_filter_post_kses' );
if ( $kses ) {
	kses_remove_filters();
}

// wp_update_post() unslashes; nothing here carries escapes, but the SVG and
// attribute quotes are exactly what a stray unslash would damage.
$result = wp_update_post( array( 'ID' => $page_id, 'post_content' => wp_slash( $content ) ), true );

if ( $kses ) {
	kses_init_filters();
}

if ( is_wp_error( $result ) ) {
	echo 'ERROR: ' . $result->get_error_message() . "\n";
	return;
}

// This page is WPBakery too, so its css= meta has to be regenerated like any
// other programmatic edit.
if ( function_exists( 'visual_composer' ) && method_exists( visual_composer(), 'buildShortcodesCss' ) ) {
	visual_composer()->buildShortcodesCss( $page_id, 'custom' );
	visual_composer()->buildShortcodesCss( $page_id, 'default' );
}

$saved = get_post_field( 'post_content', $page_id );

echo 'page: ' . $page_id . ' (' . get_post_status( $page_id ) . ")\n";
echo 'length: ' . strlen( $saved ) . "\n";
echo 'unioncorp cards: ' . substr_count( $saved, 'id="theme-unioncorp"' ) . " (must be 1)\n";
echo 'unioncorp picker links: ' . substr_count( $saved, '#theme-unioncorp' ) . " (must be 1)\n";
echo 'pato cards still present: ' . substr_count( $saved, 'id="theme-pato"' ) . " (must be 1)\n";
echo 'total cards: ' . substr_count( $saved, 'class="clt-theme"' ) . "\n";
