<?php
/**
 * WP-Cron schedules and desk email / reminder / digest jobs.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hours before a pending inquiry is considered stale.
 *
 * @return int
 */
function hotel_booking_stale_pending_hours() {
	return max( 1, (int) apply_filters( 'hotel_booking_stale_pending_hours', 48 ) );
}

/**
 * Desk inbox address from settings.
 *
 * @return string
 */
function hotel_booking_desk_mail_to() {
	$email = hotel_booking_get_setting( 'desk_email' );

	return is_email( (string) $email ) ? (string) $email : '';
}

/**
 * Schedule daily digest and stale-pending events.
 *
 * @return void
 */
function hotel_booking_schedule_cron_events() {
	if ( ! wp_next_scheduled( 'hotel_booking_stale_pending' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'hotel_booking_stale_pending' );
	}
	if ( ! wp_next_scheduled( 'hotel_booking_desk_digest' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'hotel_booking_desk_digest' );
	}
}
add_action( 'init', 'hotel_booking_schedule_cron_events' );

/**
 * Clear cron events on deactivation.
 *
 * @return void
 */
function hotel_booking_clear_cron_events() {
	wp_clear_scheduled_hook( 'hotel_booking_stale_pending' );
	wp_clear_scheduled_hook( 'hotel_booking_desk_digest' );
	wp_clear_scheduled_hook( 'hotel_booking_workflow_tick' );
}

/**
 * After an inquiry row exists: queue email, or send in this request.
 *
 * @param int $inquiry_id Insert id.
 * @return void
 */
function hotel_booking_on_inquiry_created( $inquiry_id ) {
	$inquiry_id = absint( $inquiry_id );
	if ( $inquiry_id < 1 ) {
		return;
	}

	$published = hotel_booking_amqp_publish(
		'inquiry.created',
		array(
			'inquiry_id' => $inquiry_id,
		)
	);
	if ( ! $published ) {
		hotel_booking_send_desk_inquiry_email( $inquiry_id );
	}
}
add_action( 'hotel_booking_inquiry_created', 'hotel_booking_on_inquiry_created' );

/**
 * Resume due remind timers (durable workflow tick).
 *
 * @return int Number of runs processed.
 */
function hotel_booking_run_stale_pending() {
	return hotel_booking_workflow_tick();
}
add_action( 'hotel_booking_stale_pending', 'hotel_booking_cron_stale_pending' );

/**
 * WP-Cron wrapper: stale pending reminders.
 *
 * @return void
 */
function hotel_booking_cron_stale_pending() {
	hotel_booking_run_stale_pending();
}

/**
 * Daily: enqueue a pending-count digest for the desk.
 *
 * @return int Pending count mailed or queued.
 */
function hotel_booking_run_desk_digest() {
	$count     = hotel_booking_count_pending_inquiries();
	$published = hotel_booking_amqp_publish(
		'desk.digest',
		array(
			'pending_count' => $count,
		)
	);
	if ( ! $published ) {
		hotel_booking_send_desk_digest_email( $count );
	}

	return $count;
}
add_action( 'hotel_booking_desk_digest', 'hotel_booking_cron_desk_digest' );

/**
 * WP-Cron wrapper: desk digest.
 *
 * @return void
 */
function hotel_booking_cron_desk_digest() {
	hotel_booking_run_desk_digest();
}

/**
 * Pending inquiries older than the stale window with no reminder yet.
 *
 * @return int[]
 */
function hotel_booking_get_stale_pending_inquiry_ids() {
	global $wpdb;

	$hours = hotel_booking_stale_pending_hours();
	$now   = current_time( 'mysql' );
	$table = hotel_booking_inquiries_table_name();

	$ids = $wpdb->get_col(
		$wpdb->prepare(
			'SELECT id FROM %i WHERE status = %s AND reminded_at IS NULL AND created_at <= DATE_SUB(%s, INTERVAL %d HOUR) ORDER BY id ASC',
			$table,
			'pending',
			$now,
			$hours
		)
	);

	if ( ! is_array( $ids ) ) {
		return array();
	}

	return array_map( 'absint', $ids );
}

/**
 * Count pending inquiries.
 *
 * @return int
 */
function hotel_booking_count_pending_inquiries() {
	global $wpdb;

	$count = $wpdb->get_var(
		$wpdb->prepare(
			'SELECT COUNT(*) FROM %i WHERE status = %s',
			hotel_booking_inquiries_table_name(),
			'pending'
		)
	);

	return absint( $count );
}

/**
 * Email the desk about a new inquiry. Idempotent via desk_mailed_at.
 *
 * @param int $inquiry_id Row ID.
 * @return bool
 */
function hotel_booking_send_desk_inquiry_email( $inquiry_id ) {
	$inquiry = hotel_booking_get_inquiry( $inquiry_id );
	if ( ! $inquiry ) {
		return false;
	}
	if ( ! empty( $inquiry->desk_mailed_at ) && '0000-00-00 00:00:00' !== $inquiry->desk_mailed_at ) {
		return true;
	}

	$to = hotel_booking_desk_mail_to();
	if ( '' === $to ) {
		return false;
	}

	$subject = sprintf(
		/* translators: %s: guest name */
		__( 'New inquiry from %s', 'hotel-booking-core' ),
		$inquiry->guest_name
	);
	$body = sprintf(
		"%s <%s>\n%s → %s\n%s\n\n%s",
		$inquiry->guest_name,
		$inquiry->guest_email,
		$inquiry->check_in,
		$inquiry->check_out,
		sprintf(
			/* translators: %s: guest count */
			__( '%s guests', 'hotel-booking-core' ),
			(string) $inquiry->guests
		),
		(string) $inquiry->message
	);

	$sent = wp_mail( $to, $subject, $body );
	if ( $sent ) {
		hotel_booking_mark_inquiry_timestamp( $inquiry_id, 'desk_mailed_at' );
		$run = hotel_booking_get_workflow_run( $inquiry_id );
		if ( $run ) {
			hotel_booking_workflow_append_event( (int) $run->id, 'activity_completed', 'inquiry.created' );
		}
	}

	return (bool) $sent;
}

/**
 * Email the desk about a stale pending inquiry. Idempotent via reminded_at.
 *
 * @param int $inquiry_id Row ID.
 * @return bool
 */
function hotel_booking_send_stale_reminder_email( $inquiry_id ) {
	$inquiry = hotel_booking_get_inquiry( $inquiry_id );
	if ( ! $inquiry || 'pending' !== $inquiry->status ) {
		return false;
	}
	if ( ! empty( $inquiry->reminded_at ) && '0000-00-00 00:00:00' !== $inquiry->reminded_at ) {
		return true;
	}

	$to = hotel_booking_desk_mail_to();
	if ( '' === $to ) {
		return false;
	}

	$subject = sprintf(
		/* translators: %s: guest name */
		__( 'Pending inquiry still waiting: %s', 'hotel-booking-core' ),
		$inquiry->guest_name
	);
	$body = sprintf(
		"%s <%s>\n%s → %s\n%s",
		$inquiry->guest_name,
		$inquiry->guest_email,
		$inquiry->check_in,
		$inquiry->check_out,
		(string) $inquiry->message
	);

	$sent = wp_mail( $to, $subject, $body );
	if ( $sent ) {
		hotel_booking_mark_inquiry_timestamp( $inquiry_id, 'reminded_at' );
	}

	return (bool) $sent;
}

/**
 * Email a pending-count digest.
 *
 * @param int $pending_count Pending rows.
 * @return bool
 */
function hotel_booking_send_desk_digest_email( $pending_count ) {
	$to = hotel_booking_desk_mail_to();
	if ( '' === $to ) {
		return false;
	}

	$pending_count = absint( $pending_count );
	$subject       = sprintf(
		/* translators: %d: pending inquiry count */
		__( 'Desk digest: %d pending inquiries', 'hotel-booking-core' ),
		$pending_count
	);
	$body = sprintf(
		/* translators: %d: pending inquiry count */
		__( 'The desk book has %d pending inquiries.', 'hotel-booking-core' ),
		$pending_count
	);

	return (bool) wp_mail( $to, $subject, $body );
}

/**
 * Set desk_mailed_at or reminded_at to now.
 *
 * @param int    $inquiry_id Row ID.
 * @param string $column     desk_mailed_at|reminded_at.
 * @return void
 */
function hotel_booking_mark_inquiry_timestamp( $inquiry_id, $column ) {
	global $wpdb;

	if ( ! in_array( $column, array( 'desk_mailed_at', 'reminded_at' ), true ) ) {
		return;
	}

	$wpdb->update(
		hotel_booking_inquiries_table_name(),
		array(
			$column      => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		),
		array(
			'id' => absint( $inquiry_id ),
		),
		array( '%s', '%s' ),
		array( '%d' )
	);
}

/**
 * Short mailed/reminded note for desk tables.
 *
 * @param object $row Inquiry row.
 * @return string
 */
function hotel_booking_inquiry_job_notes( $row ) {
	$notes = array();
	if ( ! empty( $row->desk_mailed_at ) && '0000-00-00 00:00:00' !== $row->desk_mailed_at ) {
		$notes[] = __( 'Mailed', 'hotel-booking-core' );
	}
	if ( ! empty( $row->reminded_at ) && '0000-00-00 00:00:00' !== $row->reminded_at ) {
		$notes[] = __( 'Reminded', 'hotel-booking-core' );
	}

	return implode( ' · ', $notes );
}

/**
 * Job notes plus recent workflow events.
 *
 * @param object $row Inquiry row.
 * @return string
 */
function hotel_booking_inquiry_desk_notes( $row ) {
	$parts = array_filter(
		array(
			hotel_booking_inquiry_job_notes( $row ),
			function_exists( 'hotel_booking_inquiry_workflow_notes' ) ? hotel_booking_inquiry_workflow_notes( $row ) : '',
		)
	);

	return implode( ' · ', $parts );
}
