<?php
/**
 * RabbitMQ publish/consume via php-amqplib (project Composer, not a plugin package).
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load php-amqplib when WordPress did not already autoload project vendor/.
 *
 * @return bool
 */
function hotel_booking_amqp_load_library() {
	if ( class_exists( '\PhpAmqpLib\Connection\AMQPStreamConnection' ) ) {
		return true;
	}

	$candidates = array(
		ABSPATH . 'vendor/autoload.php',
		dirname( HOTEL_BOOKING_CORE_PATH, 3 ) . '/vendor/autoload.php',
	);

	foreach ( $candidates as $autoload ) {
		if ( is_readable( $autoload ) ) {
			require_once $autoload;
			break;
		}
	}

	return class_exists( '\PhpAmqpLib\Connection\AMQPStreamConnection' );
}

/**
 * Whether AMQP constants are set and php-amqplib is available.
 *
 * @return bool
 */
function hotel_booking_amqp_is_configured() {
	$host    = hotel_booking_amqp_setting( 'WP_AMQP_HOST', '' );
	$enabled = is_string( $host ) && '' !== $host && hotel_booking_service_host_up( (string) $host ) && hotel_booking_amqp_load_library();

	return (bool) apply_filters( 'hotel_booking_amqp_enabled', $enabled );
}

/**
 * Read an AMQP constant if it is set.
 *
 * @param string     $name     Constant name.
 * @param string|int $fallback Fallback.
 * @return string|int
 */
function hotel_booking_amqp_setting( $name, $fallback ) {
	if ( ! defined( $name ) ) {
		return $fallback;
	}

	$value = constant( $name );
	if ( null === $value || '' === $value ) {
		return $fallback;
	}

	return $value;
}

/**
 * Topic exchange and queue names.
 *
 * @return array{exchange:string,email:string,search:string}
 */
function hotel_booking_amqp_topology() {
	return array(
		'exchange' => 'hotel-booking',
		'email'    => 'hotel-booking.email',
		'search'   => 'hotel-booking.search',
	);
}

/**
 * Open a short-timeout AMQP connection, or null on failure.
 *
 * @return \PhpAmqpLib\Connection\AMQPStreamConnection|null
 */
function hotel_booking_amqp_connect() {
	if ( ! hotel_booking_amqp_is_configured() ) {
		return null;
	}

	$host  = hotel_booking_amqp_setting( 'WP_AMQP_HOST', '' );
	$port  = (int) hotel_booking_amqp_setting( 'WP_AMQP_PORT', 5672 );
	$user  = (string) hotel_booking_amqp_setting( 'WP_AMQP_USER', 'guest' );
	$pass  = (string) hotel_booking_amqp_setting( 'WP_AMQP_PASS', 'guest' );
	$vhost = (string) hotel_booking_amqp_setting( 'WP_AMQP_VHOST', '/' );
	if ( '' === $vhost ) {
		$vhost = '/';
	}
	if ( $port < 1 ) {
		$port = 5672;
	}

	try {
		return new \PhpAmqpLib\Connection\AMQPStreamConnection( $host, $port, $user, $pass, $vhost, false, 'AMQPLAIN', null, 'en_US', 2.0, 3.0 );
	} catch ( Exception $e ) {
		hotel_booking_log( 'AMQP connect failed: ' . $e->getMessage() );
		return null;
	}
}

/**
 * Declare exchange, queues, and bindings on a channel.
 *
 * @param \PhpAmqpLib\Channel\AMQPChannel $channel Channel.
 * @return void
 */
function hotel_booking_amqp_declare( $channel ) {
	$names = hotel_booking_amqp_topology();
	$channel->exchange_declare( $names['exchange'], 'topic', false, true, false );
	$channel->queue_declare( $names['email'], false, true, false, false );
	$channel->queue_declare( $names['search'], false, true, false, false );
	$channel->queue_bind( $names['email'], $names['exchange'], 'inquiry.created' );
	$channel->queue_bind( $names['email'], $names['exchange'], 'inquiry.remind' );
	$channel->queue_bind( $names['email'], $names['exchange'], 'desk.digest' );
	$channel->queue_bind( $names['search'], $names['exchange'], 'room.updated' );
	$channel->queue_bind( $names['search'], $names['exchange'], 'room.deleted' );
}

/**
 * Publish a small JSON payload. Returns false if the broker is down.
 *
 * @param string               $routing_key inquiry.created|inquiry.remind|desk.digest|room.updated|room.deleted.
 * @param array<string, mixed> $payload     Ids only.
 * @return bool
 */
function hotel_booking_amqp_publish( $routing_key, $payload ) {
	if ( ! hotel_booking_amqp_is_configured() ) {
		return false;
	}

	$encoded = wp_json_encode( $payload );
	if ( false === $encoded ) {
		return false;
	}

	$connection = hotel_booking_amqp_connect();
	if ( ! $connection ) {
		return false;
	}

	try {
		$channel = $connection->channel();
		hotel_booking_amqp_declare( $channel );
		$message = new \PhpAmqpLib\Message\AMQPMessage(
			$encoded,
			array(
				'content_type'  => 'application/json',
				'delivery_mode' => \PhpAmqpLib\Message\AMQPMessage::DELIVERY_MODE_PERSISTENT,
			)
		);
		$channel->basic_publish( $message, hotel_booking_amqp_topology()['exchange'], (string) $routing_key );
		$channel->close();
		$connection->close();
	} catch ( Exception $e ) {
		hotel_booking_log( 'AMQP publish failed: ' . $e->getMessage() );
		return false;
	}

	return true;
}

/**
 * Run one job from a consumed message.
 *
 * @param string $routing_key Routing key.
 * @param mixed  $payload     Decoded JSON.
 * @return void
 */
function hotel_booking_amqp_handle_message( $routing_key, $payload ) {
	if ( ! is_array( $payload ) ) {
		$payload = array();
	}

	switch ( $routing_key ) {
		case 'inquiry.created':
			hotel_booking_send_desk_inquiry_email( isset( $payload['inquiry_id'] ) ? (int) $payload['inquiry_id'] : 0 );
			break;
		case 'inquiry.remind':
			hotel_booking_send_stale_reminder_email( isset( $payload['inquiry_id'] ) ? (int) $payload['inquiry_id'] : 0 );
			break;
		case 'desk.digest':
			hotel_booking_send_desk_digest_email( isset( $payload['pending_count'] ) ? (int) $payload['pending_count'] : 0 );
			break;
		case 'room.updated':
			hotel_booking_opensearch_index_room( isset( $payload['room_id'] ) ? (int) $payload['room_id'] : 0 );
			break;
		case 'room.deleted':
			hotel_booking_opensearch_delete_room( isset( $payload['room_id'] ) ? (int) $payload['room_id'] : 0 );
			break;
	}
}

/**
 * Blocking consume of email and search queues.
 *
 * @return void
 */
function hotel_booking_amqp_consume() {
	$connection = hotel_booking_amqp_connect();
	if ( ! $connection ) {
		throw new RuntimeException( 'Could not connect to RabbitMQ.' );
	}

	$channel = $connection->channel();
	hotel_booking_amqp_declare( $channel );
	$channel->basic_qos( 0, 1, false );

	$callback = static function ( $msg ) {
		$routing = '';
		if ( is_object( $msg ) && method_exists( $msg, 'getRoutingKey' ) ) {
			$routing = (string) $msg->getRoutingKey();
		} elseif ( is_object( $msg ) && isset( $msg->delivery_info['routing_key'] ) ) {
			$routing = (string) $msg->delivery_info['routing_key'];
		}

		$body    = is_object( $msg ) && method_exists( $msg, 'getBody' ) ? $msg->getBody() : '';
		$decoded = json_decode( (string) $body, true );

		try {
			hotel_booking_amqp_handle_message( $routing, is_array( $decoded ) ? $decoded : array() );
			if ( is_object( $msg ) && method_exists( $msg, 'ack' ) ) {
				$msg->ack();
			}
		} catch ( Exception $e ) {
			if ( is_object( $msg ) && method_exists( $msg, 'nack' ) ) {
				$msg->nack( true );
			}
		}
	};

	$names = hotel_booking_amqp_topology();
	$channel->basic_consume( $names['email'], '', false, false, false, false, $callback );
	$channel->basic_consume( $names['search'], '', false, false, false, false, $callback );

	while ( $channel->is_consuming() ) {
		$channel->wait();
	}

	$channel->close();
	$connection->close();
}
