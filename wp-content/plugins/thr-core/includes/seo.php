<?php
/**
 * SEO glue. Yoast SEO (free) is the recommended plugin and owns titles, meta,
 * canonicals, XML sitemaps, schema and llms.txt. This file only adds what a
 * news site needs on top, and a minimal fallback when no SEO plugin is active:
 *
 * - Google News sitemap at /news-sitemap.xml (Yoast's is a paid add-on).
 * - The dek as the description wherever an editor left Yoast's description empty.
 * - Articles typed NewsArticle in Yoast's schema graph.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether a full SEO plugin is handling meta tags and schema.
 *
 * @return bool
 */
function thr_has_seo_plugin() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' );
}

/**
 * Description for the current singular story: the dek, else the excerpt.
 *
 * @return string
 */
function thr_seo_description() {
	if ( ! is_singular() ) {
		return '';
	}
	$post_id = get_queried_object_id();
	$dek     = (string) get_post_meta( $post_id, 'thr_dek', true );
	$text    = '' !== $dek ? $dek : wp_strip_all_tags( get_the_excerpt( $post_id ) );
	return wp_html_excerpt( $text, 160, '…' );
}

/* ---------- Yoast integration ---------- */

/**
 * Use the dek when Yoast has no description for a story.
 *
 * @param string $description Description from Yoast.
 * @return string
 */
function thr_yoast_description_fallback( $description ) {
	return ( '' === trim( (string) $description ) && is_singular() ) ? thr_seo_description() : $description;
}
add_filter( 'wpseo_metadesc', 'thr_yoast_description_fallback' );
add_filter( 'wpseo_opengraph_desc', 'thr_yoast_description_fallback' );
add_filter( 'wpseo_twitter_description', 'thr_yoast_description_fallback' );

/**
 * Mark stories as NewsArticle in Yoast's schema graph (Google Top Stories eligibility).
 *
 * @param string|string[] $type Article type(s).
 * @return string|string[]
 */
function thr_yoast_news_article_type( $type ) {
	if ( is_singular( array( 'post', 'thr_list', 'thr_gallery', 'thr_video' ) ) ) {
		return 'NewsArticle';
	}
	return $type;
}
add_filter( 'wpseo_schema_article_type', 'thr_yoast_news_article_type' );

/* ---------- Fallback meta when no SEO plugin is active ---------- */

function thr_fallback_meta_tags() {
	if ( thr_has_seo_plugin() || is_admin() ) {
		return;
	}

	$description = is_singular() ? thr_seo_description() : get_bloginfo( 'description' );
	$title       = wp_get_document_title();
	$url         = is_singular() ? get_permalink() : home_url( add_query_arg( array() ) );

	if ( $description ) {
		echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
	}
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";
	echo '<meta property="og:type" content="' . ( is_singular( 'post' ) ? 'article' : 'website' ) . '" />' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
	if ( $description ) {
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
	}
	if ( is_singular() && has_post_thumbnail() ) {
		echo '<meta property="og:image" content="' . esc_url( get_the_post_thumbnail_url( null, 'full' ) ) . '" />' . "\n";
	}
	echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
}
add_action( 'wp_head', 'thr_fallback_meta_tags', 2 );

/* ---------- Google News sitemap ---------- */

function thr_news_sitemap_rewrite() {
	add_rewrite_rule( '^news-sitemap\.xml$', 'index.php?thr_news_sitemap=1', 'top' );
}
add_action( 'init', 'thr_news_sitemap_rewrite' );

/**
 * Register the sitemap query var.
 *
 * @param string[] $vars Query vars.
 * @return string[]
 */
function thr_news_sitemap_query_var( $vars ) {
	$vars[] = 'thr_news_sitemap';
	return $vars;
}
add_filter( 'query_vars', 'thr_news_sitemap_query_var' );

/**
 * Rewrite rules only rebuild on activation, but plugin updates don't activate;
 * flush once whenever the plugin version changes so new routes work.
 */
function thr_maybe_flush_rewrites() {
	if ( get_option( 'thr_rewrite_version' ) !== THR_CORE_VERSION ) {
		flush_rewrite_rules( false );
		update_option( 'thr_rewrite_version', THR_CORE_VERSION );
	}
}
add_action( 'init', 'thr_maybe_flush_rewrites', 99 );

function thr_render_news_sitemap() {
	if ( ! get_query_var( 'thr_news_sitemap' ) ) {
		return;
	}

	$xml = get_transient( 'thr_news_sitemap' );
	if ( false === $xml ) {
		// Google News only reads articles from the last two days, at most 1,000.
		$posts = get_posts(
			array(
				'post_type'        => 'post',
				'post_status'      => 'publish',
				'posts_per_page'   => 1000,
				'date_query'       => array( array( 'after' => '2 days ago' ) ),
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		);

		$name = get_bloginfo( 'name' );
		$lang = substr( get_locale(), 0, 2 );
		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";
		foreach ( $posts as $post ) {
			$xml .= "\t<url>\n";
			$xml .= "\t\t<loc>" . esc_url( get_permalink( $post ) ) . "</loc>\n";
			$xml .= "\t\t<news:news>\n";
			$xml .= "\t\t\t<news:publication><news:name>" . esc_html( $name ) . '</news:name><news:language>' . esc_html( $lang ) . "</news:language></news:publication>\n";
			$xml .= "\t\t\t<news:publication_date>" . esc_html( get_post_time( 'c', true, $post ) ) . "</news:publication_date>\n";
			$xml .= "\t\t\t<news:title>" . esc_html( wp_strip_all_tags( get_the_title( $post ) ) ) . "</news:title>\n";
			$xml .= "\t\t</news:news>\n";
			$xml .= "\t</url>\n";
		}
		$xml .= "</urlset>\n";
		set_transient( 'thr_news_sitemap', $xml, 10 * MINUTE_IN_SECONDS );
	}

	header( 'Content-Type: application/xml; charset=UTF-8' );
	header( 'X-Robots-Tag: noindex, follow' );
	echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above.
	exit;
}
add_action( 'template_redirect', 'thr_render_news_sitemap', 0 );

/**
 * Refresh the news sitemap when a story is published or unpublished.
 *
 * @param string  $new_status New status.
 * @param string  $old_status Old status.
 * @param WP_Post $post       Post.
 */
function thr_flush_news_sitemap( $new_status, $old_status, $post ) {
	if ( 'post' === $post->post_type && ( 'publish' === $new_status || 'publish' === $old_status ) ) {
		delete_transient( 'thr_news_sitemap' );
	}
}
add_action( 'transition_post_status', 'thr_flush_news_sitemap', 10, 3 );

/**
 * List the news sitemap in robots.txt (Yoast adds its own sitemap index line).
 *
 * @param string $output robots.txt contents.
 * @return string
 */
function thr_news_sitemap_robots( $output ) {
	return $output . "\nSitemap: " . home_url( '/news-sitemap.xml' ) . "\n";
}
add_filter( 'robots_txt', 'thr_news_sitemap_robots' );
