<?php
/**
 * Title: Hero
 * Slug: unioncorp/hero
 * Categories: unioncorp-sections
 * Keywords: hero, banner
 * Description: Full-width opening banner with a headline and two buttons.
 *
 * @package Unioncorp
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:cover {"url":"<?php echo esc_url( get_theme_file_uri( 'assets/images/bg_1.avif' ) ); ?>","dimRatio":70,"overlayColor":"dark","isUserOverlayColor":true,"minHeight":70,"minHeightUnit":"vh","align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull" style="min-height:70vh"><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( get_theme_file_uri( 'assets/images/bg_1.avif' ) ); ?>" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-dark-background-color has-background-dim-70 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"unioncorp-eyebrow","style":{"typography":{"textAlign":"center"}},"textColor":"overlay","fontSize":"small"} -->
<p class="has-text-align-center unioncorp-eyebrow has-overlay-color has-text-color has-small-font-size">Finance &amp; consultation</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"style":{"typography":{"textAlign":"center"}},"textColor":"overlay","fontSize":"colossal"} -->
<h1 class="wp-block-heading has-text-align-center has-overlay-color has-text-color has-colossal-font-size">We're always here to give financial help</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"textAlign":"center"}},"textColor":"overlay","fontSize":"large"} -->
<p class="has-text-align-center has-overlay-color has-text-color has-large-font-size">Planning, investment and risk management for businesses that would rather spend their time on the business.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Get started</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-unioncorp-ghost"} -->
<div class="wp-block-button is-style-unioncorp-ghost"><a class="wp-block-button__link wp-element-button" href="#">Our services</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->
