<?php
/**
 * Room card front-end.
 *
 * @package Hotel_Booking_Core
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$room_id   = isset( $attributes['roomId'] ) ? absint( $attributes['roomId'] ) : 0;
$room_post = null;

if ( $room_id ) {
	$candidate = get_post( $room_id );
	if ( $candidate && 'hb_room' === $candidate->post_type && 'publish' === $candidate->post_status ) {
		$room_post = $candidate;
	}
} else {
	$latest    = get_posts(
		array(
			'post_type'      => 'hb_room',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
	$room_post = $latest ? $latest[0] : null;
}

$wrapper = get_block_wrapper_attributes( array( 'class' => 'hb-room-card-block' ) );
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<?php
	if ( ! $room_post ) {
		echo '<p>' . esc_html__( 'No published rooms yet.', 'hotel-booking-core' ) . '</p>';
	} else {
		echo hotel_booking_render_room_card_html( hotel_booking_prepare_room_for_rest( $room_post ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- helper escapes.
	}
	?>
</div>
