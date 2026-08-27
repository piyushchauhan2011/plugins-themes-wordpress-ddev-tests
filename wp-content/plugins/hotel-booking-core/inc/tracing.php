<?php
/**
 * OpenTelemetry traces for Hotel Booking Core (OTLP HTTP → Tempo).
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load the OpenTelemetry SDK from project vendor/.
 *
 * @return bool
 */
function hotel_booking_otel_load_library() {
	if ( class_exists( '\OpenTelemetry\SDK\Trace\TracerProvider' ) ) {
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

	return class_exists( '\OpenTelemetry\SDK\Trace\TracerProvider' );
}

/**
 * Configured OTLP HTTP base (no /v1/traces suffix).
 *
 * @return string
 */
function hotel_booking_otel_endpoint() {
	if ( ! defined( 'WP_OTEL_ENDPOINT' ) || ! is_string( WP_OTEL_ENDPOINT ) ) {
		return '';
	}

	return rtrim( WP_OTEL_ENDPOINT, '/' );
}

/**
 * Tracer or null when Tempo/SDK is unset.
 *
 * @return \OpenTelemetry\API\Trace\TracerInterface|null
 */
function hotel_booking_tracer() {
	static $tracer = false;

	if ( false !== $tracer ) {
		return $tracer;
	}

	$tracer   = null;
	$endpoint = hotel_booking_otel_endpoint();
	$enabled  = '' !== $endpoint && hotel_booking_otel_load_library();
	$enabled  = (bool) apply_filters( 'hotel_booking_otel_enabled', $enabled );
	if ( ! $enabled ) {
		return null;
	}

	try {
		$resource = \OpenTelemetry\SDK\Resource\ResourceInfo::create(
			\OpenTelemetry\SDK\Common\Attribute\Attributes::create(
				array(
					\OpenTelemetry\SemConv\ResourceAttributes::SERVICE_NAME => 'hotel-booking',
				)
			)
		);

		$transport = ( new \OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory() )->create(
			$endpoint . '/v1/traces',
			'application/json'
		);
		$exporter  = new \OpenTelemetry\Contrib\Otlp\SpanExporter( $transport );
		$provider  = new \OpenTelemetry\SDK\Trace\TracerProvider(
			array(
				new \OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor( $exporter ),
			),
			null,
			$resource
		);

		$tracer = $provider->getTracer( 'hotel-booking-core', HOTEL_BOOKING_CORE_VERSION );
	} catch ( Throwable $e ) {
		hotel_booking_log( 'OTLP setup failed: ' . $e->getMessage() );
		$tracer = null;
	}

	return $tracer;
}

/**
 * Current trace id when a span is active.
 *
 * @return string
 */
function hotel_booking_current_trace_id() {
	if ( ! class_exists( '\OpenTelemetry\API\Trace\Span' ) ) {
		return '';
	}

	$context = \OpenTelemetry\API\Trace\Span::getCurrent()->getContext();
	if ( ! $context->isValid() ) {
		return '';
	}

	return $context->getTraceId();
}

/**
 * Mark the active span as an error (e.g. REST 404).
 *
 * @param string $message Status description.
 * @return void
 */
function hotel_booking_trace_error( $message ) {
	if ( ! class_exists( '\OpenTelemetry\API\Trace\Span' ) ) {
		return;
	}

	$span = \OpenTelemetry\API\Trace\Span::getCurrent();
	if ( ! $span->getContext()->isValid() ) {
		return;
	}

	$span->setStatus( \OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, (string) $message );
}

/**
 * Run $callback inside a named span. No-ops when tracing is off.
 *
 * @template T
 * @param string                $name       Span name.
 * @param callable(): T         $callback   Work.
 * @param array<string, scalar> $attributes Span attributes.
 * @return T
 */
function hotel_booking_trace( $name, callable $callback, $attributes = array() ) {
	$tracer = hotel_booking_tracer();
	if ( ! $tracer ) {
		return $callback();
	}

	$builder = $tracer->spanBuilder( (string) $name );
	foreach ( $attributes as $key => $value ) {
		$builder->setAttribute( (string) $key, $value );
	}

	$span  = $builder->startSpan();
	$scope = $span->activate();
	try {
		return $callback();
	} catch ( Throwable $e ) {
		$span->recordException( $e );
		$span->setStatus( \OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, $e->getMessage() );
		throw $e;
	} finally {
		$span->end();
		$scope->detach();
	}
}
