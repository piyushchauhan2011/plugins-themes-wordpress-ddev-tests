<?php
/**
 * Stay FAQ accordion (theme Interactivity API).
 *
 * @package Hotel_Booking
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : array();
$clean = array();

$default_faq = array(
	'Check-in|Rooms are ready after 3pm. Leave bags at the desk if you arrive earlier.' => array(
		'title' => __( 'Check-in', 'hotel-booking' ),
		'text'  => __( 'Rooms are ready after 3pm. Leave bags at the desk if you arrive earlier.', 'hotel-booking' ),
	),
	'Quiet hours|After 10pm the house stays still. Breakfast is from 7:30.'            => array(
		'title' => __( 'Quiet hours', 'hotel-booking' ),
		'text'  => __( 'After 10pm the house stays still. Breakfast is from 7:30.', 'hotel-booking' ),
	),
	'Pets|Dogs by arrangement. Write to the desk when you request a stay.'             => array(
		'title' => __( 'Pets', 'hotel-booking' ),
		'text'  => __( 'Dogs by arrangement. Write to the desk when you request a stay.', 'hotel-booking' ),
	),
);

foreach ( $items as $item ) {
	if ( ! is_array( $item ) ) {
		continue;
	}
	$item_title = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
	$item_text  = isset( $item['text'] ) ? sanitize_textarea_field( $item['text'] ) : '';
	if ( '' === $item_title && '' === $item_text ) {
		continue;
	}

	$default_key = $item_title . '|' . $item_text;
	if ( isset( $default_faq[ $default_key ] ) ) {
		$item_title = $default_faq[ $default_key ]['title'];
		$item_text  = $default_faq[ $default_key ]['text'];
	}

	$clean[] = array(
		'title' => $item_title,
		'text'  => $item_text,
	);
}

wp_interactivity_state(
	'hotel-booking-theme/stay-faq',
	array(
		'openIndex' => 0,
	)
);

$wrapper = get_block_wrapper_attributes(
	array(
		'class'               => 'hb-stay-faq',
		'data-wp-interactive' => 'hotel-booking-theme/stay-faq',
	)
);
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<?php foreach ( $clean as $index => $item ) : ?>
		<div
			class="hb-stay-faq__item"
			<?php echo wp_interactivity_data_wp_context( array( 'index' => (int) $index ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		>
			<button
				type="button"
				class="hb-stay-faq__trigger"
				data-wp-on--click="actions.toggle"
				data-wp-bind--aria-expanded="state.isOpen"
			>
				<?php echo esc_html( $item['title'] ); ?>
			</button>
			<div class="hb-stay-faq__panel" data-wp-bind--hidden="state.isHidden">
				<p><?php echo esc_html( $item['text'] ); ?></p>
			</div>
		</div>
	<?php endforeach; ?>
</div>
