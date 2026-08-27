<?php
/**
 * Inquiry form block.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wrapper = get_block_wrapper_attributes();
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<?php echo hotel_booking_render_inquiry_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugin HTML. ?>
</div>
