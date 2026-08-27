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

	public function test_language_switcher_block_is_registered() {
		$registry = WP_Block_Type_Registry::get_instance();
		$this->assertTrue( $registry->is_registered( 'hotel-booking-theme/language-switcher' ) );
	}

	public function test_language_switcher_renders_locale_links() {
		$html = do_blocks( '<!-- wp:hotel-booking-theme/language-switcher /-->' );

		$this->assertStringContainsString( 'hb-language', $html );
		$this->assertStringContainsString( 'lang=en_US', $html );
		$this->assertStringContainsString( 'lang=es_ES', $html );
		$this->assertStringContainsString( 'Español', $html );
	}

	public function test_heading_and_body_fonts_are_self_hosted() {
		$dir = get_template_directory() . '/assets/fonts';

		$this->assertFileExists( $dir . '/playfair-display-latin-600.woff2' );
		$this->assertFileExists( $dir . '/source-sans-3-latin-400.woff2' );
		$this->assertFileExists( $dir . '/source-sans-3-latin-600.woff2' );

		$json = json_decode( file_get_contents( get_template_directory() . '/theme.json' ), true );
		$this->assertSame( 'swap', $json['settings']['typography']['fontFamilies'][0]['fontFace'][0]['fontDisplay'] );
		$this->assertSame( '600', $json['settings']['typography']['fontFamilies'][0]['fontFace'][0]['fontWeight'] );
	}

	public function test_front_end_style_does_not_depend_on_google_fonts() {
		do_action( 'wp_enqueue_scripts' );

		$this->assertTrue( wp_style_is( 'hotel-booking-style', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'hotel-booking-fonts', 'registered' ) );
		$this->assertSame( array(), wp_styles()->registered['hotel-booking-style']->deps );
	}

	public function test_core_block_assets_load_separately() {
		$this->assertTrue( apply_filters( 'should_load_separate_core_block_assets', false ) );
	}

	public function test_front_emoji_assets_are_removed() {
		$this->assertFalse( has_action( 'wp_head', 'print_emoji_detection_script' ) );
		$this->assertFalse( has_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' ) );
	}
}
