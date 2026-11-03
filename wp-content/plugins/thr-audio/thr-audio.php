<?php
/**
 * Plugin Name: THR Audio
 * Plugin URI:  https://thehollywoodreporter.co.uk
 * Description: "Listen to this article" for The Hollywood Reporter UK. Makes audio with ElevenLabs when an article is published (default voice in full, an opening clip for the other voices), makes other voices on request, and plays them in a player with voice and speed controls.
 * Version:     2.0.0
 * Author:      The Hollywood Reporter UK
 * Author URI:  https://thehollywoodreporter.co.uk
 * Text Domain: thr-audio
 * Requires at least: 6.2
 * Requires PHP: 8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'THR_AUDIO_VERSION', '2.0.0' );
define( 'THR_AUDIO_PATH', plugin_dir_path( __FILE__ ) );
define( 'THR_AUDIO_URL', plugin_dir_url( __FILE__ ) );

/**
 * Post types that can carry article audio.
 *
 * @return string[]
 */
function thr_audio_post_types() {
	return apply_filters( 'thr_audio_post_types', array( 'post', 'thr_list', 'thr_gallery', 'thr_video' ) );
}

require_once THR_AUDIO_PATH . 'includes/settings.php';
require_once THR_AUDIO_PATH . 'includes/class-thr-audio-elevenlabs.php';
require_once THR_AUDIO_PATH . 'includes/text.php';
require_once THR_AUDIO_PATH . 'includes/meta.php';
require_once THR_AUDIO_PATH . 'includes/generator.php';
require_once THR_AUDIO_PATH . 'includes/rest.php';
require_once THR_AUDIO_PATH . 'includes/meta-box.php';
require_once THR_AUDIO_PATH . 'includes/player.php';

/**
 * Stop the background safety-net event when the plugin is switched off. Audio and settings stay.
 */
function thr_audio_deactivate() {
	wp_clear_scheduled_hook( 'thr_audio_cron' );
}
register_deactivation_hook( __FILE__, 'thr_audio_deactivate' );

/**
 * Link to the settings from the Plugins screen.
 *
 * @param array $links Action links.
 * @return array
 */
function thr_audio_action_links( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=thr-audio' ) ) . '">' . esc_html__( 'Settings', 'thr-audio' ) . '</a>' );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'thr_audio_action_links' );
