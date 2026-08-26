<?php
/**
 * Title: Inquiry form
 * Slug: hotel-booking/inquiry-form
 * Categories: hotel-booking
 * Viewport Width: 800
 * Description: Marketing inquiry form posted to the booking page.
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
	<p>This form does not charge a card. It is a GET request so you can inspect query args while learning theme templates.</p>
	<!-- /wp:paragraph -->

	<!-- wp:html -->
	<form class="hb-booking-form" action="/booking/" method="get">
		<label>Name
			<input type="text" name="guest_name" required>
		</label>
		<label>Email
			<input type="email" name="guest_email" required>
		</label>
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
		<button type="submit">Send inquiry</button>
	</form>
	<!-- /wp:html -->
</div>
<!-- /wp:group -->
