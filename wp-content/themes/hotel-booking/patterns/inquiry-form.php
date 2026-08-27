<?php
/**
 * Title: Inquiry form
 * Slug: hotel-booking/inquiry-form
 * Categories: hotel-booking
 * Viewport Width: 800
 * Description: Server-rendered booking inquiry form saved to a custom table.
 *
 * @package Hotel_Booking
 */
?>
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading">Tell us the dates</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<p>This form POSTs to WordPress and inserts a row in the <code>wp_hb_inquiries</code> custom table. It does not charge a card.</p>
	<!-- /wp:paragraph -->

	<!-- wp:shortcode -->
	[hotel_inquiry_form]
	<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
