<?php
/**
 * Admin: Settings → THR Distribute, the Distribution box on articles, and the "allowed off-site"
 * tick box on images.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_distribute_menu() {
	add_options_page( __( 'THR Distribute', 'thr-distribute' ), __( 'THR Distribute', 'thr-distribute' ), 'manage_options', 'thr-distribute', 'thr_distribute_render_settings' );
}
add_action( 'admin_menu', 'thr_distribute_menu' );

/**
 * Buttons on the settings page: send now, retry failed, test, new secret.
 */
function thr_distribute_admin_action() {
	check_admin_referer( 'thr_distribute_action' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'thr-distribute' ), 403 );
	}
	$do     = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
	$notice = '';

	if ( 'send' === $do ) {
		$r      = thr_distribute_deliver( 100 );
		$notice = sprintf( /* translators: 1: sent, 2: failed. */ __( 'Sent %1$d, failed %2$d.', 'thr-distribute' ), $r['sent'], $r['failed'] );
	} elseif ( 'retry' === $do ) {
		$notice = sprintf( /* translators: %d: events. */ __( '%d failed events queued again.', 'thr-distribute' ), thr_distribute_retry_failed() );
	} elseif ( 'test' === $do ) {
		$r      = thr_distribute_send_test();
		$notice = is_wp_error( $r ) ? __( 'Test failed: ', 'thr-distribute' ) . $r->get_error_message() : __( 'Test event delivered.', 'thr-distribute' );
	} elseif ( 'secret' === $do && ! defined( 'THR_DISTRIBUTE_SECRET' ) ) {
		update_option( 'thr_distribute_secret', wp_generate_password( 48, false ), false );
		$notice = __( 'New secret made. Update it in n8n, or signatures will stop matching.', 'thr-distribute' );
	}

	set_transient( 'thr_distribute_notice_' . get_current_user_id(), $notice, MINUTE_IN_SECONDS );
	wp_safe_redirect( admin_url( 'options-general.php?page=thr-distribute' ) );
	exit;
}
add_action( 'admin_post_thr_distribute_action', 'thr_distribute_admin_action' );

/**
 * @param string $do    Action.
 * @param string $label Button text.
 * @param string $class Button class.
 */
function thr_distribute_action_button( $do, $label, $class = 'button' ) {
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin:0 6px 6px 0">
		<input type="hidden" name="action" value="thr_distribute_action" />
		<input type="hidden" name="do" value="<?php echo esc_attr( $do ); ?>" />
		<?php wp_nonce_field( 'thr_distribute_action' ); ?>
		<button type="submit" class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $label ); ?></button>
	</form>
	<?php
}

function thr_distribute_render_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	global $wpdb;
	$s      = thr_distribute_settings();
	$name   = 'thr_distribute_settings';
	$counts = thr_distribute_outbox_counts();
	$t      = thr_distribute_tables();
	$notice = get_transient( 'thr_distribute_notice_' . get_current_user_id() );
	delete_transient( 'thr_distribute_notice_' . get_current_user_id() );

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are ours.
	$events  = $wpdb->get_results( "SELECT type, package_id, post_id, status, attempts, last_error, created_at FROM {$t['events']} ORDER BY id DESC LIMIT 20" );
	$removed = $wpdb->get_results( "SELECT package_id, title, deleted_at FROM {$t['removed']} WHERE closed_at IS NULL ORDER BY deleted_at DESC LIMIT 50" );
	// phpcs:enable
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'THR Distribute', 'thr-distribute' ); ?></h1>
		<p><?php esc_html_e( 'Every article becomes a content package (links, text, transcript, images, audio). When one is published, updated, unpublished or deleted, an event is sent to your automations. They report back where they posted it, so a deletion can follow it everywhere.', 'thr-distribute' ); ?></p>

		<?php if ( $notice ) : ?>
			<div class="notice notice-info is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'thr_distribute_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Send events', 'thr-distribute' ); ?></th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $s['enabled'] ); ?> /> <?php esc_html_e( 'On. While off, events wait in the queue and nothing is lost.', 'thr-distribute' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="thr-dist-url"><?php esc_html_e( 'Webhook address', 'thr-distribute' ); ?></label></th>
					<td>
						<input type="url" id="thr-dist-url" class="large-text code" name="<?php echo esc_attr( $name ); ?>[webhook_url]" value="<?php echo esc_attr( $s['webhook_url'] ); ?>" placeholder="https://your-n8n/webhook/thr-distribute" />
						<p class="description"><?php esc_html_e( 'The n8n Webhook node (POST) that receives every event. One address; the workflow routes by event type.', 'thr-distribute' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="thr-dist-site"><?php esc_html_e( 'Site ID', 'thr-distribute' ); ?></label></th>
					<td>
						<input type="text" id="thr-dist-site" class="regular-text code" name="<?php echo esc_attr( $name ); ?>[site_id]" value="<?php echo esc_attr( $s['site_id'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Starts every package ID, so several sites can share the same automations. Set it once and don\'t change it: platforms store IDs made with it.', 'thr-distribute' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Tracked links', 'thr-distribute' ); ?></th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[utm]" value="1" <?php checked( $s['utm'] ); ?> /> <?php esc_html_e( 'Include a link template with utm_source={platform}, so analytics show which platform sent readers', 'thr-distribute' ); ?></label></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<h2><?php esc_html_e( 'Signing secret', 'thr-distribute' ); ?></h2>
		<p><?php esc_html_e( 'Every event is signed with this secret (header X-THR-Signature). Put it in n8n to check events really come from this site.', 'thr-distribute' ); ?></p>
		<?php if ( defined( 'THR_DISTRIBUTE_SECRET' ) ) : ?>
			<p><strong><?php esc_html_e( 'Set in wp-config.php.', 'thr-distribute' ); ?></strong></p>
		<?php else : ?>
			<p>
				<input type="password" readonly class="regular-text code" id="thr-dist-secret" value="<?php echo esc_attr( thr_distribute_secret() ); ?>" />
				<button type="button" class="button" onclick="const f=document.getElementById('thr-dist-secret');f.type=f.type==='password'?'text':'password';"><?php esc_html_e( 'Show', 'thr-distribute' ); ?></button>
			</p>
			<?php thr_distribute_action_button( 'secret', __( 'Make a new secret', 'thr-distribute' ) ); ?>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Queue', 'thr-distribute' ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: 1: waiting, 2: delivered, 3: failed. */
				esc_html__( 'Waiting: %1$s · Delivered (last 30 days): %2$s · Failed: %3$s', 'thr-distribute' ),
				'<strong>' . esc_html( number_format_i18n( $counts['pending'] ) ) . '</strong>',
				'<strong>' . esc_html( number_format_i18n( $counts['sent'] ) ) . '</strong>',
				'<strong>' . esc_html( number_format_i18n( $counts['failed'] ) ) . '</strong>'
			);
			?>
		</p>
		<p>
			<?php
			thr_distribute_action_button( 'test', __( 'Send test event', 'thr-distribute' ) );
			thr_distribute_action_button( 'send', __( 'Send waiting events now', 'thr-distribute' ) );
			if ( $counts['failed'] ) {
				thr_distribute_action_button( 'retry', __( 'Retry failed', 'thr-distribute' ) );
			}
			?>
		</p>

		<?php if ( $events ) : ?>
			<table class="widefat striped" style="max-width:1100px">
				<thead><tr><th><?php esc_html_e( 'When (UTC)', 'thr-distribute' ); ?></th><th><?php esc_html_e( 'Event', 'thr-distribute' ); ?></th><th><?php esc_html_e( 'Article', 'thr-distribute' ); ?></th><th><?php esc_html_e( 'Delivery', 'thr-distribute' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $events as $e ) : ?>
					<tr>
						<td><?php echo esc_html( $e->created_at ); ?></td>
						<td><code><?php echo esc_html( $e->type ); ?></code></td>
						<td>
							<?php
							$title = $e->post_id ? get_the_title( (int) $e->post_id ) : '';
							echo esc_html( $title ? $title : $e->package_id );
							?>
						</td>
						<td>
							<?php
							echo esc_html( $e->status );
							if ( $e->attempts > 1 || 'sent' !== $e->status ) {
								echo ' · ' . esc_html( sprintf( /* translators: %d: tries. */ _n( '%d try', '%d tries', (int) $e->attempts, 'thr-distribute' ), (int) $e->attempts ) );
							}
							if ( $e->last_error ) {
								echo ' · ' . esc_html( $e->last_error );
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Deleted articles still on other platforms', 'thr-distribute' ); ?></h2>
		<?php if ( ! $removed ) : ?>
			<p><?php esc_html_e( 'None. Every deleted article has been confirmed removed everywhere it was posted.', 'thr-distribute' ); ?></p>
		<?php else : ?>
			<table class="widefat striped" style="max-width:1100px">
				<thead><tr><th><?php esc_html_e( 'Article', 'thr-distribute' ); ?></th><th><?php esc_html_e( 'Deleted (UTC)', 'thr-distribute' ); ?></th><th><?php esc_html_e( 'Still live on', 'thr-distribute' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $removed as $r ) : ?>
					<tr>
						<td><?php echo esc_html( $r->title ); ?><br /><code><?php echo esc_html( $r->package_id ); ?></code></td>
						<td><?php echo esc_html( $r->deleted_at ); ?></td>
						<td>
							<?php
							$live = array();
							foreach ( thr_distribute_receipts_for( $r->package_id ) as $receipt ) {
								if ( in_array( $receipt['status'], array( 'live', 'pending' ), true ) ) {
									$live[] = $receipt['platform'] . ( 'article' !== $receipt['item'] ? ' (' . $receipt['item'] . ')' : '' );
								}
							}
							echo esc_html( $live ? implode( ', ', $live ) : '—' );
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h2><?php esc_html_e( 'API for automations', 'thr-distribute' ); ?></h2>
		<ol>
			<li><?php esc_html_e( 'Create a user with the role "Distribution automation". It can read packages and report receipts, and cannot edit articles.', 'thr-distribute' ); ?></li>
			<li><?php esc_html_e( 'In that user\'s profile, add an Application Password and use it in n8n (Basic Auth).', 'thr-distribute' ); ?></li>
		</ol>
		<p><code><?php echo esc_html( rest_url( 'thr-distribute/v1/packages?since=2026-11-01T00:00:00Z' ) ); ?></code></p>
		<p><code>POST <?php echo esc_html( rest_url( 'thr-distribute/v1/packages/{id}/receipts' ) ); ?></code> &nbsp; <code>{"platform":"youtube","status":"live","external_id":"…","url":"…"}</code></p>
		<p><code><?php echo esc_html( rest_url( 'thr-distribute/v1/events?after=0' ) ); ?></code></p>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Distribution box on articles.
 * ---------------------------------------------------------------------- */

function thr_distribute_add_meta_box() {
	foreach ( thr_distribute_post_types() as $type ) {
		add_meta_box( 'thr-distribute', __( 'Distribution', 'thr-distribute' ), 'thr_distribute_render_meta_box', $type, 'side', 'low' );
	}
}
add_action( 'add_meta_boxes', 'thr_distribute_add_meta_box' );

/**
 * @param WP_Post $post Article.
 */
function thr_distribute_render_meta_box( $post ) {
	$version  = (int) get_post_meta( $post->ID, '_thr_dist_version', true );
	$receipts = thr_distribute_receipts_for( thr_distribute_package_id( $post->ID ) );
	$labels   = array(
		'live'    => __( 'Live', 'thr-distribute' ),
		'pending' => __( 'Posting…', 'thr-distribute' ),
		'failed'  => __( 'Failed', 'thr-distribute' ),
		'removed' => __( 'Removed', 'thr-distribute' ),
	);
	if ( ! $version ) {
		echo '<p>' . esc_html__( 'Sent to your automations when published.', 'thr-distribute' ) . '</p>';
		return;
	}
	echo '<p>' . esc_html( sprintf( /* translators: 1: package ID, 2: version. */ __( 'Package %1$s · version %2$d', 'thr-distribute' ), thr_distribute_package_id( $post->ID ), $version ) ) . '</p>';
	if ( ! $receipts ) {
		echo '<p>' . esc_html__( 'Not reported on any platform yet.', 'thr-distribute' ) . '</p>';
		return;
	}
	echo '<ul style="margin:0">';
	foreach ( $receipts as $r ) {
		$label = $r['platform'] . ( 'article' !== $r['item'] ? ' (' . $r['item'] . ')' : '' );
		echo '<li>';
		if ( $r['url'] && 'removed' !== $r['status'] ) {
			echo '<a href="' . esc_url( $r['url'] ) . '" target="_blank" rel="noopener">' . esc_html( $label ) . '</a>';
		} else {
			echo esc_html( $label );
		}
		echo ': <strong>' . esc_html( isset( $labels[ $r['status'] ] ) ? $labels[ $r['status'] ] : $r['status'] ) . '</strong>';
		if ( $r['message'] ) {
			echo '<br /><span class="description">' . esc_html( $r['message'] ) . '</span>';
		}
		echo '</li>';
	}
	echo '</ul>';
}

/* -------------------------------------------------------------------------
 * "Allowed off-site" on images.
 * ---------------------------------------------------------------------- */

/**
 * @param array   $fields Attachment fields.
 * @param WP_Post $post   Attachment.
 * @return array
 */
function thr_distribute_image_field( $fields, $post ) {
	if ( ! wp_attachment_is_image( $post ) ) {
		return $fields;
	}
	$fields['thr_offsite_ok'] = array(
		'label' => __( 'Allowed off-site', 'thr-distribute' ),
		'input' => 'html',
		// The hidden field marks that the box was on the submitted form, so saving other fields elsewhere never unticks it.
		'html'  => '<input type="hidden" name="attachments[' . (int) $post->ID . '][thr_offsite_shown]" value="1" /><label><input type="checkbox" name="attachments[' . (int) $post->ID . '][thr_offsite_ok]" value="1" ' . checked( (bool) get_post_meta( $post->ID, 'thr_offsite_ok', true ), true, false ) . ' /> ' . esc_html__( 'We own this image or its licence covers YouTube, podcasts and social.', 'thr-distribute' ) . '</label>',
		'helps' => __( 'Agency photos are usually licensed for the website only. Unticked images are not used by automations.', 'thr-distribute' ),
	);
	return $fields;
}
add_filter( 'attachment_fields_to_edit', 'thr_distribute_image_field', 10, 2 );

/**
 * @param array $post       Attachment data.
 * @param array $attachment Submitted fields.
 * @return array
 */
function thr_distribute_image_field_save( $post, $attachment ) {
	if ( ! empty( $attachment['thr_offsite_shown'] ) && current_user_can( 'edit_post', $post['ID'] ) ) {
		if ( ! empty( $attachment['thr_offsite_ok'] ) ) {
			update_post_meta( $post['ID'], 'thr_offsite_ok', 1 );
		} else {
			delete_post_meta( $post['ID'], 'thr_offsite_ok' );
		}
	}
	return $post;
}
add_filter( 'attachment_fields_to_save', 'thr_distribute_image_field_save', 10, 2 );
