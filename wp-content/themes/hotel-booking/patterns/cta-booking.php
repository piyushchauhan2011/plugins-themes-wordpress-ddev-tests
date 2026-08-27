<?php
/**
 * Title: Booking call to action
 * Slug: hotel-booking/cta-booking
 * Categories: hotel-booking
 * Viewport Width: 1400
 * Description: Closing reservation call to action.
 *
 * @package Hotel_Booking
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl"}}},"backgroundColor":"espresso","textColor":"cream","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-cream-color has-espresso-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl)">
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Write to the desk', 'hotel-booking' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center"} -->
	<p class="has-text-align-center"><?php esc_html_e( 'This is a learning theme, not a payment system. The booking page collects an inquiry so you can practice forms, pages, and redirects.', 'hotel-booking' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons">
		<!-- wp:button {"backgroundColor":"terracotta","textColor":"linen"} -->
		<div class="wp-block-button"><a class="wp-block-button__link has-linen-color has-terracotta-background-color has-text-color has-background wp-element-button" href="/booking/"><?php esc_html_e( 'Request a stay', 'hotel-booking' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
