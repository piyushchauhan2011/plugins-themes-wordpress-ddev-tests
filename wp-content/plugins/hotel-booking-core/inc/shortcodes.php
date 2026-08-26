<?php
/**
 * Shortcodes.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Room details shortcode for the single-room template.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function hotel_booking_room_meta_shortcode( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'id' => get_the_ID(),
		),
		$atts,
		'hotel_room_meta'
	);

	return hotel_booking_render_room_meta( (int) $atts['id'] );
}
add_shortcode( 'hotel_room_meta', 'hotel_booking_room_meta_shortcode' );
