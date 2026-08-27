<?php
/**
 * Rooms grid with Interactivity guest filter.
 *
 * @package Hotel_Booking_Core
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$guests = isset( $attributes['guests'] ) ? absint( $attributes['guests'] ) : 0;
$lang   = hotel_booking_query_lang();
$rooms  = hotel_booking_query_rooms_for_grid( $guests, $lang );
$rest   = rest_url( 'hotel-booking/v1/rooms' );

wp_interactivity_state(
	'hotel-booking/rooms-grid',
	array(
		'rooms'   => $rooms,
		'guests'  => $guests,
		'lang'    => $lang,
		'restUrl' => $rest,
	)
);

$filters = array( 0, 1, 2, 4 );
$wrapper = get_block_wrapper_attributes(
	array(
		'class'               => 'hb-rooms-grid',
		'data-wp-interactive' => 'hotel-booking/rooms-grid',
	)
);
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<div class="hb-rooms-grid__filters" role="group" aria-label="<?php esc_attr_e( 'Filter rooms by guests', 'hotel-booking-core' ); ?>">
		<?php foreach ( $filters as $filter_guests ) : ?>
			<button
				type="button"
				class="hb-rooms-grid__chip"
				data-wp-on--click="actions.filterGuests"
				<?php echo wp_interactivity_data_wp_context( array( 'guests' => $filter_guests ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				data-wp-class--is-active="state.isFilterActive"
			>
				<?php
				if ( 0 === $filter_guests ) {
					esc_html_e( 'All', 'hotel-booking-core' );
				} else {
					/* translators: %d: minimum guest count */
					echo esc_html( sprintf( __( '%d+', 'hotel-booking-core' ), $filter_guests ) );
				}
				?>
			</button>
		<?php endforeach; ?>
	</div>

	<p class="hb-rooms-grid__empty" data-wp-bind--hidden="state.hasRooms">
		<?php esc_html_e( 'No rooms for that many guests.', 'hotel-booking-core' ); ?>
	</p>

	<div class="hb-rooms-grid__list">
		<template data-wp-each--room="state.rooms" data-wp-each-key="context.room.id">
			<article class="hb-room-card">
				<a class="hb-room-card__media" data-wp-bind--href="context.room.permalink" data-wp-bind--hidden="state.imageHidden">
					<img
						alt=""
						width="300"
						height="188"
						loading="lazy"
						decoding="async"
						data-wp-bind--src="context.room.featured_image"
						data-wp-bind--alt="context.room.image_alt"
						data-wp-bind--width="context.room.image_width"
						data-wp-bind--height="context.room.image_height"
					/>
				</a>
				<div class="hb-room-card__body">
					<h3 class="hb-room-card__title">
						<a data-wp-bind--href="context.room.permalink" data-wp-text="context.room.title"></a>
					</h3>
					<p class="hb-room-card__excerpt" data-wp-text="context.room.excerpt" data-wp-bind--hidden="state.excerptHidden"></p>
					<p class="hb-room-card__meta">
						<span data-wp-text="context.room.price_formatted"></span>
						<span data-wp-text="state.guestsLabel"></span>
					</p>
				</div>
			</article>
		</template>
	</div>
</div>
