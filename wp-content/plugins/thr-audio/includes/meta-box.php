<?php
/**
 * "Article Audio" box in the post editor.
 *
 * One row per voice: whether it is made on publish, its status (queued, generating, ready,
 * failed, outdated), players for what exists, and actions. Actions apply immediately over
 * AJAX — they do not wait for the post to be saved — and the box refreshes itself while
 * audio is being made.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_audio_add_meta_box() {
	add_meta_box(
		'thr_audio_box',
		__( 'Article Audio', 'thr-audio' ),
		'thr_audio_render_meta_box',
		thr_audio_post_types(),
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'thr_audio_add_meta_box' );

function thr_audio_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->post_type, thr_audio_post_types(), true ) ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script( 'thr-audio-admin', THR_AUDIO_URL . 'assets/js/admin.js', array(), THR_AUDIO_VERSION, true );
	wp_add_inline_style(
		'wp-admin',
		'.thr-audio-admin td{vertical-align:top}'
		. '.thr-audio-admin audio{display:block;width:100%;max-width:320px;height:32px;margin-bottom:4px}'
		. '.thr-audio-admin__clip{font-size:11px;color:#646970;margin:0 0 2px}'
		. '.thr-audio-admin__state{font-weight:600}'
		. '.thr-audio-admin__state--ready{color:#008a20}'
		. '.thr-audio-admin__state--failed,.thr-audio-admin__state--outdated{color:#b32d2e}'
		. '.thr-audio-admin__state--working{color:#996800}'
		. '.thr-audio-admin__meta{color:#646970;font-size:12px}'
		. '.thr-audio-admin__actions .button{margin:0 4px 4px 0}'
		. '.thr-audio-admin-bar{display:flex;gap:8px;align-items:center;margin:10px 0 0}'
		. '[data-thr-audio-panel].is-loading{opacity:.6;pointer-events:none}'
	);
}
add_action( 'admin_enqueue_scripts', 'thr_audio_admin_assets' );

function thr_audio_render_meta_box( $post ) {
	?>
	<div data-thr-audio-panel data-post="<?php echo esc_attr( $post->ID ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'thr_audio_admin' ) ); ?>">
		<?php echo thr_audio_panel_html( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
	</div>
	<?php
}

/**
 * Readable model name.
 *
 * @param string $model Model ID.
 * @return string
 */
function thr_audio_model_name( $model ) {
	if ( 'test' === $model ) {
		return __( 'Test sample', 'thr-audio' );
	}
	if ( 'manual' === $model || '' === $model ) {
		return __( 'Uploaded MP3', 'thr-audio' );
	}
	$models = get_transient( 'thr_audio_models' );
	return is_array( $models ) && isset( $models[ $model ] ) ? $models[ $model ] : $model;
}

/**
 * The box contents (also returned by the AJAX refresh).
 *
 * @param int $post_id Post ID.
 * @return string
 */
function thr_audio_panel_html( $post_id ) {
	$settings = thr_audio_settings();
	$voices   = thr_audio_voices();
	$stored   = thr_audio_stored( $post_id );
	$ticked   = thr_audio_publish_voices( $post_id );
	$default  = thr_audio_default_slot();
	$hash     = md5( thr_audio_script( $post_id ) );
	$busy     = false;

	$keys = array_unique( array_merge( array_keys( $voices ), array_keys( $stored ) ) );

	ob_start();

	if ( ! $voices ) {
		printf(
			'<p>%s <a href="%s">%s</a></p>',
			esc_html__( 'No voices are set up yet.', 'thr-audio' ),
			esc_url( admin_url( 'options-general.php?page=thr-audio' ) ),
			esc_html__( 'Set them up in Settings → THR Audio.', 'thr-audio' )
		);
	}

	if ( 'live' !== $settings['mode'] ) {
		echo '<p class="notice notice-warning inline" style="padding:6px 10px">' . esc_html__( 'Test mode: audio uses a sample recording and costs nothing. Switch to Live in Settings → THR Audio when ready.', 'thr-audio' ) . '</p>';
	}

	if ( $keys ) :
		?>
		<p class="description">
			<?php esc_html_e( 'Ticked voices are made when the article is published: the default voice in full, the others as a short opening clip. A full version of another voice is made when a reader picks it, or when you click "Make full recording".', 'thr-audio' ); ?>
		</p>
		<table class="widefat striped thr-audio-admin">
			<thead>
				<tr>
					<th style="width:8%"><?php esc_html_e( 'On publish', 'thr-audio' ); ?></th>
					<th style="width:16%"><?php esc_html_e( 'Voice', 'thr-audio' ); ?></th>
					<th style="width:30%"><?php esc_html_e( 'Status', 'thr-audio' ); ?></th>
					<th><?php esc_html_e( 'Listen', 'thr-audio' ); ?></th>
					<th style="width:24%"><?php esc_html_e( 'Actions', 'thr-audio' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $keys as $key ) :
				$config  = isset( $voices[ $key ] ) ? $voices[ $key ] : null;
				$variant = isset( $stored[ $key ] ) ? $stored[ $key ] : array();
				$full    = thr_audio_file( isset( $variant['attachment_id'] ) ? $variant['attachment_id'] : 0, isset( $variant['duration'] ) ? $variant['duration'] : 0 );
				if ( ! $full && ! empty( $variant['url'] ) && empty( $variant['attachment_id'] ) ) {
					$full = array(
						'url'      => $variant['url'],
						'duration' => isset( $variant['duration'] ) ? (int) $variant['duration'] : 0,
					);
				}
				$preview  = thr_audio_file( isset( $variant['preview_attachment_id'] ) ? $variant['preview_attachment_id'] : 0, isset( $variant['preview_duration'] ) ? $variant['preview_duration'] : 0 );
				$s_full   = thr_audio_get_status( $post_id, $key, 'full' );
				$s_prev   = thr_audio_get_status( $post_id, $key, 'preview' );
				$working  = in_array( $s_full['state'], array( 'queued', 'generating' ), true ) || in_array( $s_prev['state'], array( 'queued', 'generating' ), true );
				$busy     = $busy || $working;
				$label    = $config ? $config['label'] : ( ! empty( $variant['label'] ) ? $variant['label'] : $key );
				$region   = $config ? $config['region'] : ( isset( $variant['region'] ) ? $variant['region'] : '' );
				$outdated = $full && ! empty( $variant['text_hash'] ) && $variant['text_hash'] !== $hash;
				?>
				<tr>
					<td>
						<?php if ( $config ) : ?>
							<input type="checkbox" data-thr-audio-toggle="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $ticked, true ) ); ?> aria-label="<?php echo esc_attr( sprintf( /* translators: %s: voice name. */ __( 'Make %s on publish', 'thr-audio' ), $label ) ); ?>" />
						<?php endif; ?>
					</td>
					<td>
						<strong><?php echo esc_html( $label ); ?></strong>
						<?php if ( $region ) : ?>
							<br /><span class="thr-audio-admin__meta"><?php echo esc_html( $region ); ?></span>
						<?php endif; ?>
						<?php if ( $key === $default ) : ?>
							<br /><span class="thr-audio-admin__meta"><?php esc_html_e( 'Default voice', 'thr-audio' ); ?></span>
						<?php endif; ?>
						<?php if ( ! $config ) : ?>
							<br /><span class="thr-audio-admin__meta"><?php esc_html_e( 'Not in Settings (added by hand or by an import)', 'thr-audio' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php
						if ( 'generating' === $s_full['state'] ) {
							thr_audio_state( 'working', __( 'Making the full recording…', 'thr-audio' ), $s_full['error'] );
						} elseif ( 'queued' === $s_full['state'] ) {
							thr_audio_state( 'working', __( 'Full recording queued…', 'thr-audio' ), $s_full['error'] );
						} elseif ( $full ) {
							thr_audio_state( $outdated ? 'outdated' : 'ready', $outdated ? __( 'Outdated: the article text changed after this was made', 'thr-audio' ) : __( 'Full recording ready', 'thr-audio' ) );
						} elseif ( 'failed' === $s_full['state'] ) {
							thr_audio_state( 'failed', __( 'Full recording failed', 'thr-audio' ), $s_full['error'] );
						} elseif ( in_array( $s_prev['state'], array( 'queued', 'generating' ), true ) ) {
							thr_audio_state( 'working', __( 'Making the opening clip…', 'thr-audio' ), $s_prev['error'] );
						} elseif ( $preview ) {
							thr_audio_state( 'ready', __( 'Opening clip ready', 'thr-audio' ), __( 'Full version is made when a reader picks this voice.', 'thr-audio' ) );
						} elseif ( 'failed' === $s_prev['state'] ) {
							thr_audio_state( 'failed', __( 'Opening clip failed', 'thr-audio' ), $s_prev['error'] );
						} else {
							thr_audio_state( '', __( 'Not made yet', 'thr-audio' ) );
						}

						if ( $full || $preview ) {
							$bits = array();
							if ( $full && $full['duration'] ) {
								$bits[] = thr_audio_format_time( $full['duration'] );
							}
							$bits[] = thr_audio_model_name( isset( $variant['model'] ) ? $variant['model'] : '' );
							if ( ! empty( $variant['generated_at'] ) ) {
								$bits[] = wp_date( 'j M Y, H:i', strtotime( $variant['generated_at'] ) );
							}
							echo '<div class="thr-audio-admin__meta">' . esc_html( implode( ' · ', $bits ) ) . '</div>';
						}
						?>
					</td>
					<td>
						<?php if ( $full ) : ?>
							<p class="thr-audio-admin__clip"><?php esc_html_e( 'Full', 'thr-audio' ); ?></p>
							<audio controls preload="none" src="<?php echo esc_url( $full['url'] ); ?>"></audio>
						<?php endif; ?>
						<?php if ( $preview ) : ?>
							<p class="thr-audio-admin__clip"><?php esc_html_e( 'Opening clip', 'thr-audio' ); ?></p>
							<audio controls preload="none" src="<?php echo esc_url( $preview['url'] ); ?>"></audio>
						<?php endif; ?>
					</td>
					<td class="thr-audio-admin__actions">
						<?php if ( $config && ! $working ) : ?>
							<button type="button" class="button" data-thr-audio-action="generate" data-voice="<?php echo esc_attr( $key ); ?>">
								<?php echo $full ? esc_html__( 'Remake', 'thr-audio' ) : esc_html__( 'Make full recording', 'thr-audio' ); ?>
							</button>
						<?php endif; ?>
						<button type="button" class="button" data-thr-audio-action="own" data-voice="<?php echo esc_attr( $key ); ?>"><?php esc_html_e( 'Use my own MP3', 'thr-audio' ); ?></button>
						<?php if ( ( $full || $preview ) && ! $working ) : ?>
							<button type="button" class="button-link button-link-delete" data-thr-audio-action="remove" data-voice="<?php echo esc_attr( $key ); ?>" data-label="<?php echo esc_attr( $label ); ?>"><?php esc_html_e( 'Remove', 'thr-audio' ); ?></button>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<div class="thr-audio-admin-bar">
			<?php if ( $voices ) : ?>
				<button type="button" class="button" data-thr-audio-action="publish_set"><?php esc_html_e( 'Make audio now for ticked voices', 'thr-audio' ); ?></button>
			<?php endif; ?>
			<span class="thr-audio-admin__meta"><?php esc_html_e( 'Removing a voice takes it off this article; the MP3 stays in the Media Library.', 'thr-audio' ); ?></span>
		</div>
		<?php
	endif;

	$html = ob_get_clean();
	return '<div data-thr-audio-busy="' . ( $busy ? '1' : '0' ) . '">' . $html . '</div>';
}

/**
 * Print a status line.
 *
 * @param string $type    ready|working|failed|outdated|''.
 * @param string $text    Status.
 * @param string $detail  Extra line.
 */
function thr_audio_state( $type, $text, $detail = '' ) {
	echo '<span class="thr-audio-admin__state' . ( $type ? ' thr-audio-admin__state--' . esc_attr( $type ) : '' ) . '">' . esc_html( $text ) . '</span>';
	if ( $detail ) {
		echo '<div class="thr-audio-admin__meta">' . esc_html( $detail ) . '</div>';
	}
}

/**
 * AJAX: refresh the box, or run an action and return the refreshed box.
 */
function thr_audio_ajax_admin() {
	check_ajax_referer( 'thr_audio_admin' );

	$post_id = isset( $_POST['post'] ) ? absint( $_POST['post'] ) : 0;
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( __( 'Not allowed.', 'thr-audio' ), 403 );
	}

	$do     = isset( $_POST['do'] ) ? sanitize_key( $_POST['do'] ) : 'refresh';
	$voice  = isset( $_POST['voice'] ) ? sanitize_key( $_POST['voice'] ) : '';
	$voices = thr_audio_voices();

	switch ( $do ) {
		case 'generate':
			if ( isset( $voices[ $voice ] ) ) {
				thr_audio_enqueue( $post_id, $voice, 'full', true, 0 );
				thr_audio_kick();
			}
			break;

		case 'publish_set':
			thr_audio_queue_publish_set( $post_id );
			break;

		case 'toggle':
			if ( isset( $voices[ $voice ] ) ) {
				$ticked = thr_audio_publish_voices( $post_id );
				$ticked = ! empty( $_POST['on'] ) ? array_unique( array_merge( $ticked, array( $voice ) ) ) : array_diff( $ticked, array( $voice ) );
				update_post_meta( $post_id, 'thr_audio_publish_voices', array_values( $ticked ) );
			}
			break;

		case 'own':
			$attachment_id = isset( $_POST['attachment'] ) ? absint( $_POST['attachment'] ) : 0;
			$mime          = $attachment_id ? (string) get_post_mime_type( $attachment_id ) : '';
			if ( 0 !== strpos( $mime, 'audio/' ) ) {
				wp_send_json_error( __( 'Please choose an audio file.', 'thr-audio' ) );
			}
			$meta   = wp_get_attachment_metadata( $attachment_id );
			$fields = array(
				'attachment_id' => $attachment_id,
				'url'           => '',
				'duration'      => ! empty( $meta['length'] ) ? (int) $meta['length'] : 0,
				'model'         => 'manual',
				'source'        => 'manual',
				'text_hash'     => md5( thr_audio_script( $post_id ) ),
				'generated_at'  => gmdate( 'c' ),
			);
			// Voices from Settings take their name from there; others keep the name they have.
			if ( isset( $voices[ $voice ] ) ) {
				$fields['label']  = $voices[ $voice ]['label'];
				$fields['region'] = $voices[ $voice ]['region'];
				$fields['gender'] = $voices[ $voice ]['gender'];
			}
			thr_audio_put_variant( $post_id, $voice, $fields );
			thr_audio_set_status( $post_id, $voice, 'full', null );
			thr_audio_purge_page( $post_id );
			break;

		case 'remove':
			thr_audio_put_variant( $post_id, $voice, null );
			thr_audio_set_status( $post_id, $voice, 'full', null );
			thr_audio_set_status( $post_id, $voice, 'preview', null );
			thr_audio_purge_page( $post_id );
			break;
	}

	wp_send_json_success( thr_audio_panel_html( $post_id ) );
}
add_action( 'wp_ajax_thr_audio_admin', 'thr_audio_ajax_admin' );

/* -------------------------------------------------------------------------
 * Posts list: "Audio" column.
 * ---------------------------------------------------------------------- */

function thr_audio_add_column( $columns ) {
	$columns['thr_audio'] = __( 'Audio', 'thr-audio' );
	return $columns;
}

function thr_audio_render_column( $column, $post_id ) {
	if ( 'thr_audio' !== $column ) {
		return;
	}

	$stored = thr_audio_stored( $post_id );
	$full   = 0;
	$clips  = 0;
	foreach ( $stored as $variant ) {
		if ( thr_audio_file( isset( $variant['attachment_id'] ) ? $variant['attachment_id'] : 0 ) || ( ! empty( $variant['url'] ) && empty( $variant['attachment_id'] ) ) ) {
			$full++;
		} elseif ( thr_audio_file( isset( $variant['preview_attachment_id'] ) ? $variant['preview_attachment_id'] : 0 ) ) {
			$clips++;
		}
	}

	$statuses = get_post_meta( $post_id, 'thr_audio_status', true );
	$working  = false;
	$failed   = false;
	foreach ( is_array( $statuses ) ? $statuses : array() as $kinds ) {
		foreach ( (array) $kinds as $state ) {
			$working = $working || in_array( $state['state'], array( 'queued', 'generating' ), true );
			$failed  = $failed || 'failed' === $state['state'];
		}
	}

	if ( $working ) {
		echo '<span style="color:#996800">' . esc_html__( 'Making…', 'thr-audio' ) . '</span>';
	} elseif ( $full || $clips ) {
		/* translators: 1: full recordings, 2: opening clips. */
		echo '<span style="color:#008a20">&#10003;</span> ' . esc_html( sprintf( __( '%1$d full, %2$d clips', 'thr-audio' ), $full, $clips ) );
	} else {
		echo '<span aria-hidden="true">—</span><span class="screen-reader-text">' . esc_html__( 'No audio', 'thr-audio' ) . '</span>';
	}
	if ( $failed ) {
		echo '<br /><span style="color:#b32d2e">' . esc_html__( 'Failed — open to retry', 'thr-audio' ) . '</span>';
	}
}

function thr_audio_register_columns() {
	foreach ( thr_audio_post_types() as $post_type ) {
		add_filter( "manage_{$post_type}_posts_columns", 'thr_audio_add_column' );
		add_action( "manage_{$post_type}_posts_custom_column", 'thr_audio_render_column', 10, 2 );
	}
}
add_action( 'admin_init', 'thr_audio_register_columns' );
