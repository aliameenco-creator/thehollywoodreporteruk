<?php
/**
 * Events: WordPress announces every change; automations react.
 *
 *   article.published    first published (or published again after being unpublished)
 *   article.updated      a published article's text, headline, images, links or terms changed
 *   article.unpublished  moved to draft, private, pending or trash: take it down, keep the ID
 *   article.deleted      deleted for good: remove it everywhere (the event lists where it is)
 *   audio.updated        a published article's recordings changed (made, remade or removed)
 *   test.ping            sent from Settings → THR Distribute to check the connection
 *
 * Every event carries the full package, so a workflow never has to call back for details.
 * Events are written to an outbox table and delivered (and retried) by delivery.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Event types other plugins may raise with do_action( 'thr_distribute_signal', $post_id, $type ).
 *
 * @return string[]
 */
function thr_distribute_signal_types() {
	return apply_filters( 'thr_distribute_signal_types', array( 'article.updated', 'audio.updated' ) );
}

/**
 * Posts being deleted in this request: their own meta clean-up must not raise more events.
 *
 * @param int|null $add Post to add.
 * @return int[]
 */
function thr_distribute_deleting( $add = null ) {
	static $ids = array();
	if ( null !== $add ) {
		$ids[] = (int) $add;
	}
	return $ids;
}

/**
 * Write an event to the outbox.
 *
 * A pending event of the same type for the same article is replaced rather than repeated, so
 * five quick edits before delivery become one article.updated with the latest package.
 *
 * @param string     $type     Event type.
 * @param int        $post_id  Post ID (0 for test.ping).
 * @param array|null $package  Package (null for test.ping).
 * @param string[]   $changes  Sections that changed.
 * @return int|false Outbox row ID.
 */
function thr_distribute_enqueue( $type, $post_id, $package, $changes = array() ) {
	global $wpdb;
	$t          = thr_distribute_tables();
	$package_id = $package ? $package['id'] : '';
	$now        = current_time( 'mysql', true );

	// Merge only into the article's newest event: merging past a different event would reorder them
	// (unpublish, publish, unpublish must not arrive as unpublish, publish).
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is ours.
	$existing = $package_id ? $wpdb->get_row( $wpdb->prepare( "SELECT id, event_id, type, status, attempts, payload FROM {$t['events']} WHERE package_id = %s ORDER BY id DESC LIMIT 1", $package_id ) ) : null;
	if ( $existing && ( $existing->type !== $type || 'pending' !== $existing->status || (int) $existing->attempts > 0 ) ) {
		$existing = null;
	}
	$event_id = $existing ? $existing->event_id : wp_generate_uuid4();

	if ( $existing ) {
		$old     = json_decode( $existing->payload, true );
		$changes = array_values( array_unique( array_merge( isset( $old['changes'] ) ? (array) $old['changes'] : array(), $changes ) ) );
	}

	$payload = array(
		'schema'      => THR_DISTRIBUTE_EVENT_SCHEMA,
		'id'          => $event_id,
		'type'        => $type,
		'occurred_at' => gmdate( 'c' ),
		'site'        => array(
			'id'  => thr_distribute_site_id(),
			'url' => home_url( '/' ),
		),
		'package_id'  => $package_id,
		'post_id'     => (int) $post_id,
		'version'     => $package ? (int) $package['version'] : 0,
		'changes'     => array_values( $changes ),
		'package'     => $package,
	);
	$json = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

	if ( $existing ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( $t['events'], array( 'payload' => $json ), array( 'id' => $existing->id ) );
		$row_id = (int) $existing->id;
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$t['events'],
			array(
				'event_id'     => $event_id,
				'type'         => $type,
				'package_id'   => $package_id,
				'post_id'      => (int) $post_id,
				'payload'      => $json,
				'status'       => 'pending',
				'attempts'     => 0,
				'next_attempt' => $now,
				'created_at'   => $now,
			)
		);
		$row_id = (int) $wpdb->insert_id;
	}

	thr_distribute_schedule_send();
	do_action( 'thr_distribute_enqueued', $type, $post_id, $payload );
	return $row_id;
}

/**
 * Build the package, note what changed and queue the event.
 *
 * @param string $type    Event type.
 * @param int    $post_id Post ID.
 * @param bool   $only_if_changed Skip when nothing in the package changed.
 * @return int|false
 */
function thr_distribute_raise( $type, $post_id, $only_if_changed = false ) {
	if ( in_array( (int) $post_id, thr_distribute_deleting(), true ) ) {
		return false;
	}
	$package = thr_distribute_package( $post_id );
	if ( ! $package ) {
		return false;
	}
	$changes = thr_distribute_track_changes( $post_id, $package );
	if ( $only_if_changed && ! $changes ) {
		return false;
	}
	if ( 'publish' !== $package['status'] ) {
		$package = thr_distribute_public_view( $package );
	}
	return thr_distribute_enqueue( $type, $post_id, $package, $changes );
}

/**
 * What leaves the site about an article that is not public: identity, status and receipts only.
 *
 * @param array $package Package.
 * @return array
 */
function thr_distribute_public_view( $package ) {
	foreach ( array( 'text', 'audio' ) as $key ) {
		$package[ $key ] = null;
	}
	$package['images'] = array( 'featured' => null );
	return $package;
}

/**
 * Publish, update and unpublish. Runs after the post, its meta and its terms are all saved,
 * including posts created through the REST API (n8n) and scheduled posts going live.
 *
 * @param int          $post_id     Post ID.
 * @param WP_Post      $post        Post after saving.
 * @param bool         $update      Whether this was an update.
 * @param WP_Post|null $post_before Post before saving.
 */
function thr_distribute_after_save( $post_id, $post, $update, $post_before ) {
	if ( ! thr_distribute_handles( $post ) ) {
		return;
	}
	$before = $post_before instanceof WP_Post ? $post_before->post_status : 'new';
	$after  = $post->post_status;

	if ( 'publish' === $after && 'publish' !== $before ) {
		thr_distribute_raise( 'article.published', $post_id );
	} elseif ( 'publish' === $after ) {
		thr_distribute_raise( 'article.updated', $post_id, true );
	} elseif ( 'publish' === $before ) {
		thr_distribute_raise( 'article.unpublished', $post_id );
	}
}
add_action( 'wp_after_insert_post', 'thr_distribute_after_save', 20, 4 );

/**
 * Trashing a published article. wp_trash_post() changes the status without wp_after_insert_post,
 * so it is caught here. If both fire, the pending event is reused, not repeated.
 *
 * @param string  $new_status New status.
 * @param string  $old_status Old status.
 * @param WP_Post $post       Post.
 */
function thr_distribute_on_trash( $new_status, $old_status, $post ) {
	if ( 'trash' === $new_status && 'publish' === $old_status && thr_distribute_handles( $post ) ) {
		thr_distribute_raise( 'article.unpublished', $post->ID );
	}
}
add_action( 'transition_post_status', 'thr_distribute_on_trash', 20, 3 );

/**
 * Changes made outside the editor (audio made in the background, meta written by a tool).
 *
 * @param int    $post_id Post ID.
 * @param string $type    One of thr_distribute_signal_types().
 */
function thr_distribute_on_signal( $post_id, $type = 'article.updated' ) {
	if ( ! in_array( $type, thr_distribute_signal_types(), true ) || 'publish' !== get_post_status( $post_id ) ) {
		return;
	}
	thr_distribute_raise( $type, (int) $post_id, true );
}
add_action( 'thr_distribute_signal', 'thr_distribute_on_signal', 10, 2 );

/**
 * Deleted for good: keep a record of the article and where it was posted until every platform
 * reports it removed, and tell the automations. Runs before other plugins clean up after it.
 *
 * @param int $post_id Post ID.
 */
function thr_distribute_before_delete( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || ! thr_distribute_handles( $post ) || ! get_post_meta( $post_id, '_thr_dist_version', true ) ) {
		thr_distribute_deleting( $post_id );
		return;
	}

	global $wpdb;
	$t       = thr_distribute_tables();
	$package = thr_distribute_package( $post_id );
	$package = thr_distribute_public_view( $package );

	$package['status']  = 'deleted';
	$package['version'] = (int) $package['version'] + 1;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$wpdb->replace(
		$t['removed'],
		array(
			'package_id' => $package['id'],
			'post_id'    => (int) $post_id,
			'title'      => $package['article']['title'],
			'snapshot'   => wp_json_encode( $package, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
			'deleted_at' => current_time( 'mysql', true ),
			'closed_at'  => null,
		)
	);
	thr_distribute_maybe_close_removed( $package['id'] );

	thr_distribute_enqueue( 'article.deleted', $post_id, $package, array( 'status' ) );
	thr_distribute_deleting( $post_id );
}
add_action( 'before_delete_post', 'thr_distribute_before_delete', 5 );
