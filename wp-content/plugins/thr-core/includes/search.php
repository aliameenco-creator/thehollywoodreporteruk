<?php
/**
 * THR Search Architecture and Live Autocomplete REST Endpoint.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_register_search_rest_routes() {
	register_rest_route(
		'thr/v1',
		'/search',
		array(
			'methods'             => 'GET',
			'callback'            => 'thr_handle_search_autocomplete',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'thr_register_search_rest_routes' );

function thr_handle_search_autocomplete( WP_REST_Request $request ) {
	$q = sanitize_text_field( $request->get_param( 'q' ) );
	if ( empty( $q ) || strlen( $q ) < 2 ) {
		return new WP_REST_Response( array( 'results' => array() ), 200 );
	}

	$query = new WP_Query(
		array(
			's'              => $q,
			'post_type'      => array( 'post', 'thr_list', 'thr_gallery', 'thr_video' ),
			'post_status'    => 'publish',
			'posts_per_page' => 5,
			'no_found_rows'  => true,
		)
	);

	$results = array();
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$results[] = array(
				'id'        => get_the_ID(),
				'title'     => get_the_title(),
				'url'       => get_permalink(),
				'thumbnail' => get_the_post_thumbnail_url( get_the_ID(), 'thr-card-s' ) ?: '',
				'date'      => get_the_date( 'M j, Y' ),
			);
		}
		wp_reset_postdata();
	}

	return new WP_REST_Response( array( 'results' => $results ), 200 );
}

function thr_search_query_var( $query ) {
	if ( ! is_admin() && $query->is_main_query() ) {
		if ( isset( $_GET['q'] ) && ! empty( $_GET['q'] ) ) {
			$query->set( 's', sanitize_text_field( wp_unslash( $_GET['q'] ) ) );
			$query->is_search = true;
		}

		if ( $query->is_search() && isset( $_GET['type'] ) && ! empty( $_GET['type'] ) ) {
			$type = sanitize_key( $_GET['type'] );
			if ( in_array( $type, array( 'post', 'thr_list', 'thr_gallery', 'thr_video' ), true ) ) {
				$query->set( 'post_type', $type );
			}
		}
	}
}
add_action( 'pre_get_posts', 'thr_search_query_var' );

function thr_search_robots( $robots ) {
	if ( is_search() ) {
		$robots['noindex']  = true;
		$robots['follow']   = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'thr_search_robots' );
