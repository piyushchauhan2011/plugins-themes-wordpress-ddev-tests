<?php
/**
 * Gutenberg block registration tests.
 *
 * @package Hotel_Booking
 */

/**
 * Blocks are compiled into hotel-booking-core/build/.
 */
class Test_Hotel_Booking_Blocks extends WP_UnitTestCase {

	public function test_block_category_is_registered() {
		$categories = apply_filters( 'block_categories_all', array(), new WP_Block_Editor_Context() );
		$slugs      = wp_list_pluck( $categories, 'slug' );
		$this->assertContains( 'hotel-booking', $slugs );
	}

	public function test_hotel_booking_blocks_are_registered() {
		$registry = WP_Block_Type_Registry::get_instance();
		foreach ( hotel_booking_block_slugs() as $slug ) {
			$this->assertTrue(
				$registry->is_registered( 'hotel-booking/' . $slug ),
				'Expected block hotel-booking/' . $slug
			);
		}
	}

	public function test_stay_pattern_is_registered() {
		$patterns = WP_Block_Patterns_Registry::get_instance();
		$this->assertTrue( $patterns->is_registered( 'hotel-booking/stay-blocks' ) );
	}

	public function test_room_card_renders_published_room() {
		$room_id = self::factory()->post->create(
			array(
				'post_type'    => 'hb_room',
				'post_title'   => 'Garden Suite',
				'post_status'  => 'publish',
				'post_excerpt' => 'Opens onto the garden.',
				'meta_input'   => array(
					'hb_price'  => 360,
					'hb_guests' => 2,
					'hb_beds'   => 1,
					'hb_size'   => 48,
				),
			)
		);

		$html = do_blocks( '<!-- wp:hotel-booking/room-card {"roomId":' . (int) $room_id . '} /-->' );
		$this->assertStringContainsString( 'Garden Suite', $html );
		$this->assertStringContainsString( 'hb-room-card', $html );
		$this->assertStringContainsString( '$360 / night', $html );
	}

	public function test_inquiry_shortcode_still_renders_form() {
		$html = do_shortcode( '[hotel_inquiry_form]' );
		$this->assertStringContainsString( 'hb_save_inquiry', $html );
		$this->assertStringContainsString( 'name="guest_name"', $html );
	}
}
