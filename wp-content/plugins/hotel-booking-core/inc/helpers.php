<?php
/**
 * Room helpers used by the shortcode and by WP_UnitTestCase.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Format a nightly rate for display.
 *
 * @param mixed $amount Numeric amount in USD.
 * @return string
 */
function hotel_booking_format_price( $amount ) {
	$amount = is_numeric( $amount ) ? (float) $amount : 0;

	return sprintf(
		/* translators: %s: formatted dollar amount */
		__( '$%s / night', 'hotel-booking-core' ),
		number_format_i18n( $amount, 0 )
	);
}

/**
 * Read room meta with defaults.
 *
 * @param int $post_id Room post ID.
 * @return array{price:int,guests:int,beds:int,size:int}
 */
function hotel_booking_get_room_meta( $post_id ) {
	return array(
		'price'  => (int) get_post_meta( $post_id, 'hb_price', true ),
		'guests' => (int) get_post_meta( $post_id, 'hb_guests', true ),
		'beds'   => (int) get_post_meta( $post_id, 'hb_beds', true ),
		'size'   => (int) get_post_meta( $post_id, 'hb_size', true ),
	);
}

/**
 * Render a definition list of room facts.
 *
 * @param int $post_id Room post ID.
 * @return string
 */
function hotel_booking_render_room_meta( $post_id ) {
	if ( 'hb_room' !== get_post_type( $post_id ) ) {
		return '';
	}

	$meta = hotel_booking_get_room_meta( $post_id );

	ob_start();
	?>
	<dl class="hb-room-meta">
		<div>
			<dt><?php esc_html_e( 'From', 'hotel-booking-core' ); ?></dt>
			<dd><?php echo esc_html( hotel_booking_format_price( $meta['price'] ) ); ?></dd>
		</div>
		<div>
			<dt><?php esc_html_e( 'Guests', 'hotel-booking-core' ); ?></dt>
			<dd><?php echo esc_html( (string) $meta['guests'] ); ?></dd>
		</div>
		<div>
			<dt><?php esc_html_e( 'Beds', 'hotel-booking-core' ); ?></dt>
			<dd><?php echo esc_html( (string) $meta['beds'] ); ?></dd>
		</div>
		<div>
			<dt><?php esc_html_e( 'Size', 'hotel-booking-core' ); ?></dt>
			<dd>
				<?php
				/* translators: %s: room size in square metres */
				echo esc_html( sprintf( __( '%s m²', 'hotel-booking-core' ), (string) $meta['size'] ) );
				?>
			</dd>
		</div>
	</dl>
	<?php

	return (string) ob_get_clean();
}

/**
 * REST/block payload for one published room.
 *
 * @param WP_Post $post Room post.
 * @return array<string, mixed>
 */
function hotel_booking_prepare_room_for_rest( WP_Post $post ) {
	$meta  = hotel_booking_get_room_meta( $post->ID );
	$thumb = get_post_thumbnail_id( $post );
	$image = $thumb ? (string) wp_get_attachment_image_url( $thumb, 'medium' ) : '';

	return array(
		'id'              => (int) $post->ID,
		'title'           => $post->post_title,
		'slug'            => $post->post_name,
		'excerpt'         => wp_strip_all_tags( (string) $post->post_excerpt ),
		'permalink'       => get_permalink( $post ),
		'price'           => $meta['price'],
		'price_formatted' => hotel_booking_format_price( $meta['price'] ),
		'guests'          => $meta['guests'],
		'beds'            => $meta['beds'],
		'size'            => $meta['size'],
		'featured_image'  => $image,
		'has_image'       => '' !== $image,
	);
}

/**
 * HTML for a room card (blocks + Interactivity each-loop).
 *
 * @param array<string, mixed> $room Payload from hotel_booking_prepare_room_for_rest().
 * @return string
 */
function hotel_booking_render_room_card_html( $room ) {
	$title     = isset( $room['title'] ) ? (string) $room['title'] : '';
	$excerpt   = isset( $room['excerpt'] ) ? (string) $room['excerpt'] : '';
	$permalink = isset( $room['permalink'] ) ? (string) $room['permalink'] : '';
	$price     = isset( $room['price_formatted'] ) ? (string) $room['price_formatted'] : '';
	$guests    = isset( $room['guests'] ) ? (string) $room['guests'] : '';
	$image     = isset( $room['featured_image'] ) ? (string) $room['featured_image'] : '';
	$has_image = ! empty( $room['has_image'] );

	ob_start();
	?>
	<article class="hb-room-card">
		<?php if ( $has_image ) : ?>
			<a class="hb-room-card__media" href="<?php echo esc_url( $permalink ); ?>">
				<img src="<?php echo esc_url( $image ); ?>" alt="" />
			</a>
		<?php endif; ?>
		<div class="hb-room-card__body">
			<h3 class="hb-room-card__title">
				<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
			</h3>
			<?php if ( $excerpt ) : ?>
				<p class="hb-room-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
			<?php endif; ?>
			<p class="hb-room-card__meta">
				<span><?php echo esc_html( $price ); ?></span>
				<span>
					<?php
					/* translators: %s: guest count */
					echo esc_html( sprintf( __( '%s guests', 'hotel-booking-core' ), $guests ) );
					?>
				</span>
			</p>
		</div>
	</article>
	<?php

	return (string) ob_get_clean();
}

/**
 * Published rooms for the rooms-grid block, same filter as the REST list.
 *
 * @param int $guests Minimum guest capacity; 0 means all.
 * @return array<int, array<string, mixed>>
 */
function hotel_booking_query_rooms_for_grid( $guests = 0 ) {
	$query_args = array(
		'post_type'      => 'hb_room',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	);

	$guests = absint( $guests );
	if ( $guests > 0 ) {
		$query_args['meta_query'] = array(
			array(
				'key'     => 'hb_guests',
				'value'   => $guests,
				'type'    => 'NUMERIC',
				'compare' => '>=',
			),
		);
	}

	$query = new WP_Query( $query_args );
	$rooms = array();

	foreach ( $query->posts as $post ) {
		$rooms[] = hotel_booking_prepare_room_for_rest( $post );
	}

	return $rooms;
}
