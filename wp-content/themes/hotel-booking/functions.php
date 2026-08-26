<?php
/**
 * Hotel Booking theme bootstrap.
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HOTEL_BOOKING_VERSION', '1.0.0' );

require_once get_template_directory() . '/inc/helpers.php';
require_once get_template_directory() . '/inc/post-types.php';
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
