<?php
/**
 * Homepage module settings (Settings → THR Settings → Homepage).
 *
 * The homepage is built from fixed THR modules; editors choose what feeds the
 * configurable ones (the two channel blocks and the labelled topic rows).
 * Every module hides itself when it has no stories.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Homepage configuration with defaults filled in.
 *
 * @return array{channels: string[], rows: array[]}
 */
function thr_homepage_config() {
	$defaults = array(
		'channels' => array( 'heat-vision', 'live-feed' ),
		'rows'     => array(
			array(
				'title'   => __( 'International', 'thr-core' ),
				'tagline' => __( 'Film and TV news from around the world.', 'thr-core' ),
				'topic'   => 'international',
			),
			array(
				'title'   => __( 'Awards', 'thr-core' ),
				'tagline' => __( 'The road to the BAFTAs and the Oscars.', 'thr-core' ),
				'topic'   => 'awards',
			),
			array(
				'title'   => '',
				'tagline' => '',
				'topic'   => '',
			),
		),
	);

	$saved = get_option( 'thr_homepage' );
	if ( ! is_array( $saved ) ) {
		return $defaults;
	}

	$config = array(
		'channels' => isset( $saved['channels'] ) ? array_pad( array_slice( (array) $saved['channels'], 0, 2 ), 2, '' ) : $defaults['channels'],
		'rows'     => array(),
	);
	foreach ( $defaults['rows'] as $i => $row ) {
		$config['rows'][ $i ] = isset( $saved['rows'][ $i ] ) ? wp_parse_args( $saved['rows'][ $i ], $row ) : $row;
	}
	return $config;
}

/**
 * Sanitize the homepage option.
 *
 * @param mixed $value Raw value from the settings form.
 * @return array
 */
function thr_sanitize_homepage( $value ) {
	$value = is_array( $value ) ? $value : array();
	$clean = array(
		'channels' => array(),
		'rows'     => array(),
	);

	foreach ( array_slice( (array) ( $value['channels'] ?? array() ), 0, 2 ) as $slug ) {
		$clean['channels'][] = sanitize_key( $slug );
	}
	foreach ( array_slice( (array) ( $value['rows'] ?? array() ), 0, 3 ) as $row ) {
		$clean['rows'][] = array(
			'title'   => sanitize_text_field( $row['title'] ?? '' ),
			'tagline' => sanitize_text_field( $row['tagline'] ?? '' ),
			'topic'   => sanitize_title( $row['topic'] ?? '' ),
		);
	}
	return $clean;
}

function thr_register_homepage_setting() {
	register_setting( 'thr_settings_group', 'thr_homepage', array( 'sanitize_callback' => 'thr_sanitize_homepage' ) );
}
add_action( 'admin_init', 'thr_register_homepage_setting' );

/**
 * Homepage section of the THR Settings form.
 */
function thr_render_homepage_settings() {
	$config    = thr_homepage_config();
	$verticals = get_terms( array( 'taxonomy' => 'vertical', 'hide_empty' => false ) );
	$topics    = get_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false, 'orderby' => 'name' ) );
	$verticals = is_wp_error( $verticals ) ? array() : $verticals;
	$topics    = is_wp_error( $topics ) ? array() : $topics;
	?>
	<h2 class="title"><?php esc_html_e( 'Homepage', 'thr-core' ); ?></h2>
	<p class="description"><?php esc_html_e( 'The homepage follows THR’s layout. Choose what feeds the channel blocks and the labelled rows; a module with no stories is hidden automatically.', 'thr-core' ); ?></p>
	<table class="form-table">
		<?php foreach ( array( 0, 1 ) as $i ) : ?>
			<tr>
				<th scope="row">
					<label for="thr_home_channel_<?php echo esc_attr( $i ); ?>">
						<?php
						/* translators: %d: channel number (1 or 2). */
						echo esc_html( sprintf( __( 'Channel block %d', 'thr-core' ), $i + 1 ) );
						?>
					</label>
				</th>
				<td>
					<select name="thr_homepage[channels][]" id="thr_home_channel_<?php echo esc_attr( $i ); ?>">
						<option value=""><?php esc_html_e( '— Hidden —', 'thr-core' ); ?></option>
						<?php foreach ( $verticals as $term ) : ?>
							<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $config['channels'][ $i ] ?? '', $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		<?php endforeach; ?>

		<?php foreach ( $config['rows'] as $i => $row ) : ?>
			<tr>
				<th scope="row">
					<?php
					/* translators: %d: row number. */
					echo esc_html( sprintf( __( 'Labelled row %d', 'thr-core' ), $i + 1 ) );
					?>
				</th>
				<td>
					<p>
						<label><?php esc_html_e( 'Title', 'thr-core' ); ?><br>
							<input type="text" class="regular-text" name="thr_homepage[rows][<?php echo esc_attr( $i ); ?>][title]" value="<?php echo esc_attr( $row['title'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. What We’re Watching', 'thr-core' ); ?>" />
						</label>
					</p>
					<p>
						<label><?php esc_html_e( 'Tagline (optional)', 'thr-core' ); ?><br>
							<input type="text" class="regular-text" name="thr_homepage[rows][<?php echo esc_attr( $i ); ?>][tagline]" value="<?php echo esc_attr( $row['tagline'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. Spoilers ahead!', 'thr-core' ); ?>" />
						</label>
					</p>
					<p>
						<label><?php esc_html_e( 'Stories from topic', 'thr-core' ); ?><br>
							<select name="thr_homepage[rows][<?php echo esc_attr( $i ); ?>][topic]">
								<option value=""><?php esc_html_e( '— Hidden —', 'thr-core' ); ?></option>
								<?php foreach ( $topics as $term ) : ?>
									<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $row['topic'], $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
					</p>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
	<?php
}
