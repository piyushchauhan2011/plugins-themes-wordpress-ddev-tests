<?php
/**
 * wp-admin menu and settings tests.
 *
 * @package Hotel_Booking
 */

/**
 * Covers add_menu_page, capabilities, Settings API sanitize, and list HTML.
 */
class Test_Hotel_Booking_Admin extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		hotel_booking_install_inquiries_table();
	}

	public function tear_down() {
		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . hotel_booking_inquiries_table_name() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		delete_option( 'hotel_booking_settings' );
		parent::tear_down();
	}

	public function test_admin_menu_hooks_are_registered() {
		global $submenu;

		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		do_action( 'admin_menu' );

		$this->assertArrayHasKey( 'hotel-booking', $GLOBALS['admin_page_hooks'] );
		$this->assertArrayHasKey( 'hotel-booking', $submenu );

		$slugs = wp_list_pluck( $submenu['hotel-booking'], 2 );
		$this->assertContains( 'hotel-booking', $slugs );
		$this->assertContains( 'hotel-booking-settings', $slugs );
	}

	public function test_editor_can_view_inquiries_but_not_settings() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );

		$this->assertTrue( user_can( $editor, 'edit_posts' ) );
		$this->assertFalse( user_can( $editor, 'manage_options' ) );
	}

	public function test_subscriber_cannot_view_inquiries() {
		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->assertFalse( user_can( $subscriber, 'edit_posts' ) );
		$this->assertFalse( user_can( $subscriber, 'manage_options' ) );
	}

	public function test_sanitize_settings_clamps_max_guests_and_clears_bad_email() {
		$clean = hotel_booking_sanitize_settings(
			array(
				'hotel_name' => '<b>The Oak</b>',
				'desk_email' => 'not-an-email',
				'max_guests' => 99,
			)
		);

		$this->assertSame( 'The Oak', $clean['hotel_name'] );
		$this->assertSame( '', $clean['desk_email'] );
		$this->assertSame( 8, $clean['max_guests'] );

		$low = hotel_booking_sanitize_settings( array( 'max_guests' => 0 ) );
		$this->assertSame( 1, $low['max_guests'] );
	}

	public function test_get_setting_returns_saved_hotel_name() {
		update_option(
			'hotel_booking_settings',
			hotel_booking_sanitize_settings(
				array(
					'hotel_name' => 'Garden House',
					'desk_email' => 'desk@example.com',
					'max_guests' => 4,
				)
			)
		);

		$this->assertSame( 'Garden House', hotel_booking_get_setting( 'hotel_name' ) );
		$this->assertSame( 'desk@example.com', hotel_booking_get_setting( 'desk_email' ) );
		$this->assertSame( 4, hotel_booking_get_setting( 'max_guests' ) );
	}

	public function test_inquiries_page_renders_guest_name() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		hotel_booking_insert_inquiry(
			array(
				'guest_name'  => 'Ada Lovelace',
				'guest_email' => 'ada@example.com',
				'check_in'    => '2026-11-01',
				'check_out'   => '2026-11-03',
				'guests'      => 2,
			)
		);

		ob_start();
		hotel_booking_render_inquiries_page();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'Ada Lovelace', $html );
		$this->assertStringContainsString( 'ada@example.com', $html );
	}
}
