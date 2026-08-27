<?php
/**
 * Room search with typeahead and facets (Interactivity API).
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Public search query string.
$q         = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
$guests    = isset( $_GET['guests'] ) ? absint( wp_unslash( $_GET['guests'] ) ) : 0;
$beds      = isset( $_GET['beds'] ) ? absint( wp_unslash( $_GET['beds'] ) ) : 0;
$price_min = isset( $_GET['price_min'] ) ? absint( wp_unslash( $_GET['price_min'] ) ) : 0;
$price_max = isset( $_GET['price_max'] ) ? absint( wp_unslash( $_GET['price_max'] ) ) : 0;
// phpcs:enable WordPress.Security.NonceVerification.Recommended

$lang  = hotel_booking_query_lang();
$rooms = hotel_booking_search_rooms(
	array(
		'q'         => $q,
		'guests'    => $guests,
		'beds'      => $beds,
		'price_min' => $price_min,
		'price_max' => $price_max,
		'lang'      => $lang,
	)
);

wp_interactivity_state(
	'hotel-booking/room-search',
	array(
		'rooms'       => $rooms,
		'suggestions' => array(),
		'q'           => $q,
		'guests'      => $guests,
		'beds'        => $beds,
		'priceMin'    => $price_min,
		'priceMax'    => $price_max,
		'lang'        => $lang,
		'restUrl'     => rest_url( 'hotel-booking/v1/rooms' ),
		'suggestUrl'  => rest_url( 'hotel-booking/v1/rooms/suggest' ),
	)
);

$wrapper = get_block_wrapper_attributes(
	array(
		'class'               => 'hb-room-search',
		'data-wp-interactive' => 'hotel-booking/room-search',
	)
);
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<form class="hb-room-search__form" data-wp-on--submit="actions.search">
		<div class="hb-room-search__query">
			<label class="hb-room-search__label" for="hb-room-search-q"><?php esc_html_e( 'Search rooms', 'hotel-booking-core' ); ?></label>
			<input
				id="hb-room-search-q"
				class="hb-room-search__input"
				type="search"
				name="q"
				autocomplete="off"
				placeholder="<?php esc_attr_e( 'Garden, suite, courtyard…', 'hotel-booking-core' ); ?>"
				value="<?php echo esc_attr( $q ); ?>"
				data-wp-bind--value="state.q"
				data-wp-on--input="actions.onQueryInput"
			/>
			<ul class="hb-room-search__suggest" data-wp-bind--hidden="state.suggestionsHidden">
				<template data-wp-each--suggestion="state.suggestions" data-wp-each-key="context.suggestion.text">
					<li>
						<button type="button" data-wp-on--click="actions.chooseSuggestion" data-wp-text="context.suggestion.text"></button>
					</li>
				</template>
			</ul>
		</div>

		<div class="hb-room-search__facets">
			<label>
				<span><?php esc_html_e( 'Guests', 'hotel-booking-core' ); ?></span>
				<input type="number" min="0" name="guests" value="<?php echo esc_attr( (string) $guests ); ?>" data-wp-bind--value="state.guests" data-wp-on--input="actions.setGuests" />
			</label>
			<label>
				<span><?php esc_html_e( 'Beds', 'hotel-booking-core' ); ?></span>
				<input type="number" min="0" name="beds" value="<?php echo esc_attr( (string) $beds ); ?>" data-wp-bind--value="state.beds" data-wp-on--input="actions.setBeds" />
			</label>
			<label>
				<span><?php esc_html_e( 'Min price', 'hotel-booking-core' ); ?></span>
				<input type="number" min="0" name="price_min" value="<?php echo esc_attr( (string) $price_min ); ?>" data-wp-bind--value="state.priceMin" data-wp-on--input="actions.setPriceMin" />
			</label>
			<label>
				<span><?php esc_html_e( 'Max price', 'hotel-booking-core' ); ?></span>
				<input type="number" min="0" name="price_max" value="<?php echo esc_attr( (string) $price_max ); ?>" data-wp-bind--value="state.priceMax" data-wp-on--input="actions.setPriceMax" />
			</label>
			<button type="submit" class="hb-room-search__submit"><?php esc_html_e( 'Search', 'hotel-booking-core' ); ?></button>
		</div>
	</form>

	<p class="hb-room-search__empty" data-wp-bind--hidden="state.hasRooms"<?php echo $rooms ? ' hidden' : ''; ?>>
		<?php esc_html_e( 'No rooms match those filters.', 'hotel-booking-core' ); ?>
	</p>

	<div class="hb-room-search__list">
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
