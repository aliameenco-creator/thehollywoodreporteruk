<?php
/**
 * Site hardening: XML-RPC off, user names hidden, security headers, code editors off,
 * WordPress version hidden.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * XML-RPC.
 * ---------------------------------------------------------------------- */

function thr_security_xmlrpc() {
	if ( ! thr_security_on( 'xmlrpc_off' ) ) {
		return;
	}
	add_filter( 'xmlrpc_enabled', '__return_false' );
	add_filter( 'xmlrpc_methods', '__return_empty_array' );
	add_filter( 'pings_open', '__return_false' );
	remove_action( 'wp_head', 'rsd_link' );
	add_filter(
		'wp_headers',
		static function ( $headers ) {
			unset( $headers['X-Pingback'] );
			return $headers;
		}
	);
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		status_header( 403 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo 'XML-RPC is disabled.';
		exit;
	}
}
add_action( 'plugins_loaded', 'thr_security_xmlrpc', 1 );

/* -------------------------------------------------------------------------
 * User names.
 * ---------------------------------------------------------------------- */

/**
 * Logged-out visitors can't list users through the REST API.
 * Logged-in editors (the block editor) and the n8n account still can.
 *
 * @param array $endpoints REST routes.
 * @return array
 */
function thr_security_hide_user_routes( $endpoints ) {
	if ( thr_security_on( 'hide_users' ) && ! is_user_logged_in() ) {
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
				unset( $endpoints[ $route ] );
			}
		}
	}
	return $endpoints;
}
add_filter( 'rest_endpoints', 'thr_security_hide_user_routes' );

/**
 * ?author=1 would redirect to /author/{username}/, revealing log-in names one by one.
 */
function thr_security_block_author_scan() {
	if ( ! thr_security_on( 'hide_users' ) || is_user_logged_in() || is_admin() ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check.
	if ( isset( $_GET['author'] ) && '' !== $_GET['author'] ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
add_action( 'template_redirect', 'thr_security_block_author_scan', 1 );

/**
 * @param WP_Sitemaps_Provider $provider Provider.
 * @param string               $name     Provider name.
 * @return WP_Sitemaps_Provider|false
 */
function thr_security_no_user_sitemap( $provider, $name ) {
	return thr_security_on( 'hide_users' ) && 'users' === $name ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'thr_security_no_user_sitemap', 10, 2 );

/* -------------------------------------------------------------------------
 * Security headers.
 * ---------------------------------------------------------------------- */

function thr_security_send_headers() {
	if ( ! thr_security_on( 'headers_on' ) || headers_sent() ) {
		return;
	}
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()' );
	if ( thr_security_on( 'hsts_on' ) && is_ssl() ) {
		// No includeSubDomains: other subdomains (mail, audio) are managed elsewhere.
		header( 'Strict-Transport-Security: max-age=31536000' );
	}
}
add_action( 'send_headers', 'thr_security_send_headers' );
add_action( 'admin_init', 'thr_security_send_headers' );
add_action( 'login_init', 'thr_security_send_headers' );

/* -------------------------------------------------------------------------
 * Code editors.
 * ---------------------------------------------------------------------- */

/**
 * @param string[] $caps Primitive capabilities.
 * @param string   $cap  Capability asked for.
 * @return string[]
 */
function thr_security_no_editors( $caps, $cap ) {
	if ( thr_security_on( 'editor_off' ) && in_array( $cap, array( 'edit_themes', 'edit_plugins', 'edit_files' ), true ) ) {
		return array( 'do_not_allow' );
	}
	return $caps;
}
add_filter( 'map_meta_cap', 'thr_security_no_editors', 10, 2 );

/* -------------------------------------------------------------------------
 * WordPress version.
 * ---------------------------------------------------------------------- */

function thr_security_hide_version() {
	if ( thr_security_on( 'hide_version' ) ) {
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string' );
	}
}
add_action( 'init', 'thr_security_hide_version' );
