<?php
/**
 * Server-rendered inquiry list (theme PHP).
 *
 * Markup lives in Hotel Booking Core so the block and shortcode share it.
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hotel_booking_render_inquiry_list' ) ) {
	echo '<p>' . esc_html__( 'Activate Hotel Booking Core to list inquiries.', 'hotel-booking' ) . '</p>';
	return;
}

echo hotel_booking_render_inquiry_list(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugin HTML, already escaped.
