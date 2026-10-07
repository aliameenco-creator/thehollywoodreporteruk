<?php
/**
 * The Hollywood Reporter UK Theme Functions and Definitions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'THR_THEME_VERSION', '1.0.0' );

require_once get_template_directory() . '/inc/template-tags.php';

function thr_theme_setup() {
	load_theme_textdomain( 'thr-theme', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );

	register_nav_menus(
		array(
			'primary' => __( 'Section Bar (Top Navigation)', 'thr-theme' ),
			'mega'    => __( 'Mega Menu Drawer', 'thr-theme' ),
			'footer'  => __( 'Footer Main Menu', 'thr-theme' ),
		)
	);
}
add_action( 'after_setup_theme', 'thr_theme_setup' );

function thr_theme_scripts() {
	wp_enqueue_style(
		'thr-fonts',
		'https://fonts.googleapis.com/css2?family=Karla:ital,wght@0,400;0,700;0,800;1,400&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,700;1,6..72,400&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'thr-main-style',
		get_template_directory_uri() . '/assets/css/main.css',
		array( 'thr-fonts' ),
		THR_THEME_VERSION
	);

	wp_enqueue_script(
		'thr-main-js',
		get_template_directory_uri() . '/assets/js/main.js',
		array(),
		THR_THEME_VERSION,
		true
	);

	wp_localize_script(
		'thr-main-js',
		'thrSettings',
		array(
			'searchApi' => esc_url_raw( rest_url( 'thr/v1/search' ) ),
			'trackApi'  => esc_url_raw( rest_url( 'thr/v1/track-view' ) ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'thr_theme_scripts' );
