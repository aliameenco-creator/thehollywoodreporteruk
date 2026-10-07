<?php
/**
 * Register and manage Term Meta for Verticals, Topics, and Sections.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_register_term_meta() {
	$vertical_keys = array(
		'thr_vertical_color'     => 'string',
		'thr_vertical_logo'      => 'string',
		'thr_vertical_tagline_1' => 'string',
		'thr_vertical_tagline_2' => 'string',
	);
	foreach ( $vertical_keys as $key => $type ) {
		register_term_meta(
			'vertical',
			$key,
			array(
				'type'              => $type,
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
	}

	register_term_meta(
		'post_tag',
		'thr_topic_banner',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	register_term_meta(
		'post_tag',
		'thr_topic_intro',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'wp_kses_post',
		)
	);

	register_term_meta(
		'category',
		'thr_heading',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	register_term_meta(
		'category',
		'thr_subtitle',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
}
add_action( 'init', 'thr_register_term_meta' );

function thr_vertical_add_form_fields() {
	wp_nonce_field( 'thr_term_meta_action', 'thr_term_meta_nonce' );
	?>
	<div class="form-field">
		<label for="thr_vertical_color"><?php esc_html_e( 'Brand Colour (Hex)', 'thr-core' ); ?></label>
		<input type="text" name="thr_vertical_color" id="thr_vertical_color" value="#D92128" />
		<p class="description"><?php esc_html_e( 'e.g. #6442AC for Heat Vision, #008080 for Live Feed.', 'thr-core' ); ?></p>
	</div>
	<div class="form-field">
		<label for="thr_vertical_tagline_1"><?php esc_html_e( 'Primary Tagline', 'thr-core' ); ?></label>
		<input type="text" name="thr_vertical_tagline_1" id="thr_vertical_tagline_1" value="" />
	</div>
	<div class="form-field">
		<label for="thr_vertical_tagline_2"><?php esc_html_e( 'Secondary Tagline', 'thr-core' ); ?></label>
		<input type="text" name="thr_vertical_tagline_2" id="thr_vertical_tagline_2" value="" />
	</div>
	<div class="form-field">
		<label for="thr_vertical_logo"><?php esc_html_e( 'Wordmark / Logo URL', 'thr-core' ); ?></label>
		<input type="text" name="thr_vertical_logo" id="thr_vertical_logo" value="" />
	</div>
	<?php
}
add_action( 'vertical_add_form_fields', 'thr_vertical_add_form_fields' );

function thr_vertical_edit_form_fields( $term ) {
	$color     = get_term_meta( $term->term_id, 'thr_vertical_color', true ) ?: '#D92128';
	$tagline_1 = get_term_meta( $term->term_id, 'thr_vertical_tagline_1', true );
	$tagline_2 = get_term_meta( $term->term_id, 'thr_vertical_tagline_2', true );
	$logo      = get_term_meta( $term->term_id, 'thr_vertical_logo', true );
	wp_nonce_field( 'thr_term_meta_action', 'thr_term_meta_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="thr_vertical_color"><?php esc_html_e( 'Brand Colour (Hex)', 'thr-core' ); ?></label></th>
		<td>
			<input type="text" name="thr_vertical_color" id="thr_vertical_color" value="<?php echo esc_attr( $color ); ?>" />
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="thr_vertical_tagline_1"><?php esc_html_e( 'Primary Tagline', 'thr-core' ); ?></label></th>
		<td><input type="text" name="thr_vertical_tagline_1" id="thr_vertical_tagline_1" value="<?php echo esc_attr( $tagline_1 ); ?>" /></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="thr_vertical_tagline_2"><?php esc_html_e( 'Secondary Tagline', 'thr-core' ); ?></label></th>
		<td><input type="text" name="thr_vertical_tagline_2" id="thr_vertical_tagline_2" value="<?php echo esc_attr( $tagline_2 ); ?>" /></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="thr_vertical_logo"><?php esc_html_e( 'Wordmark / Logo URL', 'thr-core' ); ?></label></th>
		<td><input type="text" name="thr_vertical_logo" id="thr_vertical_logo" value="<?php echo esc_url( $logo ); ?>" /></td>
	</tr>
	<?php
}
add_action( 'vertical_edit_form_fields', 'thr_vertical_edit_form_fields' );

function thr_save_vertical_meta( $term_id ) {
	if ( ! isset( $_POST['thr_term_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['thr_term_meta_nonce'] ), 'thr_term_meta_action' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	if ( isset( $_POST['thr_vertical_color'] ) ) {
		update_term_meta( $term_id, 'thr_vertical_color', sanitize_hex_color( wp_unslash( $_POST['thr_vertical_color'] ) ) );
	}
	if ( isset( $_POST['thr_vertical_tagline_1'] ) ) {
		update_term_meta( $term_id, 'thr_vertical_tagline_1', sanitize_text_field( wp_unslash( $_POST['thr_vertical_tagline_1'] ) ) );
	}
	if ( isset( $_POST['thr_vertical_tagline_2'] ) ) {
		update_term_meta( $term_id, 'thr_vertical_tagline_2', sanitize_text_field( wp_unslash( $_POST['thr_vertical_tagline_2'] ) ) );
	}
	if ( isset( $_POST['thr_vertical_logo'] ) ) {
		update_term_meta( $term_id, 'thr_vertical_logo', esc_url_raw( wp_unslash( $_POST['thr_vertical_logo'] ) ) );
	}
}
add_action( 'created_vertical', 'thr_save_vertical_meta' );
add_action( 'edited_vertical', 'thr_save_vertical_meta' );

function thr_topic_edit_form_fields( $term ) {
	$banner = get_term_meta( $term->term_id, 'thr_topic_banner', true );
	$intro  = get_term_meta( $term->term_id, 'thr_topic_intro', true );
	wp_nonce_field( 'thr_topic_meta_action', 'thr_topic_meta_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="thr_topic_banner"><?php esc_html_e( 'Banner Image URL (1500×598)', 'thr-core' ); ?></label></th>
		<td><input type="text" name="thr_topic_banner" id="thr_topic_banner" value="<?php echo esc_url( $banner ); ?>" /></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="thr_topic_intro"><?php esc_html_e( 'Topic Intro / Newsletter note', 'thr-core' ); ?></label></th>
		<td><textarea name="thr_topic_intro" id="thr_topic_intro" rows="4"><?php echo esc_textarea( $intro ); ?></textarea></td>
	</tr>
	<?php
}
add_action( 'post_tag_edit_form_fields', 'thr_topic_edit_form_fields' );

function thr_save_topic_meta( $term_id ) {
	if ( ! isset( $_POST['thr_topic_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['thr_topic_meta_nonce'] ), 'thr_topic_meta_action' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	if ( isset( $_POST['thr_topic_banner'] ) ) {
		update_term_meta( $term_id, 'thr_topic_banner', esc_url_raw( wp_unslash( $_POST['thr_topic_banner'] ) ) );
	}
	if ( isset( $_POST['thr_topic_intro'] ) ) {
		update_term_meta( $term_id, 'thr_topic_intro', wp_kses_post( wp_unslash( $_POST['thr_topic_intro'] ) ) );
	}
}
add_action( 'created_post_tag', 'thr_save_topic_meta' );
add_action( 'edited_post_tag', 'thr_save_topic_meta' );

function thr_category_edit_form_fields( $term ) {
	$heading  = get_term_meta( $term->term_id, 'thr_heading', true );
	$subtitle = get_term_meta( $term->term_id, 'thr_subtitle', true );
	wp_nonce_field( 'thr_category_meta_action', 'thr_category_meta_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="thr_heading"><?php esc_html_e( 'Archive Heading Override', 'thr-core' ); ?></label></th>
		<td><input type="text" name="thr_heading" id="thr_heading" value="<?php echo esc_attr( $heading ); ?>" /></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="thr_subtitle"><?php esc_html_e( 'Archive Subtitle', 'thr-core' ); ?></label></th>
		<td><input type="text" name="thr_subtitle" id="thr_subtitle" value="<?php echo esc_attr( $subtitle ); ?>" /></td>
	</tr>
	<?php
}
add_action( 'category_edit_form_fields', 'thr_category_edit_form_fields' );

function thr_save_category_meta( $term_id ) {
	if ( ! isset( $_POST['thr_category_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['thr_category_meta_nonce'] ), 'thr_category_meta_action' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	if ( isset( $_POST['thr_heading'] ) ) {
		update_term_meta( $term_id, 'thr_heading', sanitize_text_field( wp_unslash( $_POST['thr_heading'] ) ) );
	}
	if ( isset( $_POST['thr_subtitle'] ) ) {
		update_term_meta( $term_id, 'thr_subtitle', sanitize_text_field( wp_unslash( $_POST['thr_subtitle'] ) ) );
	}
}
add_action( 'created_category', 'thr_save_category_meta' );
add_action( 'edited_category', 'thr_save_category_meta' );
