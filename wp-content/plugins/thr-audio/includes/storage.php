<?php
/**
 * Storage: keep recordings on Cloudflare R2 and serve them from audio.thehollywoodreporteruk.com.
 *
 * Recordings stay ordinary attachments, so the Audio Library, the editor box and the podcast
 * feed are unchanged. Only two things differ when Storage is set to R2:
 *   - a new recording is uploaded to R2 straight after it is saved, and
 *   - wp_get_attachment_url() returns the R2 address for files that are there.
 * Switching back to Local points every address at the copy on this server again.
 *
 * Credentials live in wp-config.php, never in the database:
 *   define( 'THR_R2_ACCOUNT_ID', '…' );
 *   define( 'THR_R2_ACCESS_KEY_ID', '…' );
 *   define( 'THR_R2_SECRET_ACCESS_KEY', '…' );
 *   define( 'THR_R2_BUCKET', 'thr-audio-uk' );
 *   define( 'THR_R2_PUBLIC_URL', 'https://audio.thehollywoodreporteruk.com' );
 * Optional, so a deleted file also disappears from Cloudflare's cache straight away:
 *   define( 'THR_CF_ZONE_ID', '…' );
 *   define( 'THR_CF_API_TOKEN', '…' ); // Zone → Cache Purge permission only.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const THR_AUDIO_R2_BATCH = 25;

/**
 * Whether every R2 credential is in wp-config.php.
 *
 * @return bool
 */
function thr_audio_r2_configured() {
	foreach ( array( 'THR_R2_ACCOUNT_ID', 'THR_R2_ACCESS_KEY_ID', 'THR_R2_SECRET_ACCESS_KEY', 'THR_R2_BUCKET', 'THR_R2_PUBLIC_URL' ) as $constant ) {
		if ( ! defined( $constant ) || '' === (string) constant( $constant ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Whether recordings are served from R2 right now.
 *
 * @return bool
 */
function thr_audio_r2_active() {
	return thr_audio_r2_configured() && 'r2' === thr_audio_settings()['storage'];
}

/**
 * Public address of an object.
 *
 * @param string $key Object key, e.g. thr-audio/2026/11/name.mp3.
 * @return string
 */
function thr_audio_r2_public_url( $key ) {
	return untrailingslashit( THR_R2_PUBLIC_URL ) . '/' . implode( '/', array_map( 'rawurlencode', explode( '/', $key ) ) );
}

/**
 * Signed request to the R2 S3 API (AWS Signature Version 4, region "auto").
 *
 * @param string $method  PUT|HEAD|DELETE|GET.
 * @param string $key     Object key.
 * @param string $body    Request body.
 * @param array  $headers Extra headers (not signed).
 * @return array|WP_Error wp_remote_request() response.
 */
function thr_audio_r2_request( $method, $key, $body = '', $headers = array() ) {
	if ( ! thr_audio_r2_configured() ) {
		return new WP_Error( 'thr_audio_r2_config', __( 'R2 is not set up in wp-config.php.', 'thr-audio' ) );
	}

	$host    = THR_R2_ACCOUNT_ID . '.r2.cloudflarestorage.com';
	$path    = '/' . rawurlencode( THR_R2_BUCKET ) . '/' . implode( '/', array_map( 'rawurlencode', explode( '/', $key ) ) );
	$amzdate = gmdate( 'Ymd\THis\Z' );
	$date    = substr( $amzdate, 0, 8 );
	$hash    = hash( 'sha256', $body );
	$scope   = $date . '/auto/s3/aws4_request';

	$canonical = implode(
		"\n",
		array(
			$method,
			$path,
			'',
			'host:' . $host,
			'x-amz-content-sha256:' . $hash,
			'x-amz-date:' . $amzdate,
			'',
			'host;x-amz-content-sha256;x-amz-date',
			$hash,
		)
	);
	$to_sign   = "AWS4-HMAC-SHA256\n" . $amzdate . "\n" . $scope . "\n" . hash( 'sha256', $canonical );
	$signing   = hash_hmac( 'sha256', 'aws4_request', hash_hmac( 'sha256', 's3', hash_hmac( 'sha256', 'auto', hash_hmac( 'sha256', $date, 'AWS4' . THR_R2_SECRET_ACCESS_KEY, true ), true ), true ), true );
	$signature = hash_hmac( 'sha256', $to_sign, $signing );

	$headers['x-amz-content-sha256'] = $hash;
	$headers['x-amz-date']           = $amzdate;
	$headers['Authorization']        = 'AWS4-HMAC-SHA256 Credential=' . THR_R2_ACCESS_KEY_ID . '/' . $scope . ', SignedHeaders=host;x-amz-content-sha256;x-amz-date, Signature=' . $signature;

	return wp_remote_request(
		'https://' . $host . $path,
		array(
			'method'  => $method,
			'headers' => $headers,
			'body'    => '' === $body ? null : $body,
			'timeout' => 60,
		)
	);
}

/**
 * Upload bytes and confirm R2 holds the full file.
 *
 * @param string $key   Object key.
 * @param string $bytes File contents.
 * @return true|WP_Error
 */
function thr_audio_r2_put( $key, $bytes ) {
	$response = thr_audio_r2_request(
		'PUT',
		$key,
		$bytes,
		array(
			'Content-Type'  => 'audio/mpeg',
			// A recording never changes (a remake gets a new name), so it can be cached for a year.
			'Cache-Control' => 'public, max-age=31536000, immutable',
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		return new WP_Error( 'thr_audio_r2_put', sprintf( /* translators: %d: HTTP status. */ __( 'R2 upload failed (HTTP %d).', 'thr-audio' ), $code ) );
	}

	$head = thr_audio_r2_request( 'HEAD', $key );
	if ( is_wp_error( $head ) || (int) wp_remote_retrieve_header( $head, 'content-length' ) !== strlen( $bytes ) ) {
		return new WP_Error( 'thr_audio_r2_verify', __( 'R2 upload could not be verified.', 'thr-audio' ) );
	}
	return true;
}

/**
 * Delete an object. A missing object counts as deleted.
 *
 * @param string $key Object key.
 * @return true|WP_Error
 */
function thr_audio_r2_delete( $key ) {
	$response = thr_audio_r2_request( 'DELETE', $key );
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( 204 !== $code && 200 !== $code && 404 !== $code ) {
		return new WP_Error( 'thr_audio_r2_delete', sprintf( /* translators: %d: HTTP status. */ __( 'R2 delete failed (HTTP %d).', 'thr-audio' ), $code ) );
	}
	return true;
}

/**
 * Drop an address from Cloudflare's cache, when a purge token is set.
 *
 * @param string[] $urls Public addresses.
 */
function thr_audio_r2_purge( $urls ) {
	if ( ! $urls || ! defined( 'THR_CF_ZONE_ID' ) || ! defined( 'THR_CF_API_TOKEN' ) || '' === (string) THR_CF_API_TOKEN ) {
		return;
	}
	wp_remote_post(
		'https://api.cloudflare.com/client/v4/zones/' . rawurlencode( THR_CF_ZONE_ID ) . '/purge_cache',
		array(
			'headers' => array(
				'Authorization' => 'Bearer ' . THR_CF_API_TOKEN,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( array( 'files' => array_values( $urls ) ) ),
			'timeout' => 15,
		)
	);
}

/**
 * Copy one recording to R2 and mark it as stored there.
 *
 * @param int $attachment_id Attachment ID.
 * @return true|WP_Error
 */
function thr_audio_r2_offload( $attachment_id ) {
	$key  = (string) get_post_meta( $attachment_id, '_wp_attached_file', true );
	$file = get_attached_file( $attachment_id );
	if ( '' === $key || ! $file || ! is_readable( $file ) ) {
		return new WP_Error( 'thr_audio_r2_missing', __( 'The local MP3 is missing.', 'thr-audio' ) );
	}

	$bytes = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
	if ( false === $bytes ) {
		return new WP_Error( 'thr_audio_r2_missing', __( 'The local MP3 could not be read.', 'thr-audio' ) );
	}

	$done = thr_audio_r2_put( $key, $bytes );
	if ( is_wp_error( $done ) ) {
		return $done;
	}
	update_post_meta( $attachment_id, '_thr_audio_r2', $key );
	delete_post_meta( $attachment_id, '_thr_audio_r2_error' );
	return true;
}

/**
 * New recordings go to R2 as soon as they are saved. If the upload fails the local copy is
 * still served, and "Move to R2" in the Audio Library picks the file up later.
 *
 * @param int $attachment_id Attachment ID.
 */
function thr_audio_r2_after_save( $attachment_id ) {
	if ( ! thr_audio_r2_active() ) {
		return;
	}
	$done = thr_audio_r2_offload( $attachment_id );
	if ( is_wp_error( $done ) ) {
		update_post_meta( $attachment_id, '_thr_audio_r2_error', $done->get_error_message() );
	}
}
add_action( 'thr_audio_file_saved', 'thr_audio_r2_after_save' );

/**
 * Serve recordings that are on R2 from the audio subdomain.
 *
 * @param string $url           Local address.
 * @param int    $attachment_id Attachment ID.
 * @return string
 */
function thr_audio_r2_attachment_url( $url, $attachment_id ) {
	$key = get_post_meta( $attachment_id, '_thr_audio_r2', true );
	if ( $key && thr_audio_r2_active() ) {
		return thr_audio_r2_public_url( $key );
	}
	return $url;
}
add_filter( 'wp_get_attachment_url', 'thr_audio_r2_attachment_url', 10, 2 );

/* -------------------------------------------------------------------------
 * Deleting: R2 follows WordPress.
 * ---------------------------------------------------------------------- */

/**
 * When a recording is deleted, delete its R2 copy and drop it from Cloudflare's cache.
 * A failed delete is kept and retried from the Audio Library.
 *
 * @param int $attachment_id Attachment being deleted.
 */
function thr_audio_r2_on_delete( $attachment_id ) {
	$key = (string) get_post_meta( $attachment_id, '_thr_audio_r2', true );
	if ( '' === $key ) {
		return;
	}
	$done = thr_audio_r2_delete( $key );
	if ( is_wp_error( $done ) ) {
		$pending   = (array) get_option( 'thr_audio_r2_pending_deletes', array() );
		$pending[] = $key;
		update_option( 'thr_audio_r2_pending_deletes', array_values( array_unique( $pending ) ), false );
		return;
	}
	thr_audio_r2_purge( array( thr_audio_r2_public_url( $key ) ) );
}
add_action( 'delete_attachment', 'thr_audio_r2_on_delete' );

/**
 * Retry deletes that failed earlier.
 *
 * @return int Deletes still waiting.
 */
function thr_audio_r2_retry_deletes() {
	$pending = (array) get_option( 'thr_audio_r2_pending_deletes', array() );
	if ( ! $pending || ! thr_audio_r2_configured() ) {
		return count( $pending );
	}
	$left = array();
	$done = array();
	foreach ( $pending as $i => $key ) {
		if ( $i >= 20 || is_wp_error( thr_audio_r2_delete( $key ) ) ) {
			$left[] = $key;
		} else {
			$done[] = thr_audio_r2_public_url( $key );
		}
	}
	thr_audio_r2_purge( $done );
	update_option( 'thr_audio_r2_pending_deletes', $left, false );
	return count( $left );
}

/**
 * Deleting an article for good also deletes the recordings the plugin made for it (and so
 * their R2 copies). Trashed or unpublished articles keep their audio so they can be restored:
 * the player, reader endpoint and podcast feed only ever use published articles.
 *
 * @param int $post_id Post being deleted.
 */
function thr_audio_delete_post_audio( $post_id ) {
	if ( ! in_array( get_post_type( $post_id ), thr_audio_post_types(), true ) ) {
		return;
	}
	$ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_parent'    => $post_id,
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => '_thr_audio_generated', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		)
	);
	foreach ( $ids as $id ) {
		wp_delete_attachment( $id, true );
	}
}
add_action( 'before_delete_post', 'thr_audio_delete_post_audio' );

/* -------------------------------------------------------------------------
 * Moving existing recordings (Audio Library).
 * ---------------------------------------------------------------------- */

/**
 * Recordings not on R2 yet.
 *
 * @param bool $count_only Return a number instead of IDs.
 * @return int|int[]
 */
function thr_audio_r2_waiting( $count_only = false ) {
	$query = new WP_Query(
		array(
			'post_type'              => 'attachment',
			'post_status'            => 'inherit',
			'posts_per_page'         => $count_only ? 1 : THR_AUDIO_R2_BATCH,
			'fields'                 => 'ids',
			'orderby'                => 'ID',
			'order'                  => 'DESC',
			'no_found_rows'          => ! $count_only,
			'update_post_term_cache' => false,
			'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_thr_audio_generated',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => '_thr_audio_r2',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_thr_audio_r2_error',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);
	return $count_only ? (int) $query->found_posts : $query->posts;
}

/**
 * Recordings whose copy to R2 failed.
 *
 * @return int
 */
function thr_audio_r2_failed_count() {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- admin screen only.
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s", '_thr_audio_r2_error' ) );
}

/**
 * Copy the next batch of recordings to R2. Local files are never removed here.
 */
function thr_audio_r2_move_batch() {
	check_admin_referer( 'thr_audio_r2_move' );
	if ( ! current_user_can( 'manage_options' ) || ! thr_audio_r2_configured() ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'thr-audio' ), 403 );
	}

	if ( ! empty( $_POST['retry_failed'] ) ) {
		delete_metadata( 'post', 0, '_thr_audio_r2_error', '', true );
	}

	$moved   = 0;
	$failed  = 0;
	$parents = array();
	foreach ( thr_audio_r2_waiting() as $id ) {
		$done = thr_audio_r2_offload( $id );
		if ( is_wp_error( $done ) ) {
			update_post_meta( $id, '_thr_audio_r2_error', $done->get_error_message() );
			++$failed;
			continue;
		}
		++$moved;
		$parents[] = (int) wp_get_post_parent_id( $id );
	}

	// Pages and the podcast feed pick up the new addresses only when R2 is switched on.
	if ( thr_audio_r2_active() ) {
		foreach ( array_filter( array_unique( $parents ) ) as $parent ) {
			thr_audio_purge_page( $parent );
		}
		thr_audio_podcast_flush();
	}
	thr_audio_r2_retry_deletes();

	$left = thr_audio_r2_waiting( true );
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'      => 'thr-audio-library',
				'r2_moved'  => $moved,
				'r2_failed' => $failed,
				'r2_left'   => $left,
				'r2_auto'   => ! empty( $_POST['auto'] ) && $left > 0 && $moved > 0 ? 1 : 0,
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_post_thr_audio_r2_move', 'thr_audio_r2_move_batch' );

/**
 * Storage box at the top of the Audio Library.
 */
function thr_audio_render_storage_box() {
	if ( ! current_user_can( 'manage_options' ) || ! thr_audio_r2_configured() ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display only.
	$moved = isset( $_GET['r2_moved'] ) ? absint( $_GET['r2_moved'] ) : null;
	$fails = isset( $_GET['r2_failed'] ) ? absint( $_GET['r2_failed'] ) : 0;
	$auto  = ! empty( $_GET['r2_auto'] );
	// phpcs:enable

	$deletes = thr_audio_r2_retry_deletes();
	$left    = thr_audio_r2_waiting( true );
	$failed  = thr_audio_r2_failed_count();
	?>
	<div class="notice notice-info inline" style="margin:12px 0;padding:8px 12px">
		<p>
			<strong><?php esc_html_e( 'Storage:', 'thr-audio' ); ?></strong>
			<?php
			echo thr_audio_r2_active()
				? esc_html__( 'Cloudflare R2. Recordings on R2 are served from the audio subdomain.', 'thr-audio' )
				: esc_html__( 'This server. R2 is set up but switched off (Settings → THR Audio → Storage).', 'thr-audio' );
			?>
		</p>
		<?php if ( null !== $moved ) : ?>
			<p>
				<?php
				printf(
					/* translators: 1: files copied, 2: files that failed. */
					esc_html__( 'Copied %1$s recordings to R2. %2$s failed.', 'thr-audio' ),
					esc_html( number_format_i18n( $moved ) ),
					esc_html( number_format_i18n( $fails ) )
				);
				?>
			</p>
		<?php endif; ?>
		<p>
			<?php
			printf(
				/* translators: 1: recordings waiting, 2: recordings that failed. */
				esc_html__( 'Not on R2 yet: %1$s. Failed: %2$s.', 'thr-audio' ),
				'<strong>' . esc_html( number_format_i18n( $left ) ) . '</strong>',
				'<strong>' . esc_html( number_format_i18n( $failed ) ) . '</strong>'
			);
			if ( $deletes ) {
				echo ' ';
				printf(
					/* translators: %s: number of files. */
					esc_html__( '%s deleted files are still waiting to be removed from R2 (retried automatically).', 'thr-audio' ),
					esc_html( number_format_i18n( $deletes ) )
				);
			}
			?>
		</p>
		<?php if ( $left || $failed ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="thr-audio-r2-move">
				<input type="hidden" name="action" value="thr_audio_r2_move" />
				<input type="hidden" name="auto" value="1" />
				<?php wp_nonce_field( 'thr_audio_r2_move' ); ?>
				<?php if ( $left ) : ?>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Copy all to R2', 'thr-audio' ); ?></button>
				<?php endif; ?>
				<?php if ( $failed ) : ?>
					<button type="submit" class="button" name="retry_failed" value="1"><?php esc_html_e( 'Retry failed', 'thr-audio' ); ?></button>
				<?php endif; ?>
				<span class="description"><?php echo esc_html( sprintf( /* translators: %d: batch size. */ __( 'Copies %d at a time and keeps going until done. Files on this server are kept.', 'thr-audio' ), THR_AUDIO_R2_BATCH ) ); ?></span>
			</form>
			<?php if ( $auto && $left ) : ?>
				<script>setTimeout( () => document.getElementById( 'thr-audio-r2-move' ).submit(), 800 );</script>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Settings → THR Audio → Storage.
 * ---------------------------------------------------------------------- */

/**
 * @param array  $s    Settings.
 * @param string $name Option name.
 */
function thr_audio_render_storage_settings( $s, $name ) {
	$ready = thr_audio_r2_configured();
	?>
	<h2 class="title"><?php esc_html_e( 'Storage', 'thr-audio' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Where recordings are served from', 'thr-audio' ); ?></th>
			<td>
				<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[storage]" value="local" <?php checked( $s['storage'], 'local' ); ?> /> <?php esc_html_e( 'This server (uploads/thr-audio)', 'thr-audio' ); ?></label><br />
				<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[storage]" value="r2" <?php checked( $s['storage'], 'r2' ); ?> <?php disabled( ! $ready ); ?> /> <?php esc_html_e( 'Cloudflare R2: new recordings are uploaded there and served from the audio subdomain', 'thr-audio' ); ?></label>
				<?php if ( $ready ) : ?>
					<p class="description">
						<?php
						printf(
							/* translators: 1: bucket, 2: public address. */
							esc_html__( 'Bucket %1$s, served from %2$s. Copy older recordings with "Copy all to R2" in the Audio Library. Switching back to "This server" is instant, because local copies are kept.', 'thr-audio' ),
							'<code>' . esc_html( THR_R2_BUCKET ) . '</code>',
							'<code>' . esc_html( THR_R2_PUBLIC_URL ) . '</code>'
						);
						?>
					</p>
					<p>
						<button type="button" class="button" id="thr-audio-r2-test" data-nonce="<?php echo esc_attr( wp_create_nonce( 'thr_audio_r2_test' ) ); ?>"><?php esc_html_e( 'Test R2', 'thr-audio' ); ?></button>
						<span id="thr-audio-r2-test-result" style="margin-left:8px"></span>
					</p>
					<?php if ( ! defined( 'THR_CF_API_TOKEN' ) ) : ?>
						<p class="description"><?php esc_html_e( 'Optional: add THR_CF_ZONE_ID and THR_CF_API_TOKEN (Cache Purge permission only) to wp-config.php so deleted recordings also leave Cloudflare\'s cache at once.', 'thr-audio' ); ?></p>
					<?php endif; ?>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'To use R2, add these lines to wp-config.php, above "That\'s all, stop editing!":', 'thr-audio' ); ?></p>
					<pre style="background:#f6f7f7;padding:8px;max-width:700px;overflow:auto">define( 'THR_R2_ACCOUNT_ID', '…' );
define( 'THR_R2_ACCESS_KEY_ID', '…' );
define( 'THR_R2_SECRET_ACCESS_KEY', '…' );
define( 'THR_R2_BUCKET', 'thr-audio-uk' );
define( 'THR_R2_PUBLIC_URL', 'https://audio.thehollywoodreporteruk.com' );</pre>
				<?php endif; ?>
			</td>
		</tr>
	</table>
	<?php if ( $ready ) : ?>
		<script>
		( () => {
			const button = document.getElementById( 'thr-audio-r2-test' );
			button.addEventListener( 'click', () => {
				const out = document.getElementById( 'thr-audio-r2-test-result' );
				out.textContent = <?php echo wp_json_encode( __( 'Checking…', 'thr-audio' ) ); ?>;
				const body = new URLSearchParams( { action: 'thr_audio_r2_test', _ajax_nonce: button.dataset.nonce } );
				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body } )
					.then( ( r ) => r.json() )
					.then( ( r ) => { out.textContent = r.data; out.style.color = r.success ? '#008a20' : '#b32d2e'; } )
					.catch( () => { out.textContent = <?php echo wp_json_encode( __( 'Request failed.', 'thr-audio' ) ); ?>; } );
			} );
		} )();
		</script>
	<?php endif; ?>
	<?php
}

/**
 * "Test R2": upload a small file, read it back through the public address, then delete it.
 */
function thr_audio_ajax_r2_test() {
	check_ajax_referer( 'thr_audio_r2_test' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Not allowed.', 'thr-audio' ), 403 );
	}

	$key   = 'thr-audio/_test/check-' . strtolower( wp_generate_password( 10, false ) ) . '.mp3';
	$bytes = str_repeat( 'THR', 1000 );
	$put   = thr_audio_r2_put( $key, $bytes );
	if ( is_wp_error( $put ) ) {
		wp_send_json_error( $put->get_error_message() );
	}

	$public = wp_remote_get( thr_audio_r2_public_url( $key ), array( 'timeout' => 15 ) );
	thr_audio_r2_delete( $key );

	if ( is_wp_error( $public ) || 200 !== (int) wp_remote_retrieve_response_code( $public ) ) {
		wp_send_json_error( __( 'Upload works, but the file could not be read from the public address. Check the custom domain on the bucket.', 'thr-audio' ) );
	}
	wp_send_json_success( __( 'R2 works: upload, public address and delete all OK.', 'thr-audio' ) );
}
add_action( 'wp_ajax_thr_audio_r2_test', 'thr_audio_ajax_r2_test' );

/**
 * Switching storage changes every recording's address: clear page caches and the feed.
 *
 * @param mixed $old Old settings.
 * @param mixed $new New settings.
 */
function thr_audio_storage_switched( $old, $new ) {
	$before = is_array( $old ) && isset( $old['storage'] ) ? $old['storage'] : 'local';
	$after  = is_array( $new ) && isset( $new['storage'] ) ? $new['storage'] : 'local';
	if ( $before !== $after ) {
		do_action( 'litespeed_purge_all' );
		thr_audio_podcast_flush();
	}
}
add_action( 'update_option_thr_audio_settings', 'thr_audio_storage_switched', 10, 2 );
