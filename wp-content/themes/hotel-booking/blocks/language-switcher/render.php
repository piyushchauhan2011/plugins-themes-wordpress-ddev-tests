<?php
/**
 * Front-end locale links (en_US / es_ES).
 *
 * Polylang URLs when Layer B is active; cookie ?lang= otherwise.
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$labels = array(
	'en_US' => 'English',
	'es_ES' => 'Español',
	'en'    => 'English',
	'es'    => 'Español',
);

$links = array();

if ( function_exists( 'pll_the_languages' ) ) {
	$raw = pll_the_languages(
		array(
			'raw'           => 1,
			'hide_if_empty' => 0,
		)
	);
	if ( is_array( $raw ) ) {
		foreach ( $raw as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$slug        = isset( $item['slug'] ) ? (string) $item['slug'] : '';
			$lang_locale = isset( $item['locale'] ) ? (string) $item['locale'] : $slug;
			$links[]     = array(
				'href'       => isset( $item['url'] ) ? (string) $item['url'] : '',
				'label'      => isset( $labels[ $slug ] ) ? $labels[ $slug ] : ( isset( $labels[ $lang_locale ] ) ? $labels[ $lang_locale ] : $slug ),
				'is_current' => ! empty( $item['current_lang'] ),
				'hreflang'   => str_replace( '_', '-', $lang_locale ),
			);
		}
	}
}

if ( ! $links ) {
	$locales = function_exists( 'hotel_booking_visitor_locales' )
		? hotel_booking_visitor_locales()
		: array( 'en_US', 'es_ES' );
	$current = determine_locale();

	foreach ( $locales as $code ) {
		$links[] = array(
			'href'       => add_query_arg( 'lang', $code ),
			'label'      => isset( $labels[ $code ] ) ? $labels[ $code ] : $code,
			'is_current' => ( $code === $current ),
			'hreflang'   => str_replace( '_', '-', $code ),
		);
	}
}

$wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'hb-language',
	)
);
?>
<nav <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?> aria-label="<?php echo esc_attr__( 'Language', 'hotel-booking' ); ?>">
	<?php foreach ( $links as $nav_link ) : ?>
		<a
			class="hb-language__link<?php echo ! empty( $nav_link['is_current'] ) ? ' is-active' : ''; ?>"
			href="<?php echo esc_url( $nav_link['href'] ); ?>"
			<?php echo ! empty( $nav_link['is_current'] ) ? ' aria-current="true"' : ''; ?>
			hreflang="<?php echo esc_attr( $nav_link['hreflang'] ); ?>"
			lang="<?php echo esc_attr( $nav_link['hreflang'] ); ?>"
		><?php echo esc_html( $nav_link['label'] ); ?></a>
	<?php endforeach; ?>
</nav>
