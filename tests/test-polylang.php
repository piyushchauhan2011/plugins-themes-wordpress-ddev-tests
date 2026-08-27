<?php
/**
 * Polylang hooks without the plugin loaded (PHPUnit).
 *
 * @package Hotel_Booking
 */

/**
 * Layer B registration and query helpers stay safe when Polylang is absent.
 */
class Test_Hotel_Booking_Polylang extends WP_UnitTestCase {

	public function test_pll_get_post_types_includes_hb_room() {
		$types = apply_filters( 'pll_get_post_types', array(), false );

		$this->assertArrayHasKey( 'hb_room', $types );
		$this->assertSame( 'hb_room', $types['hb_room'] );
		$this->assertArrayHasKey( 'wp_navigation', $types );
	}

	public function test_polylang_is_inactive_in_phpunit() {
		$this->assertFalse( hotel_booking_polylang_is_active() );
		$this->assertSame( '', hotel_booking_query_lang() );
		$this->assertSame( '', hotel_booking_query_lang( 'es' ) );
	}

	public function test_query_args_omit_lang_without_polylang() {
		$args = hotel_booking_query_args_with_lang(
			array(
				'post_type' => 'hb_room',
			),
			'es'
		);

		$this->assertArrayNotHasKey( 'lang', $args );
	}
}
