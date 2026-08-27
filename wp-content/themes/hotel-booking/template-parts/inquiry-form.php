<?php
/**
 * Server-rendered inquiry form (theme PHP).
 *
 * Data and save logic live in Hotel Booking Core. This file is presentation.
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hotel_booking_insert_inquiry' ) ) {
	echo '<p>' . esc_html__( 'Activate Hotel Booking Core to collect inquiries.', 'hotel-booking' ) . '</p>';
	return;
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display-only query args after a redirect.
$prefill_name   = isset( $_GET['guest_name'] ) ? sanitize_text_field( wp_unslash( $_GET['guest_name'] ) ) : '';
$prefill_email  = isset( $_GET['guest_email'] ) ? sanitize_email( wp_unslash( $_GET['guest_email'] ) ) : '';
$prefill_in     = isset( $_GET['check_in'] ) ? sanitize_text_field( wp_unslash( $_GET['check_in'] ) ) : '';
$prefill_out    = isset( $_GET['check_out'] ) ? sanitize_text_field( wp_unslash( $_GET['check_out'] ) ) : '';
$prefill_guests = isset( $_GET['guests'] ) ? absint( $_GET['guests'] ) : 2;
$max_guests     = function_exists( 'hotel_booking_get_setting' ) ? (int) hotel_booking_get_setting( 'max_guests' ) : 8;
$max_guests     = min( 8, max( 1, $max_guests ) );
if ( $prefill_guests < 1 || $prefill_guests > $max_guests ) {
	$prefill_guests = min( 2, $max_guests );
}
$saved_id      = isset( $_GET['inquiry'] ) ? absint( $_GET['inquiry'] ) : 0;
$inquiry_error = isset( $_GET['hb_error'] ) ? sanitize_text_field( wp_unslash( $_GET['hb_error'] ) ) : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended
$saved = $saved_id ? hotel_booking_get_inquiry( $saved_id ) : null;

$rooms = get_posts(
	array(
		'post_type'      => 'hb_room',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'orderby'        => 'title',
		'order'          => 'ASC',
	)
);
?>

<div class="hb-inquiry">
	<?php if ( $inquiry_error ) : ?>
		<p class="hb-inquiry__notice hb-inquiry__notice--error" role="alert"><?php echo esc_html( $inquiry_error ); ?></p>
	<?php endif; ?>

	<?php if ( $saved ) : ?>
		<div class="hb-inquiry__notice hb-inquiry__notice--ok" role="status">
			<p><?php esc_html_e( 'The desk has your dates. This row is in the custom inquiries table.', 'hotel-booking' ); ?></p>
			<dl class="hb-room-meta">
				<div>
					<dt><?php esc_html_e( 'Name', 'hotel-booking' ); ?></dt>
					<dd><?php echo esc_html( $saved->guest_name ); ?></dd>
				</div>
				<div>
					<dt><?php esc_html_e( 'Email', 'hotel-booking' ); ?></dt>
					<dd><?php echo esc_html( $saved->guest_email ); ?></dd>
				</div>
				<div>
					<dt><?php esc_html_e( 'Stay', 'hotel-booking' ); ?></dt>
					<dd><?php echo esc_html( $saved->check_in . ' → ' . $saved->check_out ); ?></dd>
				</div>
				<div>
					<dt><?php esc_html_e( 'Guests', 'hotel-booking' ); ?></dt>
					<dd><?php echo esc_html( (string) $saved->guests ); ?></dd>
				</div>
			</dl>
		</div>
	<?php endif; ?>

	<form class="hb-booking-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<input type="hidden" name="action" value="hb_save_inquiry">
		<?php wp_nonce_field( 'hb_save_inquiry', 'hb_inquiry_nonce' ); ?>

		<label><?php esc_html_e( 'Name', 'hotel-booking' ); ?>
			<input type="text" name="guest_name" value="<?php echo esc_attr( $prefill_name ); ?>" required>
		</label>
		<label><?php esc_html_e( 'Email', 'hotel-booking' ); ?>
			<input type="email" name="guest_email" value="<?php echo esc_attr( $prefill_email ); ?>" required>
		</label>
		<label><?php esc_html_e( 'Check in', 'hotel-booking' ); ?>
			<input type="date" name="check_in" value="<?php echo esc_attr( $prefill_in ); ?>" required>
		</label>
		<label><?php esc_html_e( 'Check out', 'hotel-booking' ); ?>
			<input type="date" name="check_out" value="<?php echo esc_attr( $prefill_out ); ?>" required>
		</label>
		<label><?php esc_html_e( 'Guests', 'hotel-booking' ); ?>
			<select name="guests">
				<?php for ( $n = 1; $n <= $max_guests; $n++ ) : ?>
					<option value="<?php echo esc_attr( (string) $n ); ?>" <?php selected( $prefill_guests, $n ); ?>>
						<?php echo esc_html( (string) $n ); ?>
					</option>
				<?php endfor; ?>
			</select>
		</label>
		<label><?php esc_html_e( 'Room', 'hotel-booking' ); ?>
			<select name="room_id">
				<option value="0"><?php esc_html_e( 'No preference', 'hotel-booking' ); ?></option>
				<?php foreach ( $rooms as $room ) : ?>
					<option value="<?php echo esc_attr( (string) $room->ID ); ?>"><?php echo esc_html( get_the_title( $room ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label class="hb-booking-form__wide"><?php esc_html_e( 'Note', 'hotel-booking' ); ?>
			<textarea name="message" rows="3"></textarea>
		</label>
		<button type="submit"><?php esc_html_e( 'Send inquiry', 'hotel-booking' ); ?></button>
	</form>
</div>
