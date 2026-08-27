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
 * Register /hotel-booking/v1/rooms routes.
 */
function hotel_booking_register_rest_routes() {
	$room_collection_args = array(
		'q'         => array(
			'description'       => __( 'Full-text query (title, excerpt, content).', 'hotel-booking-core' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'guests'    => array(
			'description'       => __( 'Minimum guest capacity.', 'hotel-booking-core' ),
			'type'              => 'integer',
			'minimum'           => 1,
			'sanitize_callback' => 'absint',
		),
		'beds'      => array(
			'description'       => __( 'Minimum bed count.', 'hotel-booking-core' ),
			'type'              => 'integer',
			'minimum'           => 1,
			'sanitize_callback' => 'absint',
		),
		'price_min' => array(
			'description'       => __( 'Minimum nightly price.', 'hotel-booking-core' ),
			'type'              => 'integer',
			'minimum'           => 1,
			'sanitize_callback' => 'absint',
		),
		'price_max' => array(
			'description'       => __( 'Maximum nightly price.', 'hotel-booking-core' ),
			'type'              => 'integer',
			'minimum'           => 1,
			'sanitize_callback' => 'absint',
		),
		'lang'      => array(
			'description'       => __( 'Polylang language slug (en, es).', 'hotel-booking-core' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_key',
		),
	);

	register_rest_route(
		'hotel-booking/v1',
		'/rooms',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'hotel_booking_rest_get_rooms',
			'permission_callback' => '__return_true',
			'args'                => $room_collection_args,
		)
	);

	register_rest_route(
		'hotel-booking/v1',
		'/rooms/suggest',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'hotel_booking_rest_get_room_suggestions',
			'permission_callback' => '__return_true',
			'args'                => array(
				'q'    => array(
					'description'       => __( 'Title prefix for typeahead.', 'hotel-booking-core' ),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'lang' => array(
					'description'       => __( 'Polylang language slug (en, es).', 'hotel-booking-core' ),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
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
 * Search args from a REST request.
 *
 * @param WP_REST_Request $request Request.
 * @return array<string, mixed>
 */
function hotel_booking_rest_room_search_args( WP_REST_Request $request ) {
	return hotel_booking_normalize_room_search_args(
		array(
			'q'         => $request->get_param( 'q' ),
			'guests'    => $request->get_param( 'guests' ),
			'beds'      => $request->get_param( 'beds' ),
			'price_min' => $request->get_param( 'price_min' ),
			'price_max' => $request->get_param( 'price_max' ),
			'lang'      => $request->get_param( 'lang' ),
		)
	);
}

/**
 * GET /hotel-booking/v1/rooms
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function hotel_booking_rest_get_rooms( WP_REST_Request $request ) {
	$rooms = hotel_booking_search_rooms( hotel_booking_rest_room_search_args( $request ) );

	return rest_ensure_response( $rooms );
}

/**
 * GET /hotel-booking/v1/rooms/suggest
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function hotel_booking_rest_get_room_suggestions( WP_REST_Request $request ) {
	$q    = (string) $request->get_param( 'q' );
	$lang = (string) $request->get_param( 'lang' );

	return rest_ensure_response( hotel_booking_suggest_rooms( $q, $lang ) );
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
