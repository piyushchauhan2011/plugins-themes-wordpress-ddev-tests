<?php
/**
 * Admin settings form.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = wp_parse_args( get_option( 'hotel_booking_settings', array() ), hotel_booking_default_settings() );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Hotel Booking Settings', 'hotel-booking-core' ); ?></h1>
	<form action="options.php" method="post">
		<?php settings_fields( 'hotel_booking' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="hotel_booking_hotel_name"><?php esc_html_e( 'Hotel name', 'hotel-booking-core' ); ?></label></th>
				<td>
					<input class="regular-text" type="text" id="hotel_booking_hotel_name" name="hotel_booking_settings[hotel_name]" value="<?php echo esc_attr( $settings['hotel_name'] ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="hotel_booking_desk_email"><?php esc_html_e( 'Desk email', 'hotel-booking-core' ); ?></label></th>
				<td>
					<input class="regular-text" type="email" id="hotel_booking_desk_email" name="hotel_booking_settings[desk_email]" value="<?php echo esc_attr( $settings['desk_email'] ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="hotel_booking_max_guests"><?php esc_html_e( 'Max guests per inquiry', 'hotel-booking-core' ); ?></label></th>
				<td>
					<input class="small-text" type="number" min="1" max="8" id="hotel_booking_max_guests" name="hotel_booking_settings[max_guests]" value="<?php echo esc_attr( (string) $settings['max_guests'] ); ?>">
					<p class="description"><?php esc_html_e( 'Limits the guest dropdown on the public booking form (1–8).', 'hotel-booking-core' ); ?></p>
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
