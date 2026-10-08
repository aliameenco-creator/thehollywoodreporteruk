<?php
/**
 * Navigation data: mega menu columns, section bar, footer columns, social links.
 *
 * Each menu reads from its registered nav menu location when one is assigned
 * (Appearance → Menus) and otherwise falls back to the THR default structure,
 * so the site works before any menu is configured.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default mega menu columns, mirroring the THR menu.
 *
 * @return array[] List of columns: label, url, children[ label, url ].
 */
function thr_default_mega_menu() {
	$newsletters = '/newsletters/';

	return array(
		array(
			'label'    => __( 'Newsletters', 'thr-theme' ),
			'url'      => $newsletters,
			'children' => array(
				array( __( 'Today In Entertainment', 'thr-theme' ), $newsletters ),
				array( __( 'Weekender', 'thr-theme' ), $newsletters ),
				array( __( 'Feinberg Forecast', 'thr-theme' ), $newsletters ),
				array( __( 'Heat Vision', 'thr-theme' ), $newsletters ),
				array( __( 'Now See This', 'thr-theme' ), $newsletters ),
				array( __( 'London Calling', 'thr-theme' ), $newsletters ),
				array( __( 'Loose Threads', 'thr-theme' ), $newsletters ),
			),
		),
		array(
			'label'    => __( 'News', 'thr-theme' ),
			'url'      => '/c/news/',
			'children' => array(
				array( __( 'Latest News', 'thr-theme' ), '/c/news/general-news/' ),
				array( __( 'THR Cover Stories', 'thr-theme' ), '/t/thr-cover-story/' ),
				array( __( 'THR Investigates', 'thr-theme' ), '/t/thr-investigates/' ),
				array( __( 'Culture & Politics', 'thr-theme' ), '/c/news/culture-politics/' ),
				array( __( 'Obituaries', 'thr-theme' ), '/t/obituaries/' ),
				array( __( 'LA/Local', 'thr-theme' ), '/c/news/la-local/' ),
			),
		),
		array(
			'label'    => __( 'Film', 'thr-theme' ),
			'url'      => '/c/movies/',
			'children' => array(
				array( __( 'Box Office', 'thr-theme' ), '/t/box-office/' ),
				array( __( 'Heat Vision', 'thr-theme' ), '/e/heat-vision/' ),
				array( __( 'News', 'thr-theme' ), '/c/movies/movie-news/' ),
				array( __( 'Features', 'thr-theme' ), '/c/movies/movie-features/' ),
				array( __( 'Reviews', 'thr-theme' ), '/c/movies/movie-reviews/' ),
			),
		),
		array(
			'label'    => __( 'TV', 'thr-theme' ),
			'url'      => '/c/tv/',
			'children' => array(
				array( __( 'Reviews', 'thr-theme' ), '/c/tv/tv-reviews/' ),
				array( __( 'Premiere Dates', 'thr-theme' ), '/t/premiere-dates/' ),
				array( __( 'Ratings', 'thr-theme' ), '/t/tv-ratings/' ),
				array( __( 'The Fien Print', 'thr-theme' ), '/e/the-fien-print/' ),
				array( __( 'Live Feed', 'thr-theme' ), '/e/live-feed/' ),
			),
		),
		array(
			'label'    => __( 'Business', 'thr-theme' ),
			'url'      => '/c/business/',
			'children' => array(
				array( __( 'THR, Esq', 'thr-theme' ), '/e/thr-esq/' ),
				array( __( 'Production News / Incentives', 'thr-theme' ), '/t/film-tv-tax-credits/' ),
				array( __( 'Unions / Labor', 'thr-theme' ), '/t/labor/' ),
				array( __( 'Signings / Representation', 'thr-theme' ), '/t/representation/' ),
			),
		),
		array(
			'label'    => __( 'Music', 'thr-theme' ),
			'url'      => '/c/music/',
			'children' => array(
				array( __( 'Music News', 'thr-theme' ), '/c/music/music-news/' ),
				array( __( 'Music Industry News', 'thr-theme' ), '/c/music/music-industry-news/' ),
				array( __( 'Music Features', 'thr-theme' ), '/c/music/music-features/' ),
				array( __( 'Film and TV Music News', 'thr-theme' ), '/c/music/film-tv-music-news/' ),
				array( __( 'Grammys', 'thr-theme' ), '/t/grammys/' ),
				array( __( 'K-Pop', 'thr-theme' ), '/t/k-pop/' ),
				array( __( 'Country Music', 'thr-theme' ), '/t/country-music/' ),
			),
		),
		array(
			'label'    => __( 'Awards', 'thr-theme' ),
			'url'      => '/t/awards/',
			'children' => array(
				array( __( 'The Race', 'thr-theme' ), '/e/the-race/' ),
				array( __( 'Feinberg Forecast', 'thr-theme' ), '/t/feinberg-forecast/' ),
				array( __( 'Awards Chatter Podcast', 'thr-theme' ), '/t/awards-chatter-podcast/' ),
				array( __( 'News', 'thr-theme' ), '/t/awards/' ),
			),
		),
		array(
			'label'    => __( 'Lifestyle', 'thr-theme' ),
			'url'      => '/c/lifestyle/',
			'children' => array(
				array( __( 'Next Big Thing', 'thr-theme' ), '/t/next-big-thing/' ),
				array( __( 'Beyond the Book', 'thr-theme' ), '/t/beyond-the-book/' ),
				array( __( 'Arts', 'thr-theme' ), '/c/lifestyle/arts/' ),
				array( __( 'Style', 'thr-theme' ), '/c/lifestyle/style/' ),
				array( __( 'Shopping', 'thr-theme' ), '/c/lifestyle/shopping/' ),
				array( __( 'Real Estate', 'thr-theme' ), '/c/lifestyle/real-estate/' ),
				array( __( 'Rambling Reporter', 'thr-theme' ), '/e/rambling-reporter/' ),
			),
		),
		array(
			'label'    => __( 'More Essentials', 'thr-theme' ),
			'url'      => '',
			'children' => array(
				array( __( 'International News', 'thr-theme' ), '/t/international/' ),
				array( __( 'Video', 'thr-theme' ), '/video/' ),
				array( __( 'Lists', 'thr-theme' ), '/lists/' ),
				array( __( 'THR Podcasts', 'thr-theme' ), '/t/thr-podcasts/' ),
				array( __( 'Featured Voices', 'thr-theme' ), '/t/featured-voices/' ),
			),
		),
	);
}

/**
 * Default section bar links (header second row on desktop).
 *
 * @return array[] List of [ label, url ].
 */
function thr_default_section_bar() {
	return array(
		array( __( 'News', 'thr-theme' ), '/c/news/' ),
		array( __( 'Film', 'thr-theme' ), '/c/movies/' ),
		array( __( 'TV', 'thr-theme' ), '/c/tv/' ),
		array( __( 'Music', 'thr-theme' ), '/c/music/' ),
		array( __( 'Awards', 'thr-theme' ), '/t/awards/' ),
		array( __( 'Lifestyle', 'thr-theme' ), '/c/lifestyle/' ),
		array( __( 'Business', 'thr-theme' ), '/c/business/' ),
		array( __( 'International', 'thr-theme' ), '/t/international/' ),
		array( __( 'Covers', 'thr-theme' ), '/t/thr-cover-story/' ),
		array( __( 'Lists', 'thr-theme' ), '/lists/' ),
		array( __( 'Video', 'thr-theme' ), '/video/' ),
	);
}

/**
 * Resolve a menu URL: site-relative paths become absolute, full URLs pass through.
 *
 * @param string $url Path or URL.
 * @return string
 */
function thr_menu_url( $url ) {
	if ( '' === $url ) {
		return '';
	}
	if ( 0 === strpos( $url, '/' ) ) {
		return home_url( $url );
	}
	return $url;
}

/**
 * Build a two-level tree from the menu assigned to a location.
 *
 * @param string $location Registered nav menu location.
 * @return array[]|null Columns in the same shape as thr_default_mega_menu(), or null when unassigned.
 */
function thr_get_location_tree( $location ) {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $location ] ) ) {
		return null;
	}

	$items = wp_get_nav_menu_items( $locations[ $location ] );
	if ( empty( $items ) ) {
		return null;
	}

	$tree = array();
	foreach ( $items as $item ) {
		if ( 0 === (int) $item->menu_item_parent ) {
			$tree[ $item->ID ] = array(
				'label'    => $item->title,
				'url'      => '#' === $item->url ? '' : $item->url,
				'children' => array(),
			);
		}
	}
	foreach ( $items as $item ) {
		$parent = (int) $item->menu_item_parent;
		if ( $parent && isset( $tree[ $parent ] ) ) {
			$tree[ $parent ]['children'][] = array( $item->title, $item->url );
		}
	}

	return array_values( $tree );
}

/**
 * Mega menu columns (assigned "mega" menu, else THR default).
 *
 * @return array[]
 */
function thr_get_mega_menu() {
	$tree = thr_get_location_tree( 'mega' );
	return null !== $tree ? $tree : thr_default_mega_menu();
}

/**
 * Section bar links (assigned "primary" menu, else THR default).
 *
 * @return array[] List of [ label, url ].
 */
function thr_get_section_bar() {
	$tree = thr_get_location_tree( 'primary' );
	if ( null === $tree ) {
		return thr_default_section_bar();
	}
	return array_map(
		function ( $col ) {
			return array( $col['label'], $col['url'] );
		},
		$tree
	);
}

/**
 * Footer link columns.
 *
 * @return array[]
 */
function thr_get_footer_columns() {
	$magazine = get_option( 'thr_magazine_url' ) ?: '/contact/';

	return array(
		array(
			'label'    => __( 'Subscriber Support', 'thr-theme' ),
			'children' => array(
				array( __( 'Get the Magazine', 'thr-theme' ), $magazine ),
				array( __( 'Customer Service', 'thr-theme' ), '/contact/' ),
				array( __( 'Back Issues', 'thr-theme' ), '/contact/' ),
			),
		),
		array(
			'label'    => __( 'The Hollywood Reporter UK', 'thr-theme' ),
			'children' => array(
				array( __( 'About Us', 'thr-theme' ), '/masthead/' ),
				array( __( 'Careers', 'thr-theme' ), '/contact/' ),
				array( __( 'Contact Us', 'thr-theme' ), '/contact/' ),
				array( __( 'Accessibility', 'thr-theme' ), '/accessibility/' ),
			),
		),
		array(
			'label'    => __( 'Legal', 'thr-theme' ),
			'children' => array(
				array( __( 'Terms of Use', 'thr-theme' ), '/terms-of-use/' ),
				array( __( 'Privacy Policy', 'thr-theme' ), '/privacy-policy/' ),
				array( __( 'Cookie Policy', 'thr-theme' ), '/cookie-policy/' ),
			),
		),
	);
}

/**
 * Social profiles, in THR order. URLs come from Settings → THR Settings.
 *
 * @return array[] List of [ icon key, label, url ].
 */
function thr_get_social_links() {
	$networks = array(
		'facebook'  => array( 'facebook', 'Facebook' ),
		'instagram' => array( 'instagram', 'Instagram' ),
		'linkedin'  => array( 'linkedin', 'LinkedIn' ),
		'threads'   => array( 'threads', 'Threads' ),
		'tiktok'    => array( 'tiktok', 'TikTok' ),
		'twitter'   => array( 'x', 'X' ),
		'youtube'   => array( 'youtube', 'YouTube' ),
	);

	$links = array();
	foreach ( $networks as $option => $meta ) {
		$links[] = array( $meta[0], $meta[1], get_option( 'thr_social_' . $option ) ?: '#' );
	}
	return $links;
}

/**
 * Newsletter form action: the configured signup endpoint, else the Newsletters page.
 *
 * @return string
 */
function thr_newsletter_action() {
	return get_option( 'thr_newsletter_url' ) ?: home_url( '/newsletters/' );
}

/**
 * Newsletter form method. Without an external provider the footer/menu boxes
 * hand the email to the Newsletters page (GET, pre-filled), where readers pick
 * newsletters and consent; that keeps nonces off cached pages.
 *
 * @return string
 */
function thr_newsletter_method() {
	return get_option( 'thr_newsletter_url' ) ? 'post' : 'get';
}
