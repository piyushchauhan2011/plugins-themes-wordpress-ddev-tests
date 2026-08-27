<?php
/**
 * Plugin Name: Hotel Booking Core
 * Plugin URI: https://hotel-booking.ddev.site
 * Description: Rooms, custom inquiries table, REST API, and shortcodes for the Hotel Booking theme.
 * Version: 1.0.0
 * Requires at least: 6.7
 * Requires PHP: 8.2
 * Author: Hotel Booking Learners
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hotel-booking-core
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HOTEL_BOOKING_CORE_VERSION', '1.0.0' );
define( 'HOTEL_BOOKING_CORE_PATH', plugin_dir_path( __FILE__ ) );

require_once HOTEL_BOOKING_CORE_PATH . 'inc/helpers.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/database.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/post-types.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/inquiries.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/shortcodes.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/rest-api.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/inquiry-form.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/admin.php';

register_activation_hook( __FILE__, 'hotel_booking_install_inquiries_table' );

/**
 * Load plugin translations.
 */
function hotel_booking_core_load_textdomain() {
	load_plugin_textdomain( 'hotel-booking-core', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'hotel_booking_core_load_textdomain' );
