<?php
/**
 * Front-end locale links (en_US / es_ES).
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$locales = function_exists( 'hotel_booking_visitor_locales' )
	? hotel_booking_visitor_locales()
	: array( 'en_US', 'es_ES' );

$current = determine_locale();
$labels  = array(
	'en_US' => 'English',
	'es_ES' => 'Español',
);

$wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'hb-language',
	)
);
?>
<nav <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?> aria-label="<?php echo esc_attr__( 'Language', 'hotel-booking' ); ?>">
	<?php foreach ( $locales as $code ) : ?>
		<?php
		$is_current = ( $code === $current );
		$label      = isset( $labels[ $code ] ) ? $labels[ $code ] : $code;
		?>
		<a
			class="hb-language__link<?php echo $is_current ? ' is-active' : ''; ?>"
			href="<?php echo esc_url( add_query_arg( 'lang', $code ) ); ?>"
			<?php echo $is_current ? ' aria-current="true"' : ''; ?>
			hreflang="<?php echo esc_attr( str_replace( '_', '-', $code ) ); ?>"
			lang="<?php echo esc_attr( str_replace( '_', '-', $code ) ); ?>"
		><?php echo esc_html( $label ); ?></a>
	<?php endforeach; ?>
</nav>
