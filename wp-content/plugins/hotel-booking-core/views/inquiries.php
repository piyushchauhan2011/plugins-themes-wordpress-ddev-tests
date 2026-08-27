<?php
/**
 * Admin inquiries list.
 *
 * @package Hotel_Booking_Core
 *
 * @var object[] $inquiries Inquiry rows.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Inquiries', 'hotel-booking-core' ); ?></h1>
	<p><?php esc_html_e( 'Rows from the custom wp_hb_inquiries table. Status updates and deletes use the same admin-post handlers as the front-end desk.', 'hotel-booking-core' ); ?></p>

	<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- flash flag after a verified delete redirect. ?>
	<?php if ( isset( $_GET['hb_deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Inquiry deleted.', 'hotel-booking-core' ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! $inquiries ) : ?>
		<p><?php esc_html_e( 'No inquiries yet. Submit the booking form on the site to insert a row.', 'hotel-booking-core' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Guest', 'hotel-booking-core' ); ?></th>
					<th><?php esc_html_e( 'Stay', 'hotel-booking-core' ); ?></th>
					<th><?php esc_html_e( 'Guests', 'hotel-booking-core' ); ?></th>
					<th><?php esc_html_e( 'Status', 'hotel-booking-core' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'hotel-booking-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $inquiries as $row ) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html( $row->guest_name ); ?></strong><br>
							<?php echo esc_html( $row->guest_email ); ?>
							<?php if ( ! empty( $row->message ) ) : ?>
								<br><em><?php echo esc_html( $row->message ); ?></em>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $row->check_in . ' → ' . $row->check_out ); ?></td>
						<td><?php echo esc_html( (string) $row->guests ); ?></td>
						<td>
							<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
								<input type="hidden" name="action" value="hb_update_inquiry">
								<input type="hidden" name="inquiry_id" value="<?php echo esc_attr( (string) $row->id ); ?>">
								<?php wp_nonce_field( 'hb_update_inquiry', 'hb_update_nonce' ); ?>
								<select name="status">
									<?php foreach ( hotel_booking_inquiry_statuses() as $inquiry_status ) : ?>
										<option value="<?php echo esc_attr( $inquiry_status ); ?>" <?php selected( $row->status, $inquiry_status ); ?>><?php echo esc_html( $inquiry_status ); ?></option>
									<?php endforeach; ?>
								</select>
								<?php submit_button( __( 'Save', 'hotel-booking-core' ), 'secondary', 'submit', false ); ?>
							</form>
						</td>
						<td>
							<?php
							$delete_url = wp_nonce_url(
								admin_url( 'admin-post.php?action=hb_delete_inquiry&inquiry_id=' . (int) $row->id ),
								'hb_delete_inquiry_' . (int) $row->id
							);
							?>
							<a href="<?php echo esc_url( $delete_url ); ?>"><?php esc_html_e( 'Delete', 'hotel-booking-core' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
