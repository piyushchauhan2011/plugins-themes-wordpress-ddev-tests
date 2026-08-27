<?php
/**
 * Title: Testimonials
 * Slug: hotel-booking/testimonials
 * Categories: hotel-booking
 * Viewport Width: 1400
 * Description: Guest quotes for the landing page.
 *
 * @package Hotel_Booking
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl)">
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'From the guest book', 'hotel-booking' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:columns {"align":"wide"} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:quote -->
			<blockquote class="wp-block-quote">
				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'We came for one night and stayed for four. The garden suite is the whole argument.', 'hotel-booking' ); ?></p>
				<!-- /wp:paragraph -->
				<cite>Mira K.</cite>
			</blockquote>
			<!-- /wp:quote -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:quote -->
			<blockquote class="wp-block-quote">
				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Someone answered the phone. That should not feel rare, and yet.', 'hotel-booking' ); ?></p>
				<!-- /wp:paragraph -->
				<cite>James P.</cite>
			</blockquote>
			<!-- /wp:quote -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
