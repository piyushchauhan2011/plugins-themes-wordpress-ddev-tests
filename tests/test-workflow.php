<?php
/**
 * Symfony Workflow transitions and durable timer tick (no Temporal).
 *
 * @package Hotel_Booking
 */

/**
 * Constrained inquiry graph plus wait_until resume.
 */
class Test_Hotel_Booking_Workflow extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		hotel_booking_install_inquiries_table();
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
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
		hotel_booking_truncate_custom_tables();
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

	public function test_workflow_library_is_available_in_phpunit() {
		$this->assertTrue( hotel_booking_workflow_enabled() );
		$this->assertNotNull( hotel_booking_inquiry_workflow() );
	}

	public function test_insert_creates_waiting_remind_run() {
		$id  = hotel_booking_insert_inquiry( $this->valid_payload() );
		$run = hotel_booking_get_workflow_run( $id );
		$this->assertNotNull( $run );
		$this->assertSame( 'pending', $run->marking );
		$this->assertSame( 'waiting', $run->run_status );
		$this->assertSame( 'remind', $run->wait_name );
		$this->assertNotEmpty( $run->wait_until );
	}

	public function test_contact_then_close() {
		$id = hotel_booking_insert_inquiry( $this->valid_payload() );
		$this->assertTrue( hotel_booking_update_inquiry( $id, array( 'status' => 'contacted' ) ) );
		$this->assertSame( 'contacted', hotel_booking_get_inquiry( $id )->status );

		$this->assertTrue( hotel_booking_apply_inquiry_transition( $id, 'close' ) );
		$this->assertSame( 'closed', hotel_booking_get_inquiry( $id )->status );
		$this->assertSame( 'completed', hotel_booking_get_workflow_run( $id )->run_status );
	}

	public function test_illegal_contacted_to_pending_fails() {
		$id = hotel_booking_insert_inquiry( $this->valid_payload() );
		hotel_booking_update_inquiry( $id, array( 'status' => 'contacted' ) );

		$result = hotel_booking_update_inquiry( $id, array( 'status' => 'pending' ) );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'hotel_booking_workflow_blocked', $result->get_error_code() );
		$this->assertSame( 'contacted', hotel_booking_get_inquiry( $id )->status );
	}

	public function test_pending_close_is_allowed() {
		$id = hotel_booking_insert_inquiry( $this->valid_payload() );
		$this->assertTrue( hotel_booking_apply_inquiry_transition( $id, 'close' ) );
		$this->assertSame( 'closed', hotel_booking_get_inquiry( $id )->status );
	}

	public function test_due_wait_tick_sends_reminder() {
		$id = hotel_booking_insert_inquiry( $this->valid_payload() );
		global $wpdb;
		$run = hotel_booking_get_workflow_run( $id );
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE %i SET wait_until = DATE_SUB(%s, INTERVAL 1 HOUR) WHERE id = %d',
				hotel_booking_workflow_runs_table_name(),
				current_time( 'mysql' ),
				(int) $run->id
			)
		);

		reset_phpmailer_instance();
		$this->assertSame( 1, hotel_booking_workflow_tick() );
		$sent = tests_retrieve_phpmailer_instance()->get_sent();
		$this->assertNotEmpty( $sent );
		$this->assertStringContainsString( 'Pending inquiry', $sent->subject );
		$this->assertNotEmpty( hotel_booking_get_inquiry( $id )->reminded_at );
	}

	public function test_tick_after_contact_skips_remind() {
		$id = hotel_booking_insert_inquiry( $this->valid_payload() );
		hotel_booking_apply_inquiry_transition( $id, 'contact' );

		reset_phpmailer_instance();
		$this->assertSame( 0, hotel_booking_workflow_tick() );
		$this->assertEmpty( tests_retrieve_phpmailer_instance()->get_sent() );
		$this->assertEmpty( hotel_booking_get_inquiry( $id )->reminded_at );
	}

	public function test_enabled_transitions_for_pending() {
		$id      = hotel_booking_insert_inquiry( $this->valid_payload() );
		$inquiry = hotel_booking_get_inquiry( $id );
		$names   = wp_list_pluck( hotel_booking_inquiry_enabled_transitions( $inquiry ), 'name' );
		$this->assertContains( 'contact', $names );
		$this->assertContains( 'close', $names );
		$this->assertNotContains( 'reopen', $names );
	}
}
