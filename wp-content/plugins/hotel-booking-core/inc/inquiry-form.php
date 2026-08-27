<?php
/**
 * Form POST handlers and shortcodes that load theme PHP templates.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include a theme PHP template part if it exists.
 *
 * @param string $relative Path under the theme, e.g. template-parts/inquiry-form.php.
 * @param array  $args     Extracted as local variables in the template.
 */
function hotel_booking_load_theme_part( $relative, $args = array() ) {
	$template = locate_template( $relative );
	if ( ! $template ) {
		return;
	}

	if ( $args ) {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- theme template locals.
		extract( $args, EXTR_SKIP );
	}

	include $template;
}

/**
 * Handle public inquiry form POST.
 */
function hotel_booking_handle_save_inquiry() {
	if ( ! isset( $_POST['hb_inquiry_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hb_inquiry_nonce'] ) ), 'hb_save_inquiry' ) ) {
		wp_die( esc_html__( 'The inquiry form expired. Please try again.', 'hotel-booking-core' ), '', array( 'response' => 403 ) );
	}

	$result = hotel_booking_insert_inquiry( wp_unslash( $_POST ) );
	$dest   = wp_get_referer() ? wp_get_referer() : home_url( '/booking/' );
	$dest   = remove_query_arg( array( 'inquiry', 'hb_error' ), $dest );

	if ( is_wp_error( $result ) ) {
		wp_safe_redirect( add_query_arg( 'hb_error', rawurlencode( $result->get_error_message() ), $dest ) );
		exit;
	}

	wp_safe_redirect( add_query_arg( 'inquiry', (int) $result, $dest ) );
	exit;
}
add_action( 'admin_post_nopriv_hb_save_inquiry', 'hotel_booking_handle_save_inquiry' );
add_action( 'admin_post_hb_save_inquiry', 'hotel_booking_handle_save_inquiry' );

/**
 * Handle staff status updates.
 */
function hotel_booking_handle_update_inquiry() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You cannot update inquiries.', 'hotel-booking-core' ), '', array( 'response' => 403 ) );
	}

	if ( ! isset( $_POST['hb_update_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hb_update_nonce'] ) ), 'hb_update_inquiry' ) ) {
		wp_die( esc_html__( 'The update form expired. Please try again.', 'hotel-booking-core' ), '', array( 'response' => 403 ) );
	}

	$id     = isset( $_POST['inquiry_id'] ) ? absint( $_POST['inquiry_id'] ) : 0;
	$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'pending';
	hotel_booking_update_inquiry( $id, array( 'status' => $status ) );

	$dest = wp_get_referer() ? wp_get_referer() : home_url( '/desk/' );
	wp_safe_redirect( remove_query_arg( array( 'hb_deleted', 'hb_error' ), $dest ) );
	exit;
}
add_action( 'admin_post_hb_update_inquiry', 'hotel_booking_handle_update_inquiry' );

/**
 * Handle staff deletes.
 */
function hotel_booking_handle_delete_inquiry() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You cannot delete inquiries.', 'hotel-booking-core' ), '', array( 'response' => 403 ) );
	}

	$id = isset( $_GET['inquiry_id'] ) ? absint( $_GET['inquiry_id'] ) : 0;
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'hb_delete_inquiry_' . $id ) ) {
		wp_die( esc_html__( 'The delete link expired. Please try again.', 'hotel-booking-core' ), '', array( 'response' => 403 ) );
	}

	hotel_booking_delete_inquiry( $id );

	$dest = wp_get_referer() ? wp_get_referer() : home_url( '/desk/' );
	wp_safe_redirect( add_query_arg( 'hb_deleted', '1', $dest ) );
	exit;
}
add_action( 'admin_post_hb_delete_inquiry', 'hotel_booking_handle_delete_inquiry' );

/**
 * Public inquiry form (theme PHP).
 *
 * @return string
 */
function hotel_booking_inquiry_form_shortcode() {
	ob_start();
	hotel_booking_load_theme_part( 'template-parts/inquiry-form.php' );
	return (string) ob_get_clean();
}
add_shortcode( 'hotel_inquiry_form', 'hotel_booking_inquiry_form_shortcode' );

/**
 * Staff inquiry list (theme PHP). Visible to editors; others see a short note.
 *
 * @return string
 */
function hotel_booking_inquiry_list_shortcode() {
	ob_start();
	hotel_booking_load_theme_part( 'template-parts/inquiries-list.php' );
	return (string) ob_get_clean();
}
add_shortcode( 'hotel_inquiry_list', 'hotel_booking_inquiry_list_shortcode' );
