<?php
/**
 * Title: Footer bar
 * Slug: hotel-booking/footer-bar
 * Categories: hotel-booking
 * Inserter: no
 * Description: Footer credit and booking link.
 *
 * @package Hotel_Booking
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|lg","bottom":"var:preset|spacing|lg"}}},"backgroundColor":"espresso","textColor":"cream","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-cream-color has-espresso-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--lg);padding-bottom:var(--wp--preset--spacing--lg)">
	<!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:paragraph -->
		<p><?php esc_html_e( 'Hotel Booking — a learning theme for WordPress block development.', 'hotel-booking' ); ?></p>
		<!-- /wp:paragraph -->
		<!-- wp:paragraph -->
		<p><a href="/booking/"><?php esc_html_e( 'Reserve a room', 'hotel-booking' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
