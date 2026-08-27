<?php
/**
 * Room helpers used by the shortcode and by WP_UnitTestCase.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Format a nightly rate for display.
 *
 * @param mixed $amount Numeric amount in USD.
 * @return string
 */
function hotel_booking_format_price( $amount ) {
	$amount = is_numeric( $amount ) ? (float) $amount : 0;

	return sprintf(
		/* translators: %s: formatted dollar amount */
		__( '$%s / night', 'hotel-booking-core' ),
		number_format_i18n( $amount, 0 )
	);
}

/**
 * Read room meta with defaults.
 *
 * @param int $post_id Room post ID.
 * @return array{price:int,guests:int,beds:int,size:int}
 */
function hotel_booking_get_room_meta( $post_id ) {
	return array(
		'price'  => (int) get_post_meta( $post_id, 'hb_price', true ),
		'guests' => (int) get_post_meta( $post_id, 'hb_guests', true ),
		'beds'   => (int) get_post_meta( $post_id, 'hb_beds', true ),
		'size'   => (int) get_post_meta( $post_id, 'hb_size', true ),
	);
}

/**
 * Render a definition list of room facts.
 *
 * @param int $post_id Room post ID.
 * @return string
 */
function hotel_booking_render_room_meta( $post_id ) {
	if ( 'hb_room' !== get_post_type( $post_id ) ) {
		return '';
	}

	$meta = hotel_booking_get_room_meta( $post_id );

	ob_start();
	?>
	<dl class="hb-room-meta">
		<div>
			<dt><?php esc_html_e( 'From', 'hotel-booking-core' ); ?></dt>
			<dd><?php echo esc_html( hotel_booking_format_price( $meta['price'] ) ); ?></dd>
		</div>
		<div>
			<dt><?php esc_html_e( 'Guests', 'hotel-booking-core' ); ?></dt>
			<dd><?php echo esc_html( (string) $meta['guests'] ); ?></dd>
		</div>
		<div>
			<dt><?php esc_html_e( 'Beds', 'hotel-booking-core' ); ?></dt>
			<dd><?php echo esc_html( (string) $meta['beds'] ); ?></dd>
		</div>
		<div>
			<dt><?php esc_html_e( 'Size', 'hotel-booking-core' ); ?></dt>
			<dd>
				<?php
				/* translators: %s: room size in square metres */
				echo esc_html( sprintf( __( '%s m²', 'hotel-booking-core' ), (string) $meta['size'] ) );
				?>
			</dd>
		</div>
	</dl>
	<?php

	return (string) ob_get_clean();
}
