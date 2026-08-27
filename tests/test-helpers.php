<?php
/**
 * Helper function tests.
 *
 * @package Hotel_Booking
 */

/**
 * Tests for plugin helpers in hotel-booking-core.
 */
class Test_Hotel_Booking_Helpers extends WP_UnitTestCase {

	public function test_format_price_uses_nightly_label() {
		$this->assertSame( '$280 / night', hotel_booking_format_price( 280 ) );
	}

	public function test_format_price_treats_non_numeric_as_zero() {
		$this->assertSame( '$0 / night', hotel_booking_format_price( 'free' ) );
	}

	public function test_jpeg_uploads_output_webp() {
		$formats = apply_filters( 'image_editor_output_format', array() );
		$this->assertSame( 'image/webp', $formats['image/jpeg'] );
	}

	public function test_room_card_image_includes_width_and_lazy_loading() {
		if ( ! function_exists( 'imagejpeg' ) ) {
			$this->markTestSkipped( 'GD is required to create a test JPEG.' );
		}

		$room_id = self::factory()->post->create(
			array(
				'post_type'   => 'hb_room',
				'post_title'  => 'Photo Suite',
				'post_status' => 'publish',
			)
		);

		$base = wp_tempnam( 'hb-test' );
		$tmp  = $base . '.jpg';
		$img  = imagecreatetruecolor( 80, 50 );
		imagejpeg( $img, $tmp, 90 );
		imagedestroy( $img );
		unlink( $base );

		$attachment_id = self::factory()->attachment->create_upload_object( $tmp, $room_id );
		unlink( $tmp );
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'Garden view' );
		set_post_thumbnail( $room_id, $attachment_id );

		$payload = hotel_booking_prepare_room_for_rest( get_post( $room_id ) );

		$this->assertTrue( $payload['has_image'] );
		$this->assertSame( 'Garden view', $payload['image_alt'] );
		$this->assertGreaterThan( 0, $payload['image_width'] );
		$this->assertGreaterThan( 0, $payload['image_height'] );

		$html = hotel_booking_render_room_card_html( $payload );
		$this->assertMatchesRegularExpression( '/width="\d+"/', $html );
		$this->assertMatchesRegularExpression( '/height="\d+"/', $html );
		$this->assertStringContainsString( 'loading="lazy"', $html );
		$this->assertStringContainsString( 'decoding="async"', $html );
		$this->assertStringContainsString( 'Garden view', $html );
	}
}
