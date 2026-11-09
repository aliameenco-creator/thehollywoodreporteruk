<?php
/**
 * Podcast feed: /feed/thr-audio/
 *
 * Spotify, Apple Podcasts and the rest don't take uploads from a website: you give them this
 * feed address once and they check it for new episodes by themselves. Every published article
 * whose default voice has a full recording is an episode, newest first.
 *
 * The feed is built once and kept until audio or an article changes, so platforms polling it
 * cost almost nothing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Podcast settings defaults (stored under thr_audio_settings['podcast']).
 *
 * @return array
 */
function thr_audio_podcast_defaults() {
	return array(
		'enabled'     => 1,
		'title'       => get_bloginfo( 'name' ),
		'author'      => get_bloginfo( 'name' ),
		'description' => get_bloginfo( 'description' ),
		'image'       => '',
		'category'    => 'News|Entertainment News',
		'email'       => get_option( 'admin_email' ),
		'episodes'    => 300,
	);
}

/**
 * Apple Podcasts categories offered ("Parent|Child").
 *
 * @return array
 */
function thr_audio_podcast_categories() {
	return array(
		'News|Entertainment News' => __( 'News › Entertainment News', 'thr-audio' ),
		'News|Daily News'         => __( 'News › Daily News', 'thr-audio' ),
		'TV & Film'               => __( 'TV & Film', 'thr-audio' ),
		'TV & Film|Film Reviews'  => __( 'TV & Film › Film Reviews', 'thr-audio' ),
		'TV & Film|TV Reviews'    => __( 'TV & Film › TV Reviews', 'thr-audio' ),
		'Arts'                    => __( 'Arts', 'thr-audio' ),
	);
}

/**
 * @param array $input Raw podcast settings.
 * @return array
 */
function thr_audio_sanitize_podcast( $input ) {
	$defaults = thr_audio_podcast_defaults();
	$category = isset( $input['category'] ) ? sanitize_text_field( $input['category'] ) : '';

	return array(
		'enabled'     => empty( $input['enabled'] ) ? 0 : 1,
		'title'       => isset( $input['title'] ) && '' !== trim( $input['title'] ) ? sanitize_text_field( $input['title'] ) : $defaults['title'],
		'author'      => isset( $input['author'] ) ? sanitize_text_field( $input['author'] ) : '',
		'description' => isset( $input['description'] ) ? sanitize_textarea_field( $input['description'] ) : '',
		'image'       => isset( $input['image'] ) ? esc_url_raw( $input['image'] ) : '',
		'category'    => isset( thr_audio_podcast_categories()[ $category ] ) ? $category : $defaults['category'],
		'email'       => isset( $input['email'] ) ? sanitize_email( $input['email'] ) : '',
		'episodes'    => isset( $input['episodes'] ) ? min( 1000, max( 10, absint( $input['episodes'] ) ) ) : 300,
	);
}

/**
 * @return string Feed address.
 */
function thr_audio_podcast_url() {
	return get_feed_link( 'thr-audio' );
}

function thr_audio_podcast_register() {
	add_feed( 'thr-audio', 'thr_audio_podcast_render' );
}
add_action( 'init', 'thr_audio_podcast_register' );

/**
 * Print the feed.
 */
function thr_audio_podcast_render() {
	$settings = thr_audio_settings();
	if ( empty( $settings['podcast']['enabled'] ) ) {
		status_header( 404 );
		nocache_headers();
		exit;
	}

	$xml = get_transient( 'thr_audio_podcast_feed' );
	if ( false === $xml ) {
		$xml = thr_audio_podcast_build( $settings['podcast'] );
		set_transient( 'thr_audio_podcast_feed', $xml, DAY_IN_SECONDS );
	}

	header( 'Content-Type: application/rss+xml; charset=UTF-8' );
	echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped while building.
}

/**
 * The default voice's full recording for an article, or null.
 *
 * @param int $post_id Post ID.
 * @return array|null url, duration, bytes.
 */
function thr_audio_podcast_episode_file( $post_id ) {
	$audio  = get_post_meta( $post_id, 'thr_audio', true );
	$stored = thr_audio_stored( $post_id );
	$key    = ! empty( $audio['default'] ) ? $audio['default'] : thr_audio_default_slot();
	if ( ! isset( $stored[ $key ] ) ) {
		return null;
	}
	$variant = $stored[ $key ];

	$id = ! empty( $variant['attachment_id'] ) ? (int) $variant['attachment_id'] : 0;
	if ( $id ) {
		$file = thr_audio_file( $id, isset( $variant['duration'] ) ? $variant['duration'] : 0 );
		if ( ! $file ) {
			return null;
		}
		$bytes = (int) get_post_meta( $id, '_thr_audio_bytes', true );
		if ( ! $bytes ) {
			$path  = get_attached_file( $id );
			$bytes = $path && file_exists( $path ) ? (int) filesize( $path ) : 0;
		}
		return $file + array( 'bytes' => $bytes );
	}

	if ( ! empty( $variant['url'] ) ) {
		return array(
			'url'      => $variant['url'],
			'duration' => isset( $variant['duration'] ) ? (int) $variant['duration'] : 0,
			'bytes'    => 0,
		);
	}
	return null;
}

/**
 * Build the RSS document.
 *
 * @param array $podcast Podcast settings.
 * @return string
 */
function thr_audio_podcast_build( $podcast ) {
	$query = new WP_Query(
		array(
			'post_type'              => thr_audio_post_types(),
			'post_status'            => 'publish',
			'has_password'           => false,
			'meta_key'               => 'thr_audio', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'posts_per_page'         => (int) $podcast['episodes'],
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'ignore_sticky_posts'    => true,
		)
	);

	$category = explode( '|', $podcast['category'] );
	$host     = wp_parse_url( home_url(), PHP_URL_HOST );

	$out  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$out .= '<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n<channel>\n";
	$out .= '<title>' . esc_xml( $podcast['title'] ) . "</title>\n";
	$out .= '<link>' . esc_url( home_url( '/' ) ) . "</link>\n";
	$out .= '<atom:link href="' . esc_url( thr_audio_podcast_url() ) . '" rel="self" type="application/rss+xml" />' . "\n";
	$out .= "<language>en-gb</language>\n";
	$out .= '<description>' . esc_xml( $podcast['description'] ? $podcast['description'] : $podcast['title'] ) . "</description>\n";
	$out .= '<itunes:author>' . esc_xml( $podcast['author'] ) . "</itunes:author>\n";
	$out .= '<itunes:owner><itunes:name>' . esc_xml( $podcast['author'] ) . '</itunes:name><itunes:email>' . esc_xml( $podcast['email'] ) . "</itunes:email></itunes:owner>\n";
	if ( $podcast['image'] ) {
		$out .= '<itunes:image href="' . esc_url( $podcast['image'] ) . '" />' . "\n";
		$out .= '<image><url>' . esc_url( $podcast['image'] ) . '</url><title>' . esc_xml( $podcast['title'] ) . '</title><link>' . esc_url( home_url( '/' ) ) . "</link></image>\n";
	}
	$out .= '<itunes:category text="' . esc_attr( $category[0] ) . '">' . ( isset( $category[1] ) ? '<itunes:category text="' . esc_attr( $category[1] ) . '" />' : '' ) . "</itunes:category>\n";
	$out .= "<itunes:explicit>false</itunes:explicit>\n<itunes:type>episodic</itunes:type>\n";
	$out .= '<lastBuildDate>' . esc_xml( gmdate( DATE_RSS ) ) . "</lastBuildDate>\n";

	foreach ( $query->posts as $post ) {
		$file = thr_audio_podcast_episode_file( $post->ID );
		if ( ! $file ) {
			continue;
		}
		$dek = (string) get_post_meta( $post->ID, 'thr_dek', true );
		$dek = '' !== trim( $dek ) ? $dek : get_the_excerpt( $post );

		$out .= "<item>\n";
		$out .= '<title>' . esc_xml( wp_strip_all_tags( get_the_title( $post ) ) ) . "</title>\n";
		$out .= '<link>' . esc_url( get_permalink( $post ) ) . "</link>\n";
		// Stays the same when audio is remade, so platforms update the episode instead of adding one.
		$out .= '<guid isPermaLink="false">' . esc_xml( 'thr-audio-' . $host . '-' . $post->ID ) . "</guid>\n";
		$out .= '<pubDate>' . esc_xml( mysql2date( DATE_RSS, $post->post_date_gmt, false ) ) . "</pubDate>\n";
		$out .= '<description>' . esc_xml( wp_strip_all_tags( $dek ) ) . "</description>\n";
		$out .= '<enclosure url="' . esc_url( $file['url'] ) . '" length="' . (int) $file['bytes'] . '" type="audio/mpeg" />' . "\n";
		if ( $file['duration'] ) {
			$out .= '<itunes:duration>' . (int) $file['duration'] . "</itunes:duration>\n";
		}
		$out .= "<itunes:episodeType>full</itunes:episodeType>\n<itunes:explicit>false</itunes:explicit>\n";
		$out .= "</item>\n";
	}

	return $out . "</channel>\n</rss>\n";
}

/**
 * Rebuild the feed next time it is asked for.
 */
function thr_audio_podcast_flush() {
	delete_transient( 'thr_audio_podcast_feed' );
	do_action( 'litespeed_purge_url', thr_audio_podcast_url() );
}

/**
 * Audio added, remade or removed on an article.
 *
 * @param int    $meta_id  Meta ID.
 * @param int    $post_id  Post ID.
 * @param string $meta_key Meta key.
 */
function thr_audio_podcast_meta_changed( $meta_id, $post_id, $meta_key ) {
	if ( 'thr_audio' === $meta_key ) {
		thr_audio_podcast_flush();
	}
}
add_action( 'added_post_meta', 'thr_audio_podcast_meta_changed', 10, 3 );
add_action( 'updated_post_meta', 'thr_audio_podcast_meta_changed', 10, 3 );
add_action( 'deleted_post_meta', 'thr_audio_podcast_meta_changed', 10, 3 );

/**
 * An article with audio published, unpublished or edited while live.
 *
 * @param string  $new_status New status.
 * @param string  $old_status Old status.
 * @param WP_Post $post       Post.
 */
function thr_audio_podcast_status_changed( $new_status, $old_status, $post ) {
	if ( ( 'publish' === $new_status || 'publish' === $old_status ) && in_array( $post->post_type, thr_audio_post_types(), true ) && metadata_exists( 'post', $post->ID, 'thr_audio' ) ) {
		thr_audio_podcast_flush();
	}
}
add_action( 'transition_post_status', 'thr_audio_podcast_status_changed', 10, 3 );
add_action( 'update_option_thr_audio_settings', 'thr_audio_podcast_flush' );

/**
 * "Podcast" section of Settings → THR Audio.
 *
 * @param array  $p    Podcast settings.
 * @param string $name Field name prefix.
 */
function thr_audio_render_podcast_settings( $p, $name ) {
	?>
	<h2 class="title"><?php esc_html_e( 'Podcast (Spotify, Apple Podcasts)', 'thr-audio' ); ?></h2>
	<p class="description" style="max-width:800px">
		<?php esc_html_e( 'Every published article with a full recording in the default voice becomes an episode. Submit the feed address once in Spotify for Creators and Apple Podcasts Connect; they pick up new episodes by themselves.', 'thr-audio' ); ?>
	</p>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Feed', 'thr-audio' ); ?></th>
			<td>
				<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $p['enabled'] ); ?> /> <?php esc_html_e( 'Publish the podcast feed', 'thr-audio' ); ?></label>
				<p><code><?php echo esc_html( thr_audio_podcast_url() ); ?></code></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="thr-audio-pod-title"><?php esc_html_e( 'Show title', 'thr-audio' ); ?></label></th>
			<td><input type="text" id="thr-audio-pod-title" class="regular-text" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $p['title'] ); ?>" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="thr-audio-pod-desc"><?php esc_html_e( 'Show description', 'thr-audio' ); ?></label></th>
			<td><textarea id="thr-audio-pod-desc" class="large-text" rows="3" name="<?php echo esc_attr( $name ); ?>[description]"><?php echo esc_textarea( $p['description'] ); ?></textarea></td>
		</tr>
		<tr>
			<th scope="row"><label for="thr-audio-pod-author"><?php esc_html_e( 'Author / owner name', 'thr-audio' ); ?></label></th>
			<td><input type="text" id="thr-audio-pod-author" class="regular-text" name="<?php echo esc_attr( $name ); ?>[author]" value="<?php echo esc_attr( $p['author'] ); ?>" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="thr-audio-pod-email"><?php esc_html_e( 'Owner email', 'thr-audio' ); ?></label></th>
			<td>
				<input type="email" id="thr-audio-pod-email" class="regular-text" name="<?php echo esc_attr( $name ); ?>[email]" value="<?php echo esc_attr( $p['email'] ); ?>" />
				<p class="description"><?php esc_html_e( 'Spotify and Apple send the ownership confirmation code here. It is visible in the feed.', 'thr-audio' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="thr-audio-pod-image"><?php esc_html_e( 'Cover image URL', 'thr-audio' ); ?></label></th>
			<td>
				<input type="url" id="thr-audio-pod-image" class="large-text" name="<?php echo esc_attr( $name ); ?>[image]" value="<?php echo esc_attr( $p['image'] ); ?>" />
				<p class="description"><?php esc_html_e( 'Square JPG or PNG, 1400–3000 pixels. Upload it to Media and paste its address. Required by Spotify and Apple.', 'thr-audio' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="thr-audio-pod-cat"><?php esc_html_e( 'Category', 'thr-audio' ); ?></label></th>
			<td>
				<select id="thr-audio-pod-cat" name="<?php echo esc_attr( $name ); ?>[category]">
					<?php foreach ( thr_audio_podcast_categories() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $p['category'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="thr-audio-pod-count"><?php esc_html_e( 'Episodes in the feed', 'thr-audio' ); ?></label></th>
			<td>
				<input type="number" id="thr-audio-pod-count" class="small-text" min="10" max="1000" name="<?php echo esc_attr( $name ); ?>[episodes]" value="<?php echo esc_attr( $p['episodes'] ); ?>" />
				<p class="description"><?php esc_html_e( 'The newest ones. Older episodes can disappear from the platforms once they drop out of the feed, so raise this to keep more listed.', 'thr-audio' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}
