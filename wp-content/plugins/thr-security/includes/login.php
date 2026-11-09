<?php
/**
 * Login protections: private login address, attempt limits, vague errors, admin-login emails,
 * and who may use application passwords.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Visitor address.
 * ---------------------------------------------------------------------- */

/**
 * The visitor's IP address, from the source chosen in Settings.
 *
 * @return string
 */
function thr_security_ip() {
	$source = thr_security_settings()['ip_source'];
	$ip     = '';
	// phpcs:disable WordPress.Security.ValidatedSanitizedInput -- validated with FILTER_VALIDATE_IP below.
	if ( 'cloudflare' === $source && ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
		$ip = wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] );
	} elseif ( 'forwarded' === $source && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ip = trim( explode( ',', wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )[0] );
	}
	if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
	}
	// phpcs:enable
	$ip = filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
	return (string) apply_filters( 'thr_security_client_ip', $ip );
}

/* -------------------------------------------------------------------------
 * Private login address.
 * ---------------------------------------------------------------------- */

/**
 * The login slug when the private address is on, else ''.
 *
 * @return string
 */
function thr_security_login_slug() {
	$s = thr_security_settings();
	return thr_security_on( 'login_on' ) && '' !== $s['login_slug'] ? $s['login_slug'] : '';
}

/**
 * The request path relative to the site root, without slashes at either end.
 *
 * @return string
 */
function thr_security_request_path() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- compared, never output.
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$home = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
	$path = trim( rawurldecode( $path ), '/' );
	if ( '' !== $home && 0 === strpos( $path, $home ) ) {
		$path = trim( substr( $path, strlen( $home ) ), '/' );
	}
	return $path;
}

/**
 * Show "not found" and stop. Never cached.
 */
function thr_security_not_found() {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	do_action( 'litespeed_control_set_nocache', 'thr-security' );
	nocache_headers();
	status_header( 404 );
	header( 'Content-Type: text/html; charset=utf-8' );
	echo '<!doctype html><meta charset="utf-8"><title>' . esc_html__( 'Not found', 'thr-security' ) . '</title><p>' . esc_html__( 'Not found.', 'thr-security' ) . '</p><p><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a></p>';
	exit;
}

/**
 * Serve the login page at the private address, and hide the usual ones.
 * Runs once WordPress is loaded, before wp-admin checks the login.
 */
function thr_security_route_login() {
	$slug = thr_security_login_slug();
	if ( '' === $slug || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return;
	}

	$path   = thr_security_request_path();
	$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( (string) wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- compared only.

	// The private address: load the normal login screen.
	if ( $path === $slug ) {
		global $error, $interim_login, $action, $user_login, $pagenow;
		$pagenow = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the login screen expects it.
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		do_action( 'litespeed_control_set_nocache', 'thr-security login' );
		require_once ABSPATH . 'wp-login.php';
		exit;
	}

	// The old address: not found for everyone.
	if ( 'wp-login.php' === $script || 'wp-login.php' === basename( $path ) || 'wp-register.php' === basename( $path ) || 'wp-signup.php' === basename( $path ) ) {
		thr_security_not_found();
	}

	// wp-admin for visitors who aren't logged in. admin-ajax.php and admin-post.php stay open:
	// the site's forms, the audio worker and logged-out actions use them.
	if ( is_admin() && ! is_user_logged_in() && ! wp_doing_ajax() && ! in_array( $script, array( 'admin-ajax.php', 'admin-post.php' ), true ) ) {
		thr_security_not_found();
	}
}
add_action( 'wp_loaded', 'thr_security_route_login', 1 );

/**
 * Point every generated login link at the private address (login, logout, lost password,
 * password-reset emails, password-protected posts, session-expired pop-up).
 *
 * @param string $url URL.
 * @return string
 */
function thr_security_login_links( $url ) {
	$slug = thr_security_login_slug();
	if ( '' === $slug || false === strpos( $url, 'wp-login.php' ) ) {
		return $url;
	}
	$parts = explode( '?', $url, 2 );
	if ( 'wp-login.php' !== basename( (string) wp_parse_url( $parts[0], PHP_URL_PATH ) ) ) {
		return $url;
	}
	return home_url( '/' . $slug . '/' ) . ( isset( $parts[1] ) ? '?' . $parts[1] : '' );
}
add_filter( 'site_url', 'thr_security_login_links', 100 );
add_filter( 'network_site_url', 'thr_security_login_links', 100 );
add_filter( 'wp_redirect', 'thr_security_login_links', 100 );
add_filter( 'login_url', 'thr_security_login_links', 100 );
add_filter( 'logout_url', 'thr_security_login_links', 100 );
add_filter( 'lostpassword_url', 'thr_security_login_links', 100 );
add_filter( 'register_url', 'thr_security_login_links', 100 );

/**
 * WordPress sends /login and /admin to the login page; with a private address it mustn't.
 */
function thr_security_no_shortcuts() {
	if ( '' !== thr_security_login_slug() ) {
		remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
	}
}
add_action( 'init', 'thr_security_no_shortcuts' );

/* -------------------------------------------------------------------------
 * Login attempt limits.
 * ---------------------------------------------------------------------- */

/**
 * @param string $ip IP address.
 * @return bool
 */
function thr_security_ip_allowed( $ip ) {
	$list = preg_split( '/\s+/', (string) thr_security_settings()['allow_ips'] );
	return in_array( $ip, $list, true );
}

/**
 * Refuse logins from a locked-out address, even with the right password.
 *
 * @param WP_User|WP_Error|null $user Result so far.
 * @return WP_User|WP_Error|null
 */
function thr_security_check_lock( $user ) {
	if ( ! thr_security_on( 'limit_on' ) ) {
		return $user;
	}
	$ip = thr_security_ip();
	if ( thr_security_ip_allowed( $ip ) ) {
		return $user;
	}
	$until = (int) get_transient( 'thr_sec_lock_' . md5( $ip ) );
	if ( $until > time() ) {
		$minutes = max( 1, (int) ceil( ( $until - time() ) / 60 ) );
		return new WP_Error(
			'thr_locked',
			sprintf( /* translators: %d: minutes. */ _n( 'Too many failed attempts. Try again in %d minute.', 'Too many failed attempts. Try again in %d minutes.', $minutes, 'thr-security' ), $minutes )
		);
	}
	return $user;
}
add_filter( 'authenticate', 'thr_security_check_lock', 30 );

/**
 * Count a failed login; lock the address out when it reaches the limit.
 */
function thr_security_login_failed() {
	if ( ! thr_security_on( 'limit_on' ) ) {
		return;
	}
	$ip = thr_security_ip();
	if ( thr_security_ip_allowed( $ip ) || get_transient( 'thr_sec_lock_' . md5( $ip ) ) ) {
		return;
	}
	$s     = thr_security_settings();
	$key   = 'thr_sec_fail_' . md5( $ip );
	$fails = (int) get_transient( $key ) + 1;

	if ( $fails < (int) $s['max_attempts'] ) {
		set_transient( $key, $fails, (int) $s['lock_minutes'] * MINUTE_IN_SECONDS );
		return;
	}

	$until = time() + (int) $s['lock_minutes'] * MINUTE_IN_SECONDS;
	set_transient( 'thr_sec_lock_' . md5( $ip ), $until, (int) $s['lock_minutes'] * MINUTE_IN_SECONDS );
	delete_transient( $key );

	$list        = thr_security_lockouts();
	$list[ $ip ] = $until;
	update_option( 'thr_security_lockouts', array_slice( $list, -200, null, true ), false );
}
add_action( 'wp_login_failed', 'thr_security_login_failed' );

/**
 * A successful login clears the address's failed count.
 *
 * @param string  $login User name.
 * @param WP_User $user  User.
 */
function thr_security_login_ok( $login, $user ) {
	delete_transient( 'thr_sec_fail_' . md5( thr_security_ip() ) );

	if ( thr_security_on( 'notify_admin' ) && user_can( $user, 'manage_options' ) ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- plain-text email only.
		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 200 ) : '';
		wp_mail(
			get_option( 'admin_email' ),
			sprintf( /* translators: 1: site name, 2: user name. */ __( '[%1$s] Administrator login: %2$s', 'thr-security' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $login ),
			sprintf(
				/* translators: 1: user, 2: time, 3: IP, 4: browser. */
				__( "%1\$s logged in as an administrator.\n\nTime: %2\$s\nAddress: %3\$s\nBrowser: %4\$s\n\nIf this wasn't expected, change that user's password and log out all sessions (Users → Profile).", 'thr-security' ),
				$login,
				wp_date( 'j F Y H:i T' ),
				thr_security_ip(),
				$agent
			)
		);
	}
}
add_action( 'wp_login', 'thr_security_login_ok', 10, 2 );

/* -------------------------------------------------------------------------
 * Vague errors.
 * ---------------------------------------------------------------------- */

/**
 * Don't reveal whether a user name or email exists.
 *
 * @param WP_User|WP_Error|null $user Result so far.
 * @return WP_User|WP_Error|null
 */
function thr_security_vague_errors( $user ) {
	if ( thr_security_on( 'generic_errors' ) && is_wp_error( $user ) && array_intersect( $user->get_error_codes(), array( 'invalid_username', 'invalid_email', 'incorrect_password' ) ) ) {
		return new WP_Error( 'thr_login_failed', __( '<strong>Error:</strong> The login details are incorrect.', 'thr-security' ) );
	}
	return $user;
}
add_filter( 'authenticate', 'thr_security_vague_errors', 99 );

/* -------------------------------------------------------------------------
 * Application passwords.
 * ---------------------------------------------------------------------- */

/**
 * @param bool    $available Whether available.
 * @param WP_User $user      User.
 * @return bool
 */
function thr_security_app_passwords( $available, $user ) {
	if ( ! thr_security_on( 'app_pw_limited' ) || ! $available ) {
		return $available;
	}
	return user_can( $user, 'manage_options' ) || user_can( $user, 'thr_distribute' );
}
add_filter( 'wp_is_application_passwords_available_for_user', 'thr_security_app_passwords', 10, 2 );
