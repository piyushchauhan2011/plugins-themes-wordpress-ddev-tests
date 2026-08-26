<?php
/**
 * Helper function tests.
 *
 * @package Hotel_Booking
 */

/**
 * Tests for inc/helpers.php — these do not need a database row.
 */
class Test_Hotel_Booking_Helpers extends WP_UnitTestCase {

	public function test_format_price_uses_nightly_label() {
		$this->assertSame( '$280 / night', hotel_booking_format_price( 280 ) );
	}

	public function test_format_price_treats_non_numeric_as_zero() {
		$this->assertSame( '$0 / night', hotel_booking_format_price( 'free' ) );
	}
}
