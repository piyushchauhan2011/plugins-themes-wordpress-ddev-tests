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

	public function test_xx_large_font_size_is_fluid() {
		$json     = json_decode( file_get_contents( get_template_directory() . '/theme.json' ), true );
		$xx_large = null;

		foreach ( $json['settings']['typography']['fontSizes'] as $size ) {
			if ( 'xx-large' === $size['slug'] ) {
				$xx_large = $size;
				break;
			}
		}

		$this->assertIsArray( $xx_large );
		$this->assertSame( '2.25rem', $xx_large['fluid']['min'] );
		$this->assertSame( '4rem', $xx_large['fluid']['max'] );
	}

	public function test_dusk_style_variation_is_registered() {
		$titles = wp_list_pluck( WP_Theme_JSON_Resolver::get_style_variations(), 'title' );

		$this->assertContains( 'Dusk', $titles );
		$this->assertContains( 'Dawn', $titles );
	}

	public function test_style_css_has_media_queries() {
		$css = file_get_contents( get_template_directory() . '/style.css' );

		$this->assertNotFalse( strpos( $css, '@media' ) );
		$this->assertNotFalse( strpos( $css, 'max-width: 600px' ) );
		$this->assertNotFalse( strpos( $css, 'is-menu-open' ) );
	}

	public function test_featured_rooms_pattern_uses_minimum_column_width() {
		$pattern = file_get_contents( get_template_directory() . '/patterns/featured-rooms.php' );

		$this->assertNotFalse( strpos( $pattern, 'minimumColumnWidth' ) );
	}

	public function test_stay_faq_block_is_registered() {
		$registry = WP_Block_Type_Registry::get_instance();
		$this->assertTrue( $registry->is_registered( 'hotel-booking-theme/stay-faq' ) );
	}

	public function test_stay_faq_block_renders_interactivity_root() {
		$html = do_blocks( '<!-- wp:hotel-booking-theme/stay-faq /-->' );

		$this->assertStringContainsString( 'data-wp-interactive="hotel-booking-theme/stay-faq"', $html );
		$this->assertStringContainsString( 'hb-stay-faq', $html );
		$this->assertStringContainsString( 'Check-in', $html );
	}

	public function test_color_scheme_toggle_block_is_registered() {
		$registry = WP_Block_Type_Registry::get_instance();
		$this->assertTrue( $registry->is_registered( 'hotel-booking-theme/color-scheme-toggle' ) );
	}

	public function test_color_scheme_toggle_renders_interactivity_root() {
		$html = do_blocks( '<!-- wp:hotel-booking-theme/color-scheme-toggle /-->' );

		$this->assertStringContainsString( 'data-wp-interactive="hotel-booking-theme/color-scheme"', $html );
		$this->assertStringContainsString( 'hb-color-scheme', $html );
		$this->assertStringContainsString( 'Use dark appearance', $html );
	}
}
