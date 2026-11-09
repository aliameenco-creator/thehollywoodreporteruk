<?php
/**
 * Audio Library: one admin screen listing every recording the plugin has made.
 *
 * Recordings are ordinary attachments (flagged _thr_audio_generated) stored in
 * uploads/thr-audio/YYYY/MM. They are hidden from the Media Library so editors only see
 * photos there, and listed here instead. No extra tables: each file carries its voice,
 * kind (full|preview) and size as attachment meta.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const THR_AUDIO_LIBRARY_PER_PAGE = 50;

function thr_audio_library_menu() {
	add_menu_page(
		__( 'Audio Library', 'thr-audio' ),
		__( 'Audio Library', 'thr-audio' ),
		'edit_posts',
		'thr-audio-library',
		'thr_audio_render_library',
		'dashicons-microphone',
		11
	);
}
add_action( 'admin_menu', 'thr_audio_library_menu' );

/* -------------------------------------------------------------------------
 * Keep recordings out of the Media Library (grid, list and the media picker).
 * ---------------------------------------------------------------------- */

/**
 * @param array $meta_query Existing meta query.
 * @return array
 */
function thr_audio_exclude_recordings( $meta_query ) {
	$meta_query   = is_array( $meta_query ) ? $meta_query : array();
	$meta_query[] = array(
		'key'     => '_thr_audio_generated',
		'compare' => 'NOT EXISTS',
	);
	return $meta_query;
}

/**
 * @param array $args Media grid / picker query.
 * @return array
 */
function thr_audio_hide_in_media_grid( $args ) {
	$args['meta_query'] = thr_audio_exclude_recordings( isset( $args['meta_query'] ) ? $args['meta_query'] : array() ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	return $args;
}
add_filter( 'ajax_query_attachments_args', 'thr_audio_hide_in_media_grid' );

/**
 * @param WP_Query $query Query.
 */
function thr_audio_hide_in_media_list( $query ) {
	global $pagenow;
	if ( is_admin() && 'upload.php' === $pagenow && $query->is_main_query() ) {
		$query->set( 'meta_query', thr_audio_exclude_recordings( $query->get( 'meta_query' ) ) );
	}
}
add_action( 'pre_get_posts', 'thr_audio_hide_in_media_list' );

/* -------------------------------------------------------------------------
 * Totals and upkeep.
 * ---------------------------------------------------------------------- */

/**
 * Number of recordings and bytes stored, cached for an hour.
 *
 * @return array count, bytes.
 */
function thr_audio_library_totals() {
	$totals = get_transient( 'thr_audio_library_totals' );
	if ( is_array( $totals ) ) {
		return $totals;
	}
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- cached in a transient.
	$row    = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS n, COALESCE(SUM(meta_value), 0) AS bytes FROM {$wpdb->postmeta} WHERE meta_key = %s", '_thr_audio_bytes' ) );
	$totals = array(
		'count' => $row ? (int) $row->n : 0,
		'bytes' => $row ? (int) $row->bytes : 0,
	);
	set_transient( 'thr_audio_library_totals', $totals, HOUR_IN_SECONDS );
	return $totals;
}

/**
 * @param int $post_id Deleted post.
 */
function thr_audio_library_deleted( $post_id ) {
	if ( get_post_meta( $post_id, '_thr_audio_generated', true ) ) {
		delete_transient( 'thr_audio_library_totals' );
	}
}
add_action( 'delete_attachment', 'thr_audio_library_deleted' );

/**
 * One-time upkeep after an update: label recordings made before 2.1 with their voice, kind
 * and size, and register the podcast feed address.
 */
function thr_audio_upgrade() {
	if ( version_compare( (string) get_option( 'thr_audio_db_version', '0' ), '2.1', '>=' ) ) {
		return;
	}

	$ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_thr_audio_generated', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'no_found_rows'  => true,
		)
	);
	foreach ( $ids as $id ) {
		if ( ! metadata_exists( 'post', $id, '_thr_audio_kind' ) ) {
			$found = thr_audio_library_find_use( $id );
			update_post_meta( $id, '_thr_audio_voice', $found ? $found['voice'] : '' );
			update_post_meta( $id, '_thr_audio_kind', $found ? $found['kind'] : 'full' );
		}
		if ( ! metadata_exists( 'post', $id, '_thr_audio_bytes' ) ) {
			$path = get_attached_file( $id );
			update_post_meta( $id, '_thr_audio_bytes', $path && file_exists( $path ) ? (int) filesize( $path ) : 0 );
		}
	}

	flush_rewrite_rules( false );
	delete_transient( 'thr_audio_library_totals' );
	update_option( 'thr_audio_db_version', '2.1' );
}
add_action( 'admin_init', 'thr_audio_upgrade' );

/**
 * Where an attachment is used on its article, or null if it no longer is.
 *
 * @param int $attachment_id Attachment ID.
 * @return array|null voice, kind.
 */
function thr_audio_library_find_use( $attachment_id ) {
	$parent = (int) wp_get_post_parent_id( $attachment_id );
	if ( ! $parent ) {
		return null;
	}
	foreach ( thr_audio_stored( $parent ) as $voice => $variant ) {
		if ( ! empty( $variant['attachment_id'] ) && (int) $variant['attachment_id'] === $attachment_id ) {
			return array(
				'voice' => $voice,
				'kind'  => 'full',
			);
		}
		if ( ! empty( $variant['preview_attachment_id'] ) && (int) $variant['preview_attachment_id'] === $attachment_id ) {
			return array(
				'voice' => $voice,
				'kind'  => 'preview',
			);
		}
	}
	return null;
}

/* -------------------------------------------------------------------------
 * Deleting a recording that is no longer used.
 * ---------------------------------------------------------------------- */

function thr_audio_library_delete() {
	$id = isset( $_GET['attachment'] ) ? absint( $_GET['attachment'] ) : 0;
	check_admin_referer( 'thr_audio_library_delete_' . $id );

	if ( ! $id || ! current_user_can( 'delete_post', $id ) || ! get_post_meta( $id, '_thr_audio_generated', true ) ) {
		wp_die( esc_html__( 'You are not allowed to delete this recording.', 'thr-audio' ), 403 );
	}
	// Only files no article is using, so a player can never lose its audio from here.
	if ( thr_audio_library_find_use( $id ) ) {
		wp_die( esc_html__( 'This recording is still used by its article. Remove it in the article\'s Audio box first.', 'thr-audio' ) );
	}

	wp_delete_attachment( $id, true );
	wp_safe_redirect( add_query_arg( 'deleted', 1, wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=thr-audio-library' ) ) );
	exit;
}
add_action( 'admin_post_thr_audio_library_delete', 'thr_audio_library_delete' );

/* -------------------------------------------------------------------------
 * The screen.
 * ---------------------------------------------------------------------- */

function thr_audio_render_library() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only filters.
	$voice  = isset( $_GET['voice'] ) ? sanitize_key( wp_unslash( $_GET['voice'] ) ) : '';
	$kind   = isset( $_GET['kind'] ) ? sanitize_key( wp_unslash( $_GET['kind'] ) ) : '';
	$month  = isset( $_GET['month'] ) ? preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_GET['month'] ) ) ) : '';
	$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	// phpcs:enable

	$meta_query = array(
		array(
			'key'     => '_thr_audio_generated',
			'compare' => 'EXISTS',
		),
	);
	if ( $voice ) {
		$meta_query[] = array(
			'key'   => '_thr_audio_voice',
			'value' => $voice,
		);
	}
	if ( in_array( $kind, array( 'full', 'preview' ), true ) ) {
		$meta_query[] = array(
			'key'   => '_thr_audio_kind',
			'value' => $kind,
		);
	}

	$args = array(
		'post_type'              => 'attachment',
		'post_status'            => 'inherit',
		'posts_per_page'         => THR_AUDIO_LIBRARY_PER_PAGE,
		'paged'                  => $paged,
		'orderby'                => 'date',
		'order'                  => 'DESC',
		'meta_query'             => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		'update_post_term_cache' => false,
	);
	if ( 6 === strlen( $month ) ) {
		$args['m'] = $month;
	}
	if ( '' !== $search ) {
		$args['s'] = $search;
	}
	$query = new WP_Query( $args );

	$parents = array_filter( array_unique( wp_list_pluck( $query->posts, 'post_parent' ) ) );
	if ( $parents ) {
		_prime_post_caches( $parents, false, true );
	}

	$totals  = thr_audio_library_totals();
	$voices  = thr_audio_settings()['voices'];
	$base    = admin_url( 'admin.php?page=thr-audio-library' );
	$podcast = thr_audio_settings()['podcast'];
	?>
	<div class="wrap thr-audio-library">
		<h1><?php esc_html_e( 'Audio Library', 'thr-audio' ); ?></h1>

		<?php if ( isset( $_GET['deleted'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Recording deleted.', 'thr-audio' ); ?></p></div>
		<?php endif; ?>

		<p>
			<?php
			printf(
				/* translators: 1: number of recordings, 2: storage used, 3: characters used. */
				esc_html__( '%1$s recordings · %2$s stored · %3$s characters made this month', 'thr-audio' ),
				'<strong>' . esc_html( number_format_i18n( $totals['count'] ) ) . '</strong>',
				'<strong>' . esc_html( size_format( $totals['bytes'], 1 ) ? size_format( $totals['bytes'], 1 ) : '0 B' ) . '</strong>',
				'<strong>' . esc_html( number_format_i18n( thr_audio_usage_this_month() ) ) . '</strong>'
			);
			?>
		</p>
		<?php thr_audio_render_storage_box(); ?>
		<?php if ( ! empty( $podcast['enabled'] ) ) : ?>
			<p>
				<?php esc_html_e( 'Podcast feed for Spotify and Apple Podcasts:', 'thr-audio' ); ?>
				<code><?php echo esc_html( thr_audio_podcast_url() ); ?></code>
				<button type="button" class="button button-small" data-thr-copy="<?php echo esc_attr( thr_audio_podcast_url() ); ?>"><?php esc_html_e( 'Copy', 'thr-audio' ); ?></button>
			</p>
		<?php endif; ?>

		<form method="get" class="thr-audio-library__filters">
			<input type="hidden" name="page" value="thr-audio-library" />
			<label class="screen-reader-text" for="thr-audio-lib-voice"><?php esc_html_e( 'Voice', 'thr-audio' ); ?></label>
			<select name="voice" id="thr-audio-lib-voice">
				<option value=""><?php esc_html_e( 'All voices', 'thr-audio' ); ?></option>
				<?php foreach ( $voices as $slot => $v ) : ?>
					<option value="<?php echo esc_attr( $slot ); ?>" <?php selected( $voice, $slot ); ?>><?php echo esc_html( $v['label'] ? trim( $v['label'] . ' ' . $v['region'] ) : $slot ); ?></option>
				<?php endforeach; ?>
			</select>
			<label class="screen-reader-text" for="thr-audio-lib-kind"><?php esc_html_e( 'Type', 'thr-audio' ); ?></label>
			<select name="kind" id="thr-audio-lib-kind">
				<option value=""><?php esc_html_e( 'Full recordings and clips', 'thr-audio' ); ?></option>
				<option value="full" <?php selected( $kind, 'full' ); ?>><?php esc_html_e( 'Full recordings', 'thr-audio' ); ?></option>
				<option value="preview" <?php selected( $kind, 'preview' ); ?>><?php esc_html_e( 'Opening clips', 'thr-audio' ); ?></option>
			</select>
			<label class="screen-reader-text" for="thr-audio-lib-month"><?php esc_html_e( 'Month', 'thr-audio' ); ?></label>
			<input type="month" id="thr-audio-lib-month" name="month" value="<?php echo esc_attr( 6 === strlen( $month ) ? substr( $month, 0, 4 ) . '-' . substr( $month, 4 ) : '' ); ?>" />
			<label class="screen-reader-text" for="thr-audio-lib-search"><?php esc_html_e( 'Search articles', 'thr-audio' ); ?></label>
			<input type="search" id="thr-audio-lib-search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search article titles', 'thr-audio' ); ?>" />
			<?php submit_button( __( 'Filter', 'thr-audio' ), 'secondary', '', false ); ?>
		</form>

		<table class="widefat striped">
			<thead>
				<tr>
					<th style="width:28%"><?php esc_html_e( 'Article', 'thr-audio' ); ?></th>
					<th><?php esc_html_e( 'Voice', 'thr-audio' ); ?></th>
					<th><?php esc_html_e( 'Type', 'thr-audio' ); ?></th>
					<th><?php esc_html_e( 'Length', 'thr-audio' ); ?></th>
					<th><?php esc_html_e( 'Size', 'thr-audio' ); ?></th>
					<th><?php esc_html_e( 'Made', 'thr-audio' ); ?></th>
					<th style="width:24%"><?php esc_html_e( 'Listen', 'thr-audio' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'thr-audio' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! $query->posts ) : ?>
					<tr><td colspan="8"><?php esc_html_e( 'No recordings found.', 'thr-audio' ); ?></td></tr>
				<?php endif; ?>
				<?php
				foreach ( $query->posts as $file ) :
					$url    = wp_get_attachment_url( $file->ID );
					$meta   = wp_get_attachment_metadata( $file->ID );
					$slot   = (string) get_post_meta( $file->ID, '_thr_audio_voice', true );
					$type   = (string) get_post_meta( $file->ID, '_thr_audio_kind', true );
					$bytes  = (int) get_post_meta( $file->ID, '_thr_audio_bytes', true );
					$parent = $file->post_parent ? get_post( $file->post_parent ) : null;
					$in_use = (bool) thr_audio_library_find_use( $file->ID );
					?>
					<tr>
						<td>
							<?php if ( $parent ) : ?>
								<strong><a href="<?php echo esc_url( get_edit_post_link( $parent ) ); ?>"><?php echo esc_html( get_the_title( $parent ) ); ?></a></strong>
								<div class="row-actions visible">
									<?php if ( 'publish' === $parent->post_status ) : ?>
										<a href="<?php echo esc_url( get_permalink( $parent ) ); ?>"><?php esc_html_e( 'View article', 'thr-audio' ); ?></a>
									<?php else : ?>
										<?php echo esc_html( get_post_status_object( $parent->post_status ) ? get_post_status_object( $parent->post_status )->label : $parent->post_status ); ?>
									<?php endif; ?>
								</div>
							<?php else : ?>
								<em><?php esc_html_e( 'Article deleted', 'thr-audio' ); ?></em>
							<?php endif; ?>
							<?php if ( ! $in_use ) : ?>
								<p class="description"><?php esc_html_e( 'Not used by the article any more.', 'thr-audio' ); ?></p>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( isset( $voices[ $slot ] ) && $voices[ $slot ]['label'] ? trim( $voices[ $slot ]['label'] . ' ' . $voices[ $slot ]['region'] ) : ( $slot ? $slot : '—' ) ); ?></td>
						<td><?php echo 'preview' === $type ? esc_html__( 'Opening clip', 'thr-audio' ) : esc_html__( 'Full', 'thr-audio' ); ?></td>
						<td><?php echo ! empty( $meta['length'] ) ? esc_html( thr_audio_format_time( $meta['length'] ) ) : '—'; ?></td>
						<td><?php echo $bytes ? esc_html( size_format( $bytes, 1 ) ) : '—'; ?></td>
						<td><?php echo esc_html( get_the_date( '', $file ) ); ?></td>
						<td><?php if ( $url ) : ?><audio controls preload="none" src="<?php echo esc_url( $url ); ?>" style="width:100%;height:32px"></audio><?php endif; ?></td>
						<td>
							<?php if ( $url ) : ?>
								<a class="button button-small" href="<?php echo esc_url( $url ); ?>" download><?php esc_html_e( 'Download', 'thr-audio' ); ?></a>
								<button type="button" class="button button-small" data-thr-copy="<?php echo esc_attr( $url ); ?>"><?php esc_html_e( 'Copy link', 'thr-audio' ); ?></button>
							<?php endif; ?>
							<?php if ( ! $in_use && current_user_can( 'delete_post', $file->ID ) ) : ?>
								<a class="button-link button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=thr_audio_library_delete&attachment=' . $file->ID ), 'thr_audio_library_delete_' . $file->ID ) ); ?>" onclick="return confirm(<?php echo esc_attr( wp_json_encode( __( 'Delete this recording for good?', 'thr-audio' ) ) ); ?>)"><?php esc_html_e( 'Delete', 'thr-audio' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php
		$links = paginate_links(
			array(
				'base'    => add_query_arg( 'paged', '%#%' ),
				'format'  => '',
				'current' => $paged,
				'total'   => (int) $query->max_num_pages,
			)
		);
		if ( $links ) {
			echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( $links ) . '</div></div>';
		}
		?>
	</div>

	<style>
		.thr-audio-library__filters{display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin:12px 0}
		.thr-audio-library td{vertical-align:middle}
	</style>
	<script>
	document.addEventListener( 'click', ( e ) => {
		const button = e.target.closest( '[data-thr-copy]' );
		if ( ! button || ! navigator.clipboard ) {
			return;
		}
		navigator.clipboard.writeText( button.dataset.thrCopy ).then( () => {
			const text = button.textContent;
			button.textContent = <?php echo wp_json_encode( __( 'Copied', 'thr-audio' ) ); ?>;
			setTimeout( () => { button.textContent = text; }, 1500 );
		} );
	} );
	</script>
	<?php
}
