<?php
/**
 * The content package: everything an automation needs about one article, in one shape.
 *
 * Other plugins add to it with the `thr_distribute_package` filter (THR Audio adds voices,
 * the read-aloud script and captions). The format is versioned by THR_DISTRIBUTE_PACKAGE_SCHEMA
 * and documented in docs/THR-DISTRIBUTE.md.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param int $post_id Post ID.
 * @return string e.g. thehollywoodreporteruk-com-123
 */
function thr_distribute_package_id( $post_id ) {
	return thr_distribute_site_id() . '-' . (int) $post_id;
}

/**
 * Post ID from a package ID, or 0 when it belongs to another site.
 *
 * @param string $package_id Package ID.
 * @return int
 */
function thr_distribute_post_from_package( $package_id ) {
	$prefix = thr_distribute_site_id() . '-';
	if ( 0 !== strpos( (string) $package_id, $prefix ) ) {
		return 0;
	}
	return absint( substr( $package_id, strlen( $prefix ) ) );
}

/**
 * Whether a post is one this plugin distributes.
 *
 * @param int|WP_Post $post Post.
 * @return bool
 */
function thr_distribute_handles( $post ) {
	$post = get_post( $post );
	return $post && in_array( $post->post_type, thr_distribute_post_types(), true ) && ! wp_is_post_revision( $post ) && ! wp_is_post_autosave( $post );
}

/**
 * Article body as plain text, one paragraph per block, with headings and list items kept.
 *
 * @param string $html Rendered HTML.
 * @return string
 */
function thr_distribute_plain_text( $html ) {
	$html = preg_replace( '#<(script|style|figure|figcaption|iframe|table)\b[^>]*>.*?</\1>#is', '', $html );
	$html = preg_replace( '#<br\s*/?>#i', "\n", $html );
	$html = preg_replace( '#</(p|h[1-6]|li|blockquote|div|pre)>#i', "\n\n", $html );
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = preg_replace( "/[ \t]+/", ' ', $text );
	$text = preg_replace( "/ *\n */", "\n", $text );
	return trim( preg_replace( "/\n{3,}/", "\n\n", $text ) );
}

/**
 * Terms as id/slug/name/url.
 *
 * @param int    $post_id  Post ID.
 * @param string $taxonomy Taxonomy.
 * @return array
 */
function thr_distribute_terms( $post_id, $taxonomy ) {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return array();
	}
	$out = array();
	foreach ( $terms as $term ) {
		$link  = get_term_link( $term );
		$out[] = array(
			'id'   => (int) $term->term_id,
			'slug' => $term->slug,
			'name' => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
			'url'  => is_wp_error( $link ) ? '' : $link,
		);
	}
	return $out;
}

/**
 * Authors, using Co-Authors Plus when it is active.
 *
 * @param WP_Post $post Post.
 * @return array name, url.
 */
function thr_distribute_authors( $post ) {
	$authors = array();
	if ( function_exists( 'get_coauthors' ) ) {
		foreach ( (array) get_coauthors( $post->ID ) as $author ) {
			$authors[] = array(
				'name' => $author->display_name,
				'url'  => function_exists( 'get_author_posts_url' ) ? get_author_posts_url( $author->ID, $author->user_nicename ) : '',
			);
		}
	}
	if ( ! $authors && $post->post_author ) {
		$authors[] = array(
			'name' => get_the_author_meta( 'display_name', $post->post_author ),
			'url'  => get_author_posts_url( $post->post_author ),
		);
	}
	return $authors;
}

/**
 * One image: address, size, alt text, credit and whether it may be used off-site.
 *
 * @param int $id Attachment ID.
 * @return array|null
 */
function thr_distribute_image( $id ) {
	$full = $id ? wp_get_attachment_image_src( $id, 'full' ) : false;
	if ( ! $full ) {
		return null;
	}
	$large = wp_get_attachment_image_src( $id, 'large' );
	return array(
		'id'              => (int) $id,
		'url'             => $full[0],
		'width'           => (int) $full[1],
		'height'          => (int) $full[2],
		'large_url'       => $large ? $large[0] : $full[0],
		'alt'             => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
		'caption'         => (string) wp_get_attachment_caption( $id ),
		'credit'          => (string) get_post_meta( $id, 'thr_credit', true ),
		// Agency photos are usually licensed for the website only, so this is off unless an editor ticks it.
		'offsite_allowed' => (bool) get_post_meta( $id, 'thr_offsite_ok', true ),
	);
}

/**
 * Build the package for an article.
 *
 * @param int $post_id Post ID.
 * @return array|null
 */
function thr_distribute_package( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || ! thr_distribute_handles( $post ) ) {
		return null;
	}

	$canonical = 'publish' === $post->post_status ? get_permalink( $post ) : '';
	$html      = do_shortcode( do_blocks( $post->post_content ) );
	if ( ! has_blocks( $post->post_content ) ) {
		$html = wpautop( $html );
	}
	$plain   = thr_distribute_plain_text( $html );
	$words   = str_word_count( wp_strip_all_tags( $plain ) );
	$dek     = (string) get_post_meta( $post->ID, 'thr_dek', true );
	$excerpt = has_excerpt( $post ) ? wp_strip_all_tags( $post->post_excerpt ) : '';
	$summary = $excerpt ? $excerpt : ( $dek ? $dek : wp_trim_words( $plain, 40, '…' ) );

	$tracked = '';
	if ( $canonical && thr_distribute_settings()['utm'] ) {
		$tracked = $canonical . ( false === strpos( $canonical, '?' ) ? '?' : '&' ) . 'utm_source={platform}&utm_medium=distribution&utm_campaign=thr-distribute';
	}

	$package = array(
		'schema'        => THR_DISTRIBUTE_PACKAGE_SCHEMA,
		'id'            => thr_distribute_package_id( $post->ID ),
		'version'       => (int) get_post_meta( $post->ID, '_thr_dist_version', true ),
		'site'          => array(
			'id'   => thr_distribute_site_id(),
			'name' => get_bloginfo( 'name' ),
			'url'  => home_url( '/' ),
		),
		'post_id'       => (int) $post->ID,
		'type'          => $post->post_type,
		'status'        => $post->post_status,
		'published_at'  => 'publish' === $post->post_status ? get_post_time( 'c', true, $post ) : null,
		'modified_at'   => get_post_modified_time( 'c', true, $post ),
		'links'         => array(
			'canonical'        => $canonical,
			'short'            => $canonical ? (string) wp_get_shortlink( $post->ID ) : '',
			// Replace {platform} with e.g. youtube, so the site's analytics show where readers came from.
			'tracked_template' => $tracked,
		),
		'article'       => array(
			'title'        => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
			'kicker'       => (string) get_post_meta( $post->ID, 'thr_kicker', true ),
			'dek'          => $dek,
			'summary'      => $summary,
			'article_type' => (string) get_post_meta( $post->ID, 'thr_article_type', true ),
			'authors'      => thr_distribute_authors( $post ),
			'categories'   => thr_distribute_terms( $post->ID, 'category' ),
			'verticals'    => thr_distribute_terms( $post->ID, 'vertical' ),
			'tags'         => thr_distribute_terms( $post->ID, 'post_tag' ),
			'breaking'     => (bool) get_post_meta( $post->ID, 'thr_breaking', true ),
			'sponsored'    => array(
				'is_sponsored' => (bool) get_post_meta( $post->ID, 'thr_sponsored', true ),
				'name'         => (string) get_post_meta( $post->ID, 'thr_sponsored_name', true ),
				'url'          => (string) get_post_meta( $post->ID, 'thr_sponsored_url', true ),
			),
		),
		'text'          => array(
			'html'            => $html,
			'plain'           => $plain,
			// The exact words read aloud. THR Audio replaces this with its script; it doubles as the transcript.
			'script'          => $plain,
			'words'           => $words,
			'reading_minutes' => max( 1, (int) ceil( $words / 230 ) ),
		),
		'images'        => array(
			'featured' => thr_distribute_image( (int) get_post_thumbnail_id( $post ) ),
		),
		'audio'         => null,
		'distributions' => thr_distribute_receipts_for( thr_distribute_package_id( $post->ID ) ),
	);

	/**
	 * Add to the package. Add new keys; don't rename or remove existing ones.
	 *
	 * @param array   $package Package.
	 * @param WP_Post $post    Article.
	 */
	return apply_filters( 'thr_distribute_package', $package, $post );
}

/**
 * Sections whose content changed since the last event, and bump the version when any did.
 *
 * @param int   $post_id Post ID.
 * @param array $package Freshly built package.
 * @return string[] Changed section names.
 */
function thr_distribute_track_changes( $post_id, &$package ) {
	$sections = array( 'article', 'text', 'images', 'links', 'audio' );
	$old      = get_post_meta( $post_id, '_thr_dist_hashes', true );
	$old      = is_array( $old ) ? $old : array();
	$new      = array();
	$changed  = array();

	foreach ( $sections as $section ) {
		$new[ $section ] = md5( (string) wp_json_encode( isset( $package[ $section ] ) ? $package[ $section ] : null ) );
		if ( ! isset( $old[ $section ] ) || $old[ $section ] !== $new[ $section ] ) {
			$changed[] = $section;
		}
	}
	if ( $changed ) {
		$package['version'] = (int) get_post_meta( $post_id, '_thr_dist_version', true ) + 1;
		update_post_meta( $post_id, '_thr_dist_version', $package['version'] );
		update_post_meta( $post_id, '_thr_dist_hashes', $new );
	}
	update_post_meta( $post_id, '_thr_dist_changed', time() );
	return $changed;
}
