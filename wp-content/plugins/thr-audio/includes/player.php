<?php
/**
 * Front-end "Listen to this article" player.
 *
 * The theme calls thr_audio_player() where the player should sit; nothing renders
 * when the post has no audio.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_audio_assets() {
	if ( ! is_singular( thr_audio_post_types() ) || ! thr_audio_get_variants( get_queried_object_id() ) ) {
		return;
	}

	wp_enqueue_style( 'thr-audio', THR_AUDIO_URL . 'assets/css/player.css', array(), THR_AUDIO_VERSION );
	wp_enqueue_script(
		'thr-audio',
		THR_AUDIO_URL . 'assets/js/player.js',
		array(),
		THR_AUDIO_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'thr_audio_assets' );

/**
 * Fallback placement: if the theme did not call thr_audio_player() before the article body,
 * put the player at the top of the body so it still shows.
 *
 * Runs after wpautop (priority 10) so the player markup is not wrapped in paragraphs.
 *
 * @param string $content Post content.
 * @return string
 */
function thr_audio_prepend_to_content( $content ) {
	$post_id = get_the_ID();

	if ( ! is_singular( thr_audio_post_types() ) || ! in_the_loop() || ! is_main_query() || get_queried_object_id() !== $post_id ) {
		return $content;
	}
	if ( ! empty( $GLOBALS['thr_audio_rendered'][ $post_id ] ) ) {
		return $content;
	}

	ob_start();
	thr_audio_player( $post_id );
	return ob_get_clean() . $content;
}
add_filter( 'the_content', 'thr_audio_prepend_to_content', 20 );

/**
 * Seconds as mm:ss.
 *
 * @param int $seconds Seconds.
 * @return string
 */
function thr_audio_format_time( $seconds ) {
	$seconds = max( 0, (int) $seconds );
	return sprintf( '%02d:%02d', floor( $seconds / 60 ), $seconds % 60 );
}

/**
 * Voice picker label, e.g. "Emma · London".
 *
 * @param array $variant Variant from thr_audio_get_variants().
 * @return string
 */
function thr_audio_voice_label( $variant ) {
	return $variant['region'] ? $variant['label'] . ' · ' . $variant['region'] : $variant['label'];
}

/**
 * Print the player.
 *
 * @param int|null $post_id Post ID, defaults to the current post.
 */
function thr_audio_player( $post_id = null ) {
	$post_id  = $post_id ? $post_id : get_the_ID();
	$variants = thr_audio_get_variants( $post_id );
	if ( empty( $variants ) ) {
		return;
	}

	$GLOBALS['thr_audio_rendered'][ $post_id ] = true;

	// Until the full recording exists, length is estimated from the text (about 15 characters a second).
	$estimate = (int) ceil( mb_strlen( thr_audio_script( $post_id ) ) / 15 );
	$first    = $variants[0];
	$length   = $first['duration'] ? $first['duration'] : $estimate;
	$minutes  = max( 1, (int) round( $length / 60 ) );
	$src      = $first['url'] ? $first['url'] : $first['preview_url'];
	?>
	<div class="thr-audio" data-thr-audio
		data-post="<?php echo esc_attr( $post_id ); ?>"
		data-endpoint="<?php echo esc_url( rest_url( 'thr-audio/v1/voice' ) ); ?>"
		data-estimate="<?php echo esc_attr( $estimate ); ?>"
		data-msg-preparing="<?php /* translators: %s: voice name. */ esc_attr_e( 'Preparing %s…', 'thr-audio' ); ?>"
		data-msg-rest="<?php esc_attr_e( 'Preparing the rest of the article…', 'thr-audio' ); ?>"
		data-msg-slow="<?php esc_attr_e( 'This is taking longer than usual. Keep listening and we\'ll switch when it\'s ready.', 'thr-audio' ); ?>"
		data-msg-failed="<?php esc_attr_e( 'Couldn\'t load this voice.', 'thr-audio' ); ?>"
		data-msg-busy="<?php esc_attr_e( 'Too many requests right now. Please try again in a little while.', 'thr-audio' ); ?>">
		<button type="button" class="thr-audio__play" data-thr-audio-play aria-label="<?php esc_attr_e( 'Play article audio', 'thr-audio' ); ?>" data-label-play="<?php esc_attr_e( 'Play article audio', 'thr-audio' ); ?>" data-label-pause="<?php esc_attr_e( 'Pause article audio', 'thr-audio' ); ?>">
			<svg class="thr-audio__icon-play" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 4.5v15l13-7.5z"/></svg>
			<svg class="thr-audio__icon-pause" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M6 4h4v16H6zm8 0h4v16h-4z"/></svg>
		</button>

		<div class="thr-audio__main">
			<div class="thr-audio__top">
				<span class="thr-audio__title"><?php esc_html_e( 'Listen to this article now', 'thr-audio' ); ?></span>
				<span class="thr-audio__length" data-thr-audio-length>
					<?php
					/* translators: %d: minutes. */
					echo esc_html( sprintf( _n( '%d min listen', '%d min listen', $minutes, 'thr-audio' ), $minutes ) );
					?>
				</span>
			</div>
			<div class="thr-audio__bar">
				<span class="thr-audio__time" data-thr-audio-current>00:00</span>
				<input type="range" class="thr-audio__seek" data-thr-audio-seek min="0" max="<?php echo esc_attr( max( 1, $length ) ); ?>" step="1" value="0" aria-label="<?php esc_attr_e( 'Seek', 'thr-audio' ); ?>" />
				<span class="thr-audio__time" data-thr-audio-total><?php echo esc_html( thr_audio_format_time( $length ) ); ?></span>
			</div>
			<p class="thr-audio__status" data-thr-audio-status role="status" aria-live="polite" hidden>
				<span class="thr-audio__spinner" aria-hidden="true"></span>
				<span data-thr-audio-status-text></span>
				<button type="button" class="thr-audio__retry" data-thr-audio-retry hidden><?php esc_html_e( 'Try again', 'thr-audio' ); ?></button>
			</p>
		</div>

		<div class="thr-audio__controls">
			<?php if ( count( $variants ) > 1 ) : ?>
				<label class="thr-audio__voice">
					<span class="screen-reader-text"><?php esc_html_e( 'Voice', 'thr-audio' ); ?></span>
					<select data-thr-audio-voice>
						<?php foreach ( $variants as $variant ) : ?>
							<option value="<?php echo esc_attr( $variant['voice'] ); ?>"
								data-label="<?php echo esc_attr( $variant['label'] ); ?>"
								data-src="<?php echo esc_url( $variant['url'] ); ?>"
								data-duration="<?php echo esc_attr( $variant['duration'] ); ?>"
								data-preview="<?php echo esc_url( $variant['preview_url'] ); ?>"
								data-preview-duration="<?php echo esc_attr( $variant['preview_duration'] ); ?>"
								data-can-make="<?php echo $variant['can_make'] ? '1' : '0'; ?>"><?php echo esc_html( thr_audio_voice_label( $variant ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php else : ?>
				<span class="thr-audio__voice-name"><?php echo esc_html( thr_audio_voice_label( $first ) ); ?></span>
				<?php // Single voice: the same data the dropdown carries, for the player script. ?>
				<span hidden data-thr-audio-only
					data-value="<?php echo esc_attr( $first['voice'] ); ?>"
					data-label="<?php echo esc_attr( $first['label'] ); ?>"
					data-src="<?php echo esc_url( $first['url'] ); ?>"
					data-duration="<?php echo esc_attr( $first['duration'] ); ?>"
					data-preview="<?php echo esc_url( $first['preview_url'] ); ?>"
					data-preview-duration="<?php echo esc_attr( $first['preview_duration'] ); ?>"
					data-can-make="<?php echo $first['can_make'] ? '1' : '0'; ?>"></span>
			<?php endif; ?>
			<button type="button" class="thr-audio__btn" data-thr-audio-skip="-15" aria-label="<?php esc_attr_e( 'Back 15 seconds', 'thr-audio' ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M4 12a8 8 0 1 0 2.3-5.7M4 4v4h4"/></svg>
				<span>15</span>
			</button>
			<button type="button" class="thr-audio__btn" data-thr-audio-skip="15" aria-label="<?php esc_attr_e( 'Forward 15 seconds', 'thr-audio' ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M20 12a8 8 0 1 1-2.3-5.7M20 4v4h-4"/></svg>
				<span>15</span>
			</button>
			<button type="button" class="thr-audio__speed" data-thr-audio-speed aria-label="<?php esc_attr_e( 'Playback speed', 'thr-audio' ); ?>">1x</button>
		</div>

		<audio preload="none" <?php echo $src ? 'src="' . esc_url( $src ) . '"' : ''; ?> data-thr-audio-el></audio>
	</div>
	<?php
}
