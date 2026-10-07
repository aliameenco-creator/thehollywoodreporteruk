<?php
/**
 * Register Taxonomies and configure native taxonomy settings for THR.
 *
 * Taxonomies:
 * - vertical    (/e/{slug}/) - Heat Vision, Live Feed, The Race, etc.
 * - vcategory   (/vcategory/{slug}/) - Video categories
 * - Native category -> relabeled "Sections", base 'c' (/c/{slug}/)
 * - Native post_tag -> relabeled "Topics", base 't' (/t/{slug}/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_register_taxonomies() {
	// 1. Verticals (/e/{slug}/)
	$vertical_labels = array(
		'name'                       => _x( 'Verticals', 'taxonomy general name', 'thr-core' ),
		'singular_name'              => _x( 'Vertical', 'taxonomy singular name', 'thr-core' ),
		'search_items'               => __( 'Search Verticals', 'thr-core' ),
		'popular_items'              => __( 'Popular Verticals', 'thr-core' ),
		'all_items'                  => __( 'All Verticals', 'thr-core' ),
		'parent_item'                => null,
		'parent_item_colon'          => null,
		'edit_item'                  => __( 'Edit Vertical', 'thr-core' ),
		'update_item'                => __( 'Update Vertical', 'thr-core' ),
		'add_new_item'               => __( 'Add New Vertical', 'thr-core' ),
		'new_item_name'              => __( 'New Vertical Name', 'thr-core' ),
		'separate_items_with_commas' => __( 'Separate verticals with commas', 'thr-core' ),
		'add_or_remove_items'        => __( 'Add or remove verticals', 'thr-core' ),
		'choose_from_most_used'      => __( 'Choose from the most used verticals', 'thr-core' ),
		'not_found'                  => __( 'No verticals found.', 'thr-core' ),
		'menu_name'                  => __( 'Verticals', 'thr-core' ),
	);

	register_taxonomy(
		'vertical',
		array( 'post', 'thr_list', 'thr_gallery' ),
		array(
			'hierarchical'          => false,
			'labels'                => $vertical_labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => array(
				'slug'         => 'e',
				'with_front'   => false,
				'hierarchical' => false,
			),
			'show_in_rest'          => true,
		)
	);

	// 2. Video Categories (/vcategory/{slug}/)
	$vcat_labels = array(
		'name'              => _x( 'Video Categories', 'taxonomy general name', 'thr-core' ),
		'singular_name'     => _x( 'Video Category', 'taxonomy singular name', 'thr-core' ),
		'search_items'      => __( 'Search Video Categories', 'thr-core' ),
		'all_items'         => __( 'All Video Categories', 'thr-core' ),
		'parent_item'       => __( 'Parent Video Category', 'thr-core' ),
		'parent_item_colon' => __( 'Parent Video Category:', 'thr-core' ),
		'edit_item'         => __( 'Edit Video Category', 'thr-core' ),
		'update_item'       => __( 'Update Video Category', 'thr-core' ),
		'add_new_item'      => __( 'Add New Video Category', 'thr-core' ),
		'new_item_name'     => __( 'New Video Category Name', 'thr-core' ),
		'menu_name'         => __( 'Video Categories', 'thr-core' ),
	);

	register_taxonomy(
		'vcategory',
		array( 'thr_video' ),
		array(
			'hierarchical'      => true,
			'labels'            => $vcat_labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array(
				'slug'         => 'vcategory',
				'with_front'   => false,
				'hierarchical' => true,
			),
			'show_in_rest'      => true,
		)
	);
}
add_action( 'init', 'thr_register_taxonomies', 0 );

/**
 * Relabel Categories to "Sections" and Tags to "Topics" in the WordPress Admin.
 */
function thr_relabel_taxonomies() {
	global $wp_taxonomies;

	// Relabel category -> Section
	if ( isset( $wp_taxonomies['category'] ) ) {
		$wp_taxonomies['category']->labels->name          = __( 'Sections', 'thr-core' );
		$wp_taxonomies['category']->labels->singular_name = __( 'Section', 'thr-core' );
		$wp_taxonomies['category']->labels->menu_name     = __( 'Sections', 'thr-core' );
		$wp_taxonomies['category']->labels->all_items     = __( 'All Sections', 'thr-core' );
		$wp_taxonomies['category']->labels->edit_item     = __( 'Edit Section', 'thr-core' );
		$wp_taxonomies['category']->labels->view_item     = __( 'View Section', 'thr-core' );
		$wp_taxonomies['category']->labels->update_item   = __( 'Update Section', 'thr-core' );
		$wp_taxonomies['category']->labels->add_new_item  = __( 'Add New Section', 'thr-core' );
		$wp_taxonomies['category']->labels->new_item_name = __( 'New Section Name', 'thr-core' );
		$wp_taxonomies['category']->labels->search_items  = __( 'Search Sections', 'thr-core' );
	}

	// Relabel post_tag -> Topic
	if ( isset( $wp_taxonomies['post_tag'] ) ) {
		$wp_taxonomies['post_tag']->labels->name          = __( 'Topics', 'thr-core' );
		$wp_taxonomies['post_tag']->labels->singular_name = __( 'Topic', 'thr-core' );
		$wp_taxonomies['post_tag']->labels->menu_name     = __( 'Topics', 'thr-core' );
		$wp_taxonomies['post_tag']->labels->all_items     = __( 'All Topics', 'thr-core' );
		$wp_taxonomies['post_tag']->labels->edit_item     = __( 'Edit Topic', 'thr-core' );
		$wp_taxonomies['post_tag']->labels->view_item     = __( 'View Topic', 'thr-core' );
		$wp_taxonomies['post_tag']->labels->update_item   = __( 'Update Topic', 'thr-core' );
		$wp_taxonomies['post_tag']->labels->add_new_item  = __( 'Add New Topic', 'thr-core' );
		$wp_taxonomies['post_tag']->labels->new_item_name = __( 'New Topic Name', 'thr-core' );
		$wp_taxonomies['post_tag']->labels->search_items  = __( 'Search Topics', 'thr-core' );
	}
}
add_action( 'init', 'thr_relabel_taxonomies', 10 );
