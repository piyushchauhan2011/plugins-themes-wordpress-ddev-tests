<?php
/**
 * WP-CLI helper: languages, hide-default URL, and Spanish content copies.
 *
 * Invoked by `ddev seed-content` / `e2e/wp-env-seed.sh` via `wp eval`. Not a front-end hook.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensure EN/ES exist, hide the default slug, and copy seeded rooms/pages into Spanish.
 *
 * @return void
 */
function hotel_booking_seed_polylang() {
	if ( ! function_exists( 'PLL' ) ) {
		if ( class_exists( 'WP_CLI' ) ) {
			WP_CLI::warning( 'Polylang is not active; skip Layer B copies.' );
		}
		return;
	}

	hotel_booking_pll_ensure_languages();
	hotel_booking_pll_assign_english_content();
	hotel_booking_pll_seed_spanish_rooms();
	hotel_booking_pll_seed_spanish_pages();
	hotel_booking_pll_seed_spanish_navigation();
	hotel_booking_pll_set_front_page();
	flush_rewrite_rules( false );

	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::log( 'Polylang: English default (unprefixed), Spanish at /es/.' );
	}
}

/**
 * Create English and Spanish and set hide_default.
 *
 * @return void
 */
function hotel_booking_pll_ensure_languages() {
	$model    = PLL()->model;
	$existing = $model->get_languages_list( array( 'fields' => 'slug' ) );
	if ( ! is_array( $existing ) ) {
		$existing = array();
	}

	if ( ! in_array( 'en', $existing, true ) ) {
		$model->add_language(
			array(
				'name'       => 'English',
				'slug'       => 'en',
				'locale'     => 'en_US',
				'rtl'        => 0,
				'flag'       => 'us',
				'term_group' => 0,
			)
		);
	}

	$existing = $model->get_languages_list( array( 'fields' => 'slug' ) );
	if ( ! is_array( $existing ) ) {
		$existing = array();
	}

	if ( ! in_array( 'es', $existing, true ) ) {
		$model->add_language(
			array(
				'name'       => 'Español',
				'slug'       => 'es',
				'locale'     => 'es_ES',
				'rtl'        => 0,
				'flag'       => 'es',
				'term_group' => 1,
			)
		);
	}

	$opt = get_option( 'polylang' );
	if ( ! is_array( $opt ) ) {
		$opt = array();
	}

	$opt['default_lang']  = 'en';
	$opt['hide_default']  = 1;
	$opt['rewrite']       = 1;
	$opt['force_lang']    = 1;
	$opt['browser']       = 0;
	$opt['redirect_lang'] = 0;
	$opt['post_types']    = array( 'hb_room', 'wp_navigation' );

	update_option( 'polylang', $opt );

	$model->options = array_merge( (array) $model->options, $opt );
	$model->clean_languages_cache();
}

/**
 * Mark existing hotel posts as English.
 *
 * @return void
 */
function hotel_booking_pll_assign_english_content() {
	$ids = get_posts(
		array(
			'post_type'        => array( 'hb_room', 'page', 'wp_navigation' ),
			'post_status'      => array( 'publish', 'draft' ),
			'posts_per_page'   => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- seed enumerates a small demo catalog.
			'fields'           => 'ids',
			'lang'             => '',
			'suppress_filters' => true,
		)
	);

	foreach ( $ids as $id ) {
		$id = (int) $id;
		if ( ! function_exists( 'pll_get_post_language' ) || ! function_exists( 'pll_set_post_language' ) ) {
			continue;
		}
		$current = pll_get_post_language( $id );
		if ( ! $current ) {
			pll_set_post_language( $id, 'en' );
		}
	}
}

/**
 * Spanish copy of each English room, linked as a translation.
 *
 * @return void
 */
function hotel_booking_pll_seed_spanish_rooms() {
	$map = array(
		'deluxe-king'    => array(
			'title'   => 'King Deluxe',
			'slug'    => 'king-deluxe',
			'excerpt' => 'Una habitación amplia con cama king, un escritorio y luz de mañana.',
			'content' => '<!-- wp:paragraph --><p>El king de lujo da al patio. Cortinas de lino, una silla de verdad y un baño que se usa.</p><!-- /wp:paragraph -->',
		),
		'garden-suite'   => array(
			'title'   => 'Suite Jardín',
			'slug'    => 'suite-jardin',
			'excerpt' => 'Una suite que abre al jardín, con sitio para sentarse y no correr.',
			'content' => '<!-- wp:paragraph --><p>Puertas al jardín, un sofá que no es de relleno y el desayuno a unos pasos de las hierbas.</p><!-- /wp:paragraph -->',
		),
		'family-room'    => array(
			'title'   => 'Habitación familiar',
			'slug'    => 'habitacion-familiar',
			'excerpt' => 'Dos estancias que comparten un pequeño salón, para quien viaja en grupo.',
			'content' => '<!-- wp:paragraph --><p>Un king y dos twins, una puerta que cierra y suelo para la maleta que nunca acaba de cerrarse.</p><!-- /wp:paragraph -->',
		),
		'penthouse'      => array(
			'title'   => 'Ático',
			'slug'    => 'atico',
			'excerpt' => 'La última planta: vistas largas, una mesa y silencio después de las diez.',
			'content' => '<!-- wp:paragraph --><p>Un dormitorio, un salón y una terraza que coge el final de la tarde.</p><!-- /wp:paragraph -->',
		),
		'courtyard-twin' => array(
			'title'   => 'Twin del patio',
			'slug'    => 'twin-del-patio',
			'excerpt' => 'Dos camas, luz del patio y una tarifa que sigue pareciendo un hotel.',
			'content' => '<!-- wp:paragraph --><p>Una habitación más pequeña para una estancia corta: dos twins, ventana al patio y un escritorio contra la pared.</p><!-- /wp:paragraph -->',
		),
	);

	foreach ( $map as $en_slug => $es ) {
		$en_id = hotel_booking_pll_find_post( 'hb_room', $en_slug );
		if ( ! $en_id ) {
			continue;
		}

		pll_set_post_language( $en_id, 'en' );

		$es_id      = hotel_booking_pll_find_post( 'hb_room', $es['slug'] );
		$es_payload = array(
			'post_type'    => 'hb_room',
			'post_status'  => 'publish',
			'post_title'   => $es['title'],
			'post_name'    => $es['slug'],
			'post_excerpt' => $es['excerpt'],
			'post_content' => $es['content'],
		);
		if ( ! $es_id ) {
			$es_id = wp_insert_post( $es_payload, true );
			if ( is_wp_error( $es_id ) ) {
				continue;
			}
		} else {
			$es_payload['ID'] = $es_id;
			wp_update_post( $es_payload );
		}

		$es_id = (int) $es_id;
		pll_set_post_language( $es_id, 'es' );

		foreach ( array( 'hb_price', 'hb_guests', 'hb_beds', 'hb_size' ) as $meta_key ) {
			update_post_meta( $es_id, $meta_key, get_post_meta( $en_id, $meta_key, true ) );
		}

		$thumb = (int) get_post_thumbnail_id( $en_id );
		if ( $thumb ) {
			set_post_thumbnail( $es_id, $thumb );
		}

		pll_save_post_translations(
			array(
				'en' => $en_id,
				'es' => $es_id,
			)
		);
	}
}

/**
 * Spanish copies of demo pages that exist in English.
 *
 * @return void
 */
function hotel_booking_pll_seed_spanish_pages() {
	$map = array(
		'home'      => array(
			'title'   => 'Inicio',
			'slug'    => 'inicio',
			'content' => null,
		),
		'about'     => array(
			'title'   => 'Acerca de',
			'slug'    => 'acerca',
			'content' => '<!-- wp:paragraph --><p>Hotel Booking es un tema de aprendizaje: plantillas de bloques, un tipo de contenido para habitaciones, datos de demo y pruebas WP_UnitTestCase que se ejecutan con DDEV.</p><!-- /wp:paragraph -->',
		),
		'amenities' => array(
			'title'   => 'Servicios',
			'slug'    => 'servicios',
			'content' => null,
		),
		'contact'   => array(
			'title'   => 'Contacto',
			'slug'    => 'contacto',
			'content' => '<!-- wp:paragraph --><p>La recepción está abierta de siete hasta tarde. Esta página es contenido de ejemplo.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Correo: desk@hotel-booking.ddev.site<br>Teléfono: (555) 010-1947</p><!-- /wp:paragraph -->',
		),
		'booking'   => array(
			'title'   => 'Reserva',
			'slug'    => 'reserva',
			'content' => null,
		),
		'desk'      => array(
			'title'   => 'Recepción',
			'slug'    => 'recepcion',
			'content' => null,
		),
		'stay'      => array(
			'title'   => 'Estancia',
			'slug'    => 'estancia',
			'content' => null,
		),
	);

	foreach ( $map as $en_slug => $es ) {
		$en_id = hotel_booking_pll_find_post( 'page', $en_slug );
		if ( ! $en_id ) {
			continue;
		}

		pll_set_post_language( $en_id, 'en' );

		$content = $es['content'];
		if ( null === $content ) {
			$content = (string) get_post_field( 'post_content', $en_id );
		}

		$es_id      = hotel_booking_pll_find_post( 'page', $es['slug'] );
		$es_payload = array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $es['title'],
			'post_name'    => $es['slug'],
			'post_content' => $content,
		);
		if ( ! $es_id ) {
			$es_id = wp_insert_post( $es_payload, true );
			if ( is_wp_error( $es_id ) ) {
				continue;
			}
		} else {
			$es_payload['ID'] = $es_id;
			wp_update_post( $es_payload );
		}

		$es_id = (int) $es_id;
		pll_set_post_language( $es_id, 'es' );
		pll_save_post_translations(
			array(
				'en' => $en_id,
				'es' => $es_id,
			)
		);
	}
}

/**
 * Spanish header menu with /es/ URLs, linked to Primary.
 *
 * @return void
 */
function hotel_booking_pll_seed_spanish_navigation() {
	$en_id = hotel_booking_pll_find_post( 'wp_navigation', 'primary' );
	if ( ! $en_id ) {
		return;
	}

	pll_set_post_language( $en_id, 'en' );

	$items = array(
		array( 'Inicio', '/es/' ),
		array( 'Habitaciones', '/es/rooms/' ),
		array( 'Estancia', '/es/estancia/' ),
		array( 'Servicios', '/es/servicios/' ),
		array( 'Acerca de', '/es/acerca/' ),
		array( 'Contacto', '/es/contacto/' ),
		array( 'Reservar', '/es/reserva/' ),
		array( 'Recepción', '/es/recepcion/' ),
	);

	$content = '';
	foreach ( $items as $item ) {
		$content .= sprintf(
			'<!-- wp:navigation-link {"label":%s,"url":%s,"kind":"custom"} /-->',
			wp_json_encode( $item[0] ),
			wp_json_encode( $item[1] )
		);
	}

	$es_id = hotel_booking_pll_find_post( 'wp_navigation', 'primary-es' );
	if ( ! $es_id ) {
		$es_id = wp_insert_post(
			array(
				'post_type'    => 'wp_navigation',
				'post_status'  => 'publish',
				'post_title'   => 'Principal',
				'post_name'    => 'primary-es',
				'post_content' => $content,
			),
			true
		);
		if ( is_wp_error( $es_id ) ) {
			return;
		}
	} else {
		wp_update_post(
			array(
				'ID'           => $es_id,
				'post_content' => $content,
			)
		);
	}

	$es_id = (int) $es_id;
	pll_set_post_language( $es_id, 'es' );
	pll_save_post_translations(
		array(
			'en' => $en_id,
			'es' => $es_id,
		)
	);
}

/**
 * Keep the English Home as the static front page; Polylang maps Inicio to /es/.
 *
 * @return void
 */
function hotel_booking_pll_set_front_page() {
	$en_id = hotel_booking_pll_find_post( 'page', 'home' );
	if ( ! $en_id ) {
		return;
	}

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $en_id );
}

/**
 * Published post ID by type and slug, ignoring Polylang language filters.
 *
 * @param string $post_type Post type.
 * @param string $slug      post_name.
 * @return int
 */
function hotel_booking_pll_find_post( $post_type, $slug ) {
	$found = get_posts(
		array(
			'name'             => $slug,
			'post_type'        => $post_type,
			'post_status'      => 'publish',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'lang'             => '',
			'suppress_filters' => true,
		)
	);

	return $found ? (int) $found[0] : 0;
}
