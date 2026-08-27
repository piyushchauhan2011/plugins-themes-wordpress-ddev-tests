<?php
/**
 * Block pattern registration tests.
 *
 * @package Hotel_Booking
 */

/**
 * Patterns in /patterns are auto-loaded after init.
 */
class Test_Hotel_Booking_Patterns extends WP_UnitTestCase {

	public function test_pattern_category_is_registered() {
		$categories = WP_Block_Pattern_Categories_Registry::get_instance();
		$this->assertTrue( $categories->is_registered( 'hotel-booking' ) );
	}

	public function test_hero_booking_pattern_is_registered() {
		$patterns = WP_Block_Patterns_Registry::get_instance();
		$this->assertTrue( $patterns->is_registered( 'hotel-booking/hero-booking' ) );
	}

	public function test_featured_rooms_pattern_is_registered() {
		$patterns = WP_Block_Patterns_Registry::get_instance();
		$this->assertTrue( $patterns->is_registered( 'hotel-booking/featured-rooms' ) );
	}

	public function test_stay_faq_pattern_is_registered() {
		$patterns = WP_Block_Patterns_Registry::get_instance();
		$this->assertTrue( $patterns->is_registered( 'hotel-booking/stay-faq' ) );
	}
}
