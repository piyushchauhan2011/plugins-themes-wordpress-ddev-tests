<?php
/**
 * Cron, desk email, and AMQP-fallback tests (no RabbitMQ).
 *
 * @package Hotel_Booking
 */

/**
 * Jobs run in-request when AMQP is unset, which is the PHPUnit path.
 */
class Test_Hotel_Booking_Jobs extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		hotel_booking_install_inquiries_table();
		update_option(
			'hotel_booking_settings',
			hotel_booking_sanitize_settings(
				array(
					'hotel_name' => 'The Oak House',
					'desk_email' => 'desk@example.com',
					'max_guests' => 6,
				)
			)
		);
	}

	public function tear_down() {
		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . hotel_booking_inquiries_table_name() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		parent::tear_down();
	}

	private function valid_payload( $overrides = array() ) {
		return array_merge(
			array(
				'guest_name'  => 'Ada Lovelace',
				'guest_email' => 'ada@example.com',
				'check_in'    => '2026-09-10',
				'check_out'   => '2026-09-12',
				'guests'      => 2,
				'room_id'     => 0,
				'message'     => 'Quiet room if you have one.',
				'status'      => 'pending',
			),
			$overrides
		);
	}

	public function test_amqp_is_unconfigured_in_phpunit() {
		$this->assertFalse( hotel_booking_amqp_is_configured() );
		$this->assertFalse( hotel_booking_amqp_publish( 'inquiry.created', array( 'inquiry_id' => 1 ) ) );
	}

	public function test_insert_sends_desk_email_when_amqp_is_down() {
		reset_phpmailer_instance();
		$id = hotel_booking_insert_inquiry( $this->valid_payload() );
		$this->assertIsInt( $id );

		$mailer = tests_retrieve_phpmailer_instance();
		$sent   = $mailer->get_sent();
		$this->assertNotEmpty( $sent );
		$this->assertSame( 'desk@example.com', $sent->to[0][0] );
		$this->assertStringContainsString( 'Ada Lovelace', $sent->subject );

		$row = hotel_booking_get_inquiry( $id );
		$this->assertNotEmpty( $row->desk_mailed_at );
		$this->assertSame( 'Mailed', hotel_booking_inquiry_job_notes( $row ) );
	}

	public function test_desk_email_is_idempotent() {
		reset_phpmailer_instance();
		$id = hotel_booking_insert_inquiry( $this->valid_payload() );
		$first = tests_retrieve_phpmailer_instance()->get_sent();
		$this->assertNotEmpty( $first );

		reset_phpmailer_instance();
		hotel_booking_send_desk_inquiry_email( $id );
		$this->assertEmpty( tests_retrieve_phpmailer_instance()->get_sent() );
	}

	public function test_stale_pending_ids_and_reminder() {
		$id = hotel_booking_insert_inquiry( $this->valid_payload() );
		global $wpdb;
		$wpdb->update(
			hotel_booking_inquiries_table_name(),
			array(
				'created_at'     => gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( 50 * HOUR_IN_SECONDS ) ),
				'desk_mailed_at' => current_time( 'mysql' ),
			),
			array(
				'id' => $id,
			),
			array( '%s', '%s' ),
			array( '%d' )
		);

		$ids = hotel_booking_get_stale_pending_inquiry_ids();
		$this->assertContains( $id, $ids );

		reset_phpmailer_instance();
		$this->assertSame( 1, hotel_booking_run_stale_pending() );
		$sent = tests_retrieve_phpmailer_instance()->get_sent();
		$this->assertNotEmpty( $sent );
		$this->assertStringContainsString( 'Pending inquiry', $sent->subject );

		$row = hotel_booking_get_inquiry( $id );
		$this->assertNotEmpty( $row->reminded_at );
		$this->assertStringContainsString( 'Reminded', hotel_booking_inquiry_job_notes( $row ) );
		$this->assertSame( array(), hotel_booking_get_stale_pending_inquiry_ids() );
	}

	public function test_digest_counts_pending() {
		hotel_booking_insert_inquiry( $this->valid_payload() );
		hotel_booking_insert_inquiry(
			$this->valid_payload(
				array(
					'guest_name'  => 'Closed Guest',
					'guest_email' => 'closed@example.com',
					'status'      => 'closed',
				)
			)
		);

		$this->assertSame( 1, hotel_booking_count_pending_inquiries() );

		reset_phpmailer_instance();
		$this->assertSame( 1, hotel_booking_run_desk_digest() );
		$sent = tests_retrieve_phpmailer_instance()->get_sent();
		$this->assertNotEmpty( $sent );
		$this->assertStringContainsString( '1 pending', $sent->subject );
	}

	public function test_cron_hooks_are_registered() {
		$this->assertNotFalse( has_action( 'hotel_booking_stale_pending', 'hotel_booking_cron_stale_pending' ) );
		$this->assertNotFalse( has_action( 'hotel_booking_desk_digest', 'hotel_booking_cron_desk_digest' ) );
	}
}
