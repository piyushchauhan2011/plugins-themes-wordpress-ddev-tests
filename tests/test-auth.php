<?php
/**
 * Staff role, authorize policies, and login form tests.
 *
 * @package Hotel_Booking
 */

/**
 * Covers hotel_manager caps and inquiry.transition ABAC (pending close).
 */
class Test_Hotel_Booking_Auth extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		hotel_booking_register_roles();
		hotel_booking_install_inquiries_table();
	}

	public function tear_down() {
		hotel_booking_truncate_custom_tables();
		parent::tear_down();
	}

	private function pending_inquiry() {
		$id = hotel_booking_insert_inquiry(
			array(
				'guest_name'  => 'Ada Lovelace',
				'guest_email' => 'ada@example.com',
				'check_in'    => '2026-11-01',
				'check_out'   => '2026-11-03',
				'guests'      => 2,
			)
		);

		return hotel_booking_get_inquiry( $id );
	}

	public function test_hotel_manager_caps() {
		$manager = self::factory()->user->create( array( 'role' => 'hotel_manager' ) );

		$this->assertTrue( user_can( $manager, 'hb_access_desk' ) );
		$this->assertTrue( user_can( $manager, 'hb_transition_inquiries' ) );
		$this->assertFalse( user_can( $manager, 'hb_delete_inquiries' ) );
		$this->assertFalse( user_can( $manager, 'hb_reopen_inquiries' ) );
		$this->assertFalse( user_can( $manager, 'manage_options' ) );
	}

	public function test_subscriber_is_denied_desk() {
		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$this->assertFalse( hotel_booking_authorize( 'desk.view' ) );
		$this->assertFalse( hotel_booking_authorize( 'inquiry.delete' ) );
	}

	public function test_manager_cannot_close_pending_or_reopen() {
		$manager = self::factory()->user->create( array( 'role' => 'hotel_manager' ) );
		wp_set_current_user( $manager );

		$inquiry = $this->pending_inquiry();
		$this->assertTrue(
			hotel_booking_authorize(
				'inquiry.transition',
				array(
					'inquiry'    => $inquiry,
					'transition' => 'contact',
				)
			)
		);
		$this->assertFalse(
			hotel_booking_authorize(
				'inquiry.transition',
				array(
					'inquiry'    => $inquiry,
					'transition' => 'close',
				)
			)
		);
		$this->assertFalse(
			hotel_booking_authorize(
				'inquiry.transition',
				array(
					'inquiry'    => $inquiry,
					'transition' => 'reopen',
				)
			)
		);
		$this->assertFalse( hotel_booking_authorize( 'inquiry.delete' ) );

		$result = hotel_booking_apply_inquiry_transition( (int) $inquiry->id, 'close' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'hotel_booking_workflow_blocked', $result->get_error_code() );
	}

	public function test_admin_can_close_pending() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$inquiry = $this->pending_inquiry();
		$this->assertTrue(
			hotel_booking_authorize(
				'inquiry.transition',
				array(
					'inquiry'    => $inquiry,
					'transition' => 'close',
				)
			)
		);
		$this->assertTrue( hotel_booking_apply_inquiry_transition( (int) $inquiry->id, 'close' ) );
		$this->assertSame( 'closed', hotel_booking_get_inquiry( (int) $inquiry->id )->status );
	}

	public function test_enabled_transitions_hide_pending_close_for_manager() {
		$manager = self::factory()->user->create( array( 'role' => 'hotel_manager' ) );
		wp_set_current_user( $manager );

		$inquiry = $this->pending_inquiry();
		$names   = wp_list_pluck( hotel_booking_inquiry_enabled_transitions( $inquiry ), 'name' );
		$this->assertContains( 'contact', $names );
		$this->assertNotContains( 'close', $names );
		$this->assertNotContains( 'reopen', $names );
	}

	public function test_staff_login_form_renders_when_logged_out() {
		wp_set_current_user( 0 );
		$html = hotel_booking_render_staff_login();
		$this->assertStringContainsString( 'name="log"', $html );
		$this->assertStringContainsString( 'hb_staff_login', $html );
		$this->assertTrue( shortcode_exists( 'hotel_staff_login' ) );
	}
}
