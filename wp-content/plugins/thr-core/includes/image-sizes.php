<?php
/**
 * Custom Image Sizes and Responsive Media for THR.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_register_image_sizes() {
	add_image_size( 'thr-card-s', 400, 225, true );
	add_image_size( 'thr-card-m', 800, 450, true );
	add_image_size( 'thr-lead', 1200, 675, true );
	add_image_size( 'thr-hero', 1600, 900, true );
	add_image_size( 'thr-cover-s', 400, 600, true );
	add_image_size( 'thr-cover-m', 800, 1200, true );
	add_image_size( 'thr-avatar-s', 150, 150, true );
	add_image_size( 'thr-avatar-m', 300, 300, true );
	add_image_size( 'thr-topic-banner', 1500, 598, true );
}
add_action( 'after_setup_theme', 'thr_register_image_sizes' );

function thr_custom_image_size_names( $sizes ) {
	return array_merge(
		$sizes,
		array(
			'thr-card-s'      => __( '16:9 Small (400×225)', 'thr-core' ),
			'thr-card-m'      => __( '16:9 Medium (800×450)', 'thr-core' ),
			'thr-lead'        => __( '16:9 Lead (1200×675)', 'thr-core' ),
			'thr-hero'        => __( '16:9 Hero (1600×900)', 'thr-core' ),
			'thr-cover-s'     => __( '2:3 Magazine Cover (400×600)', 'thr-core' ),
			'thr-avatar-m'    => __( '1:1 Headshot (300×300)', 'thr-core' ),
			'thr-topic-banner'=> __( 'Topic Banner (1500×598)', 'thr-core' ),
		)
	);
}
add_filter( 'image_size_names_choose', 'thr_custom_image_size_names' );

add_filter( 'wp_editor_set_quality', function( $quality ) {
	return 85;
} );
