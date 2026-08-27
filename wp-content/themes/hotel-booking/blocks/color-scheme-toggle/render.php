<?php
/**
 * Light/dark color scheme toggle.
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$label_dark  = __( 'Use dark appearance', 'hotel-booking' );
$label_light = __( 'Use light appearance', 'hotel-booking' );
$short_dark  = __( 'Dark', 'hotel-booking' );
$short_light = __( 'Light', 'hotel-booking' );

wp_interactivity_state(
	'hotel-booking-theme/color-scheme',
	array(
		'scheme'     => 'light',
		'labelDark'  => $label_dark,
		'labelLight' => $label_light,
		'shortDark'  => $short_dark,
		'shortLight' => $short_light,
		'label'      => $label_dark,
		'shortLabel' => $short_dark,
	)
);

$wrapper = get_block_wrapper_attributes(
	array(
		'class'               => 'hb-color-scheme',
		'data-wp-interactive' => 'hotel-booking-theme/color-scheme',
	)
);
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<button
		type="button"
		class="hb-color-scheme__button"
		aria-label="<?php echo esc_attr( $label_dark ); ?>"
		aria-pressed="false"
		data-wp-on--click="actions.toggle"
		data-wp-bind--aria-pressed="state.isDark"
		data-wp-bind--aria-label="state.label"
	>
		<span class="hb-color-scheme__icon" aria-hidden="true"></span>
		<span data-wp-text="state.shortLabel"><?php echo esc_html( $short_dark ); ?></span>
	</button>
</div>
