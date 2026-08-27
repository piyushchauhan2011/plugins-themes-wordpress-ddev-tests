<?php
/**
 * Demo users, settings, and fake inquiries for local onboarding.
 *
 * Invoked by `ddev seed-content` via `wp eval-file`. Not plugin runtime.
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hotel_booking_insert_inquiry' ) ) {
	WP_CLI::error( 'Activate Hotel Booking Core before seeding demo data.' );
}

$force = '1' === getenv( 'HOTEL_BOOKING_SEED_FORCE' );

hotel_booking_install_inquiries_table();

if ( $force ) {
	global $wpdb;
	$wpdb->query( 'TRUNCATE TABLE ' . hotel_booking_inquiries_table_name() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	WP_CLI::log( 'Truncated inquiries table.' );
}

$users = array(
	array(
		'user_login'    => 'desk',
		'user_pass'     => 'desk',
		'user_email'    => 'desk@hotel-booking.ddev.site',
		'display_name'  => 'Desk editor',
		'role'          => 'editor',
	),
	array(
		'user_login'    => 'guest',
		'user_pass'     => 'guest',
		'user_email'    => 'guest@hotel-booking.ddev.site',
		'display_name'  => 'Site guest',
		'role'          => 'subscriber',
	),
);

foreach ( $users as $user ) {
	if ( username_exists( $user['user_login'] ) ) {
		WP_CLI::log( 'User already exists: ' . $user['user_login'] );
		continue;
	}
	$id = wp_insert_user( $user );
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $id->get_error_message() );
		continue;
	}
	WP_CLI::log( 'Created user ' . $user['user_login'] . ' / ' . $user['user_pass'] . ' (' . $user['role'] . ')' );
}

update_option(
	'hotel_booking_settings',
	hotel_booking_sanitize_settings(
		array(
			'hotel_name' => 'The Oak House',
			'desk_email' => 'desk@hotel-booking.ddev.site',
			'max_guests' => 6,
		)
	)
);
WP_CLI::log( 'Saved hotel_booking_settings (The Oak House, max_guests 6).' );

$room_id = static function ( $slug ) {
	$found = get_posts(
		array(
			'name'           => $slug,
			'post_type'      => 'hb_room',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	return $found ? (int) $found[0] : 0;
};

$deluxe = $room_id( 'deluxe-king' );
$family = $room_id( 'family-room' );
$twin   = $room_id( 'courtyard-twin' );

$existing = hotel_booking_get_inquiries( array( 'limit' => 1 ) );
if ( $existing && ! $force ) {
	WP_CLI::log( 'Inquiries already present; skipping fake rows.' );
	return;
}

$samples = array(
	array(
		'guest_name'  => 'Priya Shah',
		'guest_email' => 'priya@example.com',
		'check_in'    => '2026-09-12',
		'check_out'   => '2026-09-15',
		'guests'      => 2,
		'room_id'     => $deluxe,
		'message'     => 'Quiet room facing the garden if you have one.',
		'status'      => 'pending',
	),
	array(
		'guest_name'  => 'Jonah Hale',
		'guest_email' => 'jonah@example.com',
		'check_in'    => '2026-09-18',
		'check_out'   => '2026-09-20',
		'guests'      => 1,
		'room_id'     => $twin,
		'message'     => 'Late check-in, around nine.',
		'status'      => 'contacted',
	),
	array(
		'guest_name'  => 'Marta Klein',
		'guest_email' => 'marta@example.com',
		'check_in'    => '2026-10-02',
		'check_out'   => '2026-10-06',
		'guests'      => 4,
		'room_id'     => $family,
		'message'     => 'Two adults, two children. Crib if possible.',
		'status'      => 'pending',
	),
	array(
		'guest_name'  => 'Sam Okonkwo',
		'guest_email' => 'sam@example.com',
		'check_in'    => '2026-08-01',
		'check_out'   => '2026-08-04',
		'guests'      => 2,
		'room_id'     => $deluxe,
		'message'     => 'Thank you — we already stayed.',
		'status'      => 'closed',
	),
	array(
		'guest_name'  => 'Elena Rossi',
		'guest_email' => 'elena@example.com',
		'check_in'    => '2026-11-20',
		'check_out'   => '2026-11-23',
		'guests'      => 2,
		'room_id'     => 0,
		'message'     => 'Anniversary; no room preference.',
		'status'      => 'contacted',
	),
	array(
		'guest_name'  => 'Chris Patel',
		'guest_email' => 'chris@example.com',
		'check_in'    => '2026-10-14',
		'check_out'   => '2026-10-16',
		'guests'      => 3,
		'room_id'     => $family,
		'message'     => 'Need a desk that actually works.',
		'status'      => 'pending',
	),
);

foreach ( $samples as $row ) {
	$result = hotel_booking_insert_inquiry( $row );
	if ( is_wp_error( $result ) ) {
		WP_CLI::warning( $result->get_error_message() );
		continue;
	}
	WP_CLI::log( 'Inquiry #' . $result . ' ' . $row['guest_name'] . ' (' . $row['status'] . ')' );
	if ( 'Priya Shah' === $row['guest_name'] ) {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE %i SET created_at = DATE_SUB(%s, INTERVAL 50 HOUR) WHERE id = %d',
				hotel_booking_inquiries_table_name(),
				current_time( 'mysql' ),
				$result
			)
		);
		WP_CLI::log( 'Backdated inquiry #' . $result . ' for stale-pending cron.' );
		$run = hotel_booking_get_workflow_run( (int) $result );
		if ( $run ) {
			$wpdb->query(
				$wpdb->prepare(
					'UPDATE %i SET wait_until = DATE_SUB(%s, INTERVAL 50 HOUR), run_status = %s, wait_name = %s WHERE id = %d',
					hotel_booking_workflow_runs_table_name(),
					current_time( 'mysql' ),
					'waiting',
					'remind',
					(int) $run->id
				)
			);
			WP_CLI::log( 'Set remind wait_until in the past for inquiry #' . $result . '.' );
		}
	}
}
