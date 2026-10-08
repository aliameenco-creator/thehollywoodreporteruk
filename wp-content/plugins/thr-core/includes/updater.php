<?php
/**
 * GitHub release updater for the THR theme and plugins.
 *
 * WordPress pulls updates itself (Dashboard > Updates); nothing connects to the
 * host over SSH. Each GitHub release tagged vX.Y.Z carries three zip assets:
 * thr-theme.zip, thr-core.zip and thr-importer.zip (built by the release workflow).
 *
 * Optional wp-config.php constants:
 *   THR_GITHUB_REPO  owner/name, defaults to the project repository.
 *   THR_GITHUB_TOKEN read-only token, only needed if the repository is private.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GitHub repository slug (owner/name).
 *
 * @return string
 */
function thr_updater_repo() {
	return defined( 'THR_GITHUB_REPO' ) ? THR_GITHUB_REPO : 'aliameenco-creator/thehollywoodreporteruk';
}

/**
 * Headers for GitHub API calls.
 *
 * @param string $accept Accept header value.
 * @return array
 */
function thr_updater_headers( $accept = 'application/vnd.github+json' ) {
	$headers = array(
		'Accept'     => $accept,
		'User-Agent' => 'thr-updater',
	);
	if ( defined( 'THR_GITHUB_TOKEN' ) && THR_GITHUB_TOKEN ) {
		$headers['Authorization'] = 'Bearer ' . THR_GITHUB_TOKEN;
	}
	return $headers;
}

/**
 * Latest GitHub release, cached for six hours.
 *
 * @param bool $force Bypass the cache.
 * @return array|null { version, notes, url, assets: name => download url }
 */
function thr_updater_latest_release( $force = false ) {
	$cache_key = 'thr_latest_release';
	$cached    = $force ? false : get_transient( $cache_key );
	if ( false !== $cached ) {
		return $cached ?: null;
	}

	$private = defined( 'THR_GITHUB_TOKEN' ) && THR_GITHUB_TOKEN;
	$release = $private ? thr_updater_release_from_api() : thr_updater_release_from_redirect();

	// Cache failures briefly too so a GitHub outage does not slow every admin page.
	set_transient( $cache_key, $release ?: 0, $release ? 6 * HOUR_IN_SECONDS : 15 * MINUTE_IN_SECONDS );
	return $release;
}

/**
 * Public repository: read the newest tag from the github.com/…/releases/latest
 * redirect. Unlike api.github.com this has no 60-requests-per-hour limit per IP,
 * which shared hosting (many sites on one IP) would otherwise hit.
 *
 * @return array|null
 */
function thr_updater_release_from_redirect() {
	$base     = 'https://github.com/' . thr_updater_repo() . '/releases';
	$response = wp_remote_head(
		$base . '/latest',
		array(
			'timeout'     => 5,
			'redirection' => 0,
			'headers'     => array( 'User-Agent' => 'thr-updater' ),
		)
	);
	if ( is_wp_error( $response ) ) {
		return null;
	}

	$location = (string) wp_remote_retrieve_header( $response, 'location' );
	if ( ! preg_match( '#/releases/tag/(v?[0-9][0-9A-Za-z.\-]*)$#', $location, $match ) ) {
		return null; // No release published yet.
	}

	$tag    = $match[1];
	$assets = array();
	foreach ( array( 'thr-theme.zip', 'thr-core.zip', 'thr-importer.zip' ) as $name ) {
		$assets[ $name ] = $base . '/download/' . rawurlencode( $tag ) . '/' . $name;
	}

	return array(
		'version' => ltrim( $tag, 'vV' ),
		'notes'   => '',
		'url'     => $base . '/tag/' . rawurlencode( $tag ),
		'assets'  => $assets,
	);
}

/**
 * Private repository: the REST API with a token (5,000 requests/hour).
 *
 * @return array|null
 */
function thr_updater_release_from_api() {
	$response = wp_remote_get(
		'https://api.github.com/repos/' . thr_updater_repo() . '/releases/latest',
		array(
			'timeout' => 5,
			'headers' => thr_updater_headers(),
		)
	);

	$release = null;
	if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( is_array( $body ) && ! empty( $body['tag_name'] ) ) {
			$assets = array();
			foreach ( (array) ( $body['assets'] ?? array() ) as $asset ) {
				// Private repos need the API asset URL plus the auth header added in thr_updater_download_auth().
				$assets[ $asset['name'] ] = $asset['url'];
			}
			$release = array(
				'version' => ltrim( $body['tag_name'], 'vV' ),
				'notes'   => (string) ( $body['body'] ?? '' ),
				'url'     => (string) ( $body['html_url'] ?? '' ),
				'assets'  => $assets,
			);
		}
	}

	return $release;
}

/**
 * Add THR plugins to the plugin update transient when a newer release exists.
 *
 * @param object $transient update_plugins transient.
 * @return object
 */
function thr_updater_filter_plugins( $transient ) {
	if ( ! is_object( $transient ) ) {
		return $transient;
	}

	$release = thr_updater_latest_release();
	if ( ! $release ) {
		return $transient;
	}

	$plugins = array(
		'thr-core/thr-core.php'         => 'thr-core.zip',
		'thr-importer/thr-importer.php' => 'thr-importer.zip',
	);

	foreach ( $plugins as $file => $asset ) {
		if ( empty( $release['assets'][ $asset ] ) || ! isset( $transient->checked[ $file ] ) ) {
			continue;
		}
		if ( version_compare( $release['version'], $transient->checked[ $file ], '>' ) ) {
			$transient->response[ $file ] = (object) array(
				'slug'        => dirname( $file ),
				'plugin'      => $file,
				'new_version' => $release['version'],
				'url'         => $release['url'],
				'package'     => $release['assets'][ $asset ],
			);
		}
	}

	return $transient;
}
add_filter( 'pre_set_site_transient_update_plugins', 'thr_updater_filter_plugins' );

/**
 * Add the THR theme to the theme update transient when a newer release exists.
 *
 * @param object $transient update_themes transient.
 * @return object
 */
function thr_updater_filter_themes( $transient ) {
	if ( ! is_object( $transient ) || empty( $transient->checked['thr-theme'] ) ) {
		return $transient;
	}

	$release = thr_updater_latest_release();
	if ( ! $release || empty( $release['assets']['thr-theme.zip'] ) ) {
		return $transient;
	}

	if ( version_compare( $release['version'], $transient->checked['thr-theme'], '>' ) ) {
		$transient->response['thr-theme'] = array(
			'theme'       => 'thr-theme',
			'new_version' => $release['version'],
			'url'         => $release['url'],
			'package'     => $release['assets']['thr-theme.zip'],
		);
	}

	return $transient;
}
add_filter( 'pre_set_site_transient_update_themes', 'thr_updater_filter_themes' );

/**
 * Authenticate package downloads from a private repository.
 *
 * @param array  $args HTTP request args.
 * @param string $url  Request URL.
 * @return array
 */
function thr_updater_download_auth( $args, $url ) {
	$prefix = 'https://api.github.com/repos/' . thr_updater_repo() . '/releases/assets/';
	if ( defined( 'THR_GITHUB_TOKEN' ) && THR_GITHUB_TOKEN && 0 === strpos( $url, $prefix ) ) {
		$args['headers'] = array_merge( (array) ( $args['headers'] ?? array() ), thr_updater_headers( 'application/octet-stream' ) );
	}
	return $args;
}
add_filter( 'http_request_args', 'thr_updater_download_auth', 10, 2 );

/**
 * Drop the cached release when WordPress is told to re-check for updates.
 */
function thr_updater_clear_cache() {
	delete_transient( 'thr_latest_release' );
}
add_action( 'load-update-core.php', 'thr_updater_clear_cache' );
