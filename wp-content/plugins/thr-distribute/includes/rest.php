<?php
/**
 * REST API for automations: /wp-json/thr-distribute/v1/…
 *
 *   GET  /packages?since=…&page=…   what changed since a time (catch-up and recovery)
 *   GET  /packages/{id}             one package, or the record of a deleted one
 *   POST /packages/{id}/receipts    report where it was posted, or that it was removed
 *   GET  /events?after=…            the outbox, for polling or replaying events
 *
 * Access: a user with the thr_distribute capability (the "Distribution automation" role,
 * or an administrator), signing in with an application password.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_distribute_register_routes() {
	$ns   = 'thr-distribute/v1';
	$auth = static function () {
		return current_user_can( 'thr_distribute' ) || current_user_can( 'manage_options' );
	};

	register_rest_route(
		$ns,
		'/packages',
		array(
			'methods'             => 'GET',
			'callback'            => 'thr_distribute_rest_list',
			'permission_callback' => $auth,
			'args'                => array(
				'since'    => array(
					'type'        => 'string',
					'description' => 'ISO 8601 time or Unix timestamp. Only packages changed at or after it.',
				),
				'page'     => array(
					'type'    => 'integer',
					'default' => 1,
					'minimum' => 1,
				),
				'per_page' => array(
					'type'    => 'integer',
					'default' => 20,
					'minimum' => 1,
					'maximum' => 50,
				),
			),
		)
	);
	register_rest_route(
		$ns,
		'/packages/(?P<id>[a-z0-9_-]+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'thr_distribute_rest_get',
			'permission_callback' => $auth,
		)
	);
	register_rest_route(
		$ns,
		'/packages/(?P<id>[a-z0-9_-]+)/receipts',
		array(
			'methods'             => 'POST',
			'callback'            => 'thr_distribute_rest_receipt',
			'permission_callback' => $auth,
			'args'                => array(
				'platform'    => array(
					'type'     => 'string',
					'required' => true,
				),
				'status'      => array(
					'type'     => 'string',
					'required' => true,
					'enum'     => thr_distribute_receipt_statuses(),
				),
				'item'        => array( 'type' => 'string' ),
				'external_id' => array( 'type' => 'string' ),
				'url'         => array( 'type' => 'string' ),
				'message'     => array( 'type' => 'string' ),
			),
		)
	);
	register_rest_route(
		$ns,
		'/events',
		array(
			'methods'             => 'GET',
			'callback'            => 'thr_distribute_rest_events',
			'permission_callback' => $auth,
			'args'                => array(
				'after' => array(
					'type'    => 'integer',
					'default' => 0,
					'minimum' => 0,
				),
				'limit' => array(
					'type'    => 'integer',
					'default' => 50,
					'minimum' => 1,
					'maximum' => 200,
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'thr_distribute_register_routes' );

/**
 * A package as the API shows it: full when published, identity and receipts otherwise.
 *
 * @param int $post_id Post ID.
 * @return array|null
 */
function thr_distribute_rest_package( $post_id ) {
	$package = thr_distribute_package( $post_id );
	if ( ! $package ) {
		return null;
	}
	return 'publish' === $package['status'] ? $package : thr_distribute_public_view( $package );
}

/**
 * The stored record of a deleted article, with current receipts.
 *
 * @param string $package_id Package ID.
 * @return array|null
 */
function thr_distribute_removed_package( $package_id ) {
	global $wpdb;
	$t = thr_distribute_tables();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is ours.
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT snapshot, deleted_at, closed_at FROM {$t['removed']} WHERE package_id = %s", $package_id ) );
	if ( ! $row ) {
		return null;
	}
	$package                  = json_decode( $row->snapshot, true );
	$package['deleted_at']    = mysql_to_rfc3339( $row->deleted_at );
	$package['removal_done']  = null !== $row->closed_at;
	$package['distributions'] = thr_distribute_receipts_for( $package_id );
	return $package;
}

/**
 * @param string $since ISO 8601 or Unix time.
 * @return int|null
 */
function thr_distribute_parse_since( $since ) {
	if ( null === $since || '' === $since ) {
		return null;
	}
	if ( ctype_digit( (string) $since ) ) {
		return (int) $since;
	}
	$time = strtotime( $since );
	return false === $time ? null : $time;
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function thr_distribute_rest_list( $request ) {
	global $wpdb;
	$since = thr_distribute_parse_since( $request['since'] );
	$page  = (int) $request['page'];
	$per   = (int) $request['per_page'];

	$meta = array(
		'key'     => '_thr_dist_changed',
		'compare' => 'EXISTS',
	);
	if ( null !== $since ) {
		$meta = array(
			'key'     => '_thr_dist_changed',
			'value'   => $since,
			'compare' => '>=',
			'type'    => 'NUMERIC',
		);
	}
	$query = new WP_Query(
		array(
			'post_type'              => thr_distribute_post_types(),
			'post_status'            => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ),
			'posts_per_page'         => $per,
			'paged'                  => $page,
			'meta_query'             => array( $meta ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_key'               => '_thr_dist_changed', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'                => 'meta_value_num',
			'order'                  => 'ASC',
			'fields'                 => 'ids',
			'update_post_term_cache' => true,
		)
	);

	$items = array();
	foreach ( $query->posts as $id ) {
		$package = thr_distribute_rest_package( $id );
		if ( $package ) {
			$package['changed_at'] = gmdate( 'c', (int) get_post_meta( $id, '_thr_dist_changed', true ) );
			$items[]               = $package;
		}
	}

	// Deleted articles are listed on the first page, so a catch-up never misses a removal.
	$deleted = array();
	if ( 1 === $page ) {
		$t = thr_distribute_tables();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is ours.
		$ids = null === $since
			? $wpdb->get_col( "SELECT package_id FROM {$t['removed']} WHERE closed_at IS NULL ORDER BY deleted_at ASC LIMIT 500" )
			: $wpdb->get_col( $wpdb->prepare( "SELECT package_id FROM {$t['removed']} WHERE deleted_at >= %s OR closed_at IS NULL ORDER BY deleted_at ASC LIMIT 500", gmdate( 'Y-m-d H:i:s', $since ) ) );
		// phpcs:enable
		foreach ( (array) $ids as $package_id ) {
			$deleted[] = thr_distribute_removed_package( $package_id );
		}
	}

	return rest_ensure_response(
		array(
			'schema'      => THR_DISTRIBUTE_PACKAGE_SCHEMA,
			'generated'   => gmdate( 'c' ),
			'page'        => $page,
			'total_pages' => (int) $query->max_num_pages,
			'total'       => (int) $query->found_posts,
			'items'       => $items,
			'deleted'     => array_values( array_filter( $deleted ) ),
		)
	);
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function thr_distribute_rest_get( $request ) {
	$id      = (string) $request['id'];
	$post_id = thr_distribute_post_from_package( $id );
	$package = $post_id && get_post( $post_id ) ? thr_distribute_rest_package( $post_id ) : null;
	if ( ! $package ) {
		$package = thr_distribute_removed_package( $id );
	}
	if ( ! $package ) {
		return new WP_Error( 'thr_distribute_not_found', __( 'No such package.', 'thr-distribute' ), array( 'status' => 404 ) );
	}
	return rest_ensure_response( $package );
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function thr_distribute_rest_receipt( $request ) {
	$id      = (string) $request['id'];
	$post_id = thr_distribute_post_from_package( $id );
	$exists  = ( $post_id && thr_distribute_handles( $post_id ) ) || thr_distribute_removed_package( $id );
	if ( ! $exists ) {
		return new WP_Error( 'thr_distribute_not_found', __( 'No such package.', 'thr-distribute' ), array( 'status' => 404 ) );
	}

	$saved = thr_distribute_save_receipt( $id, $request->get_params() );
	if ( is_wp_error( $saved ) ) {
		return $saved;
	}
	if ( $post_id && get_post( $post_id ) ) {
		update_post_meta( $post_id, '_thr_dist_changed', time() );
	}
	return rest_ensure_response(
		array(
			'package_id'    => $id,
			'distributions' => thr_distribute_receipts_for( $id ),
		)
	);
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function thr_distribute_rest_events( $request ) {
	global $wpdb;
	$t = thr_distribute_tables();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is ours.
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, status, payload FROM {$t['events']} WHERE id > %d ORDER BY id ASC LIMIT %d", (int) $request['after'], (int) $request['limit'] ) );

	$events = array();
	foreach ( (array) $rows as $row ) {
		$event             = json_decode( $row->payload, true );
		$event['sequence'] = (int) $row->id;
		$event['delivery'] = $row->status;
		$events[]          = $event;
	}
	return rest_ensure_response(
		array(
			'events' => $events,
			'last'   => $events ? end( $events )['sequence'] : (int) $request['after'],
		)
	);
}
