<?php
/**
 * Plugin Name: THR Distribute
 * Plugin URI:  https://thehollywoodreporter.co.uk
 * Description: Turns every article into a content package (links, text, transcript, images, audio) and tells your automations when it is published, updated, unpublished or deleted. Platforms report back where each article lives, so deletions can follow it everywhere.
 * Version:     2.4.0
 * Author:      The Hollywood Reporter UK
 * Author URI:  https://thehollywoodreporter.co.uk
 * Text Domain: thr-distribute
 * Requires at least: 6.2
 * Requires PHP: 8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'THR_DISTRIBUTE_VERSION', '2.4.0' );
define( 'THR_DISTRIBUTE_DB_VERSION', '1' );
define( 'THR_DISTRIBUTE_PATH', plugin_dir_path( __FILE__ ) );

/** Package format. Fields are only ever added, never renamed or removed. */
const THR_DISTRIBUTE_PACKAGE_SCHEMA = 'thr.package/1';
const THR_DISTRIBUTE_EVENT_SCHEMA   = 'thr.event/1';

/**
 * Post types that are distributed.
 *
 * @return string[]
 */
function thr_distribute_post_types() {
	return apply_filters( 'thr_distribute_post_types', array( 'post', 'thr_list', 'thr_gallery', 'thr_video' ) );
}

require_once THR_DISTRIBUTE_PATH . 'includes/install.php';
require_once THR_DISTRIBUTE_PATH . 'includes/settings.php';
require_once THR_DISTRIBUTE_PATH . 'includes/package.php';
require_once THR_DISTRIBUTE_PATH . 'includes/receipts.php';
require_once THR_DISTRIBUTE_PATH . 'includes/events.php';
require_once THR_DISTRIBUTE_PATH . 'includes/delivery.php';
require_once THR_DISTRIBUTE_PATH . 'includes/rest.php';
require_once THR_DISTRIBUTE_PATH . 'includes/admin.php';

register_activation_hook( __FILE__, 'thr_distribute_activate' );
register_deactivation_hook( __FILE__, 'thr_distribute_deactivate' );
