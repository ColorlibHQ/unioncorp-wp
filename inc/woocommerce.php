<?php
/**
 * WooCommerce support.
 *
 * A restaurant that sells gift cards, a cookbook or collection orders ends up
 * with WooCommerce, and Woo's markup answers to none of the theme's tokens.
 * This declares support and loads the mapping stylesheet — but only on the
 * pages that need it, so a site with a shop of three gift cards does not pay
 * for shop CSS on its menu page.
 *
 * @package Unioncorp
 */

defined( 'ABSPATH' ) || exit;

/**
 * Declare support, and turn off Woo's own layout wrappers.
 *
 * A block theme supplies its own templates; Woo's `woocommerce_content`
 * wrappers would nest a second container inside them.
 */
function unioncorp_woocommerce_setup() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'unioncorp_woocommerce_setup' );

/**
 * Load the mapping stylesheet where a shop can appear.
 *
 * `is_woocommerce()` covers shop, product and product taxonomy pages but not
 * cart, checkout or account, so those are named. A shortcode or block dropped
 * on an arbitrary page is caught by the body class check Woo adds.
 */
function unioncorp_woocommerce_styles() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$needed = is_woocommerce() || is_cart() || is_checkout() || is_account_page();

	/**
	 * Filters whether the WooCommerce stylesheet loads on this request.
	 *
	 * @param bool $needed Whether to load it.
	 */
	if ( ! apply_filters( 'unioncorp_load_woocommerce_styles', $needed ) ) {
		return;
	}

	wp_enqueue_style(
		'unioncorp-woocommerce',
		get_template_directory_uri() . '/assets/css/woocommerce.css',
		array( 'unioncorp-style' ),
		UNIONCORP_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'unioncorp_woocommerce_styles', 20 );

/**
 * Three products to a row, matching the stylesheet's grid.
 *
 * @return int
 */
function unioncorp_woocommerce_columns() {
	return 3;
}
add_filter( 'loop_shop_columns', 'unioncorp_woocommerce_columns' );
