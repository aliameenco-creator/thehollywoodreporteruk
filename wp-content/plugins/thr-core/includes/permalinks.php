<?php
/**
 * Custom Permalinks, Primary Category resolution, Gallery ID routing, and 301 Redirects.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Choose the category WordPress uses for %category% in article permalinks.
 *
 * Core picks the lowest term ID, which is usually the parent section. THR URLs
 * use the subsection (/movies/movie-news/slug-123/), so prefer Yoast's primary
 * category, then the first child category, then core's choice.
 *
 * @param WP_Term   $category Category core selected.
 * @param WP_Term[] $cats     All categories on the post, sorted by term ID.
 * @param WP_Post   $post     The post.
 * @return WP_Term
 */
function thr_primary_permalink_category( $category, $cats, $post ) {
	if ( class_exists( 'WPSEO_Primary_Term' ) ) {
		$primary_id = ( new WPSEO_Primary_Term( 'category', $post->ID ) )->get_primary_term();
		foreach ( $cats as $cat ) {
			if ( (int) $cat->term_id === (int) $primary_id ) {
				return $cat;
			}
		}
	}

	foreach ( $cats as $cat ) {
		if ( 0 !== (int) $cat->parent ) {
			return $cat;
		}
	}

	return $category;
}
add_filter( 'post_link_category', 'thr_primary_permalink_category', 10, 3 );

function thr_custom_gallery_link( $post_link, $post ) {
	if ( 'thr_gallery' === $post->post_type ) {
		return home_url( user_trailingslashit( 'gallery/' . $post->post_name . '-' . $post->ID ) );
	}
	return $post_link;
}
add_filter( 'post_type_link', 'thr_custom_gallery_link', 10, 2 );

function thr_add_rewrite_rules() {
	add_rewrite_rule(
		'^gallery/([^/]+)-([0-9]+)/?$',
		'index.php?thr_gallery=$matches[1]&p=$matches[2]',
		'top'
	);

	add_rewrite_rule(
		'^results/?$',
		'index.php?s=',
		'top'
	);
}
add_action( 'init', 'thr_add_rewrite_rules', 10 );

function thr_canonical_id_redirect() {
	if ( is_admin() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( is_search() && isset( $_GET['s'] ) && ! isset( $_GET['q'] ) ) {
		$search_query = sanitize_text_field( wp_unslash( $_GET['s'] ) );
		wp_safe_redirect( home_url( '/results/?q=' . rawurlencode( $search_query ) ), 301 );
		exit;
	}

	if ( is_singular( array( 'post', 'thr_gallery' ) ) ) {
		global $post;
		if ( ! $post ) {
			return;
		}

		$current_uri   = trim( wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
		$canonical_url = get_permalink( $post->ID );
		$canonical_uri = trim( wp_parse_url( $canonical_url, PHP_URL_PATH ), '/' );

		if ( ! empty( $current_uri ) && ! empty( $canonical_uri ) && $current_uri !== $canonical_uri ) {
			wp_safe_redirect( $canonical_url, 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'thr_canonical_id_redirect', 1 );
