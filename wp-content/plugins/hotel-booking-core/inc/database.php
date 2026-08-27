<?php
/**
 * Custom table install for booking inquiries and workflow runs.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HOTEL_BOOKING_DB_VERSION', '1.2.0' );

/**
 * Prefixed inquiries table name.
 *
 * @return string
 */
function hotel_booking_inquiries_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'hb_inquiries';
}

/**
 * Prefixed workflow runs table name.
 *
 * @return string
 */
function hotel_booking_workflow_runs_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'hb_workflow_runs';
}

/**
 * Prefixed workflow events table name.
 *
 * @return string
 */
function hotel_booking_workflow_events_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'hb_workflow_events';
}

/**
 * Create or update custom tables with dbDelta.
 */
function hotel_booking_install_inquiries_table() {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();
	$inquiries       = hotel_booking_inquiries_table_name();
	$runs            = hotel_booking_workflow_runs_table_name();
	$events          = hotel_booking_workflow_events_table_name();

	$inquiries_sql = "CREATE TABLE {$inquiries} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		guest_name varchar(191) NOT NULL,
		guest_email varchar(191) NOT NULL,
		check_in date NOT NULL,
		check_out date NOT NULL,
		guests smallint unsigned NOT NULL DEFAULT 1,
		room_id bigint(20) unsigned DEFAULT NULL,
		message text NULL,
		status varchar(20) NOT NULL DEFAULT 'pending',
		desk_mailed_at datetime DEFAULT NULL,
		reminded_at datetime DEFAULT NULL,
		created_at datetime NOT NULL,
		updated_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY guest_email (guest_email),
		KEY status (status),
		KEY check_in (check_in)
	) {$charset_collate};";

	$runs_sql = "CREATE TABLE {$runs} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		workflow varchar(32) NOT NULL DEFAULT 'inquiry',
		subject_id bigint(20) unsigned NOT NULL,
		marking varchar(20) NOT NULL DEFAULT 'pending',
		run_status varchar(20) NOT NULL DEFAULT 'waiting',
		wait_until datetime DEFAULT NULL,
		wait_name varchar(32) DEFAULT NULL,
		created_at datetime NOT NULL,
		updated_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY workflow_subject (workflow,subject_id),
		KEY run_wait (run_status,wait_until)
	) {$charset_collate};";

	$events_sql = "CREATE TABLE {$events} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		run_id bigint(20) unsigned NOT NULL,
		type varchar(32) NOT NULL,
		name varchar(64) NOT NULL DEFAULT '',
		from_place varchar(20) DEFAULT NULL,
		to_place varchar(20) DEFAULT NULL,
		payload longtext NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY run_id (run_id),
		KEY type (type)
	) {$charset_collate};";

	dbDelta( $inquiries_sql );
	dbDelta( $runs_sql );
	dbDelta( $events_sql );
	update_option( 'hotel_booking_db_version', HOTEL_BOOKING_DB_VERSION );

	if ( function_exists( 'hotel_booking_workflow_backfill_runs' ) ) {
		hotel_booking_workflow_backfill_runs();
	}
}

/**
 * Install the table when the stored schema version is missing or stale.
 */
function hotel_booking_maybe_install_inquiries_table() {
	if ( get_option( 'hotel_booking_db_version' ) === HOTEL_BOOKING_DB_VERSION ) {
		return;
	}

	hotel_booking_install_inquiries_table();
}
add_action( 'plugins_loaded', 'hotel_booking_maybe_install_inquiries_table' );

/**
 * Empty custom plugin tables (tests).
 *
 * @return void
 */
function hotel_booking_truncate_custom_tables() {
	global $wpdb;

	$wpdb->query( 'TRUNCATE TABLE ' . hotel_booking_workflow_events_table_name() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$wpdb->query( 'TRUNCATE TABLE ' . hotel_booking_workflow_runs_table_name() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$wpdb->query( 'TRUNCATE TABLE ' . hotel_booking_inquiries_table_name() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}
