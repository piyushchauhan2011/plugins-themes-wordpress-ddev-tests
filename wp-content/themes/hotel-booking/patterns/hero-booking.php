<?php
/**
 * Title: Hero booking
 * Slug: hotel-booking/hero-booking
 * Categories: hotel-booking
 * Viewport Width: 1400
 * Description: Full-width hotel hero with a booking inquiry form.
 *
 * @package Hotel_Booking
 */
?>
<!-- wp:group {"align":"full","className":"hb-hero","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","left":"var:preset|spacing|sm","right":"var:preset|spacing|sm"}}},"layout":{"type":"constrained","contentSize":"760px"}} -->
<div class="wp-block-group alignfull hb-hero" style="padding-top:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--sm);padding-bottom:var(--wp--preset--spacing--xl);padding-left:var(--wp--preset--spacing--sm)">
	<!-- wp:paragraph {"align":"center","style":{"typography":{"letterSpacing":"0.22em","textTransform":"uppercase"}},"fontSize":"small"} -->
	<p class="has-text-align-center has-small-font-size" style="letter-spacing:0.22em;text-transform:uppercase">Boutique stay</p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"textAlign":"center","level":1} -->
	<h1 class="wp-block-heading has-text-align-center">A quiet night, well kept</h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center"} -->
	<p class="has-text-align-center">Garden suites, slow mornings, and a reservation desk that still answers the phone.</p>
	<!-- /wp:paragraph -->

	<!-- wp:html -->
	<form class="hb-booking-form" action="/booking/" method="get">
		<label>Check in
			<input type="date" name="check_in" required>
		</label>
		<label>Check out
			<input type="date" name="check_out" required>
		</label>
		<label>Guests
			<select name="guests">
				<option value="1">1 guest</option>
				<option value="2" selected>2 guests</option>
				<option value="3">3 guests</option>
				<option value="4">4 guests</option>
			</select>
		</label>
		<button type="submit">Check availability</button>
	</form>
	<!-- /wp:html -->
</div>
<!-- /wp:group -->
