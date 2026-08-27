<?php
/**
 * Gutenberg blocks: category, registration, and the Stay pattern.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block directory names under build/.
 *
 * @return string[]
 */
function hotel_booking_block_slugs() {
	return array(
		'booking-cta',
		'room-card',
		'rooms-grid',
		'inquiry-form',
		'inquiry-list',
		'amenities',
	);
}

/**
 * Add a Hotel Booking category to the inserter.
 *
 * @param array $categories Existing categories.
 * @return array
 */
function hotel_booking_block_categories( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'hotel-booking',
			'title' => __( 'Hotel Booking', 'hotel-booking-core' ),
		)
	);

	return $categories;
}
add_filter( 'block_categories_all', 'hotel_booking_block_categories' );

/**
 * Enqueue a block's style and view module when a shortcode renders the same markup.
 *
 * @param string $block_name Block name, e.g. hotel-booking/inquiry-form.
 */
function hotel_booking_enqueue_block_front_assets( $block_name ) {
	$block = WP_Block_Type_Registry::get_instance()->get_registered( $block_name );
	if ( ! $block ) {
		return;
	}

	if ( ! empty( $block->style_handles ) ) {
		foreach ( $block->style_handles as $handle ) {
			wp_enqueue_style( $handle );
		}
	}

	if ( ! empty( $block->view_script_module_ids ) && function_exists( 'wp_enqueue_script_module' ) ) {
		foreach ( $block->view_script_module_ids as $module_id ) {
			wp_enqueue_script_module( $module_id );
		}
	}
}

/**
 * Register blocks from compiled metadata.
 */
function hotel_booking_register_blocks() {
	$build = HOTEL_BOOKING_CORE_PATH . 'build';

	foreach ( hotel_booking_block_slugs() as $slug ) {
		$dir = $build . '/' . $slug;
		if ( file_exists( $dir . '/block.json' ) ) {
			$block = register_block_type( $dir );
			if ( $block && ! empty( $block->editor_script_handles ) ) {
				foreach ( $block->editor_script_handles as $handle ) {
					wp_set_script_translations( $handle, 'hotel-booking-core', HOTEL_BOOKING_CORE_PATH . 'languages' );
				}
			}
		}
	}

	hotel_booking_register_stay_pattern();
}
add_action( 'init', 'hotel_booking_register_blocks' );

/**
 * Page-building pattern: CTA, grid, card, amenities, inquiry form.
 */
function hotel_booking_register_stay_pattern() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}

	register_block_pattern(
		'hotel-booking/stay-blocks',
		array(
			'title'       => __( 'Stay (blocks)', 'hotel-booking-core' ),
			'description' => __( 'Compose a stay page from Hotel Booking blocks.', 'hotel-booking-core' ),
			'categories'  => array( 'hotel-booking' ),
			'content'     => hotel_booking_stay_pattern_content(),
		)
	);
}

/**
 * Default Stay page markup.
 *
 * @return string
 */
function hotel_booking_stay_pattern_content() {
	$amenities = wp_json_encode(
		array(
			'items' => array(
				array(
					'title' => __( 'Breakfast', 'hotel-booking-core' ),
					'text'  => __( 'Served until ten in the dining room.', 'hotel-booking-core' ),
				),
				array(
					'title' => __( 'Garden', 'hotel-booking-core' ),
					'text'  => __( 'A quiet courtyard for late light.', 'hotel-booking-core' ),
				),
				array(
					'title' => __( 'Desk', 'hotel-booking-core' ),
					'text'  => __( 'Someone at the desk from seven until late.', 'hotel-booking-core' ),
				),
			),
		)
	);

	$cta = wp_json_encode(
		array(
			'heading'    => __( 'A quiet night, well kept.', 'hotel-booking-core' ),
			'buttonText' => __( 'Book a room', 'hotel-booking-core' ),
			'url'        => '/booking/',
		)
	);

	return '<!-- wp:paragraph --><p>' . esc_html__( 'Drop Hotel Booking blocks onto any page. This layout is a starting point: CTA, rooms, a featured card, amenities, and the inquiry form.', 'hotel-booking-core' ) . '</p><!-- /wp:paragraph -->'
		. '<!-- wp:hotel-booking/booking-cta ' . $cta . ' /-->'
		. '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html__( 'Rooms', 'hotel-booking-core' ) . '</h2><!-- /wp:heading -->'
		. '<!-- wp:hotel-booking/rooms-grid {"guests":0} /-->'
		. '<!-- wp:hotel-booking/room-card {"roomId":0} /-->'
		. '<!-- wp:hotel-booking/amenities ' . $amenities . ' /-->'
		. '<!-- wp:hotel-booking/inquiry-form /-->';
}
