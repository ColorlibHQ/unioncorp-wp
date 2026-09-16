<?php
/**
 * Title: Sidebar
 * Slug: unioncorp/sidebar
 * Keywords: sidebar
 * Description: Search, categories and recent posts.
 * Inserter: no
 *
 * @package Unioncorp
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:search {"label":"Search","buttonText":"Search"} /-->

<!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">Categories</h3>
<!-- /wp:heading -->

<!-- wp:categories /-->

<!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">Recent posts</h3>
<!-- /wp:heading -->

<!-- wp:latest-posts {"postsToShow":4,"displayPostDate":true} /--></div>
<!-- /wp:group -->
