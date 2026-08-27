<?php
/**
 * Server-rendered inquiry form (theme PHP).
 *
 * Markup lives in Hotel Booking Core so the block and shortcode share it.
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hotel_booking_render_inquiry_form' ) ) {
	echo '<p>' . esc_html__( 'Activate Hotel Booking Core to collect inquiries.', 'hotel-booking' ) . '</p>';
	return;
}

echo hotel_booking_render_inquiry_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugin HTML, already escaped.
