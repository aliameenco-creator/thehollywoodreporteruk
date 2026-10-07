<?php
/**
 * Most Popular Story Tracking and Cache-safe Beacon Endpoint.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_register_popular_beacon_endpoint() {
	register_rest_route(
		'thr/v1',
		'/track-view',
		array(
			'methods'             => 'POST',
			'callback'            => 'thr_handle_view_beacon',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'thr_register_popular_beacon_endpoint' );

function thr_handle_view_beacon( WP_REST_Request $request ) {
	$post_id = absint( $request->get_param( 'post_id' ) );
	if ( ! $post_id || 'publish' !== get_post_status( $post_id ) ) {
		return new WP_REST_Response( array( 'status' => 'ignored' ), 200 );
	}

	$total = (int) get_post_meta( $post_id, 'thr_views_total', true );
	update_post_meta( $post_id, 'thr_views_total', $total + 1 );

	$today_key = 'thr_views_' . gmdate( 'Ymd' );
	$daily     = (int) get_post_meta( $post_id, $today_key, true );
	update_post_meta( $post_id, $today_key, $daily + 1 );

	return new WP_REST_Response( array( 'status' => 'recorded' ), 200 );
}

function thr_get_popular_post_ids( $limit = 6, $window = '24h' ) {
	$cache_key = 'thr_popular_ids_' . $window . '_' . $limit;
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return $cached;
	}

	$meta_key = ( '24h' === $window ) ? 'thr_views_' . gmdate( 'Ymd' ) : 'thr_views_total';

	$query = new WP_Query(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_key'       => $meta_key,
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	$ids = $query->posts;

	if ( count( $ids ) < $limit ) {
		$fallback = new WP_Query(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'post__not_in'   => $ids,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		$ids = array_merge( $ids, $fallback->posts );
	}

	set_transient( $cache_key, $ids, 15 * MINUTE_IN_SECONDS );
	return $ids;
}
