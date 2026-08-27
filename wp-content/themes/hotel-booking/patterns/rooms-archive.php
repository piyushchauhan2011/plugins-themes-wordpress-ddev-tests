<?php
/**
 * Title: Rooms archive
 * Slug: hotel-booking/rooms-archive
 * Categories: hotel-booking
 * Inserter: no
 * Description: Rooms archive heading, intro, and query loop.
 *
 * @package Hotel_Booking
 */
?>
<!-- wp:group {"tagName":"main","style":{"spacing":{"padding":{"top":"var:preset|spacing|lg","bottom":"var:preset|spacing|lg"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group" style="padding-top:var(--wp--preset--spacing--lg);padding-bottom:var(--wp--preset--spacing--lg)">
	<!-- wp:heading {"level":1} -->
	<h1 class="wp-block-heading"><?php esc_html_e( 'Rooms', 'hotel-booking' ); ?></h1>
	<!-- /wp:heading -->
	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'Quiet rooms with garden light, proper desks, and linen that still feels like linen.', 'hotel-booking' ); ?></p>
	<!-- /wp:paragraph -->
	<!-- wp:query {"query":{"perPage":12,"pages":0,"offset":0,"postType":"hb_room","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true},"align":"wide"} -->
	<div class="wp-block-query alignwide">
		<!-- wp:post-template {"layout":{"type":"grid","minimumColumnWidth":"18rem"}} -->
			<!-- wp:group {"style":{"spacing":{"blockGap":"0.75rem"}}} -->
			<div class="wp-block-group">
				<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3"} /-->
				<!-- wp:post-title {"isLink":true,"level":2} /-->
				<?php
				echo '<!-- wp:post-excerpt ' . wp_json_encode(
					array(
						'moreText' => __( 'View room', 'hotel-booking' ),
					)
				) . ' /-->';
				?>
			</div>
			<!-- /wp:group -->
		<!-- /wp:post-template -->
		<!-- wp:query-pagination -->
			<!-- wp:query-pagination-previous /-->
			<!-- wp:query-pagination-numbers /-->
			<!-- wp:query-pagination-next /-->
		<!-- /wp:query-pagination -->
		<!-- wp:query-no-results -->
			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'No rooms are listed yet.', 'hotel-booking' ); ?></p>
			<!-- /wp:paragraph -->
		<!-- /wp:query-no-results -->
	</div>
	<!-- /wp:query -->
</main>
<!-- /wp:group -->
