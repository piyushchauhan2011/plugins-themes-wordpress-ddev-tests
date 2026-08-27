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
 * Public inquiry form (theme PHP or block).
 *
 * @return string
 */
function hotel_booking_inquiry_form_shortcode() {
	return hotel_booking_render_inquiry_form();
}
add_shortcode( 'hotel_inquiry_form', 'hotel_booking_inquiry_form_shortcode' );

/**
 * Staff inquiry list (theme PHP or block). Visible to editors; others see a short note.
 *
 * @return string
 */
function hotel_booking_inquiry_list_shortcode() {
	return hotel_booking_render_inquiry_list();
}
add_shortcode( 'hotel_inquiry_list', 'hotel_booking_inquiry_list_shortcode' );

/**
 * Markup for the public inquiry form (shortcode + block).
 *
 * @return string
 */
function hotel_booking_render_inquiry_form() {
	if ( ! function_exists( 'hotel_booking_insert_inquiry' ) ) {
		return '<p>' . esc_html__( 'Activate Hotel Booking Core to collect inquiries.', 'hotel-booking-core' ) . '</p>';
	}

	hotel_booking_enqueue_block_front_assets( 'hotel-booking/inquiry-form' );

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

	$min_check_out = $prefill_in ? gmdate( 'Y-m-d', strtotime( $prefill_in . ' +1 day' ) ) : '';

	wp_interactivity_state(
		'hotel-booking/inquiry-form',
		array(
			'guests'      => $prefill_guests,
			'maxGuests'   => $max_guests,
			'checkIn'     => $prefill_in,
			'minCheckOut' => $min_check_out,
		)
	);

	ob_start();
	?>
	<div
		class="hb-inquiry"
		data-wp-interactive="hotel-booking/inquiry-form"
	>
		<?php if ( $inquiry_error ) : ?>
			<p class="hb-inquiry__notice hb-inquiry__notice--error" role="alert"><?php echo esc_html( $inquiry_error ); ?></p>
		<?php endif; ?>

		<?php if ( $saved ) : ?>
			<div class="hb-inquiry__notice hb-inquiry__notice--ok" role="status">
				<p><?php esc_html_e( 'The desk has your dates. This row is in the custom inquiries table.', 'hotel-booking-core' ); ?></p>
				<dl class="hb-room-meta">
					<div>
						<dt><?php esc_html_e( 'Name', 'hotel-booking-core' ); ?></dt>
						<dd><?php echo esc_html( $saved->guest_name ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Email', 'hotel-booking-core' ); ?></dt>
						<dd><?php echo esc_html( $saved->guest_email ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Stay', 'hotel-booking-core' ); ?></dt>
						<dd><?php echo esc_html( $saved->check_in . ' → ' . $saved->check_out ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Guests', 'hotel-booking-core' ); ?></dt>
						<dd><?php echo esc_html( (string) $saved->guests ); ?></dd>
					</div>
				</dl>
			</div>
		<?php endif; ?>

		<form class="hb-booking-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="hb_save_inquiry">
			<?php wp_nonce_field( 'hb_save_inquiry', 'hb_inquiry_nonce' ); ?>

			<label><?php esc_html_e( 'Name', 'hotel-booking-core' ); ?>
				<input type="text" name="guest_name" value="<?php echo esc_attr( $prefill_name ); ?>" required>
			</label>
			<label><?php esc_html_e( 'Email', 'hotel-booking-core' ); ?>
				<input type="email" name="guest_email" value="<?php echo esc_attr( $prefill_email ); ?>" required>
			</label>
			<label><?php esc_html_e( 'Check in', 'hotel-booking-core' ); ?>
				<input
					type="date"
					name="check_in"
					value="<?php echo esc_attr( $prefill_in ); ?>"
					data-wp-on--change="actions.setCheckIn"
					required
				>
			</label>
			<label><?php esc_html_e( 'Check out', 'hotel-booking-core' ); ?>
				<input
					type="date"
					name="check_out"
					value="<?php echo esc_attr( $prefill_out ); ?>"
					data-wp-bind--min="state.minCheckOut"
					required
				>
			</label>
			<label><?php esc_html_e( 'Guests', 'hotel-booking-core' ); ?>
				<span class="hb-guest-stepper">
					<button type="button" class="hb-guest-stepper__btn" data-wp-on--click="actions.decrementGuests" aria-label="<?php esc_attr_e( 'Fewer guests', 'hotel-booking-core' ); ?>">−</button>
					<select name="guests" data-wp-bind--value="state.guests" data-wp-on--change="actions.setGuestsFromSelect">
						<?php for ( $n = 1; $n <= $max_guests; $n++ ) : ?>
							<option value="<?php echo esc_attr( (string) $n ); ?>" <?php selected( $prefill_guests, $n ); ?>>
								<?php echo esc_html( (string) $n ); ?>
							</option>
						<?php endfor; ?>
					</select>
					<button type="button" class="hb-guest-stepper__btn" data-wp-on--click="actions.incrementGuests" aria-label="<?php esc_attr_e( 'More guests', 'hotel-booking-core' ); ?>">+</button>
				</span>
			</label>
			<label><?php esc_html_e( 'Room', 'hotel-booking-core' ); ?>
				<select name="room_id">
					<option value="0"><?php esc_html_e( 'No preference', 'hotel-booking-core' ); ?></option>
					<?php foreach ( $rooms as $room ) : ?>
						<option value="<?php echo esc_attr( (string) $room->ID ); ?>"><?php echo esc_html( get_the_title( $room ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label class="hb-booking-form__wide"><?php esc_html_e( 'Note', 'hotel-booking-core' ); ?>
				<textarea name="message" rows="3"></textarea>
			</label>
			<button type="submit"><?php esc_html_e( 'Send inquiry', 'hotel-booking-core' ); ?></button>
		</form>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Markup for the staff inquiry list (shortcode + block).
 *
 * @return string
 */
function hotel_booking_render_inquiry_list() {
	if ( ! function_exists( 'hotel_booking_get_inquiries' ) ) {
		return '<p>' . esc_html__( 'Activate Hotel Booking Core to list inquiries.', 'hotel-booking-core' ) . '</p>';
	}

	hotel_booking_enqueue_block_front_assets( 'hotel-booking/inquiry-list' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		return '<p>' . esc_html__( 'The desk book is for staff. Log in as an editor to read, update, and delete inquiries stored in the custom table.', 'hotel-booking-core' ) . '</p>';
	}

	$inquiries = hotel_booking_get_inquiries(
		array(
			'orderby' => 'created_at',
			'order'   => 'DESC',
			'limit'   => 100,
		)
	);

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- flash flag after a verified delete redirect.
	$deleted = isset( $_GET['hb_deleted'] );

	wp_interactivity_state(
		'hotel-booking/inquiry-list',
		array(
			'filter' => 'all',
		)
	);

	ob_start();
	?>
	<div class="hb-desk" data-wp-interactive="hotel-booking/inquiry-list">
		<?php if ( $deleted ) : ?>
			<p class="hb-inquiry__notice hb-inquiry__notice--ok" role="status"><?php esc_html_e( 'Inquiry deleted.', 'hotel-booking-core' ); ?></p>
		<?php endif; ?>

		<?php if ( ! $inquiries ) : ?>
			<p><?php esc_html_e( 'No inquiries yet. Submit the booking form to insert a row.', 'hotel-booking-core' ); ?></p>
		<?php else : ?>
			<div class="hb-desk__filters" role="group" aria-label="<?php esc_attr_e( 'Filter by status', 'hotel-booking-core' ); ?>">
				<button type="button" class="hb-desk__chip" data-wp-on--click="actions.filterAll" data-wp-class--is-active="state.isAll"><?php esc_html_e( 'All', 'hotel-booking-core' ); ?></button>
				<?php foreach ( hotel_booking_inquiry_statuses() as $inquiry_status ) : ?>
					<button
						type="button"
						class="hb-desk__chip"
						data-wp-on--click="actions.setFilter"
						<?php echo wp_interactivity_data_wp_context( array( 'status' => $inquiry_status ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						data-wp-class--is-active="state.isFilterActive"
					><?php echo esc_html( hotel_booking_inquiry_status_label( $inquiry_status ) ); ?></button>
				<?php endforeach; ?>
			</div>
			<div class="hb-desk__table-wrap">
			<table class="hb-desk__table">
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
						<tr
							<?php echo wp_interactivity_data_wp_context( array( 'rowStatus' => $row->status ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							data-wp-bind--hidden="state.rowHidden"
						>
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
										<?php foreach ( hotel_booking_inquiry_statuses() as $inquiry_status ) : ?>
											<option value="<?php echo esc_attr( $inquiry_status ); ?>" <?php selected( $row->status, $inquiry_status ); ?>><?php echo esc_html( hotel_booking_inquiry_status_label( $inquiry_status ) ); ?></option>
										<?php endforeach; ?>
									</select>
									<button type="submit"><?php esc_html_e( 'Save', 'hotel-booking-core' ); ?></button>
								</form>
								<?php
								$job_notes = hotel_booking_inquiry_job_notes( $row );
								if ( $job_notes ) :
									?>
									<br><small><?php echo esc_html( $job_notes ); ?></small>
								<?php endif; ?>
							</td>
							<td>
								<?php
								$delete_url = wp_nonce_url(
									admin_url( 'admin-post.php?action=hb_delete_inquiry&inquiry_id=' . (int) $row->id ),
									'hb_delete_inquiry_' . (int) $row->id
								);
								?>
								<a class="hb-desk__delete" href="<?php echo esc_url( $delete_url ); ?>"><?php esc_html_e( 'Delete', 'hotel-booking-core' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			</div>
		<?php endif; ?>
	</div>
	<?php

	return (string) ob_get_clean();
}
