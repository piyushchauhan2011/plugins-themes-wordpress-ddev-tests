<?php
/**
 * PHPUnit bootstrap file.
 *
 * Loads Yoast polyfills, then the WordPress test suite, then the Hotel Booking
 * Core plugin and hotel-booking theme.
 *
 * @package Hotel_Booking
 */

$project_root = dirname( __DIR__ );

if ( file_exists( $project_root . '/vendor/autoload.php' ) ) {
	require_once $project_root . '/vendor/autoload.php';
}

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) && is_dir( $project_root . '/vendor/yoast/phpunit-polyfills' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $project_root . '/vendor/yoast/phpunit-polyfills' );
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = '/var/www/html/.wp-tests/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run ddev setup-tests ?" . PHP_EOL;
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Load the companion plugin before WordPress finishes booting.
 */
function hotel_booking_tests_load_plugin() {
	require dirname( __DIR__ ) . '/wp-content/plugins/hotel-booking-core/hotel-booking-core.php';
}

/**
 * Registers this theme as the active theme for the test suite.
 */
function hotel_booking_tests_register_theme() {
	$theme_dir     = dirname( __DIR__ ) . '/wp-content/themes/hotel-booking';
	$current_theme = 'hotel-booking';
	$theme_root    = dirname( $theme_dir );

	add_filter(
		'theme_root',
		static function () use ( $theme_root ) {
			return $theme_root;
		}
	);

	register_theme_directory( $theme_root );

	add_filter(
		'pre_option_template',
		static function () use ( $current_theme ) {
			return $current_theme;
		}
	);

	add_filter(
		'pre_option_stylesheet',
		static function () use ( $current_theme ) {
			return $current_theme;
		}
	);
}

tests_add_filter( 'muplugins_loaded', 'hotel_booking_tests_load_plugin' );
tests_add_filter( 'muplugins_loaded', 'hotel_booking_tests_register_theme' );
tests_add_filter( 'setup_theme', 'hotel_booking_tests_register_theme' );

require $_tests_dir . '/includes/bootstrap.php';
