<?php
/**
 * Hotel Booking theme bootstrap.
 *
 * Presentation only: setup, assets, and pattern category.
 * Rooms CPT and shortcodes live in the Hotel Booking Core plugin.
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HOTEL_BOOKING_VERSION', '1.0.0' );

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
 * Front-end styles and fonts.
 */
function hotel_booking_enqueue_assets() {
	wp_enqueue_style(
		'hotel-booking-fonts',
		'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@400;500;600&display=swap',
		array(),
		null
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
