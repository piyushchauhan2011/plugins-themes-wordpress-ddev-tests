<?php
/**
 * Public REST API for rooms and Prometheus metrics.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register /hotel-booking/v1/rooms and /metrics routes.
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

	register_rest_route(
		'hotel-booking/v1',
		'/metrics',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'hotel_booking_rest_get_metrics',
			'permission_callback' => '__return_true',
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

/**
 * Prometheus exposition text (inquiry counts, OpenSearch up).
 *
 * @return string
 */
function hotel_booking_prometheus_metrics() {
	global $wpdb;

	$counts = array();
	foreach ( hotel_booking_inquiry_statuses() as $status ) {
		$counts[ $status ] = 0;
	}

	if ( function_exists( 'hotel_booking_inquiries_table_name' ) ) {
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT status, COUNT(*) AS n FROM %i GROUP BY status',
				hotel_booking_inquiries_table_name()
			)
		);
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$status = (string) $row->status;
				if ( isset( $counts[ $status ] ) ) {
					$counts[ $status ] = (int) $row->n;
				}
			}
		}
	}

	$opensearch_up = 0;
	if ( function_exists( 'hotel_booking_opensearch_is_configured' ) && hotel_booking_opensearch_is_configured() ) {
		$health        = hotel_booking_opensearch_request( 'GET', '/_cluster/health', null, 1 );
		$opensearch_up = is_wp_error( $health ) ? 0 : 1;
	}

	$lines   = array();
	$lines[] = '# HELP hotel_booking_inquiries Inquiry rows by status.';
	$lines[] = '# TYPE hotel_booking_inquiries gauge';
	foreach ( $counts as $status => $n ) {
		$lines[] = 'hotel_booking_inquiries{status="' . $status . '"} ' . (string) $n;
	}
	$lines[] = '# HELP hotel_booking_opensearch_up 1 if OpenSearch answers, 0 otherwise.';
	$lines[] = '# TYPE hotel_booking_opensearch_up gauge';
	$lines[] = 'hotel_booking_opensearch_up ' . (string) $opensearch_up;

	return implode( "\n", $lines ) . "\n";
}

/**
 * GET /hotel-booking/v1/metrics
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function hotel_booking_rest_get_metrics( WP_REST_Request $request ) {
	unset( $request );

	$response = new WP_REST_Response( hotel_booking_prometheus_metrics() );
	$response->header( 'Content-Type', 'text/plain; version=0.0.4; charset=utf-8' );

	return $response;
}

/**
 * Serve metrics as Prometheus text instead of JSON.
 *
 * @param bool             $served  Whether the request is already served.
 * @param WP_HTTP_Response $result  Result to send.
 * @param WP_REST_Request  $request Request.
 * @param WP_REST_Server   $server  Server instance.
 * @return bool
 */
function hotel_booking_serve_prometheus_metrics( $served, $result, $request, $server ) {
	unset( $server );

	if ( '/hotel-booking/v1/metrics' !== $request->get_route() ) {
		return $served;
	}

	if ( ! $result instanceof WP_REST_Response ) {
		return $served;
	}

	if ( ! headers_sent() ) {
		header( 'Content-Type: text/plain; version=0.0.4; charset=utf-8' );
	}
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Prometheus text built from integer counts.
	echo $result->get_data();

	return true;
}
add_filter( 'rest_pre_serve_request', 'hotel_booking_serve_prometheus_metrics', 10, 4 );
