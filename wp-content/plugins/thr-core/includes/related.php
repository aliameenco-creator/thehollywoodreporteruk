<?php
/**
 * Related Story Query Engine.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_get_related_posts( $post_id = null, $limit = 2 ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}
	if ( ! $post_id ) {
		return array();
	}

	$post_type = get_post_type( $post_id );
	$tags      = wp_get_post_terms( $post_id, 'post_tag', array( 'fields' => 'ids' ) );
	$results   = array();

	if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
		$tag_query = new WP_Query(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'tag__in'        => $tags,
				'post__not_in'   => array( $post_id ),
				'posts_per_page' => $limit,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);
		$results = $tag_query->posts;
	}

	$count = count( $results );
	if ( $count < $limit ) {
		$exclude_ids = array_merge( array( $post_id ), wp_list_pluck( $results, 'ID' ) );
		$categories  = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'ids' ) );

		$cat_query_args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'post__not_in'   => $exclude_ids,
			'posts_per_page' => ( $limit - $count ),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		);

		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$cat_query_args['category__in'] = $categories;
		}

		$cat_query = new WP_Query( $cat_query_args );
		$results   = array_merge( $results, $cat_query->posts );
	}

	return $results;
}
