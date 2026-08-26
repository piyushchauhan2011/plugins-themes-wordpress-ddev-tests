<?php
/**
 * Block pattern category registration.
 *
 * Pattern files in /patterns are auto-discovered. The category must exist so
 * they group together in the inserter, and so tests can assert registration.
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the theme pattern category.
 */
function hotel_booking_register_pattern_category() {
	register_block_pattern_category(
		'hotel-booking',
		array(
			'label' => __( 'Hotel Booking', 'hotel-booking' ),
		)
	);
}
add_action( 'init', 'hotel_booking_register_pattern_category' );
