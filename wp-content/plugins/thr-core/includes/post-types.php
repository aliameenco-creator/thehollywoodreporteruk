<?php
/**
 * Register Custom Post Types for THR.
 *
 * Post types:
 * - thr_list     (/lists/)
 * - thr_gallery  (/gallery/{slug}-{id}/)
 * - thr_video    (/video/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_register_post_types() {
	// 1. Lists (/lists/)
	$list_labels = array(
		'name'               => _x( 'Lists', 'post type general name', 'thr-core' ),
		'singular_name'      => _x( 'List', 'post type singular name', 'thr-core' ),
		'menu_name'          => _x( 'Lists', 'admin menu', 'thr-core' ),
		'name_admin_bar'     => _x( 'List', 'add new on admin bar', 'thr-core' ),
		'add_new'            => _x( 'Add New', 'list', 'thr-core' ),
		'add_new_item'       => __( 'Add New List', 'thr-core' ),
		'new_item'           => __( 'New List', 'thr-core' ),
		'edit_item'          => __( 'Edit List', 'thr-core' ),
		'view_item'          => __( 'View List', 'thr-core' ),
		'all_items'          => __( 'All Lists', 'thr-core' ),
		'search_items'       => __( 'Search Lists', 'thr-core' ),
		'not_found'          => __( 'No lists found.', 'thr-core' ),
		'not_found_in_trash' => __( 'No lists found in Trash.', 'thr-core' ),
	);

	$list_args = array(
		'labels'             => $list_labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'lists', 'with_front' => false ),
		'capability_type'    => 'post',
		'has_archive'        => 'lists',
		'hierarchical'       => false,
		'menu_position'      => 6,
		'menu_icon'          => 'dashicons-list-view',
		'show_in_rest'       => true,
		'taxonomies'         => array( 'category', 'post_tag', 'vertical' ),
		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'revisions', 'custom-fields' ),
	);
	register_post_type( 'thr_list', $list_args );

	// 2. Galleries (/gallery/{slug}-{id}/)
	$gallery_labels = array(
		'name'               => _x( 'Galleries', 'post type general name', 'thr-core' ),
		'singular_name'      => _x( 'Gallery', 'post type singular name', 'thr-core' ),
		'menu_name'          => _x( 'Galleries', 'admin menu', 'thr-core' ),
		'name_admin_bar'     => _x( 'Gallery', 'add new on admin bar', 'thr-core' ),
		'add_new'            => _x( 'Add New', 'gallery', 'thr-core' ),
		'add_new_item'       => __( 'Add New Gallery', 'thr-core' ),
		'new_item'           => __( 'New Gallery', 'thr-core' ),
		'edit_item'          => __( 'Edit Gallery', 'thr-core' ),
		'view_item'          => __( 'View Gallery', 'thr-core' ),
		'all_items'          => __( 'All Galleries', 'thr-core' ),
		'search_items'       => __( 'Search Galleries', 'thr-core' ),
		'not_found'          => __( 'No galleries found.', 'thr-core' ),
		'not_found_in_trash' => __( 'No galleries found in Trash.', 'thr-core' ),
	);

	$gallery_args = array(
		'labels'             => $gallery_labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'gallery', 'with_front' => false ),
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => 7,
		'menu_icon'          => 'dashicons-format-gallery',
		'show_in_rest'       => true,
		'taxonomies'         => array( 'category', 'post_tag', 'vertical' ),
		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'revisions', 'custom-fields' ),
	);
	register_post_type( 'thr_gallery', $gallery_args );

	// 3. Videos (/video/)
	$video_labels = array(
		'name'               => _x( 'Videos', 'post type general name', 'thr-core' ),
		'singular_name'      => _x( 'Video', 'post type singular name', 'thr-core' ),
		'menu_name'          => _x( 'Videos', 'admin menu', 'thr-core' ),
		'name_admin_bar'     => _x( 'Video', 'add new on admin bar', 'thr-core' ),
		'add_new'            => _x( 'Add New', 'video', 'thr-core' ),
		'add_new_item'       => __( 'Add New Video', 'thr-core' ),
		'new_item'           => __( 'New Video', 'thr-core' ),
		'edit_item'          => __( 'Edit Video', 'thr-core' ),
		'view_item'          => __( 'View Video', 'thr-core' ),
		'all_items'          => __( 'All Videos', 'thr-core' ),
		'search_items'       => __( 'Search Videos', 'thr-core' ),
		'not_found'          => __( 'No videos found.', 'thr-core' ),
		'not_found_in_trash' => __( 'No videos found in Trash.', 'thr-core' ),
	);

	$video_args = array(
		'labels'             => $video_labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'video', 'with_front' => false ),
		'capability_type'    => 'post',
		'has_archive'        => 'video',
		'hierarchical'       => false,
		'menu_position'      => 8,
		'menu_icon'          => 'dashicons-video-alt3',
		'show_in_rest'       => true,
		'taxonomies'         => array( 'vcategory', 'post_tag' ),
		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'revisions', 'custom-fields' ),
	);
	register_post_type( 'thr_video', $video_args );
}
add_action( 'init', 'thr_register_post_types', 0 );
