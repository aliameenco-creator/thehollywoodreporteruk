<?php
/**
 * Custom Permalinks, Primary Category resolution, Gallery ID routing, and 301 Redirects.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_custom_post_link( $permalink, $post ) {
	if ( 'post' !== $post->post_type ) {
		return $permalink;
	}

	if ( false !== strpos( $permalink, '%category%' ) ) {
		$primary_cat_id = 0;

		if ( class_exists( 'WPSEO_Primary_Term' ) ) {
			$wpseo_primary_term = new WPSEO_Primary_Term( 'category', $post->ID );
			$primary_cat_id     = $wpseo_primary_term->get_primary_term();
		}

		if ( ! $primary_cat_id ) {
			$categories = get_the_category( $post->ID );
			if ( ! empty( $categories ) ) {
				$chosen = $categories[0];
				foreach ( $categories as $cat ) {
					if ( 0 !== $cat->parent ) {
						$chosen = $cat;
						break;
					}
				}
				$primary_cat_id = $chosen->term_id;
			}
		}

		if ( $primary_cat_id ) {
			$category_term = get_term( $primary_cat_id, 'category' );
			if ( $category_term && ! is_wp_error( $category_term ) ) {
				$cat_path = $category_term->slug;
				if ( 0 !== $category_term->parent ) {
					$parent_term = get_term( $category_term->parent, 'category' );
					if ( $parent_term && ! is_wp_error( $parent_term ) ) {
						$cat_path = $parent_term->slug . '/' . $cat_path;
					}
				}
				$permalink = str_replace( '%category%', $cat_path, $permalink );
			}
		} else {
			$permalink = str_replace( '%category%', 'news', $permalink );
		}
	}

	if ( false !== strpos( $permalink, '%post_id%' ) ) {
		$permalink = str_replace( '%post_id%', $post->ID, $permalink );
	}

	return $permalink;
}
add_filter( 'post_link', 'thr_custom_post_link', 10, 2 );

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
