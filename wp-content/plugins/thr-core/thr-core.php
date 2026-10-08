<?php
/**
 * Plugin Name: THR Core
 * Plugin URI:  https://thehollywoodreporter.co.uk
 * Description: Editorial data model, custom post types, taxonomies, custom permalinks, and settings for The Hollywood Reporter UK.
 * Version:     1.2.1
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

define( 'THR_CORE_VERSION', '1.2.1' );
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
require_once THR_CORE_PATH . 'includes/mail.php';
require_once THR_CORE_PATH . 'includes/tip-form.php';
require_once THR_CORE_PATH . 'includes/contact-form.php';
require_once THR_CORE_PATH . 'includes/newsletter.php';
require_once THR_CORE_PATH . 'includes/seo.php';
require_once THR_CORE_PATH . 'includes/settings.php';
require_once THR_CORE_PATH . 'includes/schema.php';
require_once THR_CORE_PATH . 'includes/updater.php';

/**
 * Plugin activation hook.
 */
function thr_core_activate() {
	thr_register_post_types();
	thr_register_taxonomies();
	thr_add_rewrite_rules();
	thr_apply_permalink_settings( '/%category%/%postname%-%post_id%/', 'c', 't' );
}

/**
 * Save permalink settings and rebuild rewrite rules from them.
 *
 * update_option() alone leaves $wp_rewrite holding the old structure, so a
 * flush straight after would regenerate the old rules; set them on $wp_rewrite.
 *
 * @param string $structure     Post permalink structure.
 * @param string $category_base Category base.
 * @param string $tag_base      Tag base.
 */
function thr_apply_permalink_settings( $structure, $category_base, $tag_base ) {
	global $wp_rewrite;

	$wp_rewrite->set_permalink_structure( $structure );
	$wp_rewrite->set_category_base( $category_base );
	$wp_rewrite->set_tag_base( $tag_base );
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
