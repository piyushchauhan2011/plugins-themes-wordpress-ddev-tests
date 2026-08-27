<?php
/**
 * OpenSearch query-body builders (no cluster required).
 *
 * @package Hotel_Booking
 */

/**
 * Mapping and DSL helpers used when OpenSearch is up; PHPUnit never needs the service.
 */
class Test_Hotel_Booking_OpenSearch extends WP_UnitTestCase {

	public function test_rooms_mapping_includes_suggest_locale_and_content() {
		$mapping    = hotel_booking_opensearch_rooms_mapping();
		$properties = $mapping['mappings']['properties'];

		$this->assertSame( 'text', $properties['title']['type'] );
		$this->assertSame( 'keyword', $properties['title']['fields']['raw']['type'] );
		$this->assertSame( 'completion', $properties['title']['fields']['suggest']['type'] );
		$this->assertSame( 'text', $properties['excerpt']['type'] );
		$this->assertSame( 'text', $properties['content']['type'] );
		$this->assertSame( 'integer', $properties['guests']['type'] );
		$this->assertSame( 'integer', $properties['price']['type'] );
		$this->assertSame( 'integer', $properties['beds']['type'] );
		$this->assertSame( 'keyword', $properties['locale']['type'] );
		$this->assertFalse( $properties['permalink']['index'] );
	}

	public function test_search_query_body_builds_filters_and_multi_match() {
		$body  = hotel_booking_opensearch_rooms_query_body(
			array(
				'q'         => 'garden',
				'guests'    => 4,
				'beds'      => 2,
				'price_min' => 200,
				'price_max' => 500,
				'lang'      => 'es',
			)
		);
		$query = $body['query']['bool'];

		$this->assertSame( 100, $body['size'] );
		$this->assertSame( 'garden', $query['must'][0]['multi_match']['query'] );
		$this->assertSame( array( 'title', 'excerpt', 'content' ), $query['must'][0]['multi_match']['fields'] );
		$this->assertSame( 4, $query['filter'][0]['range']['guests']['gte'] );
		$this->assertSame( 2, $query['filter'][1]['range']['beds']['gte'] );
		$this->assertSame( 200, $query['filter'][2]['range']['price']['gte'] );
		$this->assertSame( 500, $query['filter'][2]['range']['price']['lte'] );
		$this->assertCount( 3, $query['filter'] );
	}

	public function test_search_query_body_match_all_without_filters() {
		$body = hotel_booking_opensearch_rooms_query_body( array() );

		$this->assertArrayHasKey( 'match_all', $body['query'] );
		$this->assertSame( 'asc', $body['sort'][0]['title.raw'] );
	}

	public function test_suggest_body_uses_title_completion() {
		$body = hotel_booking_opensearch_suggest_body( 'gar' );

		$this->assertSame( 0, $body['size'] );
		$this->assertSame( 'gar', $body['suggest']['room-suggest']['prefix'] );
		$this->assertSame( 'title.suggest', $body['suggest']['room-suggest']['completion']['field'] );
	}

	public function test_room_document_defaults_locale_to_english() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'hb_room',
				'post_title'   => 'Garden Suite',
				'post_excerpt' => 'Opens onto the garden.',
				'post_status'  => 'publish',
				'meta_input'   => array(
					'hb_price'  => 360,
					'hb_guests' => 2,
					'hb_beds'   => 1,
					'hb_size'   => 48,
				),
			)
		);

		$doc = hotel_booking_opensearch_room_document( get_post( $post_id ) );

		$this->assertSame( $post_id, $doc['id'] );
		$this->assertSame( 'Garden Suite', $doc['title'] );
		$this->assertSame( 'en', $doc['locale'] );
		$this->assertSame( 360, $doc['price'] );
		$this->assertSame( 2, $doc['guests'] );
		$this->assertNotEmpty( $doc['permalink'] );
	}

	public function test_opensearch_is_unconfigured_in_phpunit() {
		$this->assertFalse( hotel_booking_opensearch_is_configured() );
		$this->assertWPError( hotel_booking_opensearch_search_rooms( array() ) );
	}
}
