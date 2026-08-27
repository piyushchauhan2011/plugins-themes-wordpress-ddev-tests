<?php
/**
 * OpenSearch client for published rooms (HTTP via wp_remote_request).
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rooms index name.
 *
 * @return string
 */
function hotel_booking_opensearch_index_name() {
	return 'hotel-booking-rooms';
}

/**
 * Whether WP_OPENSEARCH_HOST is set (DDEV post-start or production wp-config).
 *
 * @return bool
 */
function hotel_booking_opensearch_is_configured() {
	$enabled = defined( 'WP_OPENSEARCH_HOST' ) && is_string( WP_OPENSEARCH_HOST ) && '' !== WP_OPENSEARCH_HOST;

	return (bool) apply_filters( 'hotel_booking_opensearch_enabled', $enabled );
}

/**
 * Cluster base URL (no trailing slash).
 *
 * @return string
 */
function hotel_booking_opensearch_base_url() {
	if ( ! hotel_booking_opensearch_is_configured() ) {
		return '';
	}

	$host = '127.0.0.1';
	if ( defined( 'WP_OPENSEARCH_HOST' ) && is_string( WP_OPENSEARCH_HOST ) && '' !== WP_OPENSEARCH_HOST ) {
		$host = WP_OPENSEARCH_HOST;
	}
	$port = defined( 'WP_OPENSEARCH_PORT' ) ? (int) WP_OPENSEARCH_PORT : 9200;
	if ( $port < 1 ) {
		$port = 9200;
	}

	return 'http://' . $host . ':' . $port;
}

/**
 * Index mapping for hotel-booking-rooms.
 *
 * @return array<string, mixed>
 */
function hotel_booking_opensearch_rooms_mapping() {
	return array(
		'settings' => array(
			'number_of_shards'   => 1,
			'number_of_replicas' => 0,
		),
		'mappings' => array(
			'properties' => array(
				'id'        => array(
					'type' => 'integer',
				),
				'title'     => array(
					'type'   => 'text',
					'fields' => array(
						'raw'     => array(
							'type' => 'keyword',
						),
						'suggest' => array(
							'type' => 'completion',
						),
					),
				),
				'excerpt'   => array(
					'type' => 'text',
				),
				'content'   => array(
					'type' => 'text',
				),
				'guests'    => array(
					'type' => 'integer',
				),
				'price'     => array(
					'type' => 'integer',
				),
				'beds'      => array(
					'type' => 'integer',
				),
				'size'      => array(
					'type' => 'integer',
				),
				'locale'    => array(
					'type' => 'keyword',
				),
				'permalink' => array(
					'type'  => 'keyword',
					'index' => false,
				),
				'modified'  => array(
					'type' => 'date',
				),
			),
		),
	);
}

/**
 * HTTP call to OpenSearch.
 *
 * @param string               $method  GET, PUT, POST, DELETE, HEAD.
 * @param string               $path    Path beginning with /.
 * @param mixed                $body    Array (JSON), string (raw), or null.
 * @param int                  $timeout Seconds.
 * @param array<string,string> $headers Extra headers.
 * @return array{status:int,body:array<string,mixed>,raw:string}|WP_Error
 */
function hotel_booking_opensearch_request( $method, $path, $body = null, $timeout = 5, $headers = array() ) {
	return hotel_booking_trace(
		'hotel_booking_opensearch_request',
		static function () use ( $method, $path, $body, $timeout, $headers ) {
			$base = hotel_booking_opensearch_base_url();
			if ( '' === $base ) {
				return new WP_Error(
					'hotel_booking_opensearch_unconfigured',
					__( 'OpenSearch is not configured.', 'hotel-booking-core' )
				);
			}

			$args = array(
				'method'      => strtoupper( (string) $method ),
				'timeout'     => max( 1, (int) $timeout ),
				'redirection' => 0,
				'headers'     => array_merge(
					array(
						'Accept' => 'application/json',
					),
					$headers
				),
			);

			if ( null !== $body ) {
				if ( is_string( $body ) ) {
					$args['body'] = $body;
				} else {
					$args['headers']['Content-Type'] = 'application/json';
					$encoded                         = wp_json_encode( $body );
					if ( false === $encoded ) {
						return new WP_Error(
							'hotel_booking_opensearch_json',
							__( 'Could not encode OpenSearch request.', 'hotel-booking-core' )
						);
					}
					$args['body'] = $encoded;
				}
			}

			$response = wp_remote_request( $base . $path, $args );
			if ( is_wp_error( $response ) ) {
				hotel_booking_trace_error( $response->get_error_message() );
				return $response;
			}

			$raw     = (string) wp_remote_retrieve_body( $response );
			$decoded = json_decode( $raw, true );

			return array(
				'status' => (int) wp_remote_retrieve_response_code( $response ),
				'body'   => is_array( $decoded ) ? $decoded : array(),
				'raw'    => $raw,
			);
		},
		array(
			'http.request.method' => strtoupper( (string) $method ),
			'url.path'            => (string) $path,
		)
	);
}

/**
 * Create the rooms index when it is missing.
 *
 * @return bool
 */
function hotel_booking_opensearch_ensure_index() {
	$index  = hotel_booking_opensearch_index_name();
	$exists = hotel_booking_opensearch_request( 'HEAD', '/' . $index, null, 3 );
	if ( is_wp_error( $exists ) ) {
		return false;
	}
	if ( 200 === $exists['status'] ) {
		return true;
	}

	$created = hotel_booking_opensearch_request( 'PUT', '/' . $index, hotel_booking_opensearch_rooms_mapping(), 10 );
	if ( is_wp_error( $created ) ) {
		return false;
	}

	return $created['status'] >= 200 && $created['status'] < 300;
}

/**
 * Locale keyword for a room post.
 *
 * @param int $post_id Room ID.
 * @return string
 */
function hotel_booking_opensearch_room_locale( $post_id ) {
	if ( function_exists( 'pll_get_post_language' ) ) {
		$lang = pll_get_post_language( (int) $post_id );
		if ( is_string( $lang ) && '' !== $lang ) {
			return $lang;
		}
	}

	return 'en';
}

/**
 * Document body for one room.
 *
 * @param WP_Post $post Room post.
 * @return array<string, mixed>
 */
function hotel_booking_opensearch_room_document( WP_Post $post ) {
	$meta = hotel_booking_get_room_meta( $post->ID );

	return array(
		'id'        => (int) $post->ID,
		'title'     => $post->post_title,
		'excerpt'   => wp_strip_all_tags( (string) $post->post_excerpt ),
		'content'   => wp_strip_all_tags( (string) $post->post_content ),
		'guests'    => $meta['guests'],
		'price'     => $meta['price'],
		'beds'      => $meta['beds'],
		'size'      => $meta['size'],
		'locale'    => hotel_booking_opensearch_room_locale( $post->ID ),
		'permalink' => (string) get_permalink( $post ),
		'modified'  => (string) get_post_modified_time( 'c', true, $post ),
	);
}

/**
 * Index or refresh a published room document.
 *
 * @param int $post_id Room ID.
 * @return void
 */
function hotel_booking_opensearch_index_room( $post_id ) {
	if ( ! hotel_booking_opensearch_is_configured() ) {
		return;
	}

	$post = get_post( (int) $post_id );
	if ( ! $post || 'hb_room' !== $post->post_type || 'publish' !== $post->post_status ) {
		return;
	}

	if ( ! hotel_booking_opensearch_ensure_index() ) {
		return;
	}

	$index = hotel_booking_opensearch_index_name();
	hotel_booking_opensearch_request(
		'PUT',
		'/' . $index . '/_doc/' . (int) $post->ID,
		hotel_booking_opensearch_room_document( $post ),
		8
	);
}

/**
 * Remove a room document.
 *
 * @param int $post_id Room ID.
 * @return void
 */
function hotel_booking_opensearch_delete_room( $post_id ) {
	if ( ! hotel_booking_opensearch_is_configured() ) {
		return;
	}

	$index = hotel_booking_opensearch_index_name();
	hotel_booking_opensearch_request( 'DELETE', '/' . $index . '/_doc/' . (int) $post_id, null, 5 );
}

/**
 * Index published rooms; drop drafts and trash.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post.
 * @param bool    $update  Whether this is an update.
 * @return void
 */
function hotel_booking_opensearch_on_save_room( $post_id, $post, $update ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	$routing = 'publish' === $post->post_status ? 'room.updated' : 'room.deleted';
	$queued  = hotel_booking_amqp_publish(
		$routing,
		array(
			'room_id' => (int) $post_id,
		)
	);
	if ( $queued ) {
		return;
	}

	if ( 'publish' === $post->post_status ) {
		hotel_booking_opensearch_index_room( $post_id );
		return;
	}

	hotel_booking_opensearch_delete_room( $post_id );
}
add_action( 'save_post_hb_room', 'hotel_booking_opensearch_on_save_room', 20, 3 );

/**
 * Delete the document before the post row is removed.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function hotel_booking_opensearch_on_delete_post( $post_id ) {
	$post = get_post( (int) $post_id );
	if ( ! $post || 'hb_room' !== $post->post_type ) {
		return;
	}

	$queued = hotel_booking_amqp_publish(
		'room.deleted',
		array(
			'room_id' => (int) $post_id,
		)
	);
	if ( $queued ) {
		return;
	}

	hotel_booking_opensearch_delete_room( $post_id );
}
add_action( 'before_delete_post', 'hotel_booking_opensearch_on_delete_post' );

/**
 * OpenSearch _search body for room filters.
 *
 * @param array<string, mixed> $args Search args.
 * @return array<string, mixed>
 */
function hotel_booking_opensearch_rooms_query_body( $args = array() ) {
	$args   = hotel_booking_normalize_room_search_args( $args );
	$filter = array();
	$must   = array();

	if ( $args['guests'] > 0 ) {
		$filter[] = array(
			'range' => array(
				'guests' => array(
					'gte' => $args['guests'],
				),
			),
		);
	}
	if ( $args['beds'] > 0 ) {
		$filter[] = array(
			'range' => array(
				'beds' => array(
					'gte' => $args['beds'],
				),
			),
		);
	}

	$price_range = array();
	if ( $args['price_min'] > 0 ) {
		$price_range['gte'] = $args['price_min'];
	}
	if ( $args['price_max'] > 0 ) {
		$price_range['lte'] = $args['price_max'];
	}
	if ( $price_range ) {
		$filter[] = array(
			'range' => array(
				'price' => $price_range,
			),
		);
	}

	$lang = hotel_booking_query_lang( $args['lang'] );
	if ( '' !== $lang ) {
		$filter[] = array(
			'term' => array(
				'locale' => $lang,
			),
		);
	}

	if ( '' !== $args['q'] ) {
		$must[] = array(
			'multi_match' => array(
				'query'  => $args['q'],
				'fields' => array( 'title', 'excerpt', 'content' ),
			),
		);
	}

	$bool = array();
	if ( $filter ) {
		$bool['filter'] = $filter;
	}
	if ( $must ) {
		$bool['must'] = $must;
	}

	$query = $bool ? array( 'bool' => $bool ) : array( 'match_all' => new stdClass() );

	return array(
		'size'  => 100,
		'query' => $query,
		'sort'  => array(
			array(
				'title.raw' => 'asc',
			),
		),
	);
}

/**
 * Completion suggester body.
 *
 * @param string $q Prefix.
 * @return array<string, mixed>
 */
function hotel_booking_opensearch_suggest_body( $q ) {
	return array(
		'size'    => 0,
		'suggest' => array(
			'room-suggest' => array(
				'prefix'     => sanitize_text_field( (string) $q ),
				'completion' => array(
					'field'           => 'title.suggest',
					'size'            => 8,
					'skip_duplicates' => true,
				),
			),
		),
	);
}

/**
 * Hydrate OpenSearch hit IDs into REST room payloads, preserving order.
 *
 * @param array<int, int|string> $ids Post IDs.
 * @return array<int, array<string, mixed>>
 */
function hotel_booking_opensearch_hydrate_rooms( $ids ) {
	$rooms = array();

	foreach ( $ids as $id ) {
		$post = get_post( (int) $id );
		if ( ! $post || 'hb_room' !== $post->post_type || 'publish' !== $post->post_status ) {
			continue;
		}
		$rooms[] = hotel_booking_prepare_room_for_rest( $post );
	}

	return $rooms;
}

/**
 * Search rooms in OpenSearch. WP_Error means caller should use WP_Query.
 *
 * @param array<string, mixed> $args Search args.
 * @return array<int, array<string, mixed>>|WP_Error
 */
function hotel_booking_opensearch_search_rooms( $args = array() ) {
	if ( ! hotel_booking_opensearch_is_configured() ) {
		return new WP_Error( 'hotel_booking_opensearch_unconfigured', 'OpenSearch is not configured.' );
	}

	$index    = hotel_booking_opensearch_index_name();
	$response = hotel_booking_opensearch_request(
		'POST',
		'/' . $index . '/_search',
		hotel_booking_opensearch_rooms_query_body( $args ),
		3
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	if ( $response['status'] < 200 || $response['status'] >= 300 ) {
		return new WP_Error( 'hotel_booking_opensearch_search', 'OpenSearch search failed.' );
	}

	$hits = array();
	if ( isset( $response['body']['hits']['hits'] ) && is_array( $response['body']['hits']['hits'] ) ) {
		$hits = $response['body']['hits']['hits'];
	}

	$ids = array();
	foreach ( $hits as $hit ) {
		if ( isset( $hit['_id'] ) ) {
			$ids[] = $hit['_id'];
		}
	}

	return hotel_booking_opensearch_hydrate_rooms( $ids );
}

/**
 * Completion suggestions. WP_Error means caller should use WP_Query.
 *
 * @param string $q    Prefix.
 * @param string $lang Optional locale to keep.
 * @return array<int, array{text:string,permalink:string}>|WP_Error
 */
function hotel_booking_opensearch_suggest_rooms( $q, $lang = '' ) {
	if ( ! hotel_booking_opensearch_is_configured() ) {
		return new WP_Error( 'hotel_booking_opensearch_unconfigured', 'OpenSearch is not configured.' );
	}

	$q = sanitize_text_field( (string) $q );
	if ( '' === $q ) {
		return array();
	}

	$index    = hotel_booking_opensearch_index_name();
	$response = hotel_booking_opensearch_request(
		'POST',
		'/' . $index . '/_search',
		hotel_booking_opensearch_suggest_body( $q ),
		3
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	if ( $response['status'] < 200 || $response['status'] >= 300 ) {
		return new WP_Error( 'hotel_booking_opensearch_suggest', 'OpenSearch suggest failed.' );
	}

	$options = array();
	if ( isset( $response['body']['suggest']['room-suggest'][0]['options'] ) && is_array( $response['body']['suggest']['room-suggest'][0]['options'] ) ) {
		$options = $response['body']['suggest']['room-suggest'][0]['options'];
	}

	$lang        = hotel_booking_query_lang( $lang );
	$suggestions = array();

	foreach ( $options as $option ) {
		if ( ! is_array( $option ) ) {
			continue;
		}

		$source = isset( $option['_source'] ) && is_array( $option['_source'] ) ? $option['_source'] : array();
		if ( '' !== $lang && isset( $source['locale'] ) && (string) $source['locale'] !== $lang ) {
			continue;
		}

		$text = isset( $option['text'] ) ? (string) $option['text'] : '';
		if ( '' === $text ) {
			continue;
		}

		$permalink = isset( $source['permalink'] ) ? (string) $source['permalink'] : '';
		if ( '' === $permalink && isset( $option['_id'] ) ) {
			$post = get_post( (int) $option['_id'] );
			if ( $post ) {
				$permalink = (string) get_permalink( $post );
			}
		}

		$suggestions[] = array(
			'text'      => $text,
			'permalink' => $permalink,
		);
	}

	return $suggestions;
}

/**
 * OpenSearch first, then WP_Query. Used by REST and the search block.
 *
 * @param array<string, mixed> $args Search args.
 * @return array<int, array<string, mixed>>
 */
function hotel_booking_search_rooms( $args = array() ) {
	$found = hotel_booking_opensearch_search_rooms( $args );
	if ( ! is_wp_error( $found ) ) {
		return $found;
	}

	return hotel_booking_query_rooms_for_search( $args );
}

/**
 * Suggest OpenSearch first, then WP_Query titles.
 *
 * @param string $q    Prefix.
 * @param string $lang Optional Polylang slug.
 * @return array<int, array{text:string,permalink:string}>
 */
function hotel_booking_suggest_rooms( $q, $lang = '' ) {
	$found = hotel_booking_opensearch_suggest_rooms( $q, $lang );
	if ( ! is_wp_error( $found ) ) {
		return $found;
	}

	return hotel_booking_query_room_suggestions( $q, $lang );
}

/**
 * Recreate the index and bulk-index published rooms.
 *
 * @return int|WP_Error Indexed document count.
 */
function hotel_booking_opensearch_reindex() {
	if ( ! hotel_booking_opensearch_is_configured() ) {
		return new WP_Error(
			'hotel_booking_opensearch_unconfigured',
			__( 'WP_OPENSEARCH_HOST is not set.', 'hotel-booking-core' )
		);
	}

	$index = hotel_booking_opensearch_index_name();
	hotel_booking_opensearch_request( 'DELETE', '/' . $index, null, 10 );

	$created = hotel_booking_opensearch_request( 'PUT', '/' . $index, hotel_booking_opensearch_rooms_mapping(), 10 );
	if ( is_wp_error( $created ) ) {
		return $created;
	}
	if ( $created['status'] < 200 || $created['status'] >= 300 ) {
		return new WP_Error(
			'hotel_booking_opensearch_mapping',
			__( 'Could not create the rooms index.', 'hotel-booking-core' )
		);
	}

	$query_args = array(
		'post_type'      => 'hb_room',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'ID',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	);
	if ( hotel_booking_polylang_is_active() ) {
		$query_args['lang'] = 'all';
	}

	$query = new WP_Query( $query_args );

	if ( ! $query->posts ) {
		return 0;
	}

	$lines = array();
	foreach ( $query->posts as $post ) {
		$lines[] = wp_json_encode(
			array(
				'index' => array(
					'_index' => $index,
					'_id'    => (string) $post->ID,
				),
			)
		);
		$lines[] = wp_json_encode( hotel_booking_opensearch_room_document( $post ) );
	}

	$bulk     = implode( "\n", $lines ) . "\n";
	$response = hotel_booking_opensearch_request(
		'POST',
		'/_bulk',
		$bulk,
		30,
		array(
			'Content-Type' => 'application/x-ndjson',
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	if ( $response['status'] < 200 || $response['status'] >= 300 ) {
		return new WP_Error(
			'hotel_booking_opensearch_bulk',
			__( 'Bulk index failed.', 'hotel-booking-core' )
		);
	}

	hotel_booking_opensearch_request( 'POST', '/' . $index . '/_refresh', null, 5 );

	return count( $query->posts );
}
