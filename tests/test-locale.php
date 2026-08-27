<?php
/**
 * Visitor locale cookie tests.
 *
 * @package Hotel_Booking
 */

/**
 * Layer A switcher: supported locales and determine_locale filter.
 */
class Test_Hotel_Booking_Locale extends WP_UnitTestCase {

	public function tear_down() {
		unset( $_GET['lang'], $_COOKIE[ hotel_booking_locale_cookie_name() ] );
		parent::tear_down();
	}

	public function test_sanitize_visitor_locale_allows_known_values() {
		$this->assertSame( 'en_US', hotel_booking_sanitize_visitor_locale( 'en_US' ) );
		$this->assertSame( 'es_ES', hotel_booking_sanitize_visitor_locale( 'es_ES' ) );
		$this->assertSame( 'es_ES', hotel_booking_sanitize_visitor_locale( 'es-ES' ) );
	}

	public function test_sanitize_visitor_locale_rejects_unknown() {
		$this->assertSame( '', hotel_booking_sanitize_visitor_locale( 'fr_FR' ) );
		$this->assertSame( '', hotel_booking_sanitize_visitor_locale( array() ) );
	}

	public function test_determine_locale_uses_visitor_cookie() {
		$_COOKIE[ hotel_booking_locale_cookie_name() ] = 'es_ES';
		$this->assertSame( 'es_ES', determine_locale() );
	}

	public function test_query_arg_wins_over_cookie() {
		$_COOKIE[ hotel_booking_locale_cookie_name() ] = 'en_US';
		$this->assertSame( 'en_US', hotel_booking_requested_visitor_locale() );

		$_GET['lang'] = 'es_ES';
		$this->assertSame( 'es_ES', hotel_booking_requested_visitor_locale() );
	}
}
