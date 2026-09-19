<?php
/**
 * Form styling, for Unioncorp's own form and for whichever plugin a site uses.
 *
 * A business site often ends up with a form plugin as well, and every one of
 * them ships markup that ignores the theme: its own input borders, its own
 * button colours, its own spacing. Rather than let a Contact Form 7 block sit
 * in the middle of a Unioncorp page looking like a different website, the theme
 * maps each plugin's classes onto its own tokens.
 *
 * The CSS is only enqueued when one of them is actually present, so a site
 * with no form plugin does not pay for it.
 *
 * @package Unioncorp
 */

defined( 'ABSPATH' ) || exit;

/**
 * Which form plugins are active.
 *
 * Detection prefers a shortcode or a registered block over a class name:
 * plugins move their namespaces between versions and their public handles
 * stay put.
 *
 * @return string[]
 */
function unioncorp_active_form_plugins() {
	$found = array();

	$shortcodes = array(
		'contact-form-7' => 'contact-form-7',
		'wpforms'        => 'wpforms',
		'gravityforms'   => 'gravityform',
		'fluentform'     => 'fluentform',
		'forminator'     => 'forminator_form',
		'ninja-forms'    => 'ninja_form',
		'formidable'     => 'formidable',
		'happyforms'     => 'happyforms',
	);

	foreach ( $shortcodes as $slug => $tag ) {
		if ( shortcode_exists( $tag ) ) {
			$found[] = $slug;
		}
	}

	return $found;
}

/**
 * Load the form stylesheet only where a form can appear.
 */
function unioncorp_enqueue_form_styles() {
	// Unioncorp's own enquiry form is always a possibility, so the stylesheet
	// is not conditional on a plugin being present — it carries both.
	wp_enqueue_style(
		'unioncorp-wp-unioncorp-forms',
		get_template_directory_uri() . '/assets/css/forms.css',
		array( 'unioncorp-wp-unioncorp-style' ),
		UNIONCORP_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'unioncorp_enqueue_form_styles' );

/**
 * Tell the editor about the same stylesheet, so a form block looks right there.
 */
function unioncorp_editor_form_styles() {
	add_editor_style( 'assets/css/forms.css' );
}
add_action( 'after_setup_theme', 'unioncorp_editor_form_styles', 20 );
