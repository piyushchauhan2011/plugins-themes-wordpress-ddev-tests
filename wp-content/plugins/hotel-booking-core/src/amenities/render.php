<?php
/**
 * Amenities accordion.
 *
 * @package Hotel_Booking_Core
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : array();
$clean = array();

foreach ( $items as $item ) {
	if ( ! is_array( $item ) ) {
		continue;
	}
	$item_title = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
	$item_text  = isset( $item['text'] ) ? sanitize_textarea_field( $item['text'] ) : '';
	if ( '' === $item_title && '' === $item_text ) {
		continue;
	}
	$clean[] = array(
		'title' => $item_title,
		'text'  => $item_text,
	);
}

wp_interactivity_state(
	'hotel-booking/amenities',
	array(
		'openIndex' => 0,
	)
);

$wrapper = get_block_wrapper_attributes(
	array(
		'class'               => 'hb-amenities',
		'data-wp-interactive' => 'hotel-booking/amenities',
	)
);
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<?php foreach ( $clean as $index => $item ) : ?>
		<div
			class="hb-amenities__item"
			<?php echo wp_interactivity_data_wp_context( array( 'index' => (int) $index ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		>
			<button
				type="button"
				class="hb-amenities__trigger"
				data-wp-on--click="actions.toggle"
				data-wp-bind--aria-expanded="state.isOpen"
			>
				<?php echo esc_html( $item['title'] ); ?>
			</button>
			<div class="hb-amenities__panel" data-wp-bind--hidden="state.isHidden">
				<p><?php echo esc_html( $item['text'] ); ?></p>
			</div>
		</div>
	<?php endforeach; ?>
</div>
