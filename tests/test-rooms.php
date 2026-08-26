<?php
/**
 * Room CPT and factory tests.
 *
 * @package Hotel_Booking
 */

/**
 * Shows how WP_UnitTestCase factories create posts and meta.
 */
class Test_Hotel_Booking_Rooms extends WP_UnitTestCase {

	public function test_room_post_type_is_registered() {
		$this->assertTrue( post_type_exists( 'hb_room' ) );

		$post_type = get_post_type_object( 'hb_room' );
		$this->assertTrue( $post_type->public );
		$this->assertTrue( $post_type->show_in_rest );
		$this->assertSame( 'rooms', $post_type->rewrite['slug'] );
	}

	public function test_factory_creates_room_with_meta() {
		$room_id = self::factory()->post->create(
			array(
				'post_type'   => 'hb_room',
				'post_title'  => 'Test Suite',
				'post_status' => 'publish',
				'meta_input'  => array(
					'hb_price'  => 199,
					'hb_guests' => 2,
					'hb_beds'   => 1,
					'hb_size'   => 30,
				),
			)
		);

		$this->assertSame( 'hb_room', get_post_type( $room_id ) );

		$meta = hotel_booking_get_room_meta( $room_id );
		$this->assertSame( 199, $meta['price'] );
		$this->assertSame( 2, $meta['guests'] );
		$this->assertSame( 1, $meta['beds'] );
		$this->assertSame( 30, $meta['size'] );

		$query = new WP_Query(
			array(
				'post_type'      => 'hb_room',
				'p'              => $room_id,
				'posts_per_page' => 1,
			)
		);

		$this->assertTrue( $query->have_posts() );
		$this->assertSame( 1, $query->found_posts );
	}

	public function test_room_meta_markup_includes_formatted_price() {
		$room_id = self::factory()->post->create(
			array(
				'post_type'  => 'hb_room',
				'post_title' => 'Price Check',
				'meta_input' => array(
					'hb_price'  => 150,
					'hb_guests' => 2,
					'hb_beds'   => 1,
					'hb_size'   => 24,
				),
			)
		);

		$html = hotel_booking_render_room_meta( $room_id );
		$this->assertStringContainsString( '$150 / night', $html );
		$this->assertStringContainsString( 'hb-room-meta', $html );
	}

	public function test_room_meta_is_empty_for_regular_posts() {
		$post_id = self::factory()->post->create();
		$this->assertSame( '', hotel_booking_render_room_meta( $post_id ) );
	}

	public function test_room_meta_shortcode_is_registered() {
		$this->assertTrue( shortcode_exists( 'hotel_room_meta' ) );
	}
}
