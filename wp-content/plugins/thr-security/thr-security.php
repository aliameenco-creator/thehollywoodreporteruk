<?php
/**
 * Plugin Name: THR Security
 * Plugin URI:  https://thehollywoodreporter.co.uk
 * Description: Basic protections for The Hollywood Reporter UK, each with its own switch: a private login address, login attempt limits, XML-RPC off, user-name hiding, security headers and more. Everything is off until switched on in Settings → THR Security.
 * Version:     2.4.0
 * Author:      The Hollywood Reporter UK
 * Author URI:  https://thehollywoodreporter.co.uk
 * Text Domain: thr-security
 * Requires at least: 6.2
 * Requires PHP: 8.1
 *
 * Locked out? Add  define( 'THR_SECURITY_OFF', true );  to wp-config.php, or rename this
 * plugin's folder in the File Manager. Either switches every protection off at once.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'THR_SECURITY_VERSION', '2.4.0' );
define( 'THR_SECURITY_PATH', plugin_dir_path( __FILE__ ) );

require_once THR_SECURITY_PATH . 'includes/settings.php';

if ( ! defined( 'THR_SECURITY_OFF' ) || ! THR_SECURITY_OFF ) {
	require_once THR_SECURITY_PATH . 'includes/login.php';
	require_once THR_SECURITY_PATH . 'includes/hardening.php';
}
