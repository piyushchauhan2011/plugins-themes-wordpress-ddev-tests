#!/usr/bin/env php
<?php
/**
 * Print a Markdown PR comment from Clover and PHPMD artifacts.
 *
 * Usage: php bin/pr-quality-comment.php
 */

declare(strict_types=1);

$root       = dirname( __DIR__ );
$clover     = $root . '/coverage/clover.xml';
$phpmd      = $root . '/phpmd-report.xml';
$thresholds = json_decode( (string) file_get_contents( $root . '/.github/quality-thresholds.json' ), true );
if ( ! is_array( $thresholds ) ) {
	$thresholds = array();
}

$min_coverage = (float) ( $thresholds['coverage_min_percent'] ?? 0 );
$cyclo_max    = (int) ( $thresholds['cyclomatic_complexity_max'] ?? 0 );
$npath_max    = (int) ( $thresholds['npath_complexity_max'] ?? 0 );
$method_max   = (int) ( $thresholds['excessive_method_length'] ?? 0 );
$class_max    = (int) ( $thresholds['excessive_class_complexity'] ?? 0 );

$coverage_line = '_Clover report was not uploaded (PHPUnit may have failed before coverage)._';
if ( is_readable( $clover ) ) {
	$xml           = simplexml_load_file( $clover );
	$metrics_nodes = ( false !== $xml ) ? $xml->xpath( '/coverage/project/metrics' ) : array();
	if ( ! empty( $metrics_nodes ) ) {
		$metrics    = $metrics_nodes[0];
		$statements = (int) $metrics['statements'];
		$covered    = (int) $metrics['coveredstatements'];
		$percent    = $statements > 0 ? round( ( $covered / $statements ) * 100, 2 ) : 0.0;
		$ok         = $percent + 0.001 >= $min_coverage;
		$badge      = $ok ? 'pass' : 'fail';
		$coverage_line = "{$percent}% ({$covered}/{$statements} statements) — floor **{$min_coverage}%** ({$badge}).";
	}
}

$phpmd_lines = array();
if ( is_readable( $phpmd ) ) {
	$pmd = simplexml_load_file( $phpmd );
	if ( false !== $pmd ) {
		foreach ( $pmd->file ?? array() as $file ) {
			$name = basename( (string) $file['name'] );
			foreach ( $file->violation ?? array() as $violation ) {
				$rule = (string) $violation['rule'];
				$line = (string) $violation['beginline'];
				$text = trim( (string) preg_replace( '/\s+/', ' ', (string) $violation ) );
				$phpmd_lines[] = "- `{$name}:{$line}` **{$rule}** — {$text}";
			}
		}
	}
}

$phpmd_body = $phpmd_lines
	? implode( "\n", $phpmd_lines )
	: ( is_readable( $phpmd ) ? 'No codesize violations.' : '_PHPMD report was not uploaded._' );

echo <<<MD
## Hotel Booking PR quality

| Check | Result |
| --- | --- |
| Line coverage | {$coverage_line} |
| Cyclomatic max | {$cyclo_max} |
| NPath max | {$npath_max} |
| Method length max | {$method_max} |
| Class complexity max | {$class_max} |

HTML coverage is a workflow artifact named **coverage-html**. Thresholds live in `.github/quality-thresholds.json`.

### Complexity (PHPMD)

{$phpmd_body}

MD;
