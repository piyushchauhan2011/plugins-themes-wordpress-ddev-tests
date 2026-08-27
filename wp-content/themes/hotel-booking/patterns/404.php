<?php
/**
 * Title: 404 message
 * Slug: hotel-booking/404
 * Categories: hotel-booking
 * Inserter: no
 * Description: Heading, copy, and search for the 404 template.
 *
 * @package Hotel_Booking
 */
?>
<!-- wp:group {"tagName":"main","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl)">
	<!-- wp:heading {"level":1,"textAlign":"center"} -->
	<h1 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Page not found', 'hotel-booking' ); ?></h1>
	<!-- /wp:heading -->
	<!-- wp:paragraph {"align":"center"} -->
	<p class="has-text-align-center"><?php esc_html_e( 'That corridor does not lead anywhere. Try a search, or return to the lobby.', 'hotel-booking' ); ?></p>
	<!-- /wp:paragraph -->
	<?php
	echo '<!-- wp:search ' . wp_json_encode(
		array(
			'label'      => __( 'Search', 'hotel-booking' ),
			'showLabel'  => false,
			'buttonText' => __( 'Search', 'hotel-booking' ),
			'align'      => 'center',
		)
	) . ' /-->';
	?>
</main>
<!-- /wp:group -->
