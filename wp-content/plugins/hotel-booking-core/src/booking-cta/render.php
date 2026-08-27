<?php
/**
 * Booking CTA front-end.
 *
 * @package Hotel_Booking_Core
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$heading     = isset( $attributes['heading'] ) ? $attributes['heading'] : '';
$button_text = isset( $attributes['buttonText'] ) ? $attributes['buttonText'] : '';
$url         = isset( $attributes['url'] ) ? $attributes['url'] : '/booking/';
$wrapper     = get_block_wrapper_attributes( array( 'class' => 'hb-booking-cta' ) );
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<p class="hb-booking-cta__heading"><?php echo wp_kses_post( $heading ); ?></p>
	<?php if ( $button_text && $url ) : ?>
		<a class="hb-booking-cta__button" href="<?php echo esc_url( $url ); ?>"><?php echo wp_kses_post( $button_text ); ?></a>
	<?php endif; ?>
</div>
