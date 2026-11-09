<?php
/**
 * Tables, role and scheduled tasks.
 *
 * Three small tables, because this data must outlive the article it describes:
 *   thr_dist_events   the outbox: every event, kept until delivered (and 30 days after)
 *   thr_dist_receipts where each article is posted, per platform
 *   thr_dist_removed  a record of each deleted article, kept until every platform confirms removal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array Table names.
 */
function thr_distribute_tables() {
	global $wpdb;
	return array(
		'events'   => $wpdb->prefix . 'thr_dist_events',
		'receipts' => $wpdb->prefix . 'thr_dist_receipts',
		'removed'  => $wpdb->prefix . 'thr_dist_removed',
	);
}

function thr_distribute_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$t       = thr_distribute_tables();
	$charset = $wpdb->get_charset_collate();

	dbDelta(
		"CREATE TABLE {$t['events']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id char(36) NOT NULL,
			type varchar(40) NOT NULL,
			package_id varchar(100) NOT NULL,
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			payload longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			attempts smallint(5) unsigned NOT NULL DEFAULT 0,
			next_attempt datetime NOT NULL,
			last_error varchar(255) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			sent_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY event_id (event_id),
			KEY due (status,next_attempt),
			KEY post_id (post_id)
		) $charset;"
	);
	dbDelta(
		"CREATE TABLE {$t['receipts']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			package_id varchar(100) NOT NULL,
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			platform varchar(40) NOT NULL,
			item varchar(60) NOT NULL DEFAULT 'article',
			external_id varchar(191) NOT NULL DEFAULT '',
			url text NOT NULL,
			status varchar(20) NOT NULL,
			message varchar(255) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY placement (package_id,platform,item),
			KEY post_id (post_id)
		) $charset;"
	);
	dbDelta(
		"CREATE TABLE {$t['removed']} (
			package_id varchar(100) NOT NULL,
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			title text NOT NULL,
			snapshot longtext NOT NULL,
			deleted_at datetime NOT NULL,
			closed_at datetime DEFAULT NULL,
			PRIMARY KEY  (package_id),
			KEY deleted_at (deleted_at)
		) $charset;"
	);

	update_option( 'thr_distribute_db_version', THR_DISTRIBUTE_DB_VERSION, false );
}

/**
 * Keep tables current after an update without needing reactivation.
 */
function thr_distribute_maybe_install() {
	if ( THR_DISTRIBUTE_DB_VERSION !== get_option( 'thr_distribute_db_version' ) ) {
		thr_distribute_install();
	}
}
add_action( 'plugins_loaded', 'thr_distribute_maybe_install' );

function thr_distribute_activate() {
	thr_distribute_install();

	// A role for the automation account: it can read packages and report receipts, nothing else.
	add_role(
		'thr_distributor',
		__( 'Distribution automation', 'thr-distribute' ),
		array(
			'read'           => true,
			'thr_distribute' => true,
		)
	);
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->add_cap( 'thr_distribute' );
	}

	if ( ! wp_next_scheduled( 'thr_distribute_cron' ) ) {
		wp_schedule_event( time() + 60, 'thr_distribute_5min', 'thr_distribute_cron' );
	}
}

function thr_distribute_deactivate() {
	wp_clear_scheduled_hook( 'thr_distribute_cron' );
	wp_clear_scheduled_hook( 'thr_distribute_send_now' );
}

/**
 * @param array $schedules Cron schedules.
 * @return array
 */
function thr_distribute_cron_schedules( $schedules ) {
	$schedules['thr_distribute_5min'] = array(
		'interval' => 5 * MINUTE_IN_SECONDS,
		'display'  => __( 'Every 5 minutes (THR Distribute)', 'thr-distribute' ),
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'thr_distribute_cron_schedules' );
