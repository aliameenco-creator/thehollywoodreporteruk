<?php
/**
 * Delivery: send outbox events to the webhook (n8n), signed, and retry until accepted.
 *
 * Signature: X-THR-Signature: sha256=HMAC_SHA256( secret, X-THR-Timestamp . "." . raw body ).
 * A receiver should check it and reject timestamps older than 5 minutes.
 *
 * Retries back off from 1 minute to 6 hours; after 12 failed tries the event is marked failed
 * and can be resent from Settings → THR Distribute. Nothing is dropped while the webhook is off:
 * events wait in the outbox, and the API can replay them.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const THR_DISTRIBUTE_MAX_ATTEMPTS = 12;

/**
 * Ask WP-Cron to deliver soon (the next page load, or the server cron within 5 minutes).
 */
function thr_distribute_schedule_send() {
	if ( ! wp_next_scheduled( 'thr_distribute_send_now' ) ) {
		wp_schedule_single_event( time(), 'thr_distribute_send_now' );
	}
}

/**
 * Whether events can be sent at all.
 *
 * @return bool
 */
function thr_distribute_can_send() {
	$s = thr_distribute_settings();
	return $s['enabled'] && '' !== $s['webhook_url'];
}

/**
 * POST one payload, signed.
 *
 * @param string $type Event type.
 * @param string $id   Event ID.
 * @param string $body JSON.
 * @return true|WP_Error
 */
function thr_distribute_post( $type, $id, $body ) {
	$time     = (string) time();
	$response = wp_remote_post(
		thr_distribute_settings()['webhook_url'],
		array(
			'timeout' => 15,
			'headers' => array(
				'Content-Type'     => 'application/json',
				'User-Agent'       => 'THR-Distribute/' . THR_DISTRIBUTE_VERSION,
				'X-THR-Event'      => $type,
				'X-THR-Event-Id'   => $id,
				'X-THR-Timestamp'  => $time,
				'X-THR-Signature'  => 'sha256=' . hash_hmac( 'sha256', $time . '.' . $body, thr_distribute_secret() ),
				'X-THR-Site'       => thr_distribute_site_id(),
			),
			'body'    => $body,
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code > 299 ) {
		return new WP_Error( 'thr_distribute_http', sprintf( 'HTTP %d', $code ) );
	}
	return true;
}

/**
 * Send events that are due, oldest first. One sender at a time.
 *
 * @param int $limit Events per run.
 * @return array sent, failed.
 */
function thr_distribute_deliver( $limit = 25 ) {
	global $wpdb;
	$result = array(
		'sent'   => 0,
		'failed' => 0,
	);
	if ( ! thr_distribute_can_send() ) {
		return $result;
	}

	// add_option() only succeeds once, so it works as a lock; a stale lock expires after 2 minutes.
	$lock = (int) get_option( 'thr_distribute_lock', 0 );
	if ( $lock && $lock > time() - 120 ) {
		return $result;
	}
	if ( $lock ) {
		delete_option( 'thr_distribute_lock' );
	}
	if ( ! add_option( 'thr_distribute_lock', time(), '', false ) ) {
		return $result;
	}

	$t = thr_distribute_tables();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is ours.
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, event_id, type, payload, attempts FROM {$t['events']} WHERE status = 'pending' AND next_attempt <= %s ORDER BY id ASC LIMIT %d", current_time( 'mysql', true ), $limit ) );

	foreach ( (array) $rows as $row ) {
		$done = thr_distribute_post( $row->type, $row->event_id, $row->payload );
		if ( true === $done ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$t['events'],
				array(
					'status'     => 'sent',
					'attempts'   => (int) $row->attempts + 1,
					'sent_at'    => current_time( 'mysql', true ),
					'last_error' => '',
				),
				array( 'id' => $row->id )
			);
			++$result['sent'];
			continue;
		}

		$attempts = (int) $row->attempts + 1;
		$wait     = min( 6 * HOUR_IN_SECONDS, MINUTE_IN_SECONDS * ( 2 ** ( $attempts - 1 ) ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$t['events'],
			array(
				'status'       => $attempts >= THR_DISTRIBUTE_MAX_ATTEMPTS ? 'failed' : 'pending',
				'attempts'     => $attempts,
				'next_attempt' => gmdate( 'Y-m-d H:i:s', time() + $wait ),
				'last_error'   => substr( $done->get_error_message(), 0, 255 ),
			),
			array( 'id' => $row->id )
		);
		++$result['failed'];
	}

	delete_option( 'thr_distribute_lock' );
	return $result;
}
add_action( 'thr_distribute_send_now', 'thr_distribute_deliver' );

/**
 * Every 5 minutes: deliver what is due and tidy old delivered events (kept 30 days).
 */
function thr_distribute_cron() {
	global $wpdb;
	thr_distribute_deliver();
	$t = thr_distribute_tables();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is ours.
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$t['events']} WHERE status = 'sent' AND sent_at < %s", gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS ) ) );
}
add_action( 'thr_distribute_cron', 'thr_distribute_cron' );

/**
 * Make sure the 5-minute task exists (it is lost if the plugin was updated without reactivating).
 */
function thr_distribute_ensure_cron() {
	if ( ! wp_next_scheduled( 'thr_distribute_cron' ) ) {
		wp_schedule_event( time() + 60, 'thr_distribute_5min', 'thr_distribute_cron' );
	}
}
add_action( 'init', 'thr_distribute_ensure_cron' );

/**
 * Put failed events back in the queue.
 *
 * @return int Events requeued.
 */
function thr_distribute_retry_failed() {
	global $wpdb;
	$t = thr_distribute_tables();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is ours.
	$n = (int) $wpdb->query( $wpdb->prepare( "UPDATE {$t['events']} SET status = 'pending', attempts = 0, next_attempt = %s WHERE status = 'failed'", current_time( 'mysql', true ) ) );
	thr_distribute_schedule_send();
	return $n;
}

/**
 * Send a test event straight away and report the result.
 *
 * @return true|WP_Error
 */
function thr_distribute_send_test() {
	if ( ! thr_distribute_can_send() ) {
		return new WP_Error( 'thr_distribute_off', __( 'Turn sending on and enter a webhook address first.', 'thr-distribute' ) );
	}
	$id   = wp_generate_uuid4();
	$body = wp_json_encode(
		array(
			'schema'      => THR_DISTRIBUTE_EVENT_SCHEMA,
			'id'          => $id,
			'type'        => 'test.ping',
			'occurred_at' => gmdate( 'c' ),
			'site'        => array(
				'id'  => thr_distribute_site_id(),
				'url' => home_url( '/' ),
			),
			'package_id'  => '',
			'post_id'     => 0,
			'version'     => 0,
			'changes'     => array(),
			'package'     => null,
		),
		JSON_UNESCAPED_SLASHES
	);
	return thr_distribute_post( 'test.ping', $id, $body );
}

/**
 * Outbox counts by status.
 *
 * @return array pending, sent, failed.
 */
function thr_distribute_outbox_counts() {
	global $wpdb;
	$t   = thr_distribute_tables();
	$out = array(
		'pending' => 0,
		'sent'    => 0,
		'failed'  => 0,
	);
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is ours.
	foreach ( (array) $wpdb->get_results( "SELECT status, COUNT(*) AS n FROM {$t['events']} GROUP BY status" ) as $row ) {
		$out[ $row->status ] = (int) $row->n;
	}
	return $out;
}
