<?php
/**
 * Light/dark color scheme toggle.
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_interactivity_state(
	'hotel-booking-theme/color-scheme',
	array(
		'scheme'     => 'light',
		'label'      => __( 'Use dark appearance', 'hotel-booking' ),
		'shortLabel' => __( 'Dark', 'hotel-booking' ),
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
		aria-label="<?php echo esc_attr__( 'Use dark appearance', 'hotel-booking' ); ?>"
		aria-pressed="false"
		data-wp-on--click="actions.toggle"
		data-wp-bind--aria-pressed="state.isDark"
		data-wp-bind--aria-label="state.label"
	>
		<span class="hb-color-scheme__icon" aria-hidden="true"></span>
		<span data-wp-text="state.shortLabel"><?php echo esc_html__( 'Dark', 'hotel-booking' ); ?></span>
	</button>
</div>
