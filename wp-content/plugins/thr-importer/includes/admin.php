<?php
/**
 * Admin Screen for THR Importer under Tools -> THR Importer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_importer_admin_menu() {
	add_management_page(
		__( 'THR Site Structure Importer', 'thr-importer' ),
		__( 'THR Importer', 'thr-importer' ),
		'manage_options',
		'thr-importer',
		'thr_render_importer_page'
	);
}
add_action( 'admin_menu', 'thr_importer_admin_menu' );

/**
 * Run the requested importer action (POST + nonce + capability) and return a notice.
 *
 * @return string Notice HTML (already escaped).
 */
function thr_importer_handle_action() {
	if ( empty( $_POST['thr_importer_action'] ) ) {
		return '';
	}

	$action = sanitize_key( wp_unslash( $_POST['thr_importer_action'] ) );
	check_admin_referer( 'thr_importer_' . $action, 'thr_importer_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		return '';
	}

	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 120 );
	}

	switch ( $action ) {
		case 'seed_structure':
			$result = THR_Structure_Seeder::seed_structure();
			if ( $result['success'] ) {
				$r                 = $result['report'];
				$result['message'] = sprintf(
					'%s Created: %d categories, %d topics, %d verticals, %d video categories, %d pages. (Already existed: %d categories, %d topics, %d verticals, %d pages.)',
					$result['message'],
					$r['categories_created'],
					$r['topics_created'],
					$r['verticals_created'],
					$r['vcategories_created'],
					$r['pages_created'],
					$r['categories_skipped'],
					$r['topics_skipped'],
					$r['verticals_skipped'],
					$r['pages_skipped']
				);
			}
			break;

		case 'seed_demo':
			$result = THR_Demo_Seeder::seed( ! empty( $_POST['thr_demo_images'] ) );
			break;

		case 'purge_demo':
			$result = THR_Demo_Seeder::purge();
			break;

		default:
			return '';
	}

	return sprintf(
		'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
		$result['success'] ? 'success' : 'error',
		esc_html( $result['message'] )
	);
}

function thr_render_importer_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$feedback = thr_importer_handle_action();

	$manifest = json_decode( (string) file_get_contents( THR_IMPORTER_PATH . 'data/site-structure.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local bundled file.
	$expected = isset( $manifest['counts'] ) ? $manifest['counts'] : array();

	$rows = array(
		array( __( 'Sections (Categories)', 'thr-importer' ), wp_count_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) ), isset( $expected['categories'] ) ? $expected['categories'] : '' ),
		array( __( 'Topics (Tags)', 'thr-importer' ), wp_count_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) ), isset( $expected['topics'] ) ? $expected['topics'] : '' ),
		array( __( 'Verticals (/e/)', 'thr-importer' ), taxonomy_exists( 'vertical' ) ? wp_count_terms( array( 'taxonomy' => 'vertical', 'hide_empty' => false ) ) : 0, isset( $expected['verticals'] ) ? $expected['verticals'] : '' ),
		array( __( 'Video Categories', 'thr-importer' ), taxonomy_exists( 'vcategory' ) ? wp_count_terms( array( 'taxonomy' => 'vcategory', 'hide_empty' => false ) ) : 0, isset( $expected['video_categories'] ) ? $expected['video_categories'] : '' ),
		array( __( 'Pages', 'thr-importer' ), wp_count_posts( 'page' )->publish + wp_count_posts( 'page' )->draft, isset( $expected['pages'] ) ? $expected['pages'] : '' ),
	);
	$demo_count = THR_Demo_Seeder::count();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'THR Importer & Structural Seeder', 'thr-importer' ); ?></h1>

		<?php echo $feedback; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_html() above. ?>

		<?php if ( ! taxonomy_exists( 'vertical' ) ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'THR Core is not active. Activate it before seeding.', 'thr-importer' ); ?></p></div>
		<?php endif; ?>

		<div class="card">
			<h2><?php esc_html_e( '1. Site structure', 'thr-importer' ); ?></h2>
			<p><?php esc_html_e( 'Sets permalinks and creates sections, topics, verticals, video categories and pages. Safe to run again: existing items are kept.', 'thr-importer' ); ?></p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Item', 'thr-importer' ); ?></th>
						<th><?php esc_html_e( 'In database', 'thr-importer' ); ?></th>
						<th><?php esc_html_e( 'Expected', 'thr-importer' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $row[0] ); ?></strong></td>
							<td><?php echo (int) $row[1]; ?></td>
							<td><?php echo esc_html( $row[2] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<form method="post">
				<?php wp_nonce_field( 'thr_importer_seed_structure', 'thr_importer_nonce' ); ?>
				<input type="hidden" name="thr_importer_action" value="seed_structure" />
				<p><?php submit_button( __( 'Seed / Sync Site Structure', 'thr-importer' ), 'primary', 'submit', false ); ?></p>
			</form>
		</div>

		<div class="card">
			<h2><?php esc_html_e( '2. Demo content (for testing)', 'thr-importer' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %d: number of demo articles. */
					esc_html__( 'Adds 24 fictional articles across every section, with placeholder images, so you can check layouts, menus and search. Currently installed: %d.', 'thr-importer' ),
					(int) $demo_count
				);
				?>
			</p>
			<form method="post">
				<?php wp_nonce_field( 'thr_importer_seed_demo', 'thr_importer_nonce' ); ?>
				<input type="hidden" name="thr_importer_action" value="seed_demo" />
				<p><label><input type="checkbox" name="thr_demo_images" value="1" checked /> <?php esc_html_e( 'Download placeholder images (slower; click again if it asks you to continue)', 'thr-importer' ); ?></label></p>
				<p><?php submit_button( __( 'Add Demo Articles', 'thr-importer' ), 'secondary', 'submit', false ); ?></p>
			</form>
			<?php if ( $demo_count ) : ?>
				<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Permanently delete all demo articles and their images?', 'thr-importer' ) ); ?>');">
					<?php wp_nonce_field( 'thr_importer_purge_demo', 'thr_importer_nonce' ); ?>
					<input type="hidden" name="thr_importer_action" value="purge_demo" />
					<p><?php submit_button( __( 'Delete Demo Content', 'thr-importer' ), 'delete', 'submit', false ); ?></p>
				</form>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
