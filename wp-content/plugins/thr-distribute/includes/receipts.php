<?php
/**
 * Receipts: where each article is posted, reported back by the automations.
 *
 * One row per package + platform + item (item is "article", or e.g. "audio:v1" when a platform
 * gets one voice). Rows live in their own table, so they survive the article being deleted:
 * that is how a deletion knows which platforms still have to remove their copy.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Statuses a receipt can have.
 *
 * @return string[]
 */
function thr_distribute_receipt_statuses() {
	return array( 'live', 'pending', 'failed', 'removed' );
}

/**
 * @param string $package_id Package ID.
 * @return array
 */
function thr_distribute_receipts_for( $package_id ) {
	global $wpdb;
	$t = thr_distribute_tables();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is ours.
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT platform, item, external_id, url, status, message, created_at, updated_at FROM {$t['receipts']} WHERE package_id = %s ORDER BY platform, item", $package_id ), ARRAY_A );
	$out  = array();
	foreach ( (array) $rows as $row ) {
		$out[] = array(
			'platform'    => $row['platform'],
			'item'        => $row['item'],
			'external_id' => $row['external_id'],
			'url'         => $row['url'],
			'status'      => $row['status'],
			'message'     => $row['message'],
			'created_at'  => mysql_to_rfc3339( $row['created_at'] ),
			'updated_at'  => mysql_to_rfc3339( $row['updated_at'] ),
		);
	}
	return $out;
}

/**
 * Record or update where an article is posted.
 *
 * @param string $package_id Package ID.
 * @param array  $data       platform, item, external_id, url, status, message.
 * @return true|WP_Error
 */
function thr_distribute_save_receipt( $package_id, $data ) {
	global $wpdb;
	$t        = thr_distribute_tables();
	$platform = sanitize_key( isset( $data['platform'] ) ? $data['platform'] : '' );
	$item     = isset( $data['item'] ) && '' !== $data['item'] ? substr( preg_replace( '/[^a-z0-9:_-]/', '', strtolower( (string) $data['item'] ) ), 0, 60 ) : 'article';
	$status   = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : '';

	if ( '' === $platform || strlen( $platform ) > 40 ) {
		return new WP_Error( 'thr_distribute_platform', __( 'A platform name is required (letters, numbers, - and _).', 'thr-distribute' ), array( 'status' => 400 ) );
	}
	if ( ! in_array( $status, thr_distribute_receipt_statuses(), true ) ) {
		return new WP_Error( 'thr_distribute_status', __( 'Status must be live, pending, failed or removed.', 'thr-distribute' ), array( 'status' => 400 ) );
	}

	$now         = current_time( 'mysql', true );
	$external_id = isset( $data['external_id'] ) ? substr( sanitize_text_field( $data['external_id'] ), 0, 191 ) : '';
	$url         = isset( $data['url'] ) ? esc_url_raw( $data['url'] ) : '';
	$fields      = array(
		'status'     => $status,
		'message'    => isset( $data['message'] ) ? substr( sanitize_text_field( $data['message'] ), 0, 255 ) : '',
		'updated_at' => $now,
	);
	// An empty ID or link keeps the stored one, so "removed" can be reported without repeating them.
	if ( '' !== $external_id ) {
		$fields['external_id'] = $external_id;
	}
	if ( '' !== $url ) {
		$fields['url'] = $url;
	}

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is ours.
	$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['receipts']} WHERE package_id = %s AND platform = %s AND item = %s", $package_id, $platform, $item ) );
	if ( $id ) {
		$wpdb->update( $t['receipts'], $fields, array( 'id' => $id ) );
	} else {
		$wpdb->insert(
			$t['receipts'],
			array_merge(
				array(
					'package_id'  => $package_id,
					'post_id'     => thr_distribute_post_from_package( $package_id ),
					'platform'    => $platform,
					'item'        => $item,
					'external_id' => $external_id,
					'url'         => $url,
					'created_at'  => $now,
				),
				$fields
			)
		);
	}
	// phpcs:enable

	thr_distribute_maybe_close_removed( $package_id );
	return true;
}

/**
 * A deleted article's record is closed once no platform still has it live or pending.
 *
 * @param string $package_id Package ID.
 */
function thr_distribute_maybe_close_removed( $package_id ) {
	global $wpdb;
	$t = thr_distribute_tables();
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are ours.
	$open = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['removed']} WHERE package_id = %s AND closed_at IS NULL", $package_id ) );
	if ( ! $open ) {
		return;
	}
	$still = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['receipts']} WHERE package_id = %s AND status IN ('live','pending')", $package_id ) );
	if ( 0 === $still ) {
		$wpdb->update( $t['removed'], array( 'closed_at' => current_time( 'mysql', true ) ), array( 'package_id' => $package_id ) );
	}
	// phpcs:enable
}
