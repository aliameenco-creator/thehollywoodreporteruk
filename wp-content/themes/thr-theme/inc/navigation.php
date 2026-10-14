<?php
/**
 * Navigation data: section bar, menu drawer, footer columns, social links.
 *
 * Every menu is managed in Appearance → Menus. The THR Importer creates the
 * starter menus (Main Navigation, Footer Menu); after that editors own them.
 * Until a menu is assigned, the section bar and drawer list the site's
 * categories, so nothing here hard-codes the site structure.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
	if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
		return home_url( $url );
	}
	return $url;
}

/**
 * Build a two-level tree from the menu assigned to a location.
 *
 * @param string $location Registered nav menu location.
 * @return array[]|null List of { label, url, children[ [ label, url ] ] }, or null when unassigned or empty.
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
 * Fallback tree before any menu is assigned: top-level categories and their children.
 *
 * @return array[] Same shape as thr_get_location_tree().
 */
function thr_get_category_tree() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'exclude'    => array( (int) get_option( 'default_category' ) ),
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}

	$tree = array();
	foreach ( $terms as $term ) {
		if ( 0 === (int) $term->parent ) {
			$tree[ $term->term_id ] = array(
				'label'    => $term->name,
				'url'      => get_term_link( $term ),
				'children' => array(),
			);
		}
	}
	foreach ( $terms as $term ) {
		if ( isset( $tree[ $term->parent ] ) ) {
			$tree[ $term->parent ]['children'][] = array( $term->name, get_term_link( $term ) );
		}
	}

	return array_values( $tree );
}

/**
 * Section bar items with their dropdown links (Main Navigation menu).
 *
 * @return array[]
 */
function thr_get_section_bar() {
	$tree = thr_get_location_tree( 'primary' );
	return null !== $tree ? $tree : thr_get_category_tree();
}

/**
 * Menu drawer columns: its own menu if one is assigned, else the Main Navigation.
 *
 * @return array[]
 */
function thr_get_mega_menu() {
	$tree = thr_get_location_tree( 'mega' );
	return null !== $tree ? $tree : thr_get_section_bar();
}

/**
 * Footer link columns: top-level items are headings, their children the links.
 *
 * @return array[]
 */
function thr_get_footer_columns() {
	$tree = thr_get_location_tree( 'footer' );
	return null !== $tree ? $tree : array();
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
