<?php
/**
 * Symfony Workflow state machine for inquiries, with MariaDB run + event durability.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load Symfony Workflow when WordPress did not already autoload project vendor/.
 *
 * @return bool
 */
function hotel_booking_workflow_load_library() {
	if ( class_exists( '\Symfony\Component\Workflow\StateMachine' ) ) {
		return true;
	}

	$candidates = array(
		ABSPATH . 'vendor/autoload.php',
		dirname( HOTEL_BOOKING_CORE_PATH, 3 ) . '/vendor/autoload.php',
	);

	foreach ( $candidates as $autoload ) {
		if ( is_readable( $autoload ) ) {
			require_once $autoload;
			break;
		}
	}

	return class_exists( '\Symfony\Component\Workflow\StateMachine' );
}

/**
 * Whether the Workflow component is available.
 *
 * @return bool
 */
function hotel_booking_workflow_enabled() {
	$enabled = hotel_booking_workflow_load_library();

	return (bool) apply_filters( 'hotel_booking_workflow_enabled', $enabled );
}

/**
 * Inquiry desk state machine.
 *
 * @return \Symfony\Component\Workflow\StateMachine|null
 */
function hotel_booking_inquiry_workflow() {
	static $workflow = null;

	if ( ! hotel_booking_workflow_enabled() ) {
		return null;
	}

	if ( ! class_exists( 'Hotel_Booking_Inquiry_Marking_Store', false ) ) {
		require_once __DIR__ . '/class-hotel-booking-inquiry-marking-store.php';
	}

	if ( $workflow instanceof \Symfony\Component\Workflow\StateMachine ) {
		return $workflow;
	}

	$definition = new \Symfony\Component\Workflow\Definition(
		array( 'pending', 'contacted', 'closed' ),
		array(
			new \Symfony\Component\Workflow\Transition( 'contact', 'pending', 'contacted' ),
			new \Symfony\Component\Workflow\Transition( 'close', 'pending', 'closed' ),
			new \Symfony\Component\Workflow\Transition( 'close', 'contacted', 'closed' ),
			new \Symfony\Component\Workflow\Transition( 'reopen', 'closed', 'pending' ),
		),
		'pending'
	);

	$workflow = new \Symfony\Component\Workflow\StateMachine(
		$definition,
		new Hotel_Booking_Inquiry_Marking_Store(),
		null,
		'inquiry'
	);

	return $workflow;
}

/**
 * Translated label for a transition name.
 *
 * @param string $name contact|close|reopen.
 * @return string
 */
function hotel_booking_workflow_transition_label( $name ) {
	$labels = array(
		'contact' => __( 'Contact', 'hotel-booking-core' ),
		'close'   => __( 'Close', 'hotel-booking-core' ),
		'reopen'  => __( 'Reopen', 'hotel-booking-core' ),
	);

	return isset( $labels[ $name ] ) ? $labels[ $name ] : (string) $name;
}

/**
 * Map a from→to status change to a transition name.
 *
 * @param string $from Current status.
 * @param string $to   Requested status.
 * @return string|null
 */
function hotel_booking_workflow_transition_for_statuses( $from, $to ) {
	$from = (string) $from;
	$to   = (string) $to;
	if ( $from === $to ) {
		return '';
	}

	$map = array(
		'pending|contacted' => 'contact',
		'pending|closed'    => 'close',
		'contacted|closed'  => 'close',
		'closed|pending'    => 'reopen',
	);
	$key = $from . '|' . $to;

	return isset( $map[ $key ] ) ? $map[ $key ] : null;
}

/**
 * Enabled transitions for an inquiry row.
 *
 * @param object|null $inquiry Inquiry row.
 * @return array<int, array{name:string,label:string}>
 */
function hotel_booking_inquiry_enabled_transitions( $inquiry ) {
	$workflow = hotel_booking_inquiry_workflow();
	if ( ! $workflow || ! is_object( $inquiry ) ) {
		return array();
	}

	$items = array();
	foreach ( $workflow->getEnabledTransitions( $inquiry ) as $transition ) {
		$items[] = array(
			'name'  => $transition->getName(),
			'label' => hotel_booking_workflow_transition_label( $transition->getName() ),
		);
	}

	return $items;
}

/**
 * Fetch the workflow run for an inquiry.
 *
 * @param int $inquiry_id Inquiry id.
 * @return object|null
 */
function hotel_booking_get_workflow_run( $inquiry_id ) {
	global $wpdb;

	$inquiry_id = absint( $inquiry_id );
	if ( $inquiry_id < 1 ) {
		return null;
	}

	return $wpdb->get_row(
		$wpdb->prepare(
			'SELECT * FROM %i WHERE workflow = %s AND subject_id = %d',
			hotel_booking_workflow_runs_table_name(),
			'inquiry',
			$inquiry_id
		)
	);
}

/**
 * Append an event to a run.
 *
 * @param int                  $run_id     Run id.
 * @param string               $type       Event type.
 * @param string               $name       Transition or activity name.
 * @param string|null          $from_place From place.
 * @param string|null          $to_place   To place.
 * @param array<string, mixed> $payload    Extra data.
 * @return void
 */
function hotel_booking_workflow_append_event( $run_id, $type, $name = '', $from_place = null, $to_place = null, $payload = array() ) {
	global $wpdb;

	$run_id = absint( $run_id );
	if ( $run_id < 1 ) {
		return;
	}

	$encoded = wp_json_encode( $payload );
	$wpdb->insert(
		hotel_booking_workflow_events_table_name(),
		array(
			'run_id'     => $run_id,
			'type'       => sanitize_key( $type ),
			'name'       => sanitize_key( $name ),
			'from_place' => null === $from_place ? null : sanitize_key( $from_place ),
			'to_place'   => null === $to_place ? null : sanitize_key( $to_place ),
			'payload'    => false === $encoded ? '{}' : $encoded,
			'created_at' => current_time( 'mysql' ),
		),
		array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
	);
}

/**
 * Recent events for an inquiry, newest first.
 *
 * @param int $inquiry_id Inquiry id.
 * @param int $limit      Max rows.
 * @return object[]
 */
function hotel_booking_get_workflow_events( $inquiry_id, $limit = 5 ) {
	global $wpdb;

	$run = hotel_booking_get_workflow_run( $inquiry_id );
	if ( ! $run ) {
		return array();
	}

	$limit = max( 1, absint( $limit ) );

	$events = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * FROM %i WHERE run_id = %d ORDER BY id DESC LIMIT %d',
			hotel_booking_workflow_events_table_name(),
			(int) $run->id,
			$limit
		)
	);

	return is_array( $events ) ? $events : array();
}

/**
 * Short history line for desk tables.
 *
 * @param object|null $row Inquiry row.
 * @return string
 */
function hotel_booking_inquiry_workflow_notes( $row ) {
	if ( ! is_object( $row ) || empty( $row->id ) ) {
		return '';
	}

	$events = hotel_booking_get_workflow_events( (int) $row->id, 3 );
	if ( ! $events ) {
		return '';
	}

	$labels = array();
	foreach ( $events as $event ) {
		if ( 'transition' === $event->type && '' !== $event->name ) {
			$labels[] = hotel_booking_workflow_transition_label( $event->name );
		} elseif ( 'timer_fired' === $event->type ) {
			$labels[] = __( 'Timer fired', 'hotel-booking-core' );
		} elseif ( 'timer_skipped' === $event->type ) {
			$labels[] = __( 'Timer skipped', 'hotel-booking-core' );
		}
	}

	return implode( ' · ', array_slice( $labels, 0, 3 ) );
}

/**
 * Wait-until datetime for the stale-pending remind timer.
 *
 * @return string MySQL datetime.
 */
function hotel_booking_workflow_remind_wait_until() {
	global $wpdb;

	$hours = function_exists( 'hotel_booking_stale_pending_hours' ) ? hotel_booking_stale_pending_hours() : 48;
	$wait  = $wpdb->get_var(
		$wpdb->prepare(
			'SELECT DATE_ADD(%s, INTERVAL %d HOUR)',
			current_time( 'mysql' ),
			$hours
		)
	);

	return is_string( $wait ) && '' !== $wait ? $wait : current_time( 'mysql' );
}

/**
 * Create the durable run after an inquiry row exists (same transaction).
 *
 * @param int    $inquiry_id Inquiry id.
 * @param string $status     Initial marking.
 * @return true|WP_Error
 */
function hotel_booking_workflow_start_inquiry( $inquiry_id, $status ) {
	global $wpdb;

	$inquiry_id = absint( $inquiry_id );
	if ( $inquiry_id < 1 ) {
		return new WP_Error( 'hotel_booking_workflow_subject', __( 'Inquiry id is missing.', 'hotel-booking-core' ) );
	}

	if ( ! hotel_booking_workflow_enabled() ) {
		return true;
	}

	if ( hotel_booking_get_workflow_run( $inquiry_id ) ) {
		return true;
	}

	$status     = in_array( $status, hotel_booking_inquiry_statuses(), true ) ? $status : 'pending';
	$now        = current_time( 'mysql' );
	$waiting    = 'pending' === $status;
	$wait_until = $waiting ? hotel_booking_workflow_remind_wait_until() : null;
	$run_status = 'closed' === $status ? 'completed' : ( $waiting ? 'waiting' : 'open' );

	$inserted = $wpdb->insert(
		hotel_booking_workflow_runs_table_name(),
		array(
			'workflow'   => 'inquiry',
			'subject_id' => $inquiry_id,
			'marking'    => $status,
			'run_status' => $run_status,
			'wait_until' => $wait_until,
			'wait_name'  => $waiting ? 'remind' : null,
			'created_at' => $now,
			'updated_at' => $now,
		),
		array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
	);

	if ( ! $inserted ) {
		return new WP_Error( 'hotel_booking_workflow_run', __( 'Could not start the inquiry workflow.', 'hotel-booking-core' ) );
	}

	$run_id = (int) $wpdb->insert_id;
	hotel_booking_workflow_append_event( $run_id, 'transition', 'start', null, $status );
	if ( $waiting ) {
		hotel_booking_workflow_append_event( $run_id, 'timer_scheduled', 'remind', $status, $status );
	}
	hotel_booking_workflow_append_event( $run_id, 'activity_scheduled', 'inquiry.created', $status, $status );

	return true;
}

/**
 * Start runs for inquiries that predate the workflow tables.
 *
 * @return void
 */
function hotel_booking_workflow_backfill_runs() {
	global $wpdb;

	if ( ! hotel_booking_workflow_enabled() ) {
		return;
	}

	$ids = $wpdb->get_col(
		$wpdb->prepare(
			'SELECT i.id FROM %i i LEFT JOIN %i r ON r.workflow = %s AND r.subject_id = i.id WHERE r.id IS NULL',
			hotel_booking_inquiries_table_name(),
			hotel_booking_workflow_runs_table_name(),
			'inquiry'
		)
	);

	if ( ! is_array( $ids ) ) {
		return;
	}

	foreach ( $ids as $id ) {
		$inquiry = hotel_booking_get_inquiry( (int) $id );
		if ( $inquiry ) {
			hotel_booking_workflow_start_inquiry( (int) $id, (string) $inquiry->status );
		}
	}
}

/**
 * Apply a named transition. Invalid jumps return WP_Error.
 *
 * @param int    $inquiry_id Inquiry id.
 * @param string $name       contact|close|reopen.
 * @return true|WP_Error
 */
function hotel_booking_apply_inquiry_transition( $inquiry_id, $name ) {
	global $wpdb;

	$inquiry_id = absint( $inquiry_id );
	$name       = sanitize_key( $name );
	$inquiry    = hotel_booking_get_inquiry( $inquiry_id );
	if ( ! $inquiry ) {
		return new WP_Error( 'hotel_booking_inquiry_missing', __( 'Inquiry not found.', 'hotel-booking-core' ) );
	}

	$workflow = hotel_booking_inquiry_workflow();
	if ( ! $workflow ) {
		return new WP_Error( 'hotel_booking_workflow_missing', __( 'The workflow library is not available.', 'hotel-booking-core' ) );
	}

	if ( ! $workflow->can( $inquiry, $name ) ) {
		return new WP_Error( 'hotel_booking_workflow_blocked', __( 'That status change is not allowed.', 'hotel-booking-core' ) );
	}

	$from = (string) $inquiry->status;
	$run  = hotel_booking_get_workflow_run( $inquiry_id );
	if ( ! $run ) {
		$started = hotel_booking_workflow_start_inquiry( $inquiry_id, $from );
		if ( is_wp_error( $started ) ) {
			return $started;
		}
		$run = hotel_booking_get_workflow_run( $inquiry_id );
	}

	$wpdb->query( 'START TRANSACTION' );

	try {
		$workflow->apply( $inquiry, $name );
	} catch ( Exception $e ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'hotel_booking_workflow_blocked', __( 'That status change is not allowed.', 'hotel-booking-core' ) );
	}

	$to      = (string) $inquiry->status;
	$now     = current_time( 'mysql' );
	$updated = $wpdb->update(
		hotel_booking_inquiries_table_name(),
		array(
			'status'     => $to,
			'updated_at' => $now,
		),
		array(
			'id' => $inquiry_id,
		),
		array( '%s', '%s' ),
		array( '%d' )
	);
	if ( false === $updated ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'hotel_booking_update_failed', __( 'Could not update the inquiry.', 'hotel-booking-core' ) );
	}

	$run_status = 'closed' === $to ? 'completed' : 'open';
	$wait_until = null;
	$wait_name  = null;
	if ( 'pending' === $to && 'reopen' === $name ) {
		$run_status = 'waiting';
		$wait_until = hotel_booking_workflow_remind_wait_until();
		$wait_name  = 'remind';
	}

	if ( $run ) {
		$wpdb->update(
			hotel_booking_workflow_runs_table_name(),
			array(
				'marking'    => $to,
				'run_status' => $run_status,
				'wait_until' => $wait_until,
				'wait_name'  => $wait_name,
				'updated_at' => $now,
			),
			array(
				'id' => (int) $run->id,
			),
			array( '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
		hotel_booking_workflow_append_event( (int) $run->id, 'transition', $name, $from, $to );
		if ( 'contact' === $name && 'remind' === (string) $run->wait_name ) {
			hotel_booking_workflow_append_event( (int) $run->id, 'timer_skipped', 'remind', $from, $to );
		}
		if ( 'reopen' === $name ) {
			hotel_booking_workflow_append_event( (int) $run->id, 'timer_scheduled', 'remind', $from, $to );
		}
	}

	$wpdb->query( 'COMMIT' );

	return true;
}

/**
 * Resume due timers (crash-safe). Returns how many runs were processed.
 *
 * @return int
 */
function hotel_booking_workflow_tick() {
	global $wpdb;

	if ( ! hotel_booking_workflow_enabled() ) {
		return 0;
	}

	$now  = current_time( 'mysql' );
	$runs = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * FROM %i WHERE run_status = %s AND wait_until IS NOT NULL AND wait_until <= %s ORDER BY id ASC',
			hotel_booking_workflow_runs_table_name(),
			'waiting',
			$now
		)
	);

	if ( ! is_array( $runs ) ) {
		return 0;
	}

	$count = 0;
	foreach ( $runs as $run ) {
		++$count;
		hotel_booking_workflow_fire_due_run( $run );
	}

	return $count;
}

/**
 * Fire or skip one due wait.
 *
 * @param object $run Run row.
 * @return void
 */
function hotel_booking_workflow_fire_due_run( $run ) {
	global $wpdb;

	$inquiry = hotel_booking_get_inquiry( (int) $run->subject_id );
	$now     = current_time( 'mysql' );

	$clear = array(
		'run_status' => 'open',
		'wait_until' => null,
		'wait_name'  => null,
		'updated_at' => $now,
	);

	if ( ! $inquiry || 'remind' !== (string) $run->wait_name ) {
		$wpdb->update(
			hotel_booking_workflow_runs_table_name(),
			$clear,
			array(
				'id' => (int) $run->id,
			),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
		hotel_booking_workflow_append_event( (int) $run->id, 'timer_skipped', (string) $run->wait_name );
		return;
	}

	if ( 'pending' !== $inquiry->status ) {
		$clear['marking']    = (string) $inquiry->status;
		$clear['run_status'] = 'closed' === $inquiry->status ? 'completed' : 'open';
		$wpdb->update(
			hotel_booking_workflow_runs_table_name(),
			$clear,
			array(
				'id' => (int) $run->id,
			),
			array( '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
		hotel_booking_workflow_append_event( (int) $run->id, 'timer_skipped', 'remind', (string) $run->marking, (string) $inquiry->status );
		return;
	}

	$published = hotel_booking_amqp_publish(
		'inquiry.remind',
		array(
			'inquiry_id' => (int) $inquiry->id,
		)
	);
	if ( ! $published ) {
		hotel_booking_send_stale_reminder_email( (int) $inquiry->id );
	}

	$wpdb->update(
		hotel_booking_workflow_runs_table_name(),
		$clear,
		array(
			'id' => (int) $run->id,
		),
		array( '%s', '%s', '%s', '%s' ),
		array( '%d' )
	);
	hotel_booking_workflow_append_event( (int) $run->id, 'timer_fired', 'remind', 'pending', 'pending' );
}

/**
 * Register a 60-second cron interval.
 *
 * @param array<string, array{interval:int,display:string}> $schedules Schedules.
 * @return array<string, array{interval:int,display:string}>
 */
function hotel_booking_workflow_cron_schedules( $schedules ) { // phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval -- DDEV ticks every 60s so wait_until resumes after a crash.
	$schedules['hotel_booking_minute'] = array(
		'interval' => MINUTE_IN_SECONDS,
		'display'  => __( 'Every minute (Hotel Booking workflow tick)', 'hotel-booking-core' ),
	);

	return $schedules;
}
// phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval -- DDEV ticks every 60s so wait_until resumes after a crash.
add_filter( 'cron_schedules', 'hotel_booking_workflow_cron_schedules' );

/**
 * Schedule the minute workflow tick.
 *
 * @return void
 */
function hotel_booking_schedule_workflow_tick() {
	if ( ! wp_next_scheduled( 'hotel_booking_workflow_tick' ) ) {
		wp_schedule_event( time() + MINUTE_IN_SECONDS, 'hotel_booking_minute', 'hotel_booking_workflow_tick' );
	}
}
add_action( 'init', 'hotel_booking_schedule_workflow_tick' );

/**
 * WP-Cron wrapper: resume due timers.
 *
 * @return void
 */
function hotel_booking_cron_workflow_tick() {
	hotel_booking_workflow_tick();
}
add_action( 'hotel_booking_workflow_tick', 'hotel_booking_cron_workflow_tick' );
