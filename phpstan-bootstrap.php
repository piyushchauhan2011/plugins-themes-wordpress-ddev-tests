<?php
/**
 * Constants PHPStan cannot see: hotel-booking-core.php exits unless ABSPATH is set.
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

if ( ! defined( 'HOTEL_BOOKING_CORE_PATH' ) ) {
	define( 'HOTEL_BOOKING_CORE_PATH', 'wp-content/plugins/hotel-booking-core/' );
}
