<?php
/**
 * Custom table install for booking inquiries.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HOTEL_BOOKING_DB_VERSION', '1.1.0' );

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
 * Create or update the inquiries table with dbDelta.
 */
function hotel_booking_install_inquiries_table() {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table           = hotel_booking_inquiries_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table} (
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

	dbDelta( $sql );
	update_option( 'hotel_booking_db_version', HOTEL_BOOKING_DB_VERSION );
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
