<?php
/**
 * Plugin Name: Hotel Booking Core
 * Plugin URI: https://hotel-booking.ddev.site
 * Description: Rooms, custom inquiries table, REST API, Gutenberg blocks, and shortcodes for the Hotel Booking theme.
 * Version: 1.0.0
 * Requires at least: 6.7
 * Requires PHP: 8.2
 * Author: Hotel Booking Learners
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hotel-booking-core
 * Domain Path: /languages
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HOTEL_BOOKING_CORE_VERSION', '1.0.0' );
define( 'HOTEL_BOOKING_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'HOTEL_BOOKING_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once HOTEL_BOOKING_CORE_PATH . 'inc/helpers.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/auth.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/locale.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/polylang.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/polylang-seed.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/database.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/post-types.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/inquiries.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/workflow.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/shortcodes.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/opensearch.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/rest-api.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/inquiry-form.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/admin.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/amqp.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/jobs.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/cli.php';
require_once HOTEL_BOOKING_CORE_PATH . 'inc/blocks.php';

register_activation_hook( __FILE__, 'hotel_booking_install_inquiries_table' );
register_activation_hook( __FILE__, 'hotel_booking_register_roles' );
register_deactivation_hook( __FILE__, 'hotel_booking_clear_cron_events' );

/**
 * Load plugin translations.
 */
function hotel_booking_core_load_textdomain() {
	$languages = dirname( plugin_basename( __FILE__ ) ) . '/languages';
	load_plugin_textdomain( 'hotel-booking-core', false, $languages );

	$mofile = HOTEL_BOOKING_CORE_PATH . 'languages/hotel-booking-core-' . determine_locale() . '.mo';
	if ( is_readable( $mofile ) ) {
		load_textdomain( 'hotel-booking-core', $mofile );
	}
}
add_action( 'plugins_loaded', 'hotel_booking_core_load_textdomain' );

/**
 * Generate WebP derivatives for JPEG uploads (GD / Imagick).
 *
 * @param array<string, string> $formats Mime map of input to output.
 * @return array<string, string>
 */
function hotel_booking_image_editor_output_format( $formats ) {
	$formats['image/jpeg'] = 'image/webp';

	return $formats;
}
add_filter( 'image_editor_output_format', 'hotel_booking_image_editor_output_format' );
