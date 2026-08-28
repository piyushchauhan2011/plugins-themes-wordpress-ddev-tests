<?php
/**
 * WP-CLI: reindex, AMQP worker, digest, and stale reminders.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Recreate the rooms OpenSearch index.
 *
 * @param array<int, string>    $args       Positional args.
 * @param array<string, string> $assoc_args Assoc args.
 * @return void
 */
function hotel_booking_cli_reindex( $args = array(), $assoc_args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	if ( ! hotel_booking_opensearch_is_configured() ) {
		WP_CLI::warning(
			__(
				'WP_OPENSEARCH_HOST is not set or OpenSearch is not running; skip reindex. Start the search profile: ddev start --profiles=search',
				'hotel-booking-core'
			)
		);
		return;
	}

	$result = hotel_booking_opensearch_reindex();
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( $result->get_error_message() );
	}

	WP_CLI::success(
		sprintf(
			/* translators: %d: number of room documents */
			__( 'Indexed %d rooms into hotel-booking-rooms.', 'hotel-booking-core' ),
			(int) $result
		)
	);
}

/**
 * Consume RabbitMQ email and search queues (blocking).
 *
 * @param array<int, string>    $args       Positional args.
 * @param array<string, string> $assoc_args Assoc args.
 * @return void
 */
function hotel_booking_cli_worker( $args = array(), $assoc_args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	if ( ! hotel_booking_amqp_is_configured() ) {
		WP_CLI::warning(
			__(
				'WP_AMQP_HOST is not set, php-amqplib is missing, or RabbitMQ is not running. Start the queue profile: ddev start --profiles=queue',
				'hotel-booking-core'
			)
		);
		return;
	}

	WP_CLI::log( __( 'Consuming hotel-booking.email and hotel-booking.search. Ctrl+C to stop.', 'hotel-booking-core' ) );

	try {
		hotel_booking_amqp_consume();
	} catch ( Exception $e ) {
		WP_CLI::error( $e->getMessage() );
	}
}

/**
 * Run the daily desk digest now.
 *
 * @param array<int, string>    $args       Positional args.
 * @param array<string, string> $assoc_args Assoc args.
 * @return void
 */
function hotel_booking_cli_digest( $args = array(), $assoc_args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	$count = hotel_booking_run_desk_digest();
	WP_CLI::success(
		sprintf(
			/* translators: %d: pending inquiry count */
			__( 'Desk digest queued or sent (%d pending).', 'hotel-booking-core' ),
			$count
		)
	);
}

/**
 * Run the stale-pending reminder job now.
 *
 * @param array<int, string>    $args       Positional args.
 * @param array<string, string> $assoc_args Assoc args.
 * @return void
 */
function hotel_booking_cli_remind_stale( $args = array(), $assoc_args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	$count = hotel_booking_run_stale_pending();
	WP_CLI::success(
		sprintf(
			/* translators: %d: inquiries processed */
			__( 'Stale-pending reminders queued or sent (%d).', 'hotel-booking-core' ),
			$count
		)
	);
}

/**
 * Resume due workflow timers now.
 *
 * @param array<int, string>    $args       Positional args.
 * @param array<string, string> $assoc_args Assoc args.
 * @return void
 */
function hotel_booking_cli_workflow_tick( $args = array(), $assoc_args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	$count = hotel_booking_workflow_tick();
	WP_CLI::success(
		sprintf(
			/* translators: %d: due runs processed */
			__( 'Workflow tick processed %d due run(s).', 'hotel-booking-core' ),
			$count
		)
	);
}

WP_CLI::add_command( 'hotel-booking reindex', 'hotel_booking_cli_reindex' );
WP_CLI::add_command( 'hotel-booking worker', 'hotel_booking_cli_worker' );
WP_CLI::add_command( 'hotel-booking digest', 'hotel_booking_cli_digest' );
WP_CLI::add_command( 'hotel-booking remind-stale', 'hotel_booking_cli_remind_stale' );
WP_CLI::add_command( 'hotel-booking workflow tick', 'hotel_booking_cli_workflow_tick' );
