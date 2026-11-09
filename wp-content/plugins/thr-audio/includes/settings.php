<?php
/**
 * Settings → THR Audio: ElevenLabs connection, model, the four voices and spending limits.
 *
 * The API key is never stored here: it is read from wp-config.php
 * ( define( 'THR_ELEVENLABS_API_KEY', '…' ); ) so it stays out of the database and the browser.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Voice slots. Slot keys stay fixed so renaming a voice never orphans audio already made with it.
 *
 * @return string[]
 */
function thr_audio_slots() {
	return array( 'v1', 'v2', 'v3', 'v4' );
}

/**
 * Settings merged with defaults.
 *
 * @return array
 */
function thr_audio_settings() {
	$voices = array();
	foreach ( thr_audio_slots() as $slot ) {
		$voices[ $slot ] = array(
			'label'    => '',
			'region'   => '',
			'gender'   => '',
			'voice_id' => '',
			'enabled'  => 1,
		);
	}

	$defaults = array(
		'mode'          => 'test',
		'model'         => 'eleven_flash_v2_5',
		'output_format' => 'mp3_44100_64',
		'default_voice' => 'v1',
		'auto_publish'  => 1,
		'other_voices'  => 'full',
		'preview_chars' => 350,
		'on_demand'     => 1,
		'rate_limit'    => 10,
		'monthly_cap'   => 2000000,
		'alert_email'   => get_option( 'admin_email' ),
		'storage'       => 'local',
		'voices'        => $voices,
		'podcast'       => thr_audio_podcast_defaults(),
	);

	$saved = get_option( 'thr_audio_settings', array() );
	$saved = is_array( $saved ) ? $saved : array();

	$settings            = array_merge( $defaults, $saved );
	$settings['podcast'] = array_merge( $defaults['podcast'], isset( $saved['podcast'] ) && is_array( $saved['podcast'] ) ? $saved['podcast'] : array() );
	foreach ( thr_audio_slots() as $slot ) {
		$settings['voices'][ $slot ] = array_merge(
			$voices[ $slot ],
			isset( $saved['voices'][ $slot ] ) && is_array( $saved['voices'][ $slot ] ) ? $saved['voices'][ $slot ] : array()
		);
	}

	return $settings;
}

/**
 * Voices ready to use: named, enabled and (in live mode) with an ElevenLabs voice ID.
 *
 * @return array Slot => voice.
 */
function thr_audio_voices() {
	$settings = thr_audio_settings();
	$voices   = array();

	foreach ( $settings['voices'] as $slot => $voice ) {
		if ( empty( $voice['enabled'] ) || '' === $voice['label'] ) {
			continue;
		}
		if ( 'live' === $settings['mode'] && '' === $voice['voice_id'] ) {
			continue;
		}
		$voices[ $slot ] = $voice;
	}

	return $voices;
}

/**
 * The slot that is made in full on publish: the chosen default, else the first usable voice.
 *
 * @return string Slot key or ''.
 */
function thr_audio_default_slot() {
	$voices   = thr_audio_voices();
	$settings = thr_audio_settings();

	if ( isset( $voices[ $settings['default_voice'] ] ) ) {
		return $settings['default_voice'];
	}
	return $voices ? (string) array_key_first( $voices ) : '';
}

/**
 * Whether the API key is configured in wp-config.php.
 *
 * @return bool
 */
function thr_audio_has_key() {
	return defined( 'THR_ELEVENLABS_API_KEY' ) && '' !== (string) THR_ELEVENLABS_API_KEY;
}

function thr_audio_settings_menu() {
	add_options_page(
		__( 'THR Audio', 'thr-audio' ),
		__( 'THR Audio', 'thr-audio' ),
		'manage_options',
		'thr-audio',
		'thr_audio_render_settings'
	);
}
add_action( 'admin_menu', 'thr_audio_settings_menu' );

function thr_audio_register_settings() {
	register_setting(
		'thr_audio_settings_group',
		'thr_audio_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'thr_audio_sanitize_settings',
		)
	);
}
add_action( 'admin_init', 'thr_audio_register_settings' );

/**
 * Clean submitted settings.
 *
 * @param mixed $input Raw input.
 * @return array
 */
function thr_audio_sanitize_settings( $input ) {
	$input = is_array( $input ) ? $input : array();
	$clean = array(
		'mode'          => isset( $input['mode'] ) && 'live' === $input['mode'] ? 'live' : 'test',
		'model'         => isset( $input['model'] ) ? sanitize_text_field( $input['model'] ) : 'eleven_flash_v2_5',
		'output_format' => isset( $input['output_format'] ) && in_array( $input['output_format'], array_keys( thr_audio_output_formats() ), true ) ? $input['output_format'] : 'mp3_44100_64',
		'default_voice' => isset( $input['default_voice'] ) && in_array( $input['default_voice'], thr_audio_slots(), true ) ? $input['default_voice'] : 'v1',
		'auto_publish'  => empty( $input['auto_publish'] ) ? 0 : 1,
		'other_voices'  => isset( $input['other_voices'] ) && 'preview' === $input['other_voices'] ? 'preview' : 'full',
		'preview_chars' => isset( $input['preview_chars'] ) ? min( 2000, max( 100, absint( $input['preview_chars'] ) ) ) : 350,
		'on_demand'     => empty( $input['on_demand'] ) ? 0 : 1,
		'rate_limit'    => isset( $input['rate_limit'] ) ? min( 200, max( 1, absint( $input['rate_limit'] ) ) ) : 10,
		'monthly_cap'   => isset( $input['monthly_cap'] ) ? absint( $input['monthly_cap'] ) : 2000000,
		'alert_email'   => isset( $input['alert_email'] ) ? sanitize_email( $input['alert_email'] ) : '',
		'storage'       => isset( $input['storage'] ) && 'r2' === $input['storage'] && thr_audio_r2_configured() ? 'r2' : 'local',
		'voices'        => array(),
		'podcast'       => thr_audio_sanitize_podcast( isset( $input['podcast'] ) && is_array( $input['podcast'] ) ? $input['podcast'] : array() ),
	);

	foreach ( thr_audio_slots() as $slot ) {
		$voice                    = isset( $input['voices'][ $slot ] ) && is_array( $input['voices'][ $slot ] ) ? $input['voices'][ $slot ] : array();
		$gender                   = isset( $voice['gender'] ) ? sanitize_key( $voice['gender'] ) : '';
		$clean['voices'][ $slot ] = array(
			'label'    => isset( $voice['label'] ) ? sanitize_text_field( $voice['label'] ) : '',
			'region'   => isset( $voice['region'] ) ? sanitize_text_field( $voice['region'] ) : '',
			'gender'   => in_array( $gender, array( 'female', 'male' ), true ) ? $gender : '',
			'voice_id' => isset( $voice['voice_id'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', $voice['voice_id'] ) : '',
			'enabled'  => empty( $voice['enabled'] ) ? 0 : 1,
		);
	}

	return $clean;
}

/**
 * MP3 formats offered. Higher bitrates may need a higher ElevenLabs plan.
 *
 * @return array
 */
function thr_audio_output_formats() {
	return array(
		'mp3_44100_64'  => __( 'MP3 64 kbps (recommended for speech)', 'thr-audio' ),
		'mp3_44100_128' => __( 'MP3 128 kbps (higher quality, double the file size)', 'thr-audio' ),
		'mp3_22050_32'  => __( 'MP3 32 kbps (smallest files)', 'thr-audio' ),
	);
}

/**
 * Models for the dropdown: live from ElevenLabs when a key is set, else the common ones.
 *
 * @return array model_id => name.
 */
function thr_audio_model_options() {
	$fallback = array(
		'eleven_flash_v2_5'      => 'Eleven Flash v2.5 (fastest, about half the credits)',
		'eleven_turbo_v2_5'      => 'Eleven Turbo v2.5 (fast, about half the credits)',
		'eleven_multilingual_v2' => 'Eleven Multilingual v2 (highest quality)',
	);

	if ( ! thr_audio_has_key() ) {
		return $fallback;
	}

	$models = THR_Audio_ElevenLabs::models();
	return is_wp_error( $models ) || empty( $models ) ? $fallback : $models;
}

/**
 * Characters sent to ElevenLabs this month by the plugin.
 *
 * @return int
 */
function thr_audio_usage_this_month() {
	return (int) get_option( 'thr_audio_usage_' . gmdate( 'Ym' ), 0 );
}

function thr_audio_render_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$s       = thr_audio_settings();
	$name    = 'thr_audio_settings';
	$models  = thr_audio_model_options();
	$usage   = thr_audio_usage_this_month();
	$nonce   = wp_create_nonce( 'thr_audio_settings_test' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'THR Audio', 'thr-audio' ); ?></h1>
		<p><?php esc_html_e( 'When an article is published, the default voice is made in full and the other voices get a short opening clip. Readers who pick another voice hear the clip straight away while the full version is made.', 'thr-audio' ); ?></p>

		<form method="post" action="options.php">
			<?php settings_fields( 'thr_audio_settings_group' ); ?>

			<h2 class="title"><?php esc_html_e( 'Connection', 'thr-audio' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Mode', 'thr-audio' ); ?></th>
					<td>
						<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[mode]" value="test" <?php checked( $s['mode'], 'test' ); ?> /> <?php esc_html_e( 'Test mode: no ElevenLabs calls, no cost. Every voice uses a sample recording.', 'thr-audio' ); ?></label><br />
						<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[mode]" value="live" <?php checked( $s['mode'], 'live' ); ?> <?php disabled( ! thr_audio_has_key() ); ?> /> <?php esc_html_e( 'Live: make real audio with ElevenLabs (uses credits).', 'thr-audio' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'API key', 'thr-audio' ); ?></th>
					<td>
						<?php if ( thr_audio_has_key() ) : ?>
							<p><strong style="color:#008a20">&#10003; <?php esc_html_e( 'Found in wp-config.php', 'thr-audio' ); ?></strong></p>
							<p>
								<button type="button" class="button" id="thr-audio-test" data-nonce="<?php echo esc_attr( $nonce ); ?>"><?php esc_html_e( 'Test connection', 'thr-audio' ); ?></button>
								<span id="thr-audio-test-result" style="margin-left:8px"></span>
							</p>
						<?php else : ?>
							<p><?php esc_html_e( 'Not set. Add this line to wp-config.php, above "That\'s all, stop editing!":', 'thr-audio' ); ?></p>
							<code>define( 'THR_ELEVENLABS_API_KEY', 'your-key-here' );</code>
							<p class="description"><?php esc_html_e( 'The key is kept out of the database on purpose. Live mode stays off until it is set.', 'thr-audio' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'Model and format', 'thr-audio' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="thr-audio-model"><?php esc_html_e( 'Model', 'thr-audio' ); ?></label></th>
					<td>
						<select id="thr-audio-model" name="<?php echo esc_attr( $name ); ?>[model]">
							<?php if ( ! isset( $models[ $s['model'] ] ) ) : ?>
								<option value="<?php echo esc_attr( $s['model'] ); ?>" selected><?php echo esc_html( $s['model'] ); ?></option>
							<?php endif; ?>
							<?php foreach ( $models as $id => $label ) : ?>
								<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $s['model'], $id ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Applies to new audio only. Every file records the model that made it, shown in the Article Audio box. Regenerate an article to remake it with a new model.', 'thr-audio' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="thr-audio-format"><?php esc_html_e( 'File format', 'thr-audio' ); ?></label></th>
					<td>
						<select id="thr-audio-format" name="<?php echo esc_attr( $name ); ?>[output_format]">
							<?php foreach ( thr_audio_output_formats() as $id => $label ) : ?>
								<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $s['output_format'], $id ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'Voices', 'thr-audio' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Find a voice in the ElevenLabs Voice Library, add it to "My Voices" and copy its Voice ID here. The name and region are what readers see in the voice menu.', 'thr-audio' ); ?></p>
			<table class="widefat striped" style="max-width:1000px">
				<thead>
					<tr>
						<th><?php esc_html_e( 'On', 'thr-audio' ); ?></th>
						<th><?php esc_html_e( 'Name', 'thr-audio' ); ?></th>
						<th><?php esc_html_e( 'Region', 'thr-audio' ); ?></th>
						<th><?php esc_html_e( 'Gender', 'thr-audio' ); ?></th>
						<th><?php esc_html_e( 'ElevenLabs Voice ID', 'thr-audio' ); ?></th>
						<th><?php esc_html_e( 'Default', 'thr-audio' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $s['voices'] as $slot => $voice ) :
						$field = $name . '[voices][' . $slot . ']';
						?>
						<tr>
							<td><input type="checkbox" name="<?php echo esc_attr( $field ); ?>[enabled]" value="1" <?php checked( $voice['enabled'] ); ?> aria-label="<?php esc_attr_e( 'Voice on', 'thr-audio' ); ?>" /></td>
							<td><input type="text" name="<?php echo esc_attr( $field ); ?>[label]" value="<?php echo esc_attr( $voice['label'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. Emma', 'thr-audio' ); ?>" /></td>
							<td><input type="text" name="<?php echo esc_attr( $field ); ?>[region]" value="<?php echo esc_attr( $voice['region'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. London', 'thr-audio' ); ?>" /></td>
							<td>
								<select name="<?php echo esc_attr( $field ); ?>[gender]">
									<option value=""><?php esc_html_e( '—', 'thr-audio' ); ?></option>
									<option value="female" <?php selected( $voice['gender'], 'female' ); ?>><?php esc_html_e( 'Female', 'thr-audio' ); ?></option>
									<option value="male" <?php selected( $voice['gender'], 'male' ); ?>><?php esc_html_e( 'Male', 'thr-audio' ); ?></option>
								</select>
							</td>
							<td><input type="text" class="regular-text code" name="<?php echo esc_attr( $field ); ?>[voice_id]" value="<?php echo esc_attr( $voice['voice_id'] ); ?>" placeholder="21m00Tcm4TlvDq8ikWAM" /></td>
							<td><input type="radio" name="<?php echo esc_attr( $name ); ?>[default_voice]" value="<?php echo esc_attr( $slot ); ?>" <?php checked( $s['default_voice'], $slot ); ?> aria-label="<?php esc_attr_e( 'Default voice', 'thr-audio' ); ?>" /></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h2 class="title"><?php esc_html_e( 'Behaviour', 'thr-audio' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'On publish', 'thr-audio' ); ?></th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[auto_publish]" value="1" <?php checked( $s['auto_publish'] ); ?> /> <?php esc_html_e( 'Make audio automatically when an article is published (manual and n8n posts alike)', 'thr-audio' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Other voices', 'thr-audio' ); ?></th>
					<td>
						<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[other_voices]" value="full" <?php checked( $s['other_voices'], 'full' ); ?> /> <?php esc_html_e( 'Full recording for every voice: switching voices is instant (costs about one full article per voice)', 'thr-audio' ); ?></label><br />
						<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[other_voices]" value="preview" <?php checked( $s['other_voices'], 'preview' ); ?> /> <?php esc_html_e( 'Opening clip only: the full version is made when a reader picks the voice (cheaper; the reader may wait a few seconds)', 'thr-audio' ); ?></label>
						<p class="description"><?php esc_html_e( 'The default voice is always made in full. For articles already published, use "Make audio now" in the Article Audio box.', 'thr-audio' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="thr-audio-preview"><?php esc_html_e( 'Opening clip length', 'thr-audio' ); ?></label></th>
					<td>
						<input type="number" id="thr-audio-preview" min="100" max="2000" step="50" name="<?php echo esc_attr( $name ); ?>[preview_chars]" value="<?php echo esc_attr( $s['preview_chars'] ); ?>" class="small-text" /> <?php esc_html_e( 'characters', 'thr-audio' ); ?>
						<p class="description"><?php esc_html_e( 'About 350 characters is roughly 20 seconds of speech. Ends on a full sentence.', 'thr-audio' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Readers', 'thr-audio' ); ?></th>
					<td>
						<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[on_demand]" value="1" <?php checked( $s['on_demand'] ); ?> /> <?php esc_html_e( 'Make the full version of a voice when a reader picks it', 'thr-audio' ); ?></label>
						<p>
							<label><?php esc_html_e( 'Limit per visitor:', 'thr-audio' ); ?> <input type="number" min="1" max="200" name="<?php echo esc_attr( $name ); ?>[rate_limit]" value="<?php echo esc_attr( $s['rate_limit'] ); ?>" class="small-text" /> <?php esc_html_e( 'new voices per hour', 'thr-audio' ); ?></label>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="thr-audio-cap"><?php esc_html_e( 'Monthly limit', 'thr-audio' ); ?></label></th>
					<td>
						<input type="number" id="thr-audio-cap" min="0" step="10000" name="<?php echo esc_attr( $name ); ?>[monthly_cap]" value="<?php echo esc_attr( $s['monthly_cap'] ); ?>" class="regular-text" /> <?php esc_html_e( 'characters (0 = no limit)', 'thr-audio' ); ?>
						<p class="description">
							<?php
							printf(
								/* translators: 1: characters used, 2: month. */
								esc_html__( 'Used this month: %1$s characters (%2$s). Audio stops being made when the limit is reached, and an email is sent.', 'thr-audio' ),
								esc_html( number_format_i18n( $usage ) ),
								esc_html( wp_date( 'F Y' ) )
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="thr-audio-email"><?php esc_html_e( 'Alert email', 'thr-audio' ); ?></label></th>
					<td><input type="email" id="thr-audio-email" name="<?php echo esc_attr( $name ); ?>[alert_email]" value="<?php echo esc_attr( $s['alert_email'] ); ?>" class="regular-text" /></td>
				</tr>
			</table>

			<?php thr_audio_render_storage_settings( $s, $name ); ?>

			<?php thr_audio_render_podcast_settings( $s['podcast'], $name . '[podcast]' ); ?>

			<?php submit_button(); ?>
		</form>
	</div>

	<script>
	( () => {
		const button = document.getElementById( 'thr-audio-test' );
		if ( ! button ) {
			return;
		}
		button.addEventListener( 'click', () => {
			const out = document.getElementById( 'thr-audio-test-result' );
			out.textContent = <?php echo wp_json_encode( __( 'Checking…', 'thr-audio' ) ); ?>;
			const body = new URLSearchParams( { action: 'thr_audio_test_connection', _ajax_nonce: button.dataset.nonce } );
			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body } )
				.then( ( r ) => r.json() )
				.then( ( r ) => { out.textContent = r.data; out.style.color = r.success ? '#008a20' : '#b32d2e'; } )
				.catch( () => { out.textContent = <?php echo wp_json_encode( __( 'Request failed.', 'thr-audio' ) ); ?>; } );
		} );
	} )();
	</script>
	<?php
}

/**
 * Settings page "Test connection": checks the key and shows the plan's credits.
 */
function thr_audio_ajax_test_connection() {
	check_ajax_referer( 'thr_audio_settings_test' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Not allowed.', 'thr-audio' ), 403 );
	}

	$sub = THR_Audio_ElevenLabs::subscription();
	if ( is_wp_error( $sub ) ) {
		wp_send_json_error( $sub->get_error_message() );
	}

	wp_send_json_success(
		sprintf(
			/* translators: 1: plan name, 2: credits used, 3: credit limit, 4: reset date. */
			__( 'Connected. Plan: %1$s. Credits used: %2$s of %3$s. Resets %4$s.', 'thr-audio' ),
			$sub['tier'],
			number_format_i18n( $sub['used'] ),
			number_format_i18n( $sub['limit'] ),
			$sub['reset'] ? wp_date( get_option( 'date_format' ), $sub['reset'] ) : '—'
		)
	);
}
add_action( 'wp_ajax_thr_audio_test_connection', 'thr_audio_ajax_test_connection' );
