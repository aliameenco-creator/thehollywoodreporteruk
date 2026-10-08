<?php
/**
 * Plugin Name: THR Importer & Structure Seeder
 * Plugin URI:  https://thehollywoodreporter.co.uk
 * Description: One-click structural setup and demonstration content seeder for The Hollywood Reporter UK.
 * Version:     1.2.2
 * Author:      The Hollywood Reporter UK
 * Author URI:  https://thehollywoodreporter.co.uk
 * Text Domain: thr-importer
 * Requires PHP: 8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'THR_IMPORTER_VERSION', '1.2.2' );
define( 'THR_IMPORTER_PATH', plugin_dir_path( __FILE__ ) );
define( 'THR_IMPORTER_URL', plugin_dir_url( __FILE__ ) );

require_once THR_IMPORTER_PATH . 'includes/structure-seeder.php';
require_once THR_IMPORTER_PATH . 'includes/demo-seeder.php';
require_once THR_IMPORTER_PATH . 'includes/admin.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once THR_IMPORTER_PATH . 'includes/cli.php';
}
