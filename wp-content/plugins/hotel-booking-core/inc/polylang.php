<?php
/**
 * Polylang (Layer B): rooms as translatable posts and query language.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether free Polylang is loaded.
 *
 * @return bool
 */
function hotel_booking_polylang_is_active() {
	return function_exists( 'pll_current_language' );
}

/**
 * Register hb_room and navigation menus as translatable post types.
 *
 * @param array<string, string> $post_types Post types Polylang knows.
 * @param bool                  $is_settings Settings screen vs front.
 * @return array<string, string>
 */
function hotel_booking_pll_post_types( $post_types, $is_settings = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	$post_types['hb_room']       = 'hb_room';
	$post_types['wp_navigation'] = 'wp_navigation';

	return $post_types;
}
add_filter( 'pll_get_post_types', 'hotel_booking_pll_post_types', 10, 2 );

/**
 * Polylang language slug for the current request, or a REST `lang` value.
 *
 * @param mixed $requested Optional slug from a REST argument.
 * @return string Empty when Polylang is off or the slug is unknown.
 */
function hotel_booking_query_lang( $requested = '' ) {
	if ( ! hotel_booking_polylang_is_active() ) {
		return '';
	}

	$requested = is_string( $requested ) ? sanitize_key( $requested ) : '';
	if ( '' !== $requested && function_exists( 'pll_languages_list' ) ) {
		$list = pll_languages_list();
		if ( is_array( $list ) && in_array( $requested, $list, true ) ) {
			return $requested;
		}
	}

	$current = pll_current_language();

	return is_string( $current ) ? $current : '';
}

/**
 * Add a Polylang `lang` clause to a room WP_Query when a slug is known.
 *
 * @param array<string, mixed> $query_args Query args.
 * @param string               $lang       Language slug.
 * @return array<string, mixed>
 */
function hotel_booking_query_args_with_lang( $query_args, $lang = '' ) {
	$lang = hotel_booking_query_lang( $lang );
	if ( '' !== $lang ) {
		$query_args['lang'] = $lang;
	}

	return $query_args;
}
