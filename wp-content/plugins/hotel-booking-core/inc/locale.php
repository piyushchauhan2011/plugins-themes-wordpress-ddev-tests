<?php
/**
 * Visitor locale cookie (Layer A gettext only).
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Locales the header switcher may request.
 *
 * @return string[]
 */
function hotel_booking_visitor_locales() {
	return array( 'en_US', 'es_ES' );
}

/**
 * Cookie name for the visitor locale.
 *
 * @return string
 */
function hotel_booking_locale_cookie_name() {
	return 'hotel_booking_locale';
}

/**
 * Return a supported locale or empty string.
 *
 * @param mixed $locale Raw locale.
 * @return string
 */
function hotel_booking_sanitize_visitor_locale( $locale ) {
	if ( ! is_string( $locale ) ) {
		return '';
	}

	$locale = str_replace( '-', '_', $locale );
	if ( in_array( $locale, hotel_booking_visitor_locales(), true ) ) {
		return $locale;
	}

	return '';
}

/**
 * Locale from ?lang= or the visitor cookie.
 *
 * @return string Empty when unset or invalid.
 */
function hotel_booking_requested_visitor_locale() {
	if ( isset( $_GET['lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public locale switch.
		$from_query = hotel_booking_sanitize_visitor_locale( wp_unslash( $_GET['lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' !== $from_query ) {
			return $from_query;
		}
	}

	$cookie = hotel_booking_locale_cookie_name();
	if ( isset( $_COOKIE[ $cookie ] ) ) {
		return hotel_booking_sanitize_visitor_locale( wp_unslash( $_COOKIE[ $cookie ] ) );
	}

	return '';
}

/**
 * Prefer the visitor cookie/query on the front; leave wp-admin on WPLANG / profile.
 *
 * @param string $locale Current locale.
 * @return string
 */
function hotel_booking_filter_determine_locale( $locale ) {
	if ( function_exists( 'hotel_booking_polylang_is_active' ) && hotel_booking_polylang_is_active() ) {
		return $locale;
	}

	if ( is_admin() && ! wp_doing_ajax() ) {
		return $locale;
	}

	$requested = hotel_booking_requested_visitor_locale();
	return '' !== $requested ? $requested : $locale;
}
add_filter( 'determine_locale', 'hotel_booking_filter_determine_locale' );

/**
 * Persist ?lang= in a cookie and drop the query arg.
 */
function hotel_booking_maybe_persist_visitor_locale() {
	if ( function_exists( 'hotel_booking_polylang_is_active' ) && hotel_booking_polylang_is_active() ) {
		return;
	}

	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}

	if ( ! isset( $_GET['lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$lang = hotel_booking_sanitize_visitor_locale( wp_unslash( $_GET['lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( '' === $lang ) {
		return;
	}

	$_COOKIE[ hotel_booking_locale_cookie_name() ] = $lang;

	if ( defined( 'WP_TESTS_DOMAIN' ) || headers_sent() ) {
		return;
	}

	$path   = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
	$domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';
	setcookie( hotel_booking_locale_cookie_name(), $lang, time() + YEAR_IN_SECONDS, $path, $domain, is_ssl(), true );

	wp_safe_redirect( remove_query_arg( 'lang' ) );
	exit;
}
add_action( 'plugins_loaded', 'hotel_booking_maybe_persist_visitor_locale', 0 );
