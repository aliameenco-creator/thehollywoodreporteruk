<?php
/**
 * Making audio: the publish trigger, the job queue and the generator itself.
 *
 * Publishing (or a reader / editor asking for a voice) adds jobs to a queue and immediately
 * fires a background request to this site that works through it, so nobody waits on the
 * page that triggered it. A WP-Cron event is the safety net if that request is blocked.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const THR_AUDIO_MAX_ATTEMPTS = 3;

/* -------------------------------------------------------------------------
 * Status (per post, per voice, per kind) — shown in the editor box.
 * ---------------------------------------------------------------------- */

/**
 * @param int    $post_id Post ID.
 * @param string $voice   Voice key.
 * @param string $kind    full|preview.
 * @return array state (queued|generating|ready|failed|''), error, updated.
 */
function thr_audio_get_status( $post_id, $voice, $kind ) {
	$all = get_post_meta( $post_id, 'thr_audio_status', true );
	return isset( $all[ $voice ][ $kind ] ) && is_array( $all[ $voice ][ $kind ] )
		? $all[ $voice ][ $kind ]
		: array(
			'state'   => '',
			'error'   => '',
			'updated' => 0,
		);
}

/**
 * @param int         $post_id Post ID.
 * @param string      $voice   Voice key.
 * @param string      $kind    full|preview.
 * @param string|null $state   New state, or null to clear.
 * @param string      $error   Error message.
 */
function thr_audio_set_status( $post_id, $voice, $kind, $state, $error = '' ) {
	$all = get_post_meta( $post_id, 'thr_audio_status', true );
	$all = is_array( $all ) ? $all : array();

	if ( null === $state ) {
		unset( $all[ $voice ][ $kind ] );
		if ( empty( $all[ $voice ] ) ) {
			unset( $all[ $voice ] );
		}
	} else {
		$all[ $voice ][ $kind ] = array(
			'state'   => $state,
			'error'   => $error,
			'updated' => time(),
		);
	}

	if ( $all ) {
		update_post_meta( $post_id, 'thr_audio_status', $all );
	} else {
		delete_post_meta( $post_id, 'thr_audio_status' );
	}
}

/* -------------------------------------------------------------------------
 * Locks: add_option() is an INSERT on a unique key, so only one caller wins.
 * ---------------------------------------------------------------------- */

function thr_audio_lock( $name, $ttl ) {
	$key = 'thr_audio_lock_' . $name;
	if ( add_option( $key, time(), '', 'no' ) ) {
		return true;
	}
	// Take over a lock left behind by a request that died.
	$since = (int) get_option( $key );
	if ( $since && time() - $since > $ttl ) {
		delete_option( $key );
		return add_option( $key, time(), '', 'no' );
	}
	return false;
}

function thr_audio_unlock( $name ) {
	delete_option( 'thr_audio_lock_' . $name );
}

/** Seconds after which a silent worker counts as dead (its lock and its job are taken over). */
const THR_AUDIO_RUNNER_TTL = 300;

/**
 * Whether a worker is alive: it holds the lock and has touched it recently.
 *
 * @return bool
 */
function thr_audio_runner_alive() {
	wp_cache_delete( 'thr_audio_lock_runner', 'options' );
	$since = (int) get_option( 'thr_audio_lock_runner' );
	return $since && time() - $since < THR_AUDIO_RUNNER_TTL;
}

/**
 * Run $callback while holding the queue lock (waits up to ~3 seconds for it).
 *
 * @param callable $callback Receives and returns the queue array.
 * @return bool Whether it ran.
 */
function thr_audio_with_queue( $callback ) {
	for ( $i = 0; $i < 30; $i++ ) {
		if ( thr_audio_lock( 'queue', 10 ) ) {
			wp_cache_delete( 'thr_audio_queue', 'options' );
			$queue = get_option( 'thr_audio_queue', array() );
			$queue = call_user_func( $callback, is_array( $queue ) ? $queue : array() );
			update_option( 'thr_audio_queue', $queue, false );
			thr_audio_unlock( 'queue' );
			return true;
		}
		usleep( 100000 );
	}
	return false;
}

/* -------------------------------------------------------------------------
 * Queue.
 * ---------------------------------------------------------------------- */

/**
 * Add a job. A job already waiting for the same post, voice and kind is not repeated.
 *
 * @param int    $post_id  Post ID.
 * @param string $voice    Voice slot.
 * @param string $kind     full|preview.
 * @param bool   $force    Remake even if an up-to-date file exists.
 * @param int    $priority 0 = a reader is waiting, 1 = background.
 */
function thr_audio_enqueue( $post_id, $voice, $kind, $force = false, $priority = 1 ) {
	$key = $post_id . '|' . $voice . '|' . $kind;

	thr_audio_with_queue(
		function ( $queue ) use ( $key, $post_id, $voice, $kind, $force, $priority ) {
			if ( isset( $queue[ $key ] ) ) {
				$queue[ $key ]['force']    = $queue[ $key ]['force'] || $force;
				$queue[ $key ]['priority'] = min( $queue[ $key ]['priority'], $priority );
				return $queue;
			}
			$queue[ $key ] = array(
				'post'       => (int) $post_id,
				'voice'      => $voice,
				'kind'       => $kind,
				'force'      => (bool) $force,
				'priority'   => (int) $priority,
				'attempts'   => 0,
				'not_before' => 0,
				'added'      => time(),
			);
			return $queue;
		}
	);

	thr_audio_set_status( $post_id, $voice, $kind, 'queued' );
}

/**
 * Start the background worker once the current request has finished (so everything the request
 * saves — e.g. the dek an API publish sets just after the status change — is in place first).
 */
function thr_audio_kick() {
	static $hooked = false;
	if ( did_action( 'shutdown' ) ) {
		thr_audio_kick_now();
	} elseif ( ! $hooked ) {
		$hooked = true;
		add_action( 'shutdown', 'thr_audio_kick_now' );
	}
}

/**
 * Do the queued work in this request, after the reply has gone to the browser. Used by the
 * reader endpoint so a voice someone is waiting for never depends on the background request
 * (which some hosts drop) or on WP-Cron (which only runs when the site gets visits).
 *
 * Returns false when the server can't send the reply early; the caller then uses thr_audio_kick().
 *
 * @return bool
 */
function thr_audio_work_after_reply() {
	if ( ! function_exists( 'litespeed_finish_request' ) && ! function_exists( 'fastcgi_finish_request' ) ) {
		return false;
	}
	static $hooked = false;
	if ( ! $hooked ) {
		$hooked = true;
		add_action(
			'shutdown',
			function () {
				if ( function_exists( 'litespeed_finish_request' ) ) {
					litespeed_finish_request();
				} else {
					fastcgi_finish_request();
				}
				thr_audio_run_queue();
			},
			1000
		);
	}
	return true;
}

/**
 * Fire the background request now, with WP-Cron as the fallback.
 */
function thr_audio_kick_now() {
	if ( ! wp_next_scheduled( 'thr_audio_cron' ) ) {
		wp_schedule_single_event( time() + 60, 'thr_audio_cron' );
	}

	wp_remote_post(
		admin_url( 'admin-ajax.php' ),
		array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			'body'      => array(
				'action' => 'thr_audio_run',
				'token'  => thr_audio_run_token(),
			),
		)
	);
}

/**
 * Secret that lets only this site start the worker.
 *
 * @return string
 */
function thr_audio_run_token() {
	$token = get_option( 'thr_audio_run_token' );
	if ( ! $token ) {
		$token = wp_generate_password( 40, false );
		update_option( 'thr_audio_run_token', $token, false );
	}
	return $token;
}

function thr_audio_ajax_run() {
	$token = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- authenticated by the shared secret.
	if ( ! hash_equals( thr_audio_run_token(), $token ) ) {
		wp_die( '', '', array( 'response' => 403 ) );
	}
	thr_audio_run_queue();
	wp_die();
}
add_action( 'wp_ajax_thr_audio_run', 'thr_audio_ajax_run' );
add_action( 'wp_ajax_nopriv_thr_audio_run', 'thr_audio_ajax_run' );
add_action( 'thr_audio_cron', 'thr_audio_run_queue' );

/**
 * @param array $schedules Cron schedules.
 * @return array
 */
function thr_audio_cron_schedules( $schedules ) {
	$schedules['thr_audio_5min'] = array(
		'interval' => 5 * MINUTE_IN_SECONDS,
		'display'  => __( 'Every 5 minutes (THR Audio)', 'thr-audio' ),
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'thr_audio_cron_schedules' );

/**
 * Safety net every 5 minutes: if work is waiting and no worker is alive, start one.
 * Recovers from a worker the host stopped, a lost background request or a stale lock.
 */
function thr_audio_watchdog() {
	$queue = get_option( 'thr_audio_queue', array() );
	if ( $queue && ! thr_audio_runner_alive() ) {
		thr_audio_run_queue();
	}
}
add_action( 'thr_audio_watchdog', 'thr_audio_watchdog' );

function thr_audio_schedule_watchdog() {
	if ( ! wp_next_scheduled( 'thr_audio_watchdog' ) ) {
		wp_schedule_event( time() + 60, 'thr_audio_5min', 'thr_audio_watchdog' );
	}
}
add_action( 'init', 'thr_audio_schedule_watchdog' );

/**
 * Work through the queue: readers' requests first, then oldest. One worker at a time.
 */
function thr_audio_run_queue() {
	if ( ! thr_audio_lock( 'runner', THR_AUDIO_RUNNER_TTL ) ) {
		return;
	}

	ignore_user_abort( true );
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- some hosts disallow it.
	}

	$started = time();

	// Short shifts: Hostinger stopped workers after about 60 seconds (9 November 2026), whatever
	// set_time_limit() says. Finishing early lets the worker hand over cleanly to the next one.
	while ( time() - $started < 45 ) {
		$job = thr_audio_next_job();
		if ( ! $job ) {
			break;
		}
		thr_audio_process_job( $job );
		// Keep the lock fresh so a long run is not mistaken for a dead one.
		update_option( 'thr_audio_lock_runner', time(), false );
	}

	thr_audio_unlock( 'runner' );

	// More to do (time ran out, or retries waiting): keep going.
	$queue = get_option( 'thr_audio_queue', array() );
	if ( $queue ) {
		$soonest = min( wp_list_pluck( $queue, 'not_before' ) );
		if ( $soonest <= time() ) {
			thr_audio_kick();
		} elseif ( ! wp_next_scheduled( 'thr_audio_cron' ) ) {
			wp_schedule_single_event( $soonest, 'thr_audio_cron' );
		}
	}
}

/**
 * Take the next due job off the queue.
 *
 * @return array|null
 */
function thr_audio_next_job() {
	$picked = null;

	thr_audio_with_queue(
		function ( $queue ) use ( &$picked ) {
			$due = array_filter(
				$queue,
				function ( $job ) {
					// A job "running" for longer than a worker can live was lost with its worker: run it again.
					$running = ! empty( $job['running_since'] ) && time() - (int) $job['running_since'] < THR_AUDIO_RUNNER_TTL;
					return $job['not_before'] <= time() && ! $running;
				}
			);
			if ( ! $due ) {
				return $queue;
			}
			uasort(
				$due,
				function ( $a, $b ) {
					return array( $a['priority'], $a['added'] ) <=> array( $b['priority'], $b['added'] );
				}
			);
			$key                            = array_key_first( $due );
			$queue[ $key ]['running_since'] = time();
			$picked                         = $queue[ $key ];
			return $queue;
		}
	);

	return $picked;
}

/**
 * Run one job, retrying temporary failures with a growing wait.
 *
 * @param array $job Job.
 */
function thr_audio_process_job( $job ) {
	$key = $job['post'] . '|' . $job['voice'] . '|' . $job['kind'];
	thr_audio_set_status( $job['post'], $job['voice'], $job['kind'], 'generating' );

	$result = thr_audio_generate( $job['post'], $job['voice'], $job['kind'], $job['force'] );

	$data  = is_wp_error( $result ) ? $result->get_error_data() : array();
	$retry = is_wp_error( $result ) && ! empty( $data['retry'] ) && $job['attempts'] + 1 < THR_AUDIO_MAX_ATTEMPTS;

	// The job stayed in the queue while it ran (so a dying worker can't lose it): settle it now.
	thr_audio_with_queue(
		function ( $queue ) use ( $key, $job, $retry ) {
			if ( $retry ) {
				$job['attempts']++;
				$job['not_before'] = time() + 30 * $job['attempts'];
				unset( $job['running_since'] );
				$queue[ $key ] = $job;
			} else {
				unset( $queue[ $key ] );
			}
			return $queue;
		}
	);

	if ( ! is_wp_error( $result ) ) {
		thr_audio_set_status( $job['post'], $job['voice'], $job['kind'], 'ready' );
		return;
	}

	if ( $retry ) {
		thr_audio_set_status( $job['post'], $job['voice'], $job['kind'], 'queued', $result->get_error_message() . ' ' . __( 'Retrying shortly.', 'thr-audio' ) );
		return;
	}

	thr_audio_set_status( $job['post'], $job['voice'], $job['kind'], 'failed', $result->get_error_message() );
}

/* -------------------------------------------------------------------------
 * Publish trigger.
 * ---------------------------------------------------------------------- */

/**
 * Voices ticked for this post in the editor (all usable voices when nothing was saved).
 *
 * @param int $post_id Post ID.
 * @return string[] Slots.
 */
function thr_audio_publish_voices( $post_id ) {
	$voices = array_keys( thr_audio_voices() );
	$picked = get_post_meta( $post_id, 'thr_audio_publish_voices', true );
	return is_array( $picked ) ? array_values( array_intersect( $voices, $picked ) ) : $voices;
}

/**
 * Queue the publish set: the default voice in full, an opening clip for each other ticked voice.
 *
 * @param int $post_id Post ID.
 */
function thr_audio_queue_publish_set( $post_id ) {
	$picked = thr_audio_publish_voices( $post_id );
	if ( ! $picked ) {
		return;
	}

	$default = thr_audio_default_slot();
	$full    = in_array( $default, $picked, true ) ? $default : $picked[0];

	$others = 'preview' === thr_audio_settings()['other_voices'] ? 'preview' : 'full';

	thr_audio_enqueue( $post_id, $full, 'full' );
	foreach ( $picked as $slot ) {
		if ( $slot !== $full ) {
			thr_audio_enqueue( $post_id, $slot, $others );
		}
	}
	thr_audio_kick();
}

/**
 * @param string  $new_status New status.
 * @param string  $old_status Old status.
 * @param WP_Post $post       Post.
 */
function thr_audio_on_publish( $new_status, $old_status, $post ) {
	if ( 'publish' !== $new_status || 'publish' === $old_status ) {
		return;
	}
	if ( ! in_array( $post->post_type, thr_audio_post_types(), true ) || wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
		return;
	}
	if ( ! thr_audio_settings()['auto_publish'] ) {
		return;
	}
	thr_audio_queue_publish_set( $post->ID );
}
add_action( 'transition_post_status', 'thr_audio_on_publish', 20, 3 );

/* -------------------------------------------------------------------------
 * The generator.
 * ---------------------------------------------------------------------- */

/**
 * Make one recording and attach it to the post.
 *
 * @param int    $post_id Post ID.
 * @param string $voice   Voice slot.
 * @param string $kind    full|preview.
 * @param bool   $force   Remake even if up to date.
 * @return true|WP_Error WP_Error data carries 'retry' => true for temporary failures.
 */
function thr_audio_generate( $post_id, $voice, $kind, $force = false ) {
	$post     = get_post( $post_id );
	$voices   = thr_audio_voices();
	$settings = thr_audio_settings();

	if ( ! $post ) {
		return new WP_Error( 'thr_audio_post', __( 'Article not found.', 'thr-audio' ) );
	}
	if ( ! isset( $voices[ $voice ] ) ) {
		return new WP_Error( 'thr_audio_voice', __( 'This voice is not set up (Settings → THR Audio).', 'thr-audio' ) );
	}

	$config = $voices[ $voice ];
	$script = thr_audio_script( $post_id );
	if ( '' === trim( $script ) ) {
		return new WP_Error( 'thr_audio_text', __( 'The article has no text to read.', 'thr-audio' ) );
	}

	$text      = 'preview' === $kind ? thr_audio_opening( $script, $settings['preview_chars'] ) : $script;
	$text_hash = md5( $script );
	$model     = 'live' === $settings['mode'] ? $settings['model'] : 'test';
	$stored    = thr_audio_stored( $post_id );
	$existing  = isset( $stored[ $voice ] ) ? $stored[ $voice ] : array();
	$id_field  = 'preview' === $kind ? 'preview_attachment_id' : 'attachment_id';
	$old_id    = isset( $existing[ $id_field ] ) ? (int) $existing[ $id_field ] : 0;

	// Already up to date: same article text, same model, file still there.
	if ( ! $force && $old_id && wp_get_attachment_url( $old_id )
		&& isset( $existing['text_hash'], $existing['model'] ) && $existing['text_hash'] === $text_hash && $existing['model'] === $model ) {
		return true;
	}
	// A full recording makes a new opening clip pointless.
	if ( 'preview' === $kind && ! $force && ! empty( $existing['attachment_id'] ) && wp_get_attachment_url( $existing['attachment_id'] ) ) {
		return true;
	}

	$chars = mb_strlen( $text );
	if ( 'live' === $settings['mode'] && ! thr_audio_within_cap( $chars ) ) {
		return new WP_Error( 'thr_audio_cap', __( 'Monthly character limit reached (Settings → THR Audio).', 'thr-audio' ) );
	}

	$pieces = 'preview' === $kind ? array( $text ) : thr_audio_pieces( $text );
	$bytes  = '';
	foreach ( $pieces as $i => $piece ) {
		$previous = $i > 0 ? mb_substr( $pieces[ $i - 1 ], -300 ) : '';
		$next     = isset( $pieces[ $i + 1 ] ) ? mb_substr( $pieces[ $i + 1 ], 0, 300 ) : '';
		$audio    = THR_Audio_ElevenLabs::speak( $config['voice_id'], $piece, $previous, $next );
		if ( is_wp_error( $audio ) ) {
			return $audio;
		}
		$bytes .= $audio;
	}

	if ( 'live' === $settings['mode'] ) {
		thr_audio_add_usage( $chars );
	}

	$attachment_id = thr_audio_save_file( $post, $voice, $config, $kind, $bytes );
	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}

	$meta     = wp_get_attachment_metadata( $attachment_id );
	$duration = ! empty( $meta['length'] ) ? (int) $meta['length'] : 0;

	$fields = array(
		'label'        => $config['label'],
		'region'       => $config['region'],
		'gender'       => $config['gender'],
		'model'        => $model,
		'text_hash'    => $text_hash,
		'source'       => 'live' === $settings['mode'] ? 'elevenlabs' : 'test',
		'generated_at' => gmdate( 'c' ),
	);
	if ( 'preview' === $kind ) {
		$fields['preview_attachment_id'] = $attachment_id;
		$fields['preview_duration']      = $duration;
	} else {
		$fields['attachment_id'] = $attachment_id;
		$fields['url']           = '';
		$fields['duration']      = $duration;
	}
	thr_audio_put_variant( $post_id, $voice, $fields );

	// Replace, don't pile up: remove the old recording only if this plugin made it.
	if ( $old_id && $old_id !== $attachment_id && get_post_meta( $old_id, '_thr_audio_generated', true ) ) {
		wp_delete_attachment( $old_id, true );
	}

	thr_audio_purge_page( $post_id );
	return true;
}

/**
 * Recordings live in their own folder, uploads/thr-audio/YYYY/MM, apart from photos.
 *
 * @param array $dirs wp_upload_dir() values.
 * @return array
 */
function thr_audio_upload_dir( $dirs ) {
	$sub            = '/thr-audio' . $dirs['subdir'];
	$dirs['path']   = $dirs['basedir'] . $sub;
	$dirs['url']    = $dirs['baseurl'] . $sub;
	$dirs['subdir'] = $sub;
	return $dirs;
}

/**
 * Let browsers and the CDN keep recordings for a year. Safe because a file never changes:
 * a remake is saved under a new name. Written once into uploads/thr-audio.
 */
function thr_audio_write_cache_rules() {
	$dir  = wp_upload_dir()['basedir'] . '/thr-audio';
	$file = $dir . '/.htaccess';
	if ( file_exists( $file ) || ! wp_mkdir_p( $dir ) ) {
		return;
	}
	$rules = "# THR Audio: recordings never change (a remake gets a new file name), so they can be cached for a year.\n"
		. "<IfModule mod_mime.c>\n\tAddType audio/mpeg .mp3\n</IfModule>\n"
		. "<IfModule mod_headers.c>\n\t<FilesMatch \"\\.mp3$\">\n\t\tHeader set Cache-Control \"public, max-age=31536000, immutable\"\n\t</FilesMatch>\n</IfModule>\n";
	file_put_contents( $file, $rules ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
}

/**
 * Save MP3 bytes as a recording, attached to the article. Recordings are kept out of the
 * Media Library and listed in the Audio Library instead.
 *
 * @param WP_Post $post   Article.
 * @param string  $voice  Voice slot.
 * @param array   $config Voice settings.
 * @param string  $kind   full|preview.
 * @param string  $bytes  MP3 data.
 * @return int|WP_Error Attachment ID.
 */
function thr_audio_save_file( $post, $voice, $config, $kind, $bytes ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$name = sanitize_title( $post->post_name ? $post->post_name : $post->post_title );
	$name = mb_substr( $name ? $name : 'article-' . $post->ID, 0, 80 );
	// The random tail gives every recording its own address, so cached copies are never stale.
	$name = sanitize_file_name( $name . '-' . sanitize_title( $config['label'] . ' ' . $config['region'] ) . ( 'preview' === $kind ? '-opening' : '' ) . '-' . strtolower( wp_generate_password( 8, false ) ) . '.mp3' );

	thr_audio_write_cache_rules();
	add_filter( 'upload_dir', 'thr_audio_upload_dir' );
	$upload = wp_upload_bits( $name, null, $bytes );
	remove_filter( 'upload_dir', 'thr_audio_upload_dir' );
	if ( ! empty( $upload['error'] ) ) {
		return new WP_Error( 'thr_audio_upload', __( 'Could not save the MP3: ', 'thr-audio' ) . $upload['error'] );
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'audio/mpeg',
			'post_title'     => sprintf(
				/* translators: 1: article title, 2: voice name, 3: "opening" or "". */
				__( 'Audio: %1$s (%2$s)%3$s', 'thr-audio' ),
				get_the_title( $post ),
				trim( $config['label'] . ' ' . $config['region'] ),
				'preview' === $kind ? ' — ' . __( 'opening', 'thr-audio' ) : ''
			),
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$upload['file'],
		$post->ID,
		true
	);
	if ( is_wp_error( $attachment_id ) ) {
		wp_delete_file( $upload['file'] );
		return $attachment_id;
	}

	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
	update_post_meta( $attachment_id, '_thr_audio_generated', 1 );
	update_post_meta( $attachment_id, '_thr_audio_voice', $voice );
	update_post_meta( $attachment_id, '_thr_audio_kind', $kind );
	update_post_meta( $attachment_id, '_thr_audio_bytes', strlen( $bytes ) );
	delete_transient( 'thr_audio_library_totals' );

	/** Fires once a recording is saved; storage.php copies it to R2 when Storage is R2. */
	do_action( 'thr_audio_file_saved', (int) $attachment_id );

	return (int) $attachment_id;
}

/**
 * Clear cached copies of the article so the new voice shows up.
 *
 * @param int $post_id Post ID.
 */
function thr_audio_purge_page( $post_id ) {
	clean_post_cache( $post_id );
	do_action( 'litespeed_purge_post', $post_id );
	do_action( 'thr_audio_purge_post', $post_id );
}

/* -------------------------------------------------------------------------
 * Spending cap.
 * ---------------------------------------------------------------------- */

function thr_audio_within_cap( $chars ) {
	$cap = (int) thr_audio_settings()['monthly_cap'];
	if ( $cap <= 0 ) {
		return true;
	}
	if ( thr_audio_usage_this_month() + $chars <= $cap ) {
		return true;
	}
	thr_audio_alert_cap();
	return false;
}

function thr_audio_add_usage( $chars ) {
	$key = 'thr_audio_usage_' . gmdate( 'Ym' );
	update_option( $key, (int) get_option( $key, 0 ) + (int) $chars, false );
}

/**
 * One email per month when the cap is hit.
 */
function thr_audio_alert_cap() {
	$flag = 'thr_audio_cap_alert_' . gmdate( 'Ym' );
	if ( get_option( $flag ) ) {
		return;
	}
	update_option( $flag, 1, false );

	$email = thr_audio_settings()['alert_email'];
	if ( $email ) {
		wp_mail(
			$email,
			__( '[THR Audio] Monthly audio limit reached', 'thr-audio' ),
			sprintf(
				/* translators: %s: settings URL. */
				__( "The monthly character limit for article audio has been reached, so no new audio is being made.\n\nRaise the limit or wait for next month: %s", 'thr-audio' ),
				admin_url( 'options-general.php?page=thr-audio' )
			)
		);
	}
}
