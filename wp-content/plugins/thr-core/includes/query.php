<?php
/**
 * THR Query Engine and Page-level Deduplication.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class THR_Query {
	public static $displayed_ids = array();

	public static function reset_displayed() {
		self::$displayed_ids = array();
	}

	public static function mark_displayed( $id ) {
		if ( $id && ! in_array( $id, self::$displayed_ids, true ) ) {
			self::$displayed_ids[] = (int) $id;
		}
	}

	public static function get( $args = array() ) {
		$defaults = array(
			'posts_per_page' => 10,
			'post_type'      => array( 'post' ),
			'post_status'    => 'publish',
			'source'         => 'latest',
			'term'           => '',
			'article_type'   => '',
			'dedupe'         => true,
			'offset'         => 0,
		);
		$parsed = wp_parse_args( $args, $defaults );

		$query_args = array(
			'post_type'           => $parsed['post_type'],
			'post_status'         => 'publish',
			'posts_per_page'      => (int) $parsed['posts_per_page'],
			'ignore_sticky_posts' => true,
		);

		if ( ! empty( $parsed['offset'] ) ) {
			$query_args['offset'] = (int) $parsed['offset'];
		}

		if ( $parsed['dedupe'] && ! empty( self::$displayed_ids ) ) {
			$query_args['post__not_in'] = self::$displayed_ids;
		}

		switch ( $parsed['source'] ) {
			case 'featured':
				$query_args['meta_query'][] = array(
					'key'   => 'thr_featured',
					'value' => '1',
				);
				break;

			case 'section':
				if ( ! empty( $parsed['term'] ) ) {
					$query_args['category_name'] = sanitize_title( $parsed['term'] );
				}
				break;

			case 'topic':
				if ( ! empty( $parsed['term'] ) ) {
					$query_args['tag'] = sanitize_title( $parsed['term'] );
				}
				break;

			case 'vertical':
				if ( ! empty( $parsed['term'] ) ) {
					$query_args['tax_query'][] = array(
						'taxonomy' => 'vertical',
						'field'    => 'slug',
						'terms'    => sanitize_title( $parsed['term'] ),
					);
				}
				break;

			case 'vcategory':
				$query_args['post_type'] = 'thr_video';
				if ( ! empty( $parsed['term'] ) ) {
					$query_args['tax_query'][] = array(
						'taxonomy' => 'vcategory',
						'field'    => 'slug',
						'terms'    => sanitize_title( $parsed['term'] ),
					);
				}
				break;

			case 'popular':
				$popular_ids = thr_get_popular_post_ids( $parsed['posts_per_page'] );
				if ( ! empty( $popular_ids ) ) {
					$query_args['post__in'] = $popular_ids;
					$query_args['orderby']  = 'post__in';
				}
				break;
		}

		if ( ! empty( $parsed['article_type'] ) ) {
			$query_args['meta_query'][] = array(
				'key'   => 'thr_article_type',
				'value' => sanitize_key( $parsed['article_type'] ),
			);
		}

		$query = new WP_Query( $query_args );

		if ( $parsed['dedupe'] && $query->have_posts() ) {
			foreach ( $query->posts as $p ) {
				self::mark_displayed( $p->ID );
			}
		}

		return $query;
	}
}
