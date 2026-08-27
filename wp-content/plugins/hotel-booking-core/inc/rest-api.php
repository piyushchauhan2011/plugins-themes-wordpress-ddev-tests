<?php
/**
 * Public REST API for rooms.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shape a room post for the custom REST namespace.
 *
 * @param WP_Post $post Room post.
 * @return array<string, mixed>
 */
function hotel_booking_prepare_room_for_rest( WP_Post $post ) {
	$meta = hotel_booking_get_room_meta( $post->ID );

	return array(
		'id'              => (int) $post->ID,
		'title'           => $post->post_title,
		'slug'            => $post->post_name,
		'excerpt'         => wp_strip_all_tags( (string) $post->post_excerpt ),
		'permalink'       => get_permalink( $post ),
		'price'           => $meta['price'],
		'price_formatted' => hotel_booking_format_price( $meta['price'] ),
		'guests'          => $meta['guests'],
		'beds'            => $meta['beds'],
		'size'            => $meta['size'],
	);
}

/**
 * Register /hotel-booking/v1/rooms routes.
 */
function hotel_booking_register_rest_routes() {
	register_rest_route(
		'hotel-booking/v1',
		'/rooms',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'hotel_booking_rest_get_rooms',
			'permission_callback' => '__return_true',
			'args'                => array(
				'guests' => array(
					'description'       => __( 'Minimum guest capacity.', 'hotel-booking-core' ),
					'type'              => 'integer',
					'minimum'           => 1,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);

	register_rest_route(
		'hotel-booking/v1',
		'/rooms/(?P<id>\d+)',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'hotel_booking_rest_get_room',
			'permission_callback' => '__return_true',
			'args'                => array(
				'id' => array(
					'description'       => __( 'Room post ID.', 'hotel-booking-core' ),
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'required'          => true,
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'hotel_booking_register_rest_routes' );

/**
 * GET /hotel-booking/v1/rooms
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function hotel_booking_rest_get_rooms( WP_REST_Request $request ) {
	$query_args = array(
		'post_type'      => 'hb_room',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	);

	$guests = (int) $request->get_param( 'guests' );
	if ( $guests > 0 ) {
		$query_args['meta_query'] = array(
			array(
				'key'     => 'hb_guests',
				'value'   => $guests,
				'type'    => 'NUMERIC',
				'compare' => '>=',
			),
		);
	}

	$query = new WP_Query( $query_args );
	$rooms = array();

	foreach ( $query->posts as $post ) {
		$rooms[] = hotel_booking_prepare_room_for_rest( $post );
	}

	return rest_ensure_response( $rooms );
}

/**
 * GET /hotel-booking/v1/rooms/{id}
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function hotel_booking_rest_get_room( WP_REST_Request $request ) {
	$post = get_post( (int) $request['id'] );

	if ( ! $post || 'hb_room' !== $post->post_type || 'publish' !== $post->post_status ) {
		return new WP_Error(
			'hotel_booking_room_not_found',
			__( 'Room not found.', 'hotel-booking-core' ),
			array( 'status' => 404 )
		);
	}

	return rest_ensure_response( hotel_booking_prepare_room_for_rest( $post ) );
}
