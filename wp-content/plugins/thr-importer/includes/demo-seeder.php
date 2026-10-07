<?php
/**
 * Demo Content Seeder: fictional test articles for checking layouts.
 *
 * Every post and image it creates carries the thr_demo meta flag, so
 * purge() removes exactly what seed() added and nothing else.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class THR_Demo_Seeder {

	const FLAG = 'thr_demo';
	const KEY  = 'thr_demo_key';

	/**
	 * Create demo posts that do not exist yet.
	 *
	 * Image downloads are slow on shared hosting, so work stops once the time
	 * budget is spent; run again to continue where it left off.
	 *
	 * @param bool $with_images Download a placeholder featured image per post.
	 * @param int  $time_budget Seconds to work before returning.
	 * @return array{success:bool,message:string,created:int,remaining:int}
	 */
	public static function seed( $with_images = true, $time_budget = 20 ) {
		if ( ! taxonomy_exists( 'vertical' ) ) {
			return self::result( false, 'Activate the THR Core plugin first.' );
		}

		$data = self::load();
		if ( ! $data ) {
			return self::result( false, 'Demo manifest data/demo-content.json is missing or invalid.' );
		}

		$started   = microtime( true );
		$created   = 0;
		$remaining = 0;
		$count     = count( $data['posts'] );

		foreach ( $data['posts'] as $index => $item ) {
			$post_id = self::find( $item['key'] );

			if ( ! $post_id ) {
				if ( ( microtime( true ) - $started ) > $time_budget ) {
					$remaining++;
					continue;
				}
				// Stagger publish times three hours apart, newest first, so "time ago" labels vary.
				$post_id = self::create( $item, ( $index * 3 ) * HOUR_IN_SECONDS, $count - $index );
				if ( ! $post_id ) {
					continue;
				}
				$created++;
			}

			if ( $with_images && ! has_post_thumbnail( $post_id ) ) {
				if ( ( microtime( true ) - $started ) > $time_budget ) {
					$remaining++;
					continue;
				}
				self::attach_image( $post_id, $item, $data['image_source'] );
			}
		}

		$message = $remaining
			? sprintf( 'Created %d demo articles. %d still to go: run it again to continue.', $created, $remaining )
			: sprintf( 'Demo content ready: %d new articles created.', $created );

		return array(
			'success'   => true,
			'message'   => $message,
			'created'   => $created,
			'remaining' => $remaining,
		);
	}

	/**
	 * Permanently delete all demo posts and their images.
	 *
	 * @return array{success:bool,message:string,created:int,remaining:int}
	 */
	public static function purge() {
		$ids = get_posts(
			array(
				'post_type'        => 'any',
				'post_status'      => 'any',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'meta_key'         => self::FLAG,
				'meta_value'       => '1',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		);
		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => self::FLAG,
				'meta_value'     => '1',
				'no_found_rows'  => true,
			)
		);

		foreach ( $attachments as $id ) {
			wp_delete_attachment( $id, true );
		}
		foreach ( $ids as $id ) {
			wp_delete_post( $id, true );
		}
		delete_transient( 'thr_popular_ids_24h_5' );
		delete_transient( 'thr_popular_ids_24h_6' );

		return self::result( true, sprintf( 'Deleted %d demo articles and %d images.', count( $ids ), count( $attachments ) ) );
	}

	/**
	 * Number of demo posts currently in the database.
	 *
	 * @return int
	 */
	public static function count() {
		$ids = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => self::FLAG,
				'meta_value'     => '1',
				'no_found_rows'  => true,
			)
		);
		return count( $ids );
	}

	private static function load() {
		$file = THR_IMPORTER_PATH . 'data/demo-content.json';
		if ( ! file_exists( $file ) ) {
			return null;
		}
		$data = json_decode( file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local bundled file.
		return ( is_array( $data ) && ! empty( $data['posts'] ) ) ? $data : null;
	}

	private static function find( $key ) {
		$ids = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::KEY,
				'meta_value'     => $key,
				'no_found_rows'  => true,
			)
		);
		return $ids ? (int) $ids[0] : 0;
	}

	private static function create( $item, $age, $order ) {
		$cat = get_term_by( 'slug', $item['category'], 'category' );
		if ( ! $cat ) {
			return 0;
		}
		$cats = array( (int) $cat->term_id );
		if ( $cat->parent ) {
			$cats[] = (int) $cat->parent;
		}

		$content = '';
		foreach ( $item['body'] as $paragraph ) {
			$content .= "<!-- wp:paragraph -->\n<p>" . esc_html( $paragraph ) . "</p>\n<!-- /wp:paragraph -->\n\n";
		}

		$timestamp = time() - $age;
		$post_id   = wp_insert_post(
			array(
				'post_title'    => $item['title'],
				'post_excerpt'  => $item['dek'],
				'post_content'  => $content,
				'post_status'   => 'publish',
				'post_type'     => 'post',
				'post_author'   => get_current_user_id() ?: 1,
				'post_date'     => wp_date( 'Y-m-d H:i:s', $timestamp ),
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $timestamp ),
				'post_category' => $cats,
				'tags_input'    => $item['tags'],
				'menu_order'    => $order,
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return 0;
		}

		if ( ! empty( $item['vertical'] ) ) {
			wp_set_object_terms( $post_id, $item['vertical'], 'vertical' );
		}

		$meta = array(
			self::FLAG         => '1',
			self::KEY          => $item['key'],
			'thr_dek'          => $item['dek'],
			'thr_article_type' => $item['article_type'],
			'thr_featured'     => ! empty( $item['featured'] ) ? '1' : '',
			'thr_breaking'     => ! empty( $item['breaking'] ) ? '1' : '',
		);
		if ( ! empty( $item['review'] ) ) {
			$review = $item['review'];
			$meta  += array(
				'thr_review_subject'     => $review['subject'],
				'thr_review_bottom_line' => $review['bottom_line'],
				'thr_review_director'    => $review['director'],
				'thr_review_cast'        => $review['cast'],
				'thr_review_venue'       => $review['venue'],
				'thr_review_rating_time' => $review['rating'],
			);
		}
		foreach ( $meta as $meta_key => $value ) {
			update_post_meta( $post_id, $meta_key, $value );
		}

		return (int) $post_id;
	}

	private static function attach_image( $post_id, $item, $source ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$url = str_replace( '{key}', rawurlencode( $item['key'] ), $source );
		$tmp = download_url( $url, 30 );
		if ( is_wp_error( $tmp ) ) {
			return;
		}

		$attachment_id = media_handle_sideload(
			array(
				'name'     => sanitize_file_name( $item['key'] ) . '.jpg',
				'tmp_name' => $tmp,
			),
			$post_id,
			$item['title']
		);
		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $tmp );
			return;
		}

		update_post_meta( $attachment_id, self::FLAG, '1' );
		update_post_meta( $attachment_id, 'thr_credit', 'Placeholder image / Picsum Photos' );
		set_post_thumbnail( $post_id, $attachment_id );
	}

	private static function result( $success, $message ) {
		return array(
			'success'   => $success,
			'message'   => $message,
			'created'   => 0,
			'remaining' => 0,
		);
	}
}
