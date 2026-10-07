<?php
/**
 * Post Meta fields and Editorial Sidebar panel for THR.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_register_post_meta() {
	$fields = array(
		'thr_dek'                  => array( 'type' => 'string',  'sanitize' => 'sanitize_text_field' ),
		'thr_kicker'               => array( 'type' => 'string',  'sanitize' => 'sanitize_text_field' ),
		'thr_article_type'         => array( 'type' => 'string',  'sanitize' => 'sanitize_key', 'default' => 'standard' ),
		'thr_featured'             => array( 'type' => 'boolean', 'sanitize' => 'rest_sanitize_boolean' ),
		'thr_breaking'             => array( 'type' => 'boolean', 'sanitize' => 'rest_sanitize_boolean' ),
		'thr_breaking_expiry'      => array( 'type' => 'string',  'sanitize' => 'sanitize_text_field' ),
		'thr_sponsored'            => array( 'type' => 'boolean', 'sanitize' => 'rest_sanitize_boolean' ),
		'thr_sponsored_name'       => array( 'type' => 'string',  'sanitize' => 'sanitize_text_field' ),
		'thr_sponsored_url'        => array( 'type' => 'string',  'sanitize' => 'esc_url_raw' ),
		'thr_review_subject'       => array( 'type' => 'string',  'sanitize' => 'sanitize_text_field' ),
		'thr_review_bottom_line'   => array( 'type' => 'string',  'sanitize' => 'sanitize_text_field' ),
		'thr_review_venue'         => array( 'type' => 'string',  'sanitize' => 'sanitize_text_field' ),
		'thr_review_release_date'  => array( 'type' => 'string',  'sanitize' => 'sanitize_text_field' ),
		'thr_review_cast'          => array( 'type' => 'string',  'sanitize' => 'sanitize_text_field' ),
		'thr_review_director'      => array( 'type' => 'string',  'sanitize' => 'sanitize_text_field' ),
		'thr_review_screenwriter'  => array( 'type' => 'string',  'sanitize' => 'sanitize_text_field' ),
		'thr_review_rating_time'   => array( 'type' => 'string',  'sanitize' => 'sanitize_text_field' ),
		'thr_review_full_credits'  => array( 'type' => 'string',  'sanitize' => 'wp_kses_post' ),
		'thr_media_url'            => array( 'type' => 'string',  'sanitize' => 'esc_url_raw' ),
		'thr_newsletter'           => array( 'type' => 'string',  'sanitize' => 'sanitize_text_field' ),
		'thr_exclude_auto'         => array( 'type' => 'boolean', 'sanitize' => 'rest_sanitize_boolean' ),
	);

	$supported_types = array( 'post', 'thr_list', 'thr_gallery', 'thr_video' );

	foreach ( $supported_types as $post_type ) {
		foreach ( $fields as $key => $config ) {
			register_post_meta(
				$post_type,
				$key,
				array(
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => $config['type'],
					'sanitize_callback' => $config['sanitize'],
					// Defaults must match the declared type or register_meta() rejects the field.
					'default'           => isset( $config['default'] ) ? $config['default'] : ( 'boolean' === $config['type'] ? false : '' ),
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
}
add_action( 'init', 'thr_register_post_meta' );

function thr_add_editorial_meta_boxes() {
	$screens = array( 'post', 'thr_list', 'thr_gallery', 'thr_video' );
	foreach ( $screens as $screen ) {
		add_meta_box(
			'thr_editorial_details',
			__( 'THR Editorial & Presentation Settings', 'thr-core' ),
			'thr_render_editorial_meta_box',
			$screen,
			'normal',
			'high'
		);
	}
}
add_action( 'add_meta_boxes', 'thr_add_editorial_meta_boxes' );

function thr_render_editorial_meta_box( $post ) {
	wp_nonce_field( 'thr_editorial_meta_nonce_action', 'thr_editorial_meta_nonce' );

	$dek              = get_post_meta( $post->ID, 'thr_dek', true );
	$kicker           = get_post_meta( $post->ID, 'thr_kicker', true );
	$article_type     = get_post_meta( $post->ID, 'thr_article_type', true ) ?: 'standard';
	$featured         = get_post_meta( $post->ID, 'thr_featured', true );
	$breaking         = get_post_meta( $post->ID, 'thr_breaking', true );
	$review_subject   = get_post_meta( $post->ID, 'thr_review_subject', true );
	$review_bottom    = get_post_meta( $post->ID, 'thr_review_bottom_line', true );
	$review_venue     = get_post_meta( $post->ID, 'thr_review_venue', true );
	$review_cast      = get_post_meta( $post->ID, 'thr_review_cast', true );
	$review_director  = get_post_meta( $post->ID, 'thr_review_director', true );
	$review_rating    = get_post_meta( $post->ID, 'thr_review_rating_time', true );
	$review_credits   = get_post_meta( $post->ID, 'thr_review_full_credits', true );
	$media_url        = get_post_meta( $post->ID, 'thr_media_url', true );
	?>
	<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
		<div>
			<p>
				<label for="thr_dek"><strong><?php esc_html_e( 'Dek (Subtitle / Deck):', 'thr-core' ); ?></strong></label><br>
				<textarea id="thr_dek" name="thr_dek" rows="3" style="width:100%;"><?php echo esc_textarea( $dek ); ?></textarea>
			</p>
			<p>
				<label for="thr_kicker"><strong><?php esc_html_e( 'Kicker Override (e.g. EXCLUSIVE):', 'thr-core' ); ?></strong></label><br>
				<input type="text" id="thr_kicker" name="thr_kicker" value="<?php echo esc_attr( $kicker ); ?>" style="width:100%;">
			</p>
			<p>
				<label for="thr_article_type"><strong><?php esc_html_e( 'Article Layout Type:', 'thr-core' ); ?></strong></label><br>
				<select id="thr_article_type" name="thr_article_type" style="width:100%;">
					<option value="standard" <?php selected( $article_type, 'standard' ); ?>><?php esc_html_e( 'Standard Article', 'thr-core' ); ?></option>
					<option value="review" <?php selected( $article_type, 'review' ); ?>><?php esc_html_e( 'Review (Honey Summary Box)', 'thr-core' ); ?></option>
					<option value="feature" <?php selected( $article_type, 'feature' ); ?>><?php esc_html_e( 'Feature (Full Bleed Hero, No Rail)', 'thr-core' ); ?></option>
					<option value="cover-story" <?php selected( $article_type, 'cover-story' ); ?>><?php esc_html_e( 'Cover Story', 'thr-core' ); ?></option>
					<option value="interview" <?php selected( $article_type, 'interview' ); ?>><?php esc_html_e( 'Interview (Q&A Style)', 'thr-core' ); ?></option>
					<option value="podcast" <?php selected( $article_type, 'podcast' ); ?>><?php esc_html_e( 'Podcast (Audio Player)', 'thr-core' ); ?></option>
				</select>
			</p>
			<p>
				<label><input type="checkbox" name="thr_featured" value="1" <?php checked( $featured, '1' ); ?>> <strong><?php esc_html_e( 'Featured Story (Lead Hero Candidate)', 'thr-core' ); ?></strong></label><br>
				<label><input type="checkbox" name="thr_breaking" value="1" <?php checked( $breaking, '1' ); ?>> <strong style="color:#D92128;"><?php esc_html_e( 'Send to Breaking News Bar', 'thr-core' ); ?></strong></label>
			</p>
			<p>
				<label for="thr_media_url"><strong><?php esc_html_e( 'Media Embed URL (YouTube or Podcast):', 'thr-core' ); ?></strong></label><br>
				<input type="text" id="thr_media_url" name="thr_media_url" value="<?php echo esc_url( $media_url ); ?>" style="width:100%;">
			</p>
		</div>

		<div style="background:#fcfaf7; border:1px solid #ebd9c5; padding:12px; border-radius:4px;">
			<h4 style="margin-top:0; color:#5c4424;"><?php esc_html_e( 'Review Data (Honey Summary Box)', 'thr-core' ); ?></h4>
			<p>
				<label for="thr_review_subject"><strong><?php esc_html_e( 'Subject / Title of Work:', 'thr-core' ); ?></strong></label><br>
				<input type="text" id="thr_review_subject" name="thr_review_subject" value="<?php echo esc_attr( $review_subject ); ?>" style="width:100%;">
			</p>
			<p>
				<label for="thr_review_bottom_line"><strong><?php esc_html_e( 'The Bottom Line (Summary Sentence):', 'thr-core' ); ?></strong></label><br>
				<input type="text" id="thr_review_bottom_line" name="thr_review_bottom_line" value="<?php echo esc_attr( $review_bottom ); ?>" style="width:100%;">
			</p>
			<p>
				<label for="thr_review_director"><strong><?php esc_html_e( 'Director:', 'thr-core' ); ?></strong></label><br>
				<input type="text" id="thr_review_director" name="thr_review_director" value="<?php echo esc_attr( $review_director ); ?>" style="width:100%;">
			</p>
			<p>
				<label for="thr_review_cast"><strong><?php esc_html_e( 'Cast:', 'thr-core' ); ?></strong></label><br>
				<input type="text" id="thr_review_cast" name="thr_review_cast" value="<?php echo esc_attr( $review_cast ); ?>" style="width:100%;">
			</p>
			<p>
				<label for="thr_review_venue"><strong><?php esc_html_e( 'Venue / Studio / Release Date:', 'thr-core' ); ?></strong></label><br>
				<input type="text" id="thr_review_venue" name="thr_review_venue" value="<?php echo esc_attr( $review_venue ); ?>" style="width:100%;">
			</p>
			<p>
				<label for="thr_review_rating_time"><strong><?php esc_html_e( 'Rating & Running Time:', 'thr-core' ); ?></strong></label><br>
				<input type="text" id="thr_review_rating_time" name="thr_review_rating_time" value="<?php echo esc_attr( $review_rating ); ?>" placeholder="e.g. Rated PG-13, 2 hours 18 minutes" style="width:100%;">
			</p>
			<p>
				<label for="thr_review_full_credits"><strong><?php esc_html_e( 'Full Credits:', 'thr-core' ); ?></strong></label><br>
				<textarea id="thr_review_full_credits" name="thr_review_full_credits" rows="3" style="width:100%;"><?php echo esc_textarea( $review_credits ); ?></textarea>
			</p>
		</div>
	</div>
	<?php
}

function thr_save_editorial_meta_box( $post_id ) {
	if ( ! isset( $_POST['thr_editorial_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['thr_editorial_meta_nonce'] ), 'thr_editorial_meta_nonce_action' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$string_fields = array(
		'thr_dek',
		'thr_kicker',
		'thr_article_type',
		'thr_review_subject',
		'thr_review_bottom_line',
		'thr_review_venue',
		'thr_review_cast',
		'thr_review_director',
		'thr_review_rating_time',
	);

	foreach ( $string_fields as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
		}
	}

	if ( isset( $_POST['thr_review_full_credits'] ) ) {
		update_post_meta( $post_id, 'thr_review_full_credits', wp_kses_post( wp_unslash( $_POST['thr_review_full_credits'] ) ) );
	}

	if ( isset( $_POST['thr_media_url'] ) ) {
		update_post_meta( $post_id, 'thr_media_url', esc_url_raw( wp_unslash( $_POST['thr_media_url'] ) ) );
	}

	$featured = ! empty( $_POST['thr_featured'] ) ? '1' : '';
	update_post_meta( $post_id, 'thr_featured', $featured );

	$breaking = ! empty( $_POST['thr_breaking'] ) ? '1' : '';
	update_post_meta( $post_id, 'thr_breaking', $breaking );
}
add_action( 'save_post', 'thr_save_editorial_meta_box' );
