<?php
/**
 * Post meta.
 *
 * thr_audio: the voice versions an article has. Written by the generator, the editor box,
 * or an outside tool through the REST API.
 * {
 *   "default":  "v1",
 *   "variants": [
 *     { "voice": "v1", "label": "Emma", "region": "London", "gender": "female",
 *       "attachment_id": 1234, "url": "", "duration": 179,                      <- full recording
 *       "preview_attachment_id": 1235, "preview_duration": 21,                  <- opening clip
 *       "model": "eleven_flash_v2_5", "text_hash": "…", "source": "elevenlabs",
 *       "generated_at": "2026-11-02T14:05:00Z" }
 *   ]
 * }
 *
 * thr_audio_status: per-voice job state, for the editor box (private).
 * thr_audio_publish_voices: voices ticked to be made on publish (absent = all).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fields of one voice variant and their REST types.
 *
 * @return array
 */
function thr_audio_variant_fields() {
	return array(
		'voice'                 => 'string',
		'label'                 => 'string',
		'region'                => 'string',
		'gender'                => 'string',
		'attachment_id'         => 'integer',
		'url'                   => 'string',
		'duration'              => 'number',
		'preview_attachment_id' => 'integer',
		'preview_duration'      => 'number',
		'model'                 => 'string',
		'text_hash'             => 'string',
		'source'                => 'string',
		'generated_at'          => 'string',
	);
}

function thr_audio_register_meta() {
	$properties = array();
	foreach ( thr_audio_variant_fields() as $field => $type ) {
		$properties[ $field ] = array( 'type' => $type );
	}

	foreach ( thr_audio_post_types() as $post_type ) {
		register_post_meta(
			$post_type,
			'thr_audio',
			array(
				'type'              => 'object',
				'single'            => true,
				'default'           => array(),
				'sanitize_callback' => 'thr_audio_sanitize',
				'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
				'show_in_rest'      => array(
					'schema' => array(
						'type'       => 'object',
						'properties' => array(
							'default'  => array( 'type' => 'string' ),
							'variants' => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => $properties,
								),
							),
						),
					),
				),
			)
		);
	}
}
add_action( 'init', 'thr_audio_register_meta' );

/**
 * Clean a thr_audio value before it is stored.
 *
 * Variants with no file at all are dropped; a repeated voice keeps its last entry.
 *
 * @param mixed $value Raw value.
 * @return array
 */
function thr_audio_sanitize( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$variants = array();
	$raw      = isset( $value['variants'] ) && is_array( $value['variants'] ) ? $value['variants'] : array();

	foreach ( $raw as $variant ) {
		if ( ! is_array( $variant ) ) {
			continue;
		}

		$clean = array(
			'attachment_id'         => isset( $variant['attachment_id'] ) ? absint( $variant['attachment_id'] ) : 0,
			'url'                   => isset( $variant['url'] ) ? esc_url_raw( $variant['url'] ) : '',
			'preview_attachment_id' => isset( $variant['preview_attachment_id'] ) ? absint( $variant['preview_attachment_id'] ) : 0,
		);
		if ( ! $clean['attachment_id'] && ! $clean['url'] && ! $clean['preview_attachment_id'] ) {
			continue;
		}

		$label = isset( $variant['label'] ) ? sanitize_text_field( $variant['label'] ) : '';
		$voice = isset( $variant['voice'] ) ? sanitize_key( $variant['voice'] ) : '';
		if ( ! $voice ) {
			$voice = sanitize_key( $label ) ?: 'voice-' . ( count( $variants ) + 1 );
		}
		$gender = isset( $variant['gender'] ) ? sanitize_key( $variant['gender'] ) : '';

		$variants[ $voice ] = array_merge(
			array(
				'voice'  => $voice,
				'label'  => $label,
				'region' => isset( $variant['region'] ) ? sanitize_text_field( $variant['region'] ) : '',
				'gender' => in_array( $gender, array( 'female', 'male' ), true ) ? $gender : '',
			),
			$clean,
			array(
				'duration'         => isset( $variant['duration'] ) ? absint( round( (float) $variant['duration'] ) ) : 0,
				'preview_duration' => isset( $variant['preview_duration'] ) ? absint( round( (float) $variant['preview_duration'] ) ) : 0,
				'model'            => isset( $variant['model'] ) ? sanitize_text_field( $variant['model'] ) : '',
				'text_hash'        => isset( $variant['text_hash'] ) ? preg_replace( '/[^a-f0-9]/', '', strtolower( $variant['text_hash'] ) ) : '',
				'source'           => isset( $variant['source'] ) ? sanitize_key( $variant['source'] ) : '',
				'generated_at'     => isset( $variant['generated_at'] ) ? sanitize_text_field( $variant['generated_at'] ) : '',
			)
		);
	}

	if ( empty( $variants ) ) {
		return array();
	}

	$default = isset( $value['default'] ) ? sanitize_key( $value['default'] ) : '';
	if ( ! isset( $variants[ $default ] ) ) {
		$default = (string) array_key_first( $variants );
	}

	return array(
		'default'  => $default,
		'variants' => array_values( $variants ),
	);
}

/**
 * Stored variants keyed by voice.
 *
 * @param int $post_id Post ID.
 * @return array
 */
function thr_audio_stored( $post_id ) {
	$audio = get_post_meta( $post_id, 'thr_audio', true );
	$list  = array();
	if ( ! empty( $audio['variants'] ) && is_array( $audio['variants'] ) ) {
		foreach ( $audio['variants'] as $variant ) {
			if ( is_array( $variant ) && ! empty( $variant['voice'] ) ) {
				$list[ $variant['voice'] ] = $variant;
			}
		}
	}
	return $list;
}

/**
 * Save one variant (merged into what is stored for that voice), or remove it with $variant = null.
 *
 * @param int        $post_id Post ID.
 * @param string     $voice   Voice key.
 * @param array|null $variant Fields to set, or null to remove.
 */
function thr_audio_put_variant( $post_id, $voice, $variant ) {
	$audio   = get_post_meta( $post_id, 'thr_audio', true );
	$default = ! empty( $audio['default'] ) ? $audio['default'] : '';
	$list    = thr_audio_stored( $post_id );

	if ( null === $variant ) {
		unset( $list[ $voice ] );
	} else {
		$list[ $voice ] = array_merge( isset( $list[ $voice ] ) ? $list[ $voice ] : array(), $variant, array( 'voice' => $voice ) );
	}

	if ( ! $list ) {
		delete_post_meta( $post_id, 'thr_audio' );
		return;
	}

	$slot = thr_audio_default_slot();
	if ( $slot && isset( $list[ $slot ] ) ) {
		$default = $slot;
	}

	update_post_meta(
		$post_id,
		'thr_audio',
		array(
			'default'  => $default,
			'variants' => array_values( $list ),
		)
	);
}

/**
 * URL and length of an attachment, or null if the file is gone.
 *
 * @param int $attachment_id Attachment ID.
 * @param int $duration      Stored length, if known.
 * @return array|null
 */
function thr_audio_file( $attachment_id, $duration = 0 ) {
	if ( ! $attachment_id ) {
		return null;
	}
	$url = wp_get_attachment_url( $attachment_id );
	if ( ! $url ) {
		return null;
	}
	if ( ! $duration ) {
		$meta     = wp_get_attachment_metadata( $attachment_id );
		$duration = ! empty( $meta['length'] ) ? (int) $meta['length'] : 0;
	}
	return array(
		'url'      => $url,
		'duration' => (int) $duration,
	);
}

/**
 * Voices for the front-end player, default first.
 *
 * Includes every configured voice: with a full file, with only the opening clip, or with
 * nothing yet (made when a reader picks it, if allowed). Names come from the current settings.
 *
 * @param int $post_id Post ID.
 * @return array[] voice, label, region, gender, url, duration, preview_url, preview_duration, state (full|preview|none).
 */
function thr_audio_get_variants( $post_id ) {
	$stored   = thr_audio_stored( $post_id );
	$voices   = thr_audio_voices();
	$settings = thr_audio_settings();
	$audio    = get_post_meta( $post_id, 'thr_audio', true );
	$default  = ! empty( $audio['default'] ) ? $audio['default'] : thr_audio_default_slot();
	$list     = array();

	$keys = array_unique( array_merge( array_keys( $voices ), array_keys( $stored ) ) );
	foreach ( $keys as $key ) {
		$variant = isset( $stored[ $key ] ) ? $stored[ $key ] : array();
		$config  = isset( $voices[ $key ] ) ? $voices[ $key ] : null;

		$full = thr_audio_file( isset( $variant['attachment_id'] ) ? $variant['attachment_id'] : 0, isset( $variant['duration'] ) ? $variant['duration'] : 0 );
		if ( ! $full && ! empty( $variant['url'] ) && empty( $variant['attachment_id'] ) ) {
			$full = array(
				'url'      => $variant['url'],
				'duration' => isset( $variant['duration'] ) ? (int) $variant['duration'] : 0,
			);
		}
		$preview = thr_audio_file( isset( $variant['preview_attachment_id'] ) ? $variant['preview_attachment_id'] : 0, isset( $variant['preview_duration'] ) ? $variant['preview_duration'] : 0 );

		if ( $full ) {
			$state = 'full';
		} elseif ( $preview ) {
			$state = 'preview';
		} elseif ( $config && $settings['on_demand'] ) {
			$state = 'none';
		} else {
			continue;
		}

		$item = array(
			'voice'            => $key,
			'label'            => $config ? $config['label'] : ( ! empty( $variant['label'] ) ? $variant['label'] : __( 'Narrator', 'thr-audio' ) ),
			'region'           => $config ? $config['region'] : ( isset( $variant['region'] ) ? $variant['region'] : '' ),
			'gender'           => $config ? $config['gender'] : ( isset( $variant['gender'] ) ? $variant['gender'] : '' ),
			'url'              => $full ? $full['url'] : '',
			'duration'         => $full ? $full['duration'] : 0,
			'preview_url'      => $preview ? $preview['url'] : '',
			'preview_duration' => $preview ? $preview['duration'] : 0,
			'state'            => $state,
			// Only voices set up in Settings can be made on request.
			'can_make'         => (bool) $config,
		);

		if ( $key === $default ) {
			array_unshift( $list, $item );
		} else {
			$list[] = $item;
		}
	}

	// A player needs at least one voice that can play now.
	foreach ( $list as $item ) {
		if ( 'none' !== $item['state'] ) {
			return $list;
		}
	}
	return array();
}
