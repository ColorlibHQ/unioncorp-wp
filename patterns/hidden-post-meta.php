<?php
/**
 * Title: Post meta
 * Slug: unioncorp/hidden-post-meta
 * Description: Date, author and categories for a single post.
 * Inserter: no
 *
 * @package Unioncorp
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:post-date {"metadata":{"bindings":{"datetime":{"source":"core/post-data","args":{"field":"date"}}}},"textColor":"muted","fontSize":"small"} /-->

<!-- wp:post-author-name {"textColor":"muted","fontSize":"small"} /-->

<!-- wp:post-terms {"term":"category","textColor":"muted","fontSize":"small"} /--></div>
<!-- /wp:group -->
