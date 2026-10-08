<?php
/**
 * SEO, Canonical Tags, and JSON-LD Structured Data (Schema.org).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_output_topic_canonical() {
	// Yoast and friends print their own canonical; never output two.
	if ( function_exists( 'thr_has_seo_plugin' ) && thr_has_seo_plugin() ) {
		return;
	}
	if ( is_tag() ) {
		$term = get_queried_object();
		if ( $term ) {
			$canonical = get_tag_link( $term->term_id );
			echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
		}
	}
}
add_action( 'wp_head', 'thr_output_topic_canonical', 1 );

function thr_output_schema_json_ld() {
	if ( is_admin() || is_feed() ) {
		return;
	}

	$data = array();

	$org = array(
		'@context' => 'https://schema.org',
		'@type'    => 'NewsMediaOrganization',
		'name'     => get_bloginfo( 'name' ),
		'url'      => home_url( '/' ),
		'logo'     => array(
			'@type' => 'ImageObject',
			'url'   => get_option( 'thr_logo_url' ) ?: home_url( '/wp-content/themes/thr-theme/assets/images/logo.svg' ),
		),
	);

	if ( is_single() ) {
		global $post;
		$article_type = get_post_meta( $post->ID, 'thr_article_type', true );

		$article_schema = array(
			'@context'         => 'https://schema.org',
			'@type'            => ( 'review' === $article_type ) ? 'Review' : 'NewsArticle',
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id'   => get_permalink( $post->ID ),
			),
			'headline'         => get_the_title( $post->ID ),
			'description'      => get_post_meta( $post->ID, 'thr_dek', true ) ?: wp_strip_all_tags( get_the_excerpt( $post->ID ) ),
			'datePublished'    => get_the_date( 'c', $post->ID ),
			'dateModified'     => get_the_modified_date( 'c', $post->ID ),
			'publisher'        => $org,
			'author'           => array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', $post->post_author ),
				'url'   => get_author_posts_url( $post->post_author ),
			),
		);

		if ( has_post_thumbnail( $post->ID ) ) {
			$thumb_id  = get_post_thumbnail_id( $post->ID );
			$thumb_url = wp_get_attachment_image_url( $thumb_id, 'full' );
			$credit    = get_post_meta( $thumb_id, 'thr_credit', true );
			$article_schema['image'] = array(
				'@type'  => 'ImageObject',
				'url'    => $thumb_url,
				'credit' => $credit,
			);
		}

		$seo_plugin = function_exists( 'thr_has_seo_plugin' ) && thr_has_seo_plugin();

		if ( 'review' === $article_type ) {
			$subject = get_post_meta( $post->ID, 'thr_review_subject', true ) ?: get_the_title( $post->ID );
			$article_schema['itemReviewed'] = array(
				'@type' => 'Movie',
				'name'  => $subject,
			);
			$director = get_post_meta( $post->ID, 'thr_review_director', true );
			if ( $director ) {
				$article_schema['itemReviewed']['director'] = array(
					'@type' => 'Person',
					'name'  => $director,
				);
			}
		}

		if ( ! $seo_plugin ) {
			$data[] = $article_schema;
		} elseif ( 'review' === $article_type ) {
			// The SEO plugin already describes the article; add only the review, which it doesn't know about.
			$data[] = array(
				'@context'     => 'https://schema.org',
				'@type'        => 'Review',
				'url'          => get_permalink( $post->ID ),
				'name'         => get_the_title( $post->ID ),
				'author'       => $article_schema['author'],
				'publisher'    => array(
					'@type' => 'NewsMediaOrganization',
					'name'  => get_bloginfo( 'name' ),
				),
				'itemReviewed' => $article_schema['itemReviewed'],
			);
		}
	}

	if ( ! empty( $data ) ) {
		echo "\n" . '<script type="application/ld+json">' . "\n";
		echo wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		echo "\n" . '</script>' . "\n";
	}
}
add_action( 'wp_head', 'thr_output_schema_json_ld', 5 );
