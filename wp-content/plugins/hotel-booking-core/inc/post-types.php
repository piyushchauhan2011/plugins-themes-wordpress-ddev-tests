<?php
/**
 * Room custom post type and meta.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the room post type.
 */
function hotel_booking_register_room_post_type() {
	register_post_type(
		'hb_room',
		array(
			'labels'       => array(
				'name'          => __( 'Rooms', 'hotel-booking-core' ),
				'singular_name' => __( 'Room', 'hotel-booking-core' ),
				'add_new_item'  => __( 'Add New Room', 'hotel-booking-core' ),
				'edit_item'     => __( 'Edit Room', 'hotel-booking-core' ),
				'view_item'     => __( 'View Room', 'hotel-booking-core' ),
				'search_items'  => __( 'Search Rooms', 'hotel-booking-core' ),
				'all_items'     => __( 'All Rooms', 'hotel-booking-core' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-building',
			'rewrite'      => array( 'slug' => 'rooms' ),
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
			'template'     => array(
				array( 'core/paragraph', array( 'placeholder' => __( 'Describe the room…', 'hotel-booking-core' ) ) ),
			),
		)
	);
}
add_action( 'init', 'hotel_booking_register_room_post_type' );

/**
 * Register room meta used by the theme templates and REST API.
 */
function hotel_booking_register_room_meta() {
	$fields = array(
		'hb_price'  => array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'description'       => __( 'Nightly rate in USD.', 'hotel-booking-core' ),
		),
		'hb_guests' => array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'description'       => __( 'Maximum guests.', 'hotel-booking-core' ),
		),
		'hb_beds'   => array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'description'       => __( 'Number of beds.', 'hotel-booking-core' ),
		),
		'hb_size'   => array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'description'       => __( 'Room size in square meters.', 'hotel-booking-core' ),
		),
	);

	foreach ( $fields as $key => $args ) {
		register_post_meta(
			'hb_room',
			$key,
			array(
				'type'              => $args['type'],
				'description'       => $args['description'],
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => $args['sanitize_callback'],
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'hotel_booking_register_room_meta' );
