<?php
/**
 * WP-CLI Commands for THR:
 * wp thr seed [--only=structure]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

class THR_CLI_Command {

	public function seed( $args, $assoc_args ) {
		WP_CLI::line( 'Seeding THR UK structural inventory from site-structure.json...' );

		$result = THR_Structure_Seeder::seed_structure();

		if ( $result['success'] ) {
			$r = $result['report'];
			WP_CLI::success( 'Structure seed completed successfully!' );
			WP_CLI::line( sprintf( '- Categories: %d created, %d existed', $r['categories_created'], $r['categories_skipped'] ) );
			WP_CLI::line( sprintf( '- Topics: %d created, %d existed', $r['topics_created'], $r['topics_skipped'] ) );
			WP_CLI::line( sprintf( '- Verticals: %d created, %d existed', $r['verticals_created'], $r['verticals_skipped'] ) );
			WP_CLI::line( sprintf( '- Video Categories: %d created, %d existed', $r['vcategories_created'], $r['vcategories_skipped'] ) );
			WP_CLI::line( sprintf( '- Pages: %d created, %d existed', $r['pages_created'], $r['pages_skipped'] ) );
		} else {
			WP_CLI::error( $result['message'] );
		}
	}

	/**
	 * Add or remove the fictional demo articles.
	 *
	 * ## OPTIONS
	 *
	 * [--no-images]
	 * : Skip downloading placeholder featured images.
	 *
	 * [--delete]
	 * : Permanently delete all demo articles and images instead.
	 *
	 * ## EXAMPLES
	 *
	 *     wp thr demo
	 *     wp thr demo --delete
	 */
	public function demo( $args, $assoc_args ) {
		if ( ! empty( $assoc_args['delete'] ) ) {
			$result = THR_Demo_Seeder::purge();
		} else {
			// No web request time limit on the CLI, so allow a generous budget.
			$result = THR_Demo_Seeder::seed( WP_CLI\Utils\get_flag_value( $assoc_args, 'images', true ), 600 );
		}

		if ( $result['success'] ) {
			WP_CLI::success( $result['message'] );
		} else {
			WP_CLI::error( $result['message'] );
		}
	}
}

WP_CLI::add_command( 'thr', 'THR_CLI_Command' );
