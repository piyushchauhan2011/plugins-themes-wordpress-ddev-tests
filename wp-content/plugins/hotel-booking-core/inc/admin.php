<?php
/**
 * wp-admin menu, inquiries list, and Settings API.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default plugin settings.
 *
 * @return array{hotel_name:string,desk_email:string,max_guests:int}
 */
function hotel_booking_default_settings() {
	return array(
		'hotel_name' => 'Hotel Booking',
		'desk_email' => '',
		'max_guests' => 8,
	);
}

/**
 * Read one setting with defaults applied.
 *
 * @param string $key Setting key.
 * @return mixed
 */
function hotel_booking_get_setting( $key ) {
	$settings = wp_parse_args( get_option( 'hotel_booking_settings', array() ), hotel_booking_default_settings() );

	return array_key_exists( $key, $settings ) ? $settings[ $key ] : null;
}

/**
 * Sanitize the hotel_booking_settings option.
 *
 * @param mixed $input Raw option value.
 * @return array{hotel_name:string,desk_email:string,max_guests:int}
 */
function hotel_booking_sanitize_settings( $input ) {
	$output = hotel_booking_default_settings();

	if ( ! is_array( $input ) ) {
		return $output;
	}

	$output['hotel_name'] = isset( $input['hotel_name'] ) ? sanitize_text_field( $input['hotel_name'] ) : '';

	$email                = isset( $input['desk_email'] ) ? sanitize_email( $input['desk_email'] ) : '';
	$output['desk_email'] = is_email( $email ) ? $email : '';

	$max                  = isset( $input['max_guests'] ) ? absint( $input['max_guests'] ) : 8;
	$output['max_guests'] = min( 8, max( 1, $max ) );

	return $output;
}

/**
 * Register the settings option.
 */
function hotel_booking_register_settings() {
	register_setting(
		'hotel_booking',
		'hotel_booking_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'hotel_booking_sanitize_settings',
			'default'           => hotel_booking_default_settings(),
		)
	);
}
add_action( 'admin_init', 'hotel_booking_register_settings' );

/**
 * Register Hotel Booking admin pages.
 */
function hotel_booking_register_admin_menu() {
	add_menu_page(
		__( 'Hotel Booking', 'hotel-booking-core' ),
		__( 'Hotel Booking', 'hotel-booking-core' ),
		'edit_posts',
		'hotel-booking',
		'hotel_booking_render_inquiries_page',
		'dashicons-building',
		58
	);

	add_submenu_page(
		'hotel-booking',
		__( 'Inquiries', 'hotel-booking-core' ),
		__( 'Inquiries', 'hotel-booking-core' ),
		'edit_posts',
		'hotel-booking',
		'hotel_booking_render_inquiries_page'
	);

	add_submenu_page(
		'hotel-booking',
		__( 'Settings', 'hotel-booking-core' ),
		__( 'Settings', 'hotel-booking-core' ),
		'manage_options',
		'hotel-booking-settings',
		'hotel_booking_render_settings_page'
	);
}
add_action( 'admin_menu', 'hotel_booking_register_admin_menu' );

/**
 * Inquiries list (edit_posts).
 */
function hotel_booking_render_inquiries_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You cannot view inquiries.', 'hotel-booking-core' ) );
	}

	$inquiries = hotel_booking_get_inquiries(
		array(
			'orderby' => 'created_at',
			'order'   => 'DESC',
			'limit'   => 100,
		)
	);

	include HOTEL_BOOKING_CORE_PATH . 'views/inquiries.php';
}

/**
 * Settings form (manage_options).
 */
function hotel_booking_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You cannot manage Hotel Booking settings.', 'hotel-booking-core' ) );
	}

	include HOTEL_BOOKING_CORE_PATH . 'views/settings.php';
}
