<?php
/**
 * Hotel Booking theme bootstrap.
 *
 * Presentation plus theme-native Interactivity blocks (Stay FAQ, color scheme).
 * Rooms CPT and shortcodes live in the Hotel Booking Core plugin.
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HOTEL_BOOKING_VERSION', '1.2.0' );

require_once get_template_directory() . '/inc/patterns.php';

/**
 * Theme setup.
 */
function hotel_booking_setup() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
	load_theme_textdomain( 'hotel-booking', get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'hotel_booking_setup' );

/**
 * Register theme Gutenberg blocks (unbundled view modules, no webpack).
 */
function hotel_booking_register_theme_blocks() {
	$blocks = glob( get_template_directory() . '/blocks/*/block.json' );

	if ( ! $blocks ) {
		return;
	}

	foreach ( $blocks as $block_json ) {
		register_block_type( dirname( $block_json ) );
	}
}
add_action( 'init', 'hotel_booking_register_theme_blocks' );

/**
 * Default light scheme on <html> when JavaScript is off.
 *
 * @param string $output Language attributes.
 * @return string
 */
function hotel_booking_html_color_scheme( $output ) {
	if ( false === strpos( $output, 'data-color-scheme' ) ) {
		$output .= ' data-color-scheme="light"';
	}

	return $output;
}
add_filter( 'language_attributes', 'hotel_booking_html_color_scheme' );

/**
 * Apply stored or system color scheme before first paint.
 */
function hotel_booking_color_scheme_boot() {
	$script = <<<'JS'
(function () {
	var k = 'hotel-booking-color-scheme';
	var s;
	try {
		s = localStorage.getItem( k );
	} catch ( e ) {
		s = null;
	}
	if ( s !== 'dark' && s !== 'light' ) {
		s = window.matchMedia( '(prefers-color-scheme: dark)' ).matches ? 'dark' : 'light';
	}
	document.documentElement.setAttribute( 'data-color-scheme', s );
})();
JS;

	wp_print_inline_script_tag( $script );
}
add_action( 'wp_head', 'hotel_booking_color_scheme_boot', 0 );

/**
 * Front-end styles and fonts.
 */
function hotel_booking_enqueue_assets() {
	wp_enqueue_style(
		'hotel-booking-fonts',
		'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@400;500;600&display=swap',
		array(),
		HOTEL_BOOKING_VERSION
	);

	wp_enqueue_style(
		'hotel-booking-style',
		get_stylesheet_uri(),
		array( 'hotel-booking-fonts' ),
		HOTEL_BOOKING_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'hotel_booking_enqueue_assets' );

/**
 * Admin notice when the companion plugin is missing.
 */
function hotel_booking_admin_notice_missing_plugin() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	if ( function_exists( 'hotel_booking_register_room_post_type' ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p>';
	echo esc_html__( 'Hotel Booking needs the Hotel Booking Core plugin for room listings and the booking inquiry shortcode.', 'hotel-booking' );
	echo '</p></div>';
}
add_action( 'admin_notices', 'hotel_booking_admin_notice_missing_plugin' );
