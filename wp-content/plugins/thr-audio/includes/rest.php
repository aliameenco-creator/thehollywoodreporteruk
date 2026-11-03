<?php
/**
 * Reader endpoint: POST /wp-json/thr-audio/v1/voice
 *
 * The player calls it when a reader picks a voice that has no full recording yet
 * (create=1), then polls it (create=0) until the recording is ready.
 *
 * Public by design (pages are cached, so there is no per-visitor nonce). It can only ever
 * queue a configured voice for a published article, is rate limited per visitor, and counts
 * toward the monthly cap. POST so page and REST caches never store the answer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_audio_register_routes() {
	register_rest_route(
		'thr-audio/v1',
		'/voice',
		array(
			'methods'             => 'POST',
			'callback'            => 'thr_audio_rest_voice',
			'permission_callback' => '__return_true',
			'args'                => array(
				'post'   => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'voice'  => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_key',
				),
				'create' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'thr_audio_register_routes' );

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function thr_audio_rest_voice( $request ) {
	$post_id = $request['post'];
	$voice   = $request['voice'];
	$post    = get_post( $post_id );

	if ( ! $post || 'publish' !== $post->post_status || post_password_required( $post ) || ! in_array( $post->post_type, thr_audio_post_types(), true ) ) {
		return thr_audio_rest_reply( 'unavailable' );
	}

	// Ready already (possibly made for another reader a moment ago).
	foreach ( thr_audio_get_variants( $post_id ) as $variant ) {
		if ( $variant['voice'] === $voice && 'full' === $variant['state'] ) {
			return thr_audio_rest_reply(
				'ready',
				array(
					'url'      => $variant['url'],
					'duration' => $variant['duration'],
				)
			);
		}
	}

	$voices = thr_audio_voices();
	if ( ! isset( $voices[ $voice ] ) ) {
		return thr_audio_rest_reply( 'unavailable' );
	}

	$status = thr_audio_get_status( $post_id, $voice, 'full' );
	if ( in_array( $status['state'], array( 'queued', 'generating' ), true ) ) {
		return thr_audio_rest_reply( 'generating' );
	}

	if ( ! $request['create'] ) {
		return thr_audio_rest_reply( 'failed' === $status['state'] ? 'failed' : 'none' );
	}

	$settings = thr_audio_settings();
	if ( ! $settings['on_demand'] ) {
		return thr_audio_rest_reply( 'unavailable' );
	}
	if ( ! thr_audio_rate_limit_ok() ) {
		return thr_audio_rest_reply( 'busy' );
	}
	if ( 'live' === $settings['mode'] && ! thr_audio_within_cap( mb_strlen( thr_audio_script( $post_id ) ) ) ) {
		return thr_audio_rest_reply( 'unavailable' );
	}

	thr_audio_enqueue( $post_id, $voice, 'full', false, 0 );
	thr_audio_kick();

	return thr_audio_rest_reply( 'generating' );
}

/**
 * Uncacheable JSON reply. Internal error details are never sent to readers.
 *
 * @param string $state ready|generating|none|failed|busy|unavailable.
 * @param array  $extra Extra fields.
 * @return WP_REST_Response
 */
function thr_audio_rest_reply( $state, $extra = array() ) {
	$response = new WP_REST_Response( array_merge( array( 'state' => $state ), $extra ) );
	$response->header( 'Cache-Control', 'no-store, max-age=0' );
	$response->header( 'X-LiteSpeed-Cache-Control', 'no-cache' );
	return $response;
}

/**
 * Count a new-voice request for this visitor; false once over the hourly limit.
 *
 * @return bool
 */
function thr_audio_rate_limit_ok() {
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$ip    = (string) apply_filters( 'thr_audio_client_ip', $ip );
	$key   = 'thr_audio_rl_' . md5( $ip . gmdate( 'YmdH' ) );
	$count = (int) get_transient( $key );

	if ( $count >= (int) thr_audio_settings()['rate_limit'] ) {
		return false;
	}
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	return true;
}
