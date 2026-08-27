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
	$meta   = hotel_booking_get_room_meta( $post->ID );
	$thumb  = (int) get_post_thumbnail_id( $post );
	$image  = '';
	$width  = 0;
	$height = 0;
	$alt    = '';

	if ( $thumb ) {
		$src = wp_get_attachment_image_src( $thumb, 'medium' );
		if ( is_array( $src ) ) {
			$image  = (string) $src[0];
			$width  = (int) $src[1];
			$height = (int) $src[2];
		}

		$alt = (string) get_post_meta( $thumb, '_wp_attachment_image_alt', true );
		if ( '' === $alt ) {
			$alt = $post->post_title;
		}
	}

	return array(
		'id'                => (int) $post->ID,
		'title'             => $post->post_title,
		'slug'              => $post->post_name,
		'excerpt'           => wp_strip_all_tags( (string) $post->post_excerpt ),
		'permalink'         => get_permalink( $post ),
		'price'             => $meta['price'],
		'price_formatted'   => hotel_booking_format_price( $meta['price'] ),
		'guests'            => $meta['guests'],
		'beds'              => $meta['beds'],
		'size'              => $meta['size'],
		'featured_image'    => $image,
		'featured_image_id' => $thumb,
		'image_width'       => $width,
		'image_height'      => $height,
		'image_alt'         => $alt,
		'has_image'         => '' !== $image,
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
	$thumb_id  = isset( $room['featured_image_id'] ) ? (int) $room['featured_image_id'] : 0;
	$width     = isset( $room['image_width'] ) ? (int) $room['image_width'] : 0;
	$height    = isset( $room['image_height'] ) ? (int) $room['image_height'] : 0;
	$alt       = isset( $room['image_alt'] ) ? (string) $room['image_alt'] : $title;
	$has_image = ! empty( $room['has_image'] );

	$image_html = '';
	if ( $thumb_id ) {
		$image_html = wp_get_attachment_image(
			$thumb_id,
			'medium',
			false,
			array(
				'alt'      => $alt,
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);
	}

	if ( '' === $image_html && '' !== $image ) {
		$image_html = sprintf(
			'<img src="%1$s" alt="%2$s"%3$s%4$s loading="lazy" decoding="async" />',
			esc_url( $image ),
			esc_attr( $alt ),
			$width > 0 ? ' width="' . esc_attr( (string) $width ) . '"' : '',
			$height > 0 ? ' height="' . esc_attr( (string) $height ) . '"' : ''
		);
	}

	ob_start();
	?>
	<article class="hb-room-card">
		<?php if ( $has_image && '' !== $image_html ) : ?>
			<a class="hb-room-card__media" href="<?php echo esc_url( $permalink ); ?>">
				<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() / escaped fallback. ?>
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
 * @param int    $guests Minimum guest capacity; 0 means all.
 * @param string $lang   Optional Polylang slug.
 * @return array<int, array<string, mixed>>
 */
function hotel_booking_query_rooms_for_grid( $guests = 0, $lang = '' ) {
	$query_args = array(
		'post_type'      => 'hb_room',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	);
	$query_args = hotel_booking_query_args_with_lang( $query_args, $lang );

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

/**
 * Normalize room search/filter arguments.
 *
 * @param mixed $args Raw args (REST, block, CLI).
 * @return array{q:string,guests:int,beds:int,price_min:int,price_max:int,lang:string}
 */
function hotel_booking_normalize_room_search_args( $args = array() ) {
	$args = is_array( $args ) ? $args : array();

	return array(
		'q'         => isset( $args['q'] ) ? sanitize_text_field( (string) $args['q'] ) : '',
		'guests'    => isset( $args['guests'] ) ? absint( $args['guests'] ) : 0,
		'beds'      => isset( $args['beds'] ) ? absint( $args['beds'] ) : 0,
		'price_min' => isset( $args['price_min'] ) ? absint( $args['price_min'] ) : 0,
		'price_max' => isset( $args['price_max'] ) ? absint( $args['price_max'] ) : 0,
		'lang'      => isset( $args['lang'] ) ? (string) $args['lang'] : '',
	);
}

/**
 * Published rooms via WP_Query (OpenSearch fallback and PHPUnit).
 *
 * @param array<string, mixed> $args Search args from hotel_booking_normalize_room_search_args().
 * @return array<int, array<string, mixed>>
 */
function hotel_booking_query_rooms_for_search( $args = array() ) {
	$args       = hotel_booking_normalize_room_search_args( $args );
	$query_args = array(
		'post_type'      => 'hb_room',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	);
	$query_args = hotel_booking_query_args_with_lang( $query_args, $args['lang'] );

	if ( '' !== $args['q'] ) {
		$query_args['s'] = $args['q'];
	}

	$meta_query = array();
	if ( $args['guests'] > 0 ) {
		$meta_query[] = array(
			'key'     => 'hb_guests',
			'value'   => $args['guests'],
			'type'    => 'NUMERIC',
			'compare' => '>=',
		);
	}
	if ( $args['beds'] > 0 ) {
		$meta_query[] = array(
			'key'     => 'hb_beds',
			'value'   => $args['beds'],
			'type'    => 'NUMERIC',
			'compare' => '>=',
		);
	}
	if ( $args['price_min'] > 0 ) {
		$meta_query[] = array(
			'key'     => 'hb_price',
			'value'   => $args['price_min'],
			'type'    => 'NUMERIC',
			'compare' => '>=',
		);
	}
	if ( $args['price_max'] > 0 ) {
		$meta_query[] = array(
			'key'     => 'hb_price',
			'value'   => $args['price_max'],
			'type'    => 'NUMERIC',
			'compare' => '<=',
		);
	}
	if ( count( $meta_query ) > 1 ) {
		$meta_query['relation'] = 'AND';
	}
	if ( $meta_query ) {
		$query_args['meta_query'] = $meta_query;
	}

	$query = new WP_Query( $query_args );
	$rooms = array();

	foreach ( $query->posts as $post ) {
		$rooms[] = hotel_booking_prepare_room_for_rest( $post );
	}

	return $rooms;
}

/**
 * Title suggestions via WP_Query (OpenSearch fallback).
 *
 * @param string $q    Prefix or search text.
 * @param string $lang Optional Polylang slug.
 * @return array<int, array{text:string,permalink:string}>
 */
function hotel_booking_query_room_suggestions( $q, $lang = '' ) {
	$q = sanitize_text_field( (string) $q );
	if ( '' === $q ) {
		return array();
	}

	$query_args = array(
		'post_type'              => 'hb_room',
		'post_status'            => 'publish',
		'posts_per_page'         => 8,
		's'                      => $q,
		'orderby'                => 'title',
		'order'                  => 'ASC',
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
	);
	$query_args = hotel_booking_query_args_with_lang( $query_args, $lang );

	$query       = new WP_Query( $query_args );
	$suggestions = array();

	foreach ( $query->posts as $post ) {
		$suggestions[] = array(
			'text'      => $post->post_title,
			'permalink' => (string) get_permalink( $post ),
		);
	}

	return $suggestions;
}
