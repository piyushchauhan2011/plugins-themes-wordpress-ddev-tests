<?php
/**
 * Theme-level WP_UnitTestCase examples.
 *
 * @package Hotel_Booking
 */

/**
 * Verifies the active theme and block-theme flag.
 */
class Test_Hotel_Booking_Theme extends WP_UnitTestCase {

	/**
	 * WP_UnitTestCase::set_up() resets globals and caches. Always call parent.
	 */
	public function set_up() {
		parent::set_up();
	}

	/**
	 * WP_UnitTestCase::tear_down() restores WordPress to a clean state.
	 */
	public function tear_down() {
		parent::tear_down();
	}

	public function test_theme_is_active() {
		$this->assertSame( 'hotel-booking', get_stylesheet() );
		$this->assertSame( 'hotel-booking', get_template() );
	}

	public function test_theme_is_block_theme() {
		$this->assertTrue( wp_is_block_theme() );
	}

	public function test_front_page_template_exists() {
		$this->assertFileExists( get_template_directory() . '/templates/front-page.html' );
	}
}
