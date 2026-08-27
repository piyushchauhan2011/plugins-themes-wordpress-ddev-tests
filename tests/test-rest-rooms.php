<?php
/**
 * REST API tests for /hotel-booking/v1/rooms.
 *
 * @package Hotel_Booking
 */

/**
 * Uses rest_do_request() so you can learn REST tests without a real HTTP client.
 */
class Test_Hotel_Booking_Rest_Rooms extends WP_UnitTestCase {

	/**
	 * Create a published room with meta.
	 *
	 * @param array $args Post args plus optional meta.
	 * @return int
	 */
	private function create_room( $args = array() ) {
		$meta = wp_parse_args(
			isset( $args['meta_input'] ) ? $args['meta_input'] : array(),
			array(
				'hb_price'  => 199,
				'hb_guests' => 2,
				'hb_beds'   => 1,
				'hb_size'   => 30,
			)
		);
		unset( $args['meta_input'] );

		$args = wp_parse_args(
			$args,
			array(
				'post_type'    => 'hb_room',
				'post_title'   => 'Test Suite',
				'post_status'  => 'publish',
				'post_excerpt' => 'A quiet room for tests.',
				'meta_input'   => $meta,
			)
		);

		return self::factory()->post->create( $args );
	}

	public function test_rooms_route_is_registered() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/hotel-booking/v1/rooms', $routes );
		$this->assertArrayHasKey( '/hotel-booking/v1/rooms/(?P<id>\\d+)', $routes );
	}

	public function test_list_rooms_returns_empty_array() {
		$request  = new WP_REST_Request( 'GET', '/hotel-booking/v1/rooms' );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array(), $response->get_data() );
	}

	public function test_list_rooms_includes_published_room_payload() {
		$room_id = $this->create_room(
			array(
				'post_title'   => 'Garden Suite',
				'post_excerpt' => 'Opens onto the garden.',
				'meta_input'   => array(
					'hb_price'  => 360,
					'hb_guests' => 2,
					'hb_beds'   => 1,
					'hb_size'   => 48,
				),
			)
		);

		$request  = new WP_REST_Request( 'GET', '/hotel-booking/v1/rooms' );
		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $data );
		$this->assertSame( $room_id, $data[0]['id'] );
		$this->assertSame( 'Garden Suite', $data[0]['title'] );
		$this->assertSame( 360, $data[0]['price'] );
		$this->assertSame( '$360 / night', $data[0]['price_formatted'] );
		$this->assertSame( 2, $data[0]['guests'] );
		$this->assertSame( 1, $data[0]['beds'] );
		$this->assertSame( 48, $data[0]['size'] );
		$this->assertSame( 'Opens onto the garden.', $data[0]['excerpt'] );
		$this->assertNotEmpty( $data[0]['permalink'] );
		$this->assertNotEmpty( $data[0]['slug'] );
		$this->assertArrayHasKey( 'featured_image', $data[0] );
		$this->assertArrayHasKey( 'featured_image_id', $data[0] );
		$this->assertArrayHasKey( 'image_width', $data[0] );
		$this->assertArrayHasKey( 'image_height', $data[0] );
		$this->assertArrayHasKey( 'image_alt', $data[0] );
		$this->assertArrayHasKey( 'has_image', $data[0] );
		$this->assertFalse( $data[0]['has_image'] );
		$this->assertSame( 0, $data[0]['image_width'] );
		$this->assertSame( 0, $data[0]['image_height'] );
	}

	public function test_list_rooms_filters_by_minimum_guests() {
		$this->create_room(
			array(
				'post_title' => 'Deluxe King',
				'meta_input' => array(
					'hb_price'  => 280,
					'hb_guests' => 2,
					'hb_beds'   => 1,
					'hb_size'   => 32,
				),
			)
		);
		$family_id = $this->create_room(
			array(
				'post_title' => 'Family Room',
				'meta_input' => array(
					'hb_price'  => 420,
					'hb_guests' => 4,
					'hb_beds'   => 3,
					'hb_size'   => 56,
				),
			)
		);

		$request = new WP_REST_Request( 'GET', '/hotel-booking/v1/rooms' );
		$request->set_param( 'guests', 4 );
		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $data );
		$this->assertSame( $family_id, $data[0]['id'] );
		$this->assertSame( 4, $data[0]['guests'] );
	}

	public function test_get_room_returns_single_payload() {
		$room_id = $this->create_room(
			array(
				'post_title' => 'Penthouse',
				'meta_input' => array(
					'hb_price'  => 540,
					'hb_guests' => 2,
					'hb_beds'   => 1,
					'hb_size'   => 70,
				),
			)
		);

		$request  = new WP_REST_Request( 'GET', '/hotel-booking/v1/rooms/' . $room_id );
		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $room_id, $data['id'] );
		$this->assertSame( 'Penthouse', $data['title'] );
		$this->assertSame( '$540 / night', $data['price_formatted'] );
	}

	public function test_get_room_unknown_id_returns_404() {
		$request  = new WP_REST_Request( 'GET', '/hotel-booking/v1/rooms/999999' );
		$response = rest_do_request( $request );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'hotel_booking_room_not_found', $response->get_data()['code'] );
	}

	public function test_get_room_regular_post_returns_404() {
		$post_id  = self::factory()->post->create();
		$request  = new WP_REST_Request( 'GET', '/hotel-booking/v1/rooms/' . $post_id );
		$response = rest_do_request( $request );

		$this->assertSame( 404, $response->get_status() );
	}

	public function test_draft_room_is_hidden() {
		$draft_id = $this->create_room(
			array(
				'post_title'  => 'Hidden Draft',
				'post_status' => 'draft',
			)
		);

		$list = rest_do_request( new WP_REST_Request( 'GET', '/hotel-booking/v1/rooms' ) );
		$this->assertSame( array(), $list->get_data() );

		$single = rest_do_request( new WP_REST_Request( 'GET', '/hotel-booking/v1/rooms/' . $draft_id ) );
		$this->assertSame( 404, $single->get_status() );
	}

	public function test_list_rooms_ignores_lang_without_polylang() {
		$this->create_room(
			array(
				'post_title' => 'Garden Suite',
			)
		);

		$request = new WP_REST_Request( 'GET', '/hotel-booking/v1/rooms' );
		$request->set_param( 'lang', 'es' );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $response->get_data() );
		$this->assertSame( 'Garden Suite', $response->get_data()[0]['title'] );
	}
}
