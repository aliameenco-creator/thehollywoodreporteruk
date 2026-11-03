<?php
/**
 * ElevenLabs API client. Server-side only: the key never reaches the browser.
 *
 * In test mode no request is made; a bundled sample recording stands in for the audio.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class THR_Audio_ElevenLabs {

	const API = 'https://api.elevenlabs.io/v1/';

	/**
	 * Turn text into MP3 bytes.
	 *
	 * @param string $voice_id ElevenLabs voice ID.
	 * @param string $text     Text to speak.
	 * @param string $previous Text spoken just before (keeps tone continuous across pieces).
	 * @param string $next     Text spoken just after.
	 * @return string|WP_Error MP3 bytes.
	 */
	public static function speak( $voice_id, $text, $previous = '', $next = '' ) {
		$settings = thr_audio_settings();

		if ( 'live' !== $settings['mode'] ) {
			return self::sample();
		}
		if ( ! thr_audio_has_key() ) {
			return new WP_Error( 'thr_audio_no_key', __( 'No ElevenLabs API key in wp-config.php.', 'thr-audio' ) );
		}

		$body = array(
			'text'     => $text,
			'model_id' => $settings['model'],
		);
		if ( '' !== $previous ) {
			$body['previous_text'] = $previous;
		}
		if ( '' !== $next ) {
			$body['next_text'] = $next;
		}

		$response = wp_remote_post(
			self::API . 'text-to-speech/' . rawurlencode( $voice_id ) . '?output_format=' . rawurlencode( $settings['output_format'] ),
			array(
				'timeout' => 90,
				'headers' => array(
					'xi-api-key'   => THR_ELEVENLABS_API_KEY,
					'Content-Type' => 'application/json',
					'Accept'       => 'audio/mpeg',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'thr_audio_http', __( 'Could not reach ElevenLabs: ', 'thr-audio' ) . $response->get_error_message(), array( 'retry' => true ) );
		}

		$code  = wp_remote_retrieve_response_code( $response );
		$type  = (string) wp_remote_retrieve_header( $response, 'content-type' );
		$bytes = wp_remote_retrieve_body( $response );

		if ( 200 !== $code || false === strpos( $type, 'audio' ) ) {
			// Busy or server-side trouble is worth retrying; a bad key, voice or empty balance is not.
			return new WP_Error( 'thr_audio_api', self::error_message( $code, $bytes ), array( 'retry' => in_array( (int) $code, array( 408, 429, 500, 502, 503, 504 ), true ) ) );
		}
		// A real clip is never this small; treat it as a failed response rather than save a broken file.
		if ( strlen( $bytes ) < 1000 ) {
			return new WP_Error( 'thr_audio_empty', __( 'ElevenLabs returned an empty recording.', 'thr-audio' ), array( 'retry' => true ) );
		}

		return $bytes;
	}

	/**
	 * Text-to-speech models on the account.
	 *
	 * @return array|WP_Error model_id => name.
	 */
	public static function models() {
		$cached = get_transient( 'thr_audio_models' );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$data = self::get( 'models' );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$models = array();
		foreach ( (array) $data as $model ) {
			if ( ! empty( $model['model_id'] ) && ! empty( $model['can_do_text_to_speech'] ) ) {
				$models[ sanitize_text_field( $model['model_id'] ) ] = sanitize_text_field( isset( $model['name'] ) ? $model['name'] : $model['model_id'] );
			}
		}

		set_transient( 'thr_audio_models', $models, 12 * HOUR_IN_SECONDS );
		return $models;
	}

	/**
	 * Plan and credit usage.
	 *
	 * @return array|WP_Error tier, used, limit, reset (unix time).
	 */
	public static function subscription() {
		$data = self::get( 'user/subscription' );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return array(
			'tier'  => isset( $data['tier'] ) ? sanitize_text_field( $data['tier'] ) : '—',
			'used'  => isset( $data['character_count'] ) ? (int) $data['character_count'] : 0,
			'limit' => isset( $data['character_limit'] ) ? (int) $data['character_limit'] : 0,
			'reset' => isset( $data['next_character_count_reset_unix'] ) ? (int) $data['next_character_count_reset_unix'] : 0,
		);
	}

	/**
	 * JSON GET request.
	 *
	 * @param string $path Path under /v1/.
	 * @return array|WP_Error
	 */
	private static function get( $path ) {
		if ( ! thr_audio_has_key() ) {
			return new WP_Error( 'thr_audio_no_key', __( 'No ElevenLabs API key in wp-config.php.', 'thr-audio' ) );
		}

		$response = wp_remote_get(
			self::API . $path,
			array(
				'timeout' => 15,
				'headers' => array( 'xi-api-key' => THR_ELEVENLABS_API_KEY ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		if ( 200 !== $code ) {
			return new WP_Error( 'thr_audio_api', self::error_message( $code, $body ) );
		}

		$data = json_decode( $body, true );
		return is_array( $data ) ? $data : new WP_Error( 'thr_audio_api', __( 'Unexpected reply from ElevenLabs.', 'thr-audio' ) );
	}

	/**
	 * Readable error from an ElevenLabs error reply. Never includes the key.
	 *
	 * @param int    $code HTTP status.
	 * @param string $body Response body.
	 * @return string
	 */
	private static function error_message( $code, $body ) {
		$data   = json_decode( (string) $body, true );
		$detail = '';
		if ( isset( $data['detail']['message'] ) ) {
			$detail = $data['detail']['message'];
		} elseif ( isset( $data['detail'] ) && is_string( $data['detail'] ) ) {
			$detail = $data['detail'];
		}

		$reasons = array(
			401 => __( 'API key rejected.', 'thr-audio' ),
			402 => __( 'Out of ElevenLabs credits or plan limit reached.', 'thr-audio' ),
			404 => __( 'Voice not found: check the Voice ID in Settings → THR Audio.', 'thr-audio' ),
			422 => __( 'ElevenLabs could not read the request.', 'thr-audio' ),
			429 => __( 'ElevenLabs is busy (too many requests); will retry.', 'thr-audio' ),
		);

		$message = isset( $reasons[ $code ] ) ? $reasons[ $code ] : sprintf( /* translators: %d: HTTP status. */ __( 'ElevenLabs error %d.', 'thr-audio' ), $code );
		return trim( $message . ' ' . sanitize_text_field( mb_substr( $detail, 0, 200 ) ) );
	}

	/**
	 * Test mode stand-in recording.
	 *
	 * @return string|WP_Error
	 */
	private static function sample() {
		$file = THR_AUDIO_PATH . 'assets/audio/sample.mp3';
		if ( ! is_readable( $file ) ) {
			return new WP_Error( 'thr_audio_sample', __( 'Test sample recording is missing from the plugin.', 'thr-audio' ) );
		}
		return (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}
}
