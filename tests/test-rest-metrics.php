<?php
/**
 * REST API tests for /hotel-booking/v1/metrics.
 *
 * @package Hotel_Booking
 */

/**
 * Prometheus text from rest_do_request(); no Prometheus container.
 */
class Test_Hotel_Booking_Rest_Metrics extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		hotel_booking_install_inquiries_table();
	}

	public function tear_down() {
		hotel_booking_truncate_custom_tables();
		parent::tear_down();
	}

	/**
	 * @param array<string, mixed> $overrides Payload overrides.
	 * @return array<string, mixed>
	 */
	private function valid_payload( $overrides = array() ) {
		return array_merge(
			array(
				'guest_name'  => 'Metrics Guest',
				'guest_email' => 'metrics@example.com',
				'check_in'    => '2026-09-10',
				'check_out'   => '2026-09-12',
				'guests'      => 2,
				'room_id'     => 0,
				'message'     => 'Metrics probe.',
				'status'      => 'pending',
			),
			$overrides
		);
	}

	public function test_metrics_route_is_registered() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/hotel-booking/v1/metrics', $routes );
	}

	public function test_metrics_exposes_inquiry_counts_by_status() {
		hotel_booking_insert_inquiry( $this->valid_payload() );
		hotel_booking_insert_inquiry( $this->valid_payload( array( 'guest_email' => 'metrics2@example.com' ) ) );
		hotel_booking_insert_inquiry(
			$this->valid_payload(
				array(
					'guest_email' => 'contacted@example.com',
					'status'      => 'contacted',
				)
			)
		);

		$request  = new WP_REST_Request( 'GET', '/hotel-booking/v1/metrics' );
		$response = rest_do_request( $request );
		$body     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsString( $body );
		$this->assertStringContainsString( 'hotel_booking_inquiries{status="pending"} 2', $body );
		$this->assertStringContainsString( 'hotel_booking_inquiries{status="contacted"} 1', $body );
		$this->assertStringContainsString( 'hotel_booking_inquiries{status="closed"} 0', $body );
		$this->assertStringContainsString( 'hotel_booking_opensearch_up 0', $body );
	}

	public function test_metrics_pre_serve_returns_plain_text() {
		hotel_booking_insert_inquiry( $this->valid_payload() );

		$request  = new WP_REST_Request( 'GET', '/hotel-booking/v1/metrics' );
		$response = rest_do_request( $request );

		ob_start();
		$served = apply_filters( 'rest_pre_serve_request', false, $response, $request, rest_get_server() );
		$out    = ob_get_clean();

		$this->assertTrue( $served );
		$this->assertStringContainsString( 'hotel_booking_inquiries{status="pending"} 1', $out );
	}
}
