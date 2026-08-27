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
	<p class="has-text-align-center has-small-font-size" style="letter-spacing:0.22em;text-transform:uppercase"><?php esc_html_e( 'Boutique stay', 'hotel-booking' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"textAlign":"center","level":1} -->
	<h1 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'A quiet night, well kept', 'hotel-booking' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center"} -->
	<p class="has-text-align-center"><?php esc_html_e( 'Garden suites, slow mornings, and a reservation desk that still answers the phone.', 'hotel-booking' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:html -->
	<form class="hb-booking-form" action="/booking/" method="get">
		<label><?php esc_html_e( 'Check in', 'hotel-booking' ); ?>
			<input type="date" name="check_in" required>
		</label>
		<label><?php esc_html_e( 'Check out', 'hotel-booking' ); ?>
			<input type="date" name="check_out" required>
		</label>
		<label><?php esc_html_e( 'Guests', 'hotel-booking' ); ?>
			<select name="guests">
				<?php for ( $n = 1; $n <= 4; $n++ ) : ?>
					<option value="<?php echo esc_attr( (string) $n ); ?>" <?php selected( 2, $n ); ?>>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: number of guests */
								_n( '%d guest', '%d guests', $n, 'hotel-booking' ),
								$n
							)
						);
						?>
					</option>
				<?php endfor; ?>
			</select>
		</label>
		<button type="submit"><?php esc_html_e( 'Check availability', 'hotel-booking' ); ?></button>
	</form>
	<!-- /wp:html -->
</div>
<!-- /wp:group -->
