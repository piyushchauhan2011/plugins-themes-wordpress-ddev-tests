#!/usr/bin/env php
<?php
/**
 * Fail CI when Clover line coverage is below .github/quality-thresholds.json.
 *
 * Usage: php bin/check-coverage.php [coverage/clover.xml]
 */

declare(strict_types=1);

$root        = dirname( __DIR__ );
$clover_path = $argv[1] ?? $root . '/coverage/clover.xml';
$thresholds  = json_decode( (string) file_get_contents( $root . '/.github/quality-thresholds.json' ), true );
if ( ! is_array( $thresholds ) ) {
	fwrite( STDERR, "Could not read .github/quality-thresholds.json\n" );
	exit( 1 );
}

$min = (float) ( $thresholds['coverage_min_percent'] ?? 0 );

if ( ! is_readable( $clover_path ) ) {
	fwrite( STDERR, "Clover file missing: {$clover_path}\n" );
	exit( 1 );
}

$xml = simplexml_load_file( $clover_path );
if ( false === $xml ) {
	fwrite( STDERR, "Clover file is not a project report: {$clover_path}\n" );
	exit( 1 );
}

$metrics_nodes = $xml->xpath( '/coverage/project/metrics' );
if ( empty( $metrics_nodes ) ) {
	fwrite( STDERR, "Clover file is not a project report: {$clover_path}\n" );
	exit( 1 );
}

$metrics    = $metrics_nodes[0];
$statements = (int) $metrics['statements'];
$covered    = (int) $metrics['coveredstatements'];
$percent    = $statements > 0 ? ( $covered / $statements ) * 100 : 0.0;
$rounded    = round( $percent, 2 );

echo "Line coverage: {$rounded}% ({$covered}/{$statements}). Floor: {$min}%.\n";

if ( $rounded + 0.001 < $min ) {
	fwrite( STDERR, "Coverage {$rounded}% is below the {$min}% floor in .github/quality-thresholds.json.\n" );
	exit( 1 );
}
