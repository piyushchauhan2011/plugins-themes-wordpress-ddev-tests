<?php
/**
 * Integration tests for inquiry form POST and rendered pages.
 *
 * @package Hotel_Booking
 */

/**
 * Hits admin-post handlers and go_to() page output, not just CRUD helpers.
 */
class Test_Hotel_Booking_Integration extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		hotel_booking_install_inquiries_table();
	}

	public function tear_down() {
		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . hotel_booking_inquiries_table_name() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$_POST = array();
		parent::tear_down();
	}

	/**
	 * Throw instead of sending headers so PHPUnit can assert the redirect URL.
	 *
	 * @return string Redirect location.
	 */
	private function capture_redirect( $callback ) {
		add_filter(
			'wp_redirect',
			static function ( $url ) {
				throw new RuntimeException( $url );
			},
			99999
		);

		try {
			$callback();
			$this->fail( 'Expected a redirect.' );
		} catch ( RuntimeException $e ) {
			return $e->getMessage();
		}
	}

	private function valid_post( $overrides = array() ) {
		return array_merge(
			array(
				'action'           => 'hb_save_inquiry',
				'hb_inquiry_nonce' => wp_create_nonce( 'hb_save_inquiry' ),
				'guest_name'       => 'Ada Lovelace',
				'guest_email'      => 'ada@example.com',
				'check_in'         => '2026-11-01',
				'check_out'        => '2026-11-03',
				'guests'           => '2',
				'room_id'          => '0',
				'message'          => 'Quiet room if you have one.',
			),
			$overrides
		);
	}

	public function test_save_inquiry_redirects_with_id_and_inserts_row() {
		$_POST = $this->valid_post();

		$location = $this->capture_redirect(
			static function () {
				hotel_booking_handle_save_inquiry();
			}
		);

		$this->assertStringContainsString( 'inquiry=', $location );

		parse_str( (string) wp_parse_url( $location, PHP_URL_QUERY ), $query );
		$id = isset( $query['inquiry'] ) ? (int) $query['inquiry'] : 0;
		$this->assertGreaterThan( 0, $id );

		$row = hotel_booking_get_inquiry( $id );
		$this->assertNotNull( $row );
		$this->assertSame( 'Ada Lovelace', $row->guest_name );
		$this->assertSame( 'ada@example.com', $row->guest_email );
	}

	public function test_save_inquiry_rejects_invalid_email() {
		$_POST = $this->valid_post( array( 'guest_email' => 'not-an-email' ) );

		$location = $this->capture_redirect(
			static function () {
				hotel_booking_handle_save_inquiry();
			}
		);

		$this->assertStringContainsString( 'hb_error=', $location );
		$this->assertSame( array(), hotel_booking_get_inquiries( array( 'limit' => 10 ) ) );
	}

	public function test_save_inquiry_rejects_bad_nonce() {
		$_POST = $this->valid_post( array( 'hb_inquiry_nonce' => 'nope' ) );

		$this->expectException( WPDieException::class );
		hotel_booking_handle_save_inquiry();
	}

	public function test_booking_page_renders_form_fields() {
		$page_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Booking',
				'post_content' => '[hotel_inquiry_form]',
			)
		);

		$this->go_to( get_permalink( $page_id ) );
		the_post();
		$html = apply_filters( 'the_content', get_the_content() );

		$this->assertStringContainsString( 'hb_save_inquiry', $html );
		$this->assertStringContainsString( 'name="guest_name"', $html );
		$this->assertStringContainsString( 'hb_inquiry_nonce', $html );
	}

	public function test_desk_page_hides_table_from_logged_out_users() {
		$page_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Desk',
				'post_content' => '[hotel_inquiry_list]',
			)
		);

		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $page_id ) );
		the_post();
		$html = apply_filters( 'the_content', get_the_content() );

		$this->assertStringContainsString( 'The desk book is for staff', $html );
		$this->assertStringNotContainsString( 'hb-desk__table', $html );
	}

	public function test_desk_page_shows_inquiry_for_editor() {
		hotel_booking_insert_inquiry(
			array(
				'guest_name'  => 'Ada Lovelace',
				'guest_email' => 'ada@example.com',
				'check_in'    => '2026-11-01',
				'check_out'   => '2026-11-03',
				'guests'      => 2,
			)
		);

		$editor  = self::factory()->user->create( array( 'role' => 'editor' ) );
		$page_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Desk',
				'post_content' => '[hotel_inquiry_list]',
			)
		);

		wp_set_current_user( $editor );
		$this->go_to( get_permalink( $page_id ) );
		the_post();
		$html = apply_filters( 'the_content', get_the_content() );

		$this->assertStringContainsString( 'Ada Lovelace', $html );
		$this->assertStringContainsString( 'hb-desk__table', $html );
	}
}
