<?php
/**
 * Inquiry CRUD against {$wpdb->prefix}hb_inquiries.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allowed inquiry statuses.
 *
 * @return string[]
 */
function hotel_booking_inquiry_statuses() {
	return array( 'pending', 'contacted', 'closed' );
}

/**
 * Translated label for an inquiry status slug.
 *
 * Stored values stay English keys. Unknown slugs are returned unchanged.
 *
 * @param string $status pending|contacted|closed.
 * @return string
 */
function hotel_booking_inquiry_status_label( $status ) {
	$labels = array(
		'pending'   => __( 'Pending', 'hotel-booking-core' ),
		'contacted' => __( 'Contacted', 'hotel-booking-core' ),
		'closed'    => __( 'Closed', 'hotel-booking-core' ),
	);

	return isset( $labels[ $status ] ) ? $labels[ $status ] : (string) $status;
}

/**
 * Sanitize and validate raw inquiry input.
 *
 * @param array $input Raw data.
 * @return array|WP_Error
 */
function hotel_booking_sanitize_inquiry_data( $input ) {
	$name    = isset( $input['guest_name'] ) ? sanitize_text_field( wp_unslash( $input['guest_name'] ) ) : '';
	$email   = isset( $input['guest_email'] ) ? sanitize_email( wp_unslash( $input['guest_email'] ) ) : '';
	$in      = isset( $input['check_in'] ) ? sanitize_text_field( wp_unslash( $input['check_in'] ) ) : '';
	$out     = isset( $input['check_out'] ) ? sanitize_text_field( wp_unslash( $input['check_out'] ) ) : '';
	$guests  = isset( $input['guests'] ) ? absint( $input['guests'] ) : 0;
	$room_id = isset( $input['room_id'] ) ? absint( $input['room_id'] ) : 0;
	$message = isset( $input['message'] ) ? sanitize_textarea_field( wp_unslash( $input['message'] ) ) : '';
	$status  = isset( $input['status'] ) ? sanitize_key( wp_unslash( $input['status'] ) ) : 'pending';

	if ( '' === $name ) {
		return new WP_Error( 'hotel_booking_invalid_name', __( 'A name is required.', 'hotel-booking-core' ) );
	}

	if ( ! is_email( $email ) ) {
		return new WP_Error( 'hotel_booking_invalid_email', __( 'A valid email is required.', 'hotel-booking-core' ) );
	}

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $in ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $out ) ) {
		return new WP_Error( 'hotel_booking_invalid_dates', __( 'Check-in and check-out must be YYYY-MM-DD.', 'hotel-booking-core' ) );
	}

	if ( $out <= $in ) {
		return new WP_Error( 'hotel_booking_invalid_range', __( 'Check-out must be after check-in.', 'hotel-booking-core' ) );
	}

	if ( $guests < 1 || $guests > 8 ) {
		return new WP_Error( 'hotel_booking_invalid_guests', __( 'Guests must be between 1 and 8.', 'hotel-booking-core' ) );
	}

	if ( $room_id && 'hb_room' !== get_post_type( $room_id ) ) {
		return new WP_Error( 'hotel_booking_invalid_room', __( 'That room does not exist.', 'hotel-booking-core' ) );
	}

	if ( ! in_array( $status, hotel_booking_inquiry_statuses(), true ) ) {
		$status = 'pending';
	}

	return array(
		'guest_name'  => $name,
		'guest_email' => $email,
		'check_in'    => $in,
		'check_out'   => $out,
		'guests'      => $guests,
		'room_id'     => $room_id,
		'message'     => $message,
		'status'      => $status,
	);
}

/**
 * Insert an inquiry row.
 *
 * @param array $input Raw or already sanitized data (use $already_sanitized).
 * @param bool  $already_sanitized Skip sanitize when true.
 * @return int|WP_Error Insert ID.
 */
function hotel_booking_insert_inquiry( $input, $already_sanitized = false ) {
	global $wpdb;

	$data = $already_sanitized ? $input : hotel_booking_sanitize_inquiry_data( $input );
	if ( is_wp_error( $data ) ) {
		return $data;
	}

	$now = current_time( 'mysql' );
	$row = array(
		'guest_name'  => $data['guest_name'],
		'guest_email' => $data['guest_email'],
		'check_in'    => $data['check_in'],
		'check_out'   => $data['check_out'],
		'guests'      => $data['guests'],
		'room_id'     => $data['room_id'],
		'message'     => $data['message'],
		'status'      => $data['status'],
		'created_at'  => $now,
		'updated_at'  => $now,
	);

	$wpdb->query( 'START TRANSACTION' );

	$inserted = $wpdb->insert(
		hotel_booking_inquiries_table_name(),
		$row,
		array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s' )
	);

	if ( ! $inserted ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'hotel_booking_insert_failed', __( 'Could not save the inquiry.', 'hotel-booking-core' ) );
	}

	$id      = (int) $wpdb->insert_id;
	$started = hotel_booking_workflow_start_inquiry( $id, $data['status'] );
	if ( is_wp_error( $started ) ) {
		$wpdb->query( 'ROLLBACK' );
		return $started;
	}

	$wpdb->query( 'COMMIT' );
	do_action( 'hotel_booking_inquiry_created', $id );

	return $id;
}

/**
 * Fetch one inquiry.
 *
 * @param int $id Row ID.
 * @return object|null
 */
function hotel_booking_get_inquiry( $id ) {
	global $wpdb;

	$id = absint( $id );
	if ( ! $id ) {
		return null;
	}

	return $wpdb->get_row(
		$wpdb->prepare(
			'SELECT * FROM %i WHERE id = %d',
			hotel_booking_inquiries_table_name(),
			$id
		)
	);
}

/**
 * Fetch inquiries.
 *
 * @param array $args {
 *     @type string $status pending|contacted|closed|empty for all.
 *     @type string $orderby id|created_at|check_in|guest_name.
 *     @type string $order   ASC|DESC.
 *     @type int    $limit
 * }
 * @return object[]
 */
function hotel_booking_get_inquiries( $args = array() ) {
	global $wpdb;

	$args = wp_parse_args(
		$args,
		array(
			'status'  => '',
			'orderby' => 'created_at',
			'order'   => 'DESC',
			'limit'   => 50,
		)
	);

	$table   = hotel_booking_inquiries_table_name();
	$orderby = in_array( $args['orderby'], array( 'id', 'created_at', 'check_in', 'guest_name' ), true ) ? $args['orderby'] : 'created_at';
	$order   = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';
	$limit   = max( 1, absint( $args['limit'] ) );
	$filter  = ( $args['status'] && in_array( $args['status'], hotel_booking_inquiry_statuses(), true ) );

	if ( $filter && 'ASC' === $order ) {
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE status = %s ORDER BY %i ASC LIMIT %d',
				$table,
				$args['status'],
				$orderby,
				$limit
			)
		);
	}

	if ( $filter ) {
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE status = %s ORDER BY %i DESC LIMIT %d',
				$table,
				$args['status'],
				$orderby,
				$limit
			)
		);
	}

	if ( 'ASC' === $order ) {
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i ORDER BY %i ASC LIMIT %d',
				$table,
				$orderby,
				$limit
			)
		);
	}

	return $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * FROM %i ORDER BY %i DESC LIMIT %d',
			$table,
			$orderby,
			$limit
		)
	);
}

/**
 * Update an inquiry.
 *
 * @param int   $id    Row ID.
 * @param array $input Fields to change.
 * @return true|WP_Error
 */
function hotel_booking_update_inquiry( $id, $input ) {
	global $wpdb;

	$existing = hotel_booking_get_inquiry( $id );
	if ( ! $existing ) {
		return new WP_Error( 'hotel_booking_inquiry_missing', __( 'Inquiry not found.', 'hotel-booking-core' ) );
	}

	if ( isset( $input['transition'] ) && is_string( $input['transition'] ) && '' !== $input['transition'] ) {
		return hotel_booking_apply_inquiry_transition( $id, $input['transition'] );
	}

	if ( isset( $input['status'] ) && (string) $input['status'] !== (string) $existing->status ) {
		$transition = hotel_booking_workflow_transition_for_statuses( (string) $existing->status, (string) $input['status'] );
		if ( null === $transition ) {
			return new WP_Error( 'hotel_booking_workflow_blocked', __( 'That status change is not allowed.', 'hotel-booking-core' ) );
		}
		if ( '' !== $transition ) {
			return hotel_booking_apply_inquiry_transition( $id, $transition );
		}
	}

	$merged = array(
		'guest_name'  => isset( $input['guest_name'] ) ? $input['guest_name'] : $existing->guest_name,
		'guest_email' => isset( $input['guest_email'] ) ? $input['guest_email'] : $existing->guest_email,
		'check_in'    => isset( $input['check_in'] ) ? $input['check_in'] : $existing->check_in,
		'check_out'   => isset( $input['check_out'] ) ? $input['check_out'] : $existing->check_out,
		'guests'      => isset( $input['guests'] ) ? $input['guests'] : $existing->guests,
		'room_id'     => array_key_exists( 'room_id', $input ) ? $input['room_id'] : $existing->room_id,
		'message'     => isset( $input['message'] ) ? $input['message'] : $existing->message,
		'status'      => isset( $input['status'] ) ? $input['status'] : $existing->status,
	);

	$data = hotel_booking_sanitize_inquiry_data( $merged );
	if ( is_wp_error( $data ) ) {
		return $data;
	}

	$data['updated_at'] = current_time( 'mysql' );

	$updated = $wpdb->update(
		hotel_booking_inquiries_table_name(),
		$data,
		array( 'id' => absint( $id ) ),
		array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' ),
		array( '%d' )
	);

	if ( false === $updated ) {
		return new WP_Error( 'hotel_booking_update_failed', __( 'Could not update the inquiry.', 'hotel-booking-core' ) );
	}

	return true;
}

/**
 * Delete an inquiry.
 *
 * @param int $id Row ID.
 * @return bool
 */
function hotel_booking_delete_inquiry( $id ) {
	global $wpdb;

	$id = absint( $id );
	if ( ! $id ) {
		return false;
	}

	$run = hotel_booking_get_workflow_run( $id );
	if ( $run ) {
		$wpdb->delete(
			hotel_booking_workflow_events_table_name(),
			array(
				'run_id' => (int) $run->id,
			),
			array( '%d' )
		);
		$wpdb->delete(
			hotel_booking_workflow_runs_table_name(),
			array(
				'id' => (int) $run->id,
			),
			array( '%d' )
		);
	}

	$deleted = $wpdb->delete(
		hotel_booking_inquiries_table_name(),
		array( 'id' => $id ),
		array( '%d' )
	);

	return (bool) $deleted;
}
