<?php
/**
 * Hotel Booking theme bootstrap.
 *
 * Presentation plus theme-native Interactivity blocks (Stay FAQ, color scheme, language switcher).
 * Rooms CPT and shortcodes live in the Hotel Booking Core plugin.
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HOTEL_BOOKING_VERSION', '1.2.0' );

require_once get_template_directory() . '/inc/patterns.php';

/**
 * Theme setup.
 */
function hotel_booking_setup() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
	hotel_booking_load_textdomain();
}
add_action( 'after_setup_theme', 'hotel_booking_setup' );

/**
 * Load theme translations from languages/ (needed for WP 6.7+ just-in-time loading).
 */
function hotel_booking_load_textdomain() {
	$languages = get_template_directory() . '/languages';
	load_theme_textdomain( 'hotel-booking', $languages );

	$mofile = $languages . '/hotel-booking-' . determine_locale() . '.mo';
	if ( is_readable( $mofile ) ) {
		load_textdomain( 'hotel-booking', $mofile );
	}
}

/**
 * Register theme Gutenberg blocks (unbundled view modules, no webpack).
 */
function hotel_booking_register_theme_blocks() {
	$blocks = glob( get_template_directory() . '/blocks/*/block.json' );

	if ( ! $blocks ) {
		return;
	}

	foreach ( $blocks as $block_json ) {
		register_block_type( dirname( $block_json ) );
	}
}
add_action( 'init', 'hotel_booking_register_theme_blocks' );

/**
 * Default light scheme on <html> when JavaScript is off.
 *
 * @param string $output Language attributes.
 * @return string
 */
function hotel_booking_html_color_scheme( $output ) {
	if ( false === strpos( $output, 'data-color-scheme' ) ) {
		$output .= ' data-color-scheme="light"';
	}

	return $output;
}
add_filter( 'language_attributes', 'hotel_booking_html_color_scheme' );

/**
 * Apply stored or system color scheme before first paint.
 */
function hotel_booking_color_scheme_boot() {
	$script = <<<'JS'
(function () {
	var k = 'hotel-booking-color-scheme';
	var s;
	try {
		s = localStorage.getItem( k );
	} catch ( e ) {
		s = null;
	}
	if ( s !== 'dark' && s !== 'light' ) {
		s = window.matchMedia( '(prefers-color-scheme: dark)' ).matches ? 'dark' : 'light';
	}
	document.documentElement.setAttribute( 'data-color-scheme', s );
})();
JS;

	wp_print_inline_script_tag( $script );
}
add_action( 'wp_head', 'hotel_booking_color_scheme_boot', 0 );

/**
 * Preload the heading font used by the home H1 (LCP).
 */
function hotel_booking_preload_heading_font() {
	$href = get_template_directory_uri() . '/assets/fonts/playfair-display-latin-600.woff2';
	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( $href )
	);
}
add_action( 'wp_head', 'hotel_booking_preload_heading_font', 1 );

/**
 * Front-end styles (fonts are self-hosted via theme.json fontFace).
 */
function hotel_booking_enqueue_assets() {
	wp_enqueue_style(
		'hotel-booking-style',
		get_stylesheet_uri(),
		array(),
		HOTEL_BOOKING_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'hotel_booking_enqueue_assets' );

/**
 * Load only the core block CSS used on the page.
 */
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

/**
 * Drop emoji detection script and styles on the front.
 */
function hotel_booking_disable_emoji() {
	if ( is_admin() ) {
		return;
	}

	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'hotel_booking_disable_emoji' );

/**
 * On the home hero, do not give below-fold room thumbs fetchpriority=high.
 *
 * @param array  $loading_attrs Loading optimization attributes.
 * @param string $tag_name      Element tag.
 * @return array
 */
function hotel_booking_front_page_image_priority( $loading_attrs, $tag_name ) {
	if ( 'img' !== $tag_name || ! is_front_page() ) {
		return $loading_attrs;
	}

	if ( isset( $loading_attrs['fetchpriority'] ) ) {
		unset( $loading_attrs['fetchpriority'] );
	}

	$loading_attrs['loading'] = 'lazy';

	return $loading_attrs;
}
add_filter( 'wp_get_loading_optimization_attributes', 'hotel_booking_front_page_image_priority', 10, 2 );

/**
 * Admin notice when the companion plugin is missing.
 */
function hotel_booking_admin_notice_missing_plugin() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	if ( function_exists( 'hotel_booking_register_room_post_type' ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p>';
	echo esc_html__( 'Hotel Booking needs the Hotel Booking Core plugin for room listings and the booking inquiry shortcode.', 'hotel-booking' );
	echo '</p></div>';
}
add_action( 'admin_notices', 'hotel_booking_admin_notice_missing_plugin' );
