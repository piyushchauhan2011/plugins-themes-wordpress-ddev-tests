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
	<h2 class="wp-block-heading"><?php esc_html_e( 'Tell us the dates', 'hotel-booking' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<p>
	<?php
	echo wp_kses(
		sprintf(
			/* translators: %s: MySQL table name wrapped in a code tag */
			__( 'This form POSTs to WordPress and inserts a row in the %s custom table. It does not charge a card.', 'hotel-booking' ),
			'<code>wp_hb_inquiries</code>'
		),
		array( 'code' => array() )
	);
	?>
	</p>
	<!-- /wp:paragraph -->

	<!-- wp:shortcode -->
	[hotel_inquiry_form]
	<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
