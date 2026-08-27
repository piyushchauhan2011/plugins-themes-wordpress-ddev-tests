<?php
/**
 * Server-rendered inquiry list (theme PHP).
 *
 * Editors can update status and delete. Guests see a short closed-desk note.
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hotel_booking_get_inquiries' ) ) {
	echo '<p>' . esc_html__( 'Activate Hotel Booking Core to list inquiries.', 'hotel-booking' ) . '</p>';
	return;
}

if ( ! current_user_can( 'edit_posts' ) ) {
	echo '<p>' . esc_html__( 'The desk book is for staff. Log in as an editor to read, update, and delete inquiries stored in the custom table.', 'hotel-booking' ) . '</p>';
	return;
}

$inquiries = hotel_booking_get_inquiries(
	array(
		'orderby' => 'created_at',
		'order'   => 'DESC',
		'limit'   => 100,
	)
);

$deleted = isset( $_GET['hb_deleted'] );
?>

<div class="hb-desk">
	<?php if ( $deleted ) : ?>
		<p class="hb-inquiry__notice hb-inquiry__notice--ok" role="status"><?php esc_html_e( 'Inquiry deleted.', 'hotel-booking' ); ?></p>
	<?php endif; ?>

	<?php if ( ! $inquiries ) : ?>
		<p><?php esc_html_e( 'No inquiries yet. Submit the booking form to insert a row.', 'hotel-booking' ); ?></p>
	<?php else : ?>
		<div class="hb-desk__table-wrap">
		<table class="hb-desk__table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Guest', 'hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Stay', 'hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Guests', 'hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Status', 'hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'hotel-booking' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $inquiries as $row ) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html( $row->guest_name ); ?></strong><br>
							<?php echo esc_html( $row->guest_email ); ?>
							<?php if ( $row->message ) : ?>
								<br><em><?php echo esc_html( $row->message ); ?></em>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $row->check_in . ' → ' . $row->check_out ); ?></td>
						<td><?php echo esc_html( (string) $row->guests ); ?></td>
						<td>
							<form class="hb-desk__status" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
								<input type="hidden" name="action" value="hb_update_inquiry">
								<input type="hidden" name="inquiry_id" value="<?php echo esc_attr( (string) $row->id ); ?>">
								<?php wp_nonce_field( 'hb_update_inquiry', 'hb_update_nonce' ); ?>
								<select name="status">
									<?php foreach ( hotel_booking_inquiry_statuses() as $status ) : ?>
										<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $row->status, $status ); ?>><?php echo esc_html( $status ); ?></option>
									<?php endforeach; ?>
								</select>
								<button type="submit"><?php esc_html_e( 'Save', 'hotel-booking' ); ?></button>
							</form>
						</td>
						<td>
							<?php
							$delete_url = wp_nonce_url(
								admin_url( 'admin-post.php?action=hb_delete_inquiry&inquiry_id=' . (int) $row->id ),
								'hb_delete_inquiry_' . (int) $row->id
							);
							?>
							<a class="hb-desk__delete" href="<?php echo esc_url( $delete_url ); ?>"><?php esc_html_e( 'Delete', 'hotel-booking' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		</div>
	<?php endif; ?>
</div>
