<?php
/**
 * Title: Contact: details, form and map
 * Slug: unioncorp/contact
 * Categories: unioncorp-sections
 * Keywords: contact, form, map
 * Description: Contact details beside the enquiry form, with a map beneath.
 *
 * @package Unioncorp
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"},"anchor":"contact"} -->
<div class="wp-block-group alignfull" id="contact" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)"><!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|60","left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"width":"45%"} -->
<div class="wp-block-column" style="flex-basis:45%"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:heading {"style":{"typography":{"textAlign":"left"}}} -->
<h2 class="wp-block-heading has-text-align-left">Contact us</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"muted"} -->
<p class="has-muted-color has-text-color">We are open for questions, second opinions and new work.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"unioncorp-detail unioncorp-icon\u002d\u002dmap-pin","textColor":"muted"} -->
<p class="unioncorp-detail unioncorp-icon--map-pin has-muted-color has-text-color"><strong>Address</strong><br>198 West 21th Street, Suite 721, New York NY 10016</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"unioncorp-detail unioncorp-icon\u002d\u002dmail","textColor":"muted"} -->
<p class="unioncorp-detail unioncorp-icon--mail has-muted-color has-text-color"><strong>Email</strong><br>info@yourdomain.com</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"unioncorp-detail unioncorp-icon\u002d\u002dphone","textColor":"muted"} -->
<p class="unioncorp-detail unioncorp-icon--phone has-muted-color has-text-color"><strong>Phone</strong><br>+1 235 2355 98</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"55%"} -->
<div class="wp-block-column" style="flex-basis:55%"><!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:shortcode -->
[unioncorp_enquiry_form]
<!-- /wp:shortcode --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dspacing\u002d\u002d60)"} -->
<div style="height:var(--wp--preset--spacing--60)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:html -->
<iframe class="unioncorp-map" title="Map showing where Unioncorp is" loading="lazy" src="https://www.openstreetmap.org/export/embed.html?bbox=-74.0170%2C40.6990%2C-74.0070%2C40.7100&amp;layer=mapnik&amp;marker=40.704644%2C-74.011987" style="width:100%;height:420px;border:0"></iframe>
<!-- /wp:html --></div>
<!-- /wp:group -->
