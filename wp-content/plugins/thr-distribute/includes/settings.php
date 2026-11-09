<?php
/**
 * Settings: where events go, the signing secret and this site's ID.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings merged with defaults.
 *
 * @return array
 */
function thr_distribute_settings() {
	$host     = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$defaults = array(
		'enabled'     => 0,
		'webhook_url' => '',
		'site_id'     => sanitize_key( str_replace( '.', '-', preg_replace( '/^www\./', '', $host ) ) ),
		'utm'         => 1,
	);
	$saved = get_option( 'thr_distribute_settings', array() );
	return array_merge( $defaults, is_array( $saved ) ? $saved : array() );
}

/**
 * The secret that signs every webhook. Made once; can be regenerated in Settings.
 * Kept out of autoloaded options and never shown in full after creation.
 *
 * @return string
 */
function thr_distribute_secret() {
	if ( defined( 'THR_DISTRIBUTE_SECRET' ) && '' !== (string) THR_DISTRIBUTE_SECRET ) {
		return (string) THR_DISTRIBUTE_SECRET;
	}
	$secret = (string) get_option( 'thr_distribute_secret', '' );
	if ( '' === $secret ) {
		$secret = wp_generate_password( 48, false );
		update_option( 'thr_distribute_secret', $secret, false );
	}
	return $secret;
}

/**
 * This site's ID, used in every package ID so several sites can share the same automations.
 *
 * @return string
 */
function thr_distribute_site_id() {
	$id = thr_distribute_settings()['site_id'];
	return '' !== $id ? $id : 'site';
}

function thr_distribute_register_settings() {
	register_setting(
		'thr_distribute_group',
		'thr_distribute_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'thr_distribute_sanitize_settings',
		)
	);
}
add_action( 'admin_init', 'thr_distribute_register_settings' );

/**
 * @param mixed $input Raw input.
 * @return array
 */
function thr_distribute_sanitize_settings( $input ) {
	$input = is_array( $input ) ? $input : array();
	$url   = isset( $input['webhook_url'] ) ? esc_url_raw( trim( $input['webhook_url'] ), array( 'https', 'http' ) ) : '';
	$site  = isset( $input['site_id'] ) ? sanitize_key( $input['site_id'] ) : '';
	return array(
		'enabled'     => empty( $input['enabled'] ) ? 0 : 1,
		'webhook_url' => $url,
		'site_id'     => '' !== $site ? $site : thr_distribute_settings()['site_id'],
		'utm'         => empty( $input['utm'] ) ? 0 : 1,
	);
}
