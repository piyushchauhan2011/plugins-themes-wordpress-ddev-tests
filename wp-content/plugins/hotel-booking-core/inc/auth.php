<?php
/**
 * Front-end staff login, hotel_manager role, and inquiry policies.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom capabilities used by hotel_booking_authorize().
 *
 * @return list<string>
 */
function hotel_booking_staff_capabilities() {
	return array(
		'hb_access_desk',
		'hb_transition_inquiries',
		'hb_delete_inquiries',
		'hb_reopen_inquiries',
	);
}

/**
 * Register hotel_manager and grant plugin caps to administrators.
 *
 * @return void
 */
function hotel_booking_register_roles() {
	$manager_caps = array(
		'read'                    => true,
		'hb_access_desk'          => true,
		'hb_transition_inquiries' => true,
	);

	$role = get_role( 'hotel_manager' );
	if ( ! $role ) {
		add_role( 'hotel_manager', __( 'Hotel Manager', 'hotel-booking-core' ), $manager_caps );
	} else {
		foreach ( array_keys( $manager_caps ) as $cap ) {
			$role->add_cap( $cap );
		}
	}

	$admin = get_role( 'administrator' );
	if ( $admin ) {
		foreach ( hotel_booking_staff_capabilities() as $cap ) {
			$admin->add_cap( $cap );
		}
	}

	$desk = get_user_by( 'login', 'desk' );
	if ( $desk instanceof WP_User && in_array( 'editor', (array) $desk->roles, true ) ) {
		$desk->set_role( 'hotel_manager' );
	}
}
add_action( 'init', 'hotel_booking_register_roles', 5 );
add_action( 'init', 'hotel_booking_ensure_staff_login_page', 20 );

/**
 * Publish a staff-login page when missing (WordPress already owns /login/ → wp-login.php).
 *
 * @return void
 */
function hotel_booking_ensure_staff_login_page() {
	if ( wp_installing() ) {
		return;
	}

	$existing = get_posts(
		array(
			'name'           => 'staff-login',
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( $existing ) {
		return;
	}

	wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => __( 'Staff login', 'hotel-booking-core' ),
			'post_name'    => 'staff-login',
			'post_content' => '<!-- wp:shortcode -->[hotel_staff_login]<!-- /wp:shortcode -->',
		),
		true
	);
}

/**
 * Whether the current user may perform a desk action.
 *
 * RBAC is the hb_* capability. ABAC adds resource attributes (transition name, inquiry status).
 *
 * @param string               $action  desk.view|inquiry.transition|inquiry.delete.
 * @param array<string, mixed> $context Optional inquiry and transition name.
 * @return bool
 */
function hotel_booking_authorize( $action, $context = array() ) {
	switch ( $action ) {
		case 'desk.view':
			return current_user_can( 'hb_access_desk' );

		case 'inquiry.delete':
			return current_user_can( 'hb_delete_inquiries' );

		case 'inquiry.transition':
			if ( ! current_user_can( 'hb_transition_inquiries' ) ) {
				return false;
			}

			$name    = isset( $context['transition'] ) ? sanitize_key( (string) $context['transition'] ) : '';
			$inquiry = isset( $context['inquiry'] ) && is_object( $context['inquiry'] ) ? $context['inquiry'] : null;

			if ( 'reopen' === $name ) {
				return current_user_can( 'hb_reopen_inquiries' );
			}

			if ( 'close' === $name && $inquiry && 'pending' === (string) $inquiry->status && ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			return true;

		default:
			return false;
	}
}

/**
 * Front-end staff login URL.
 *
 * @return string
 */
function hotel_booking_login_url() {
	return home_url( '/staff-login/' );
}

/**
 * After wp-login.php, send non-superadmins to the desk.
 *
 * @param string           $redirect_to           Default redirect.
 * @param string           $requested_redirect_to Requested redirect.
 * @param WP_User|WP_Error $user                  Authenticated user.
 * @return string
 */
function hotel_booking_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
	unset( $requested_redirect_to );

	if ( is_wp_error( $user ) ) {
		return $redirect_to;
	}

	if ( user_can( $user, 'manage_options' ) ) {
		return $redirect_to;
	}

	return home_url( '/desk/' );
}
add_filter( 'login_redirect', 'hotel_booking_login_redirect', 10, 3 );

/**
 * Hide the admin bar for anyone who is not a superadmin.
 *
 * @param bool $show Whether to show the bar.
 * @return bool
 */
function hotel_booking_show_admin_bar( $show ) {
	return current_user_can( 'manage_options' ) ? $show : false;
}
add_filter( 'show_admin_bar', 'hotel_booking_show_admin_bar' );

/**
 * Keep hotel managers (and other non-superadmins) out of wp-admin screens.
 *
 * @return void
 */
function hotel_booking_block_wp_admin() {
	if ( ! is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return;
	}

	global $pagenow;
	if ( in_array( $pagenow, array( 'admin-post.php', 'admin-ajax.php' ), true ) ) {
		return;
	}

	if ( ! is_user_logged_in() || current_user_can( 'manage_options' ) ) {
		return;
	}

	wp_safe_redirect( home_url( '/desk/' ) );
	exit;
}
add_action( 'admin_init', 'hotel_booking_block_wp_admin' );

/**
 * Handle front-end staff login POST.
 *
 * @return void
 */
function hotel_booking_handle_staff_login() {
	if ( ! isset( $_POST['hb_login_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hb_login_nonce'] ) ), 'hb_staff_login' ) ) {
		wp_die( esc_html__( 'The login form expired. Please try again.', 'hotel-booking-core' ), '', array( 'response' => 403 ) );
	}

	$login = isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : '';
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- passwords must not be altered.
	$password = isset( $_POST['pwd'] ) ? wp_unslash( $_POST['pwd'] ) : '';

	$user = wp_signon(
		array(
			'user_login'    => $login,
			'user_password' => $password,
			'remember'      => ! empty( $_POST['rememberme'] ),
		),
		is_ssl()
	);

	$dest = wp_get_referer() ? wp_get_referer() : hotel_booking_login_url();
	$dest = remove_query_arg( 'hb_login_error', $dest );

	if ( is_wp_error( $user ) ) {
		wp_safe_redirect( add_query_arg( 'hb_login_error', '1', $dest ) );
		exit;
	}

	if ( user_can( $user, 'manage_options' ) ) {
		wp_safe_redirect( admin_url() );
		exit;
	}

	wp_safe_redirect( home_url( '/desk/' ) );
	exit;
}
add_action( 'admin_post_nopriv_hb_staff_login', 'hotel_booking_handle_staff_login' );
add_action( 'admin_post_hb_staff_login', 'hotel_booking_handle_staff_login' );

/**
 * Staff login form shortcode.
 *
 * @return string
 */
function hotel_booking_staff_login_shortcode() {
	return hotel_booking_render_staff_login();
}
add_shortcode( 'hotel_staff_login', 'hotel_booking_staff_login_shortcode' );

/**
 * Markup for the front-end staff login form.
 *
 * @return string
 */
function hotel_booking_render_staff_login() {
	if ( is_user_logged_in() ) {
		if ( current_user_can( 'manage_options' ) ) {
			return '<p><a href="' . esc_url( admin_url() ) . '">' . esc_html__( 'Open wp-admin', 'hotel-booking-core' ) . '</a></p>';
		}
		if ( hotel_booking_authorize( 'desk.view' ) ) {
			return '<p><a href="' . esc_url( home_url( '/desk/' ) ) . '">' . esc_html__( 'Open the desk book', 'hotel-booking-core' ) . '</a></p>';
		}

		return '<p>' . esc_html__( 'You are already logged in.', 'hotel-booking-core' ) . '</p>';
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- flash flag after a failed login redirect.
	$failed = isset( $_GET['hb_login_error'] );

	ob_start();
	?>
	<div class="hb-staff-login">
		<?php if ( $failed ) : ?>
			<p class="hb-inquiry__notice" role="alert"><?php esc_html_e( 'That username or password is not correct.', 'hotel-booking-core' ); ?></p>
		<?php endif; ?>
		<form class="hb-booking-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="hb_staff_login">
			<?php wp_nonce_field( 'hb_staff_login', 'hb_login_nonce' ); ?>
			<label><?php esc_html_e( 'Username', 'hotel-booking-core' ); ?>
				<input type="text" name="log" autocomplete="username" required>
			</label>
			<label><?php esc_html_e( 'Password', 'hotel-booking-core' ); ?>
				<input type="password" name="pwd" autocomplete="current-password" required>
			</label>
			<label>
				<input type="checkbox" name="rememberme" value="1">
				<?php esc_html_e( 'Remember me', 'hotel-booking-core' ); ?>
			</label>
			<button type="submit"><?php esc_html_e( 'Log in', 'hotel-booking-core' ); ?></button>
		</form>
	</div>
	<?php

	return (string) ob_get_clean();
}
