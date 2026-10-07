<?php
/**
 * Author Profiles, Job Titles, and Bylines.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_user_profile_fields( $user ) {
	$job_title = get_user_meta( $user->ID, 'thr_job_title', true );
	$contact_email = get_user_meta( $user->ID, 'thr_public_email', true );
	$twitter = get_user_meta( $user->ID, 'thr_twitter', true );
	$instagram = get_user_meta( $user->ID, 'thr_instagram', true );
	$linkedin = get_user_meta( $user->ID, 'thr_linkedin', true );
	$threads = get_user_meta( $user->ID, 'thr_threads', true );
	?>
	<h3><?php esc_html_e( 'THR Editorial Profile Details', 'thr-core' ); ?></h3>
	<table class="form-table">
		<tr>
			<th><label for="thr_job_title"><?php esc_html_e( 'Job Title / Editorial Role', 'thr-core' ); ?></label></th>
			<td>
				<input type="text" name="thr_job_title" id="thr_job_title" value="<?php echo esc_attr( $job_title ); ?>" class="regular-text" />
				<p class="description"><?php esc_html_e( 'e.g. "Chief Film Critic", "Executive Editor, International"', 'thr-core' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="thr_public_email"><?php esc_html_e( 'Public Contact / Tip Email', 'thr-core' ); ?></label></th>
			<td>
				<input type="email" name="thr_public_email" id="thr_public_email" value="<?php echo esc_attr( $contact_email ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th><label for="thr_twitter"><?php esc_html_e( 'X / Twitter Profile URL', 'thr-core' ); ?></label></th>
			<td><input type="url" name="thr_twitter" id="thr_twitter" value="<?php echo esc_url( $twitter ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="thr_instagram"><?php esc_html_e( 'Instagram URL', 'thr-core' ); ?></label></th>
			<td><input type="url" name="thr_instagram" id="thr_instagram" value="<?php echo esc_url( $instagram ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="thr_linkedin"><?php esc_html_e( 'LinkedIn URL', 'thr-core' ); ?></label></th>
			<td><input type="url" name="thr_linkedin" id="thr_linkedin" value="<?php echo esc_url( $linkedin ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="thr_threads"><?php esc_html_e( 'Threads URL', 'thr-core' ); ?></label></th>
			<td><input type="url" name="thr_threads" id="thr_threads" value="<?php echo esc_url( $threads ); ?>" class="regular-text" /></td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'thr_user_profile_fields' );
add_action( 'edit_user_profile', 'thr_user_profile_fields' );

function thr_save_user_profile_fields( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}

	if ( isset( $_POST['thr_job_title'] ) ) {
		update_user_meta( $user_id, 'thr_job_title', sanitize_text_field( wp_unslash( $_POST['thr_job_title'] ) ) );
	}
	if ( isset( $_POST['thr_public_email'] ) ) {
		update_user_meta( $user_id, 'thr_public_email', sanitize_email( wp_unslash( $_POST['thr_public_email'] ) ) );
	}
	if ( isset( $_POST['thr_twitter'] ) ) {
		update_user_meta( $user_id, 'thr_twitter', esc_url_raw( wp_unslash( $_POST['thr_twitter'] ) ) );
	}
	if ( isset( $_POST['thr_instagram'] ) ) {
		update_user_meta( $user_id, 'thr_instagram', esc_url_raw( wp_unslash( $_POST['thr_instagram'] ) ) );
	}
	if ( isset( $_POST['thr_linkedin'] ) ) {
		update_user_meta( $user_id, 'thr_linkedin', esc_url_raw( wp_unslash( $_POST['thr_linkedin'] ) ) );
	}
	if ( isset( $_POST['thr_threads'] ) ) {
		update_user_meta( $user_id, 'thr_threads', esc_url_raw( wp_unslash( $_POST['thr_threads'] ) ) );
	}
}
add_action( 'personal_options_update', 'thr_save_user_profile_fields' );
add_action( 'edit_user_profile_update', 'thr_save_user_profile_fields' );

function thr_get_byline( $post_id = null ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	if ( function_exists( 'get_coauthors' ) ) {
		$coauthors = get_coauthors( $post_id );
		if ( ! empty( $coauthors ) ) {
			$names = array();
			foreach ( $coauthors as $author ) {
				$link = get_author_posts_url( $author->ID, $author->user_nicename );
				$names[] = sprintf( '<a href="%s" class="thr-byline__link">%s</a>', esc_url( $link ), esc_html( strtoupper( $author->display_name ) ) );
			}
			$count = count( $names );
			if ( 1 === $count ) {
				return '<span class="thr-byline">' . sprintf( __( 'BY %s', 'thr-core' ), $names[0] ) . '</span>';
			} elseif ( 2 === $count ) {
				return '<span class="thr-byline">' . sprintf( __( 'BY %s AND %s', 'thr-core' ), $names[0], $names[1] ) . '</span>';
			} else {
				$last = array_pop( $names );
				return '<span class="thr-byline">' . sprintf( __( 'BY %s AND %s', 'thr-core' ), implode( ', ', $names ), $last ) . '</span>';
			}
		}
	}

	$author_id = get_post_field( 'post_author', $post_id );
	$author_name = get_the_author_meta( 'display_name', $author_id );
	$author_link = get_author_posts_url( $author_id );

	return sprintf(
		'<span class="thr-byline">%s <a href="%s" class="thr-byline__link">%s</a></span>',
		esc_html__( 'BY', 'thr-core' ),
		esc_url( $author_link ),
		esc_html( strtoupper( $author_name ) )
	);
}
