<?php
/**
 * Custom table CRUD tests for wp_hb_inquiries.
 *
 * @package Hotel_Booking
 */

/**
 * Exercises $wpdb insert/get/update/delete against a table created with dbDelta.
 */
class Test_Hotel_Booking_Inquiries extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		hotel_booking_install_inquiries_table();
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
	}

	public function tear_down() {
		hotel_booking_truncate_custom_tables();
		parent::tear_down();
	}

	private function valid_payload( $overrides = array() ) {
		return array_merge(
			array(
				'guest_name'  => "O'Brien",
				'guest_email' => 'desk@example.com',
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

	public function test_inquiries_table_exists() {
		global $wpdb;

		$table = hotel_booking_inquiries_table_name();
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );

		$this->assertSame( $table, $found );
	}

	public function test_insert_and_get_inquiry() {
		$id = hotel_booking_insert_inquiry( $this->valid_payload() );
		$this->assertIsInt( $id );

		$row = hotel_booking_get_inquiry( $id );
		$this->assertNotNull( $row );
		$this->assertSame( "O'Brien", $row->guest_name );
		$this->assertSame( 'desk@example.com', $row->guest_email );
		$this->assertSame( '2026-09-10', $row->check_in );
		$this->assertSame( 2, (int) $row->guests );
		$this->assertSame( 'pending', $row->status );
	}

	public function test_insert_rejects_invalid_email() {
		$result = hotel_booking_insert_inquiry( $this->valid_payload( array( 'guest_email' => 'not-an-email' ) ) );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'hotel_booking_invalid_email', $result->get_error_code() );
	}

	public function test_insert_rejects_checkout_before_checkin() {
		$result = hotel_booking_insert_inquiry(
			$this->valid_payload(
				array(
					'check_in'  => '2026-09-12',
					'check_out' => '2026-09-10',
				)
			)
		);
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'hotel_booking_invalid_range', $result->get_error_code() );
	}

	public function test_get_inquiries_filters_by_status() {
		hotel_booking_insert_inquiry( $this->valid_payload( array( 'guest_name' => 'Pending Guest' ) ) );
		$closed_id = hotel_booking_insert_inquiry(
			$this->valid_payload(
				array(
					'guest_name'  => 'Closed Guest',
					'guest_email' => 'closed@example.com',
					'status'      => 'closed',
				)
			)
		);

		$closed = hotel_booking_get_inquiries( array( 'status' => 'closed' ) );
		$this->assertCount( 1, $closed );
		$this->assertSame( $closed_id, (int) $closed[0]->id );
	}

	public function test_update_inquiry_changes_status() {
		$id = hotel_booking_insert_inquiry( $this->valid_payload() );
		$result = hotel_booking_update_inquiry( $id, array( 'status' => 'contacted' ) );

		$this->assertTrue( $result );
		$this->assertSame( 'contacted', hotel_booking_get_inquiry( $id )->status );
	}

	public function test_update_missing_inquiry_returns_error() {
		$result = hotel_booking_update_inquiry( 99999, array( 'status' => 'closed' ) );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'hotel_booking_inquiry_missing', $result->get_error_code() );
	}

	public function test_delete_inquiry() {
		$id = hotel_booking_insert_inquiry( $this->valid_payload() );
		$this->assertTrue( hotel_booking_delete_inquiry( $id ) );
		$this->assertNull( hotel_booking_get_inquiry( $id ) );
	}

	public function test_inquiry_status_label_translates_known_slugs() {
		$this->assertSame( 'Pending', hotel_booking_inquiry_status_label( 'pending' ) );
		$this->assertSame( 'Contacted', hotel_booking_inquiry_status_label( 'contacted' ) );
		$this->assertSame( 'Closed', hotel_booking_inquiry_status_label( 'closed' ) );
	}

	public function test_inquiry_status_label_leaves_unknown_slug() {
		$this->assertSame( 'archived', hotel_booking_inquiry_status_label( 'archived' ) );
	}

	public function test_shortcodes_are_registered() {
		$this->assertTrue( shortcode_exists( 'hotel_inquiry_form' ) );
		$this->assertTrue( shortcode_exists( 'hotel_inquiry_list' ) );
		$this->assertTrue( shortcode_exists( 'hotel_staff_login' ) );
	}

	public function test_theme_form_template_exists() {
		$this->assertFileExists( get_template_directory() . '/template-parts/inquiry-form.php' );
		$this->assertFileExists( get_template_directory() . '/template-parts/inquiries-list.php' );
	}
}
