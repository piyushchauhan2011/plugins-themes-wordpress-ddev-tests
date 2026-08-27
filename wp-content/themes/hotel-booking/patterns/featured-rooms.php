<?php
/**
 * Title: Featured rooms
 * Slug: hotel-booking/featured-rooms
 * Categories: hotel-booking
 * Viewport Width: 1400
 * Description: Query loop of hotel rooms for the landing page.
 *
 * @package Hotel_Booking
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl)">
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center">Rooms with a view, and a desk</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center"} -->
	<p class="has-text-align-center">Four rooms, one house. Rates are nightly and include breakfast.</p>
	<!-- /wp:paragraph -->

	<!-- wp:query {"query":{"perPage":4,"pages":0,"offset":0,"postType":"hb_room","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":false},"align":"wide"} -->
	<div class="wp-block-query alignwide">
		<!-- wp:post-template {"layout":{"type":"grid","minimumColumnWidth":"18rem"}} -->
			<!-- wp:group {"style":{"spacing":{"blockGap":"0.75rem","padding":{"bottom":"var:preset|spacing|md"}}}} -->
			<div class="wp-block-group" style="padding-bottom:var(--wp--preset--spacing--md)">
				<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3"} /-->
				<!-- wp:post-title {"isLink":true,"level":3} /-->
				<!-- wp:post-excerpt /-->
			</div>
			<!-- /wp:group -->
		<!-- /wp:post-template -->

		<!-- wp:query-no-results -->
			<!-- wp:paragraph {"align":"center"} -->
			<p class="has-text-align-center">Rooms will appear here after you run <code>ddev seed-content</code>.</p>
			<!-- /wp:paragraph -->
		<!-- /wp:query-no-results -->
	</div>
	<!-- /wp:query -->
</div>
<!-- /wp:group -->
