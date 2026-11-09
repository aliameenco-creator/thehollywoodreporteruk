<?php
/**
 * THR Distribute integration: adds the recordings and the read-aloud script to each article's
 * content package, and announces when a published article's audio changes.
 *
 * Does nothing unless THR Distribute is active.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param array   $package Content package.
 * @param WP_Post $post    Article.
 * @return array
 */
function thr_audio_distribute_package( $package, $post ) {
	if ( ! in_array( $post->post_type, thr_audio_post_types(), true ) ) {
		return $package;
	}

	// The exact words read aloud: headline, dek, then the body. It is the transcript of every voice.
	$script                    = thr_audio_script( $post->ID );
	$package['text']['script'] = $script;

	$stored   = thr_audio_stored( $post->ID );
	$voices   = array();
	$settings = thr_audio_settings();
	foreach ( thr_audio_get_variants( $post->ID ) as $variant ) {
		if ( 'none' === $variant['state'] ) {
			continue;
		}
		$saved = isset( $stored[ $variant['voice'] ] ) ? $stored[ $variant['voice'] ] : array();
		$full  = ! empty( $saved['attachment_id'] ) ? (int) $saved['attachment_id'] : 0;
		$clip  = ! empty( $saved['preview_attachment_id'] ) ? (int) $saved['preview_attachment_id'] : 0;

		$voices[] = array(
			'voice'            => $variant['voice'],
			'label'            => $variant['label'],
			'region'           => $variant['region'],
			'gender'           => $variant['gender'],
			'state'            => $variant['state'],
			'url'              => $variant['url'],
			'duration'         => (int) $variant['duration'],
			'bytes'            => $full ? (int) get_post_meta( $full, '_thr_audio_bytes', true ) : 0,
			'preview_url'      => $variant['preview_url'],
			'preview_duration' => (int) $variant['preview_duration'],
			'preview_bytes'    => $clip ? (int) get_post_meta( $clip, '_thr_audio_bytes', true ) : 0,
			'mime'             => 'audio/mpeg',
			'model'            => isset( $saved['model'] ) ? $saved['model'] : '',
			'source'           => isset( $saved['source'] ) ? $saved['source'] : '',
			'generated_at'     => isset( $saved['generated_at'] ) ? $saved['generated_at'] : null,
			// Same text the audio was made from; if they differ, the recording is outdated.
			'up_to_date'       => isset( $saved['text_hash'] ) && md5( $script ) === $saved['text_hash'],
		);
	}

	if ( ! $voices ) {
		return $package;
	}

	$audio_meta = get_post_meta( $post->ID, 'thr_audio', true );
	$host       = wp_parse_url( home_url(), PHP_URL_HOST );

	$package['audio'] = array(
		'default_voice' => ! empty( $audio_meta['default'] ) ? $audio_meta['default'] : thr_audio_default_slot(),
		'voices'        => $voices,
		// Synthetic narration: platforms such as YouTube have disclosure rules for AI voices.
		'ai_generated'  => true,
		'provider'      => 'elevenlabs',
		'transcript'    => $script,
		// Word timings and SRT/VTT captions arrive in a later version; the key is reserved.
		'captions'      => null,
		'podcast'       => array(
			'feed'         => ! empty( $settings['podcast']['enabled'] ) ? thr_audio_podcast_url() : '',
			// The feed's episode ID: stays the same when audio is remade.
			'episode_guid' => 'thr-audio-' . $host . '-' . $post->ID,
		),
	);
	return $package;
}
add_filter( 'thr_distribute_package', 'thr_audio_distribute_package', 10, 2 );

/**
 * Announce audio changes on published articles (made, remade, removed).
 *
 * @param int    $meta_id  Meta ID.
 * @param int    $post_id  Post ID.
 * @param string $meta_key Meta key.
 */
function thr_audio_distribute_signal( $meta_id, $post_id, $meta_key ) {
	if ( 'thr_audio' === $meta_key ) {
		do_action( 'thr_distribute_signal', (int) $post_id, 'audio.updated' );
	}
}
add_action( 'added_post_meta', 'thr_audio_distribute_signal', 10, 3 );
add_action( 'updated_post_meta', 'thr_audio_distribute_signal', 10, 3 );
add_action( 'deleted_post_meta', 'thr_audio_distribute_signal', 10, 3 );
