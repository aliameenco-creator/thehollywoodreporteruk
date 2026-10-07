<?php
/**
 * Plugin Name: THR Core
 * Plugin URI:  https://thehollywoodreporter.co.uk
 * Description: Editorial data model, custom post types, taxonomies, custom permalinks, and settings for The Hollywood Reporter UK.
 * Version:     1.0.0
 * Author:      The Hollywood Reporter UK
 * Author URI:  https://thehollywoodreporter.co.uk
 * Text Domain: thr-core
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'THR_CORE_VERSION', '1.0.0' );
define( 'THR_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'THR_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load core modules.
 */
require_once THR_CORE_PATH . 'includes/post-types.php';
require_once THR_CORE_PATH . 'includes/taxonomies.php';
require_once THR_CORE_PATH . 'includes/term-meta.php';
require_once THR_CORE_PATH . 'includes/post-meta.php';
require_once THR_CORE_PATH . 'includes/attachment-credit.php';
require_once THR_CORE_PATH . 'includes/coauthors.php';
require_once THR_CORE_PATH . 'includes/permalinks.php';
require_once THR_CORE_PATH . 'includes/image-sizes.php';
require_once THR_CORE_PATH . 'includes/query.php';
require_once THR_CORE_PATH . 'includes/popular.php';
require_once THR_CORE_PATH . 'includes/related.php';
require_once THR_CORE_PATH . 'includes/search.php';
require_once THR_CORE_PATH . 'includes/tip-form.php';
require_once THR_CORE_PATH . 'includes/settings.php';
require_once THR_CORE_PATH . 'includes/schema.php';

/**
 * Plugin activation hook.
 */
function thr_core_activate() {
	thr_register_post_types();
	thr_register_taxonomies();
	thr_add_rewrite_rules();

	if ( 'c' !== get_option( 'category_base' ) ) {
		update_option( 'category_base', 'c' );
	}
	if ( 't' !== get_option( 'tag_base' ) ) {
		update_option( 'tag_base', 't' );
	}
	if ( '/%category%/%postname%-%post_id%/' !== get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%category%/%postname%-%post_id%/' );
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'thr_core_activate' );

/**
 * Plugin deactivation hook.
 */
function thr_core_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'thr_core_deactivate' );
