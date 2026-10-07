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

function thr_render_importer_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$feedback = '';

	if ( isset( $_POST['thr_seed_structure_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['thr_seed_structure_nonce'] ), 'thr_seed_structure_action' ) ) {
		$result = THR_Structure_Seeder::seed_structure();
		if ( $result['success'] ) {
			$r = $result['report'];
			$feedback = sprintf(
				'<div class="notice notice-success is-dismissible"><p><strong>%s</strong> Created: %d categories, %d topics, %d verticals, %d vcategories, %d pages. (Skipped: %d categories, %d topics, %d verticals, %d pages already existed).</p></div>',
				esc_html( $result['message'] ),
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
		} else {
			$feedback = sprintf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $result['message'] ) );
		}
	}

	$cat_count  = wp_count_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) );
	$tag_count  = wp_count_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) );
	$vert_count = wp_count_terms( array( 'taxonomy' => 'vertical', 'hide_empty' => false ) );
	$vcat_count = wp_count_terms( array( 'taxonomy' => 'vcategory', 'hide_empty' => false ) );
	$page_count = wp_count_posts( 'page' )->publish + wp_count_posts( 'page' )->draft;
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'THR Importer & Structural Seeder', 'thr-importer' ); ?></h1>
		<p><?php esc_html_e( 'One-click tool to configure permalinks, taxonomy bases, 26 parent/child categories, 25 topics, 7 verticals, 12 video categories, and static pages matching the THR specification.', 'thr-importer' ); ?></p>

		<?php echo $feedback; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div class="card" style="max-width:700px; padding:20px; margin-top:20px;">
			<h2><?php esc_html_e( 'Current Database Inventory', 'thr-importer' ); ?></h2>
			<table class="widefat striped" style="margin-bottom:20px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Taxonomy / Post Type', 'thr-importer' ); ?></th>
						<th><?php esc_html_e( 'Count in Database', 'thr-importer' ); ?></th>
						<th><?php esc_html_e( 'Expected from Manifest', 'thr-importer' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Sections (Categories)', 'thr-importer' ); ?></strong></td>
						<td><?php echo (int) $cat_count; ?></td>
						<td>26 (6 parents + 20 children)</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Topics (Tags)', 'thr-importer' ); ?></strong></td>
						<td><?php echo (int) $tag_count; ?></td>
						<td>25</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Verticals (/e/)', 'thr-importer' ); ?></strong></td>
						<td><?php echo (int) $vert_count; ?></td>
						<td>7</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Video Categories (/vcategory/)', 'thr-importer' ); ?></strong></td>
						<td><?php echo (int) $vcat_count; ?></td>
						<td>12</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Pages', 'thr-importer' ); ?></strong></td>
						<td><?php echo (int) $page_count; ?></td>
						<td>11</td>
					</tr>
				</tbody>
			</table>

			<form method="post" action="">
				<?php wp_nonce_field( 'thr_seed_structure_action', 'thr_seed_structure_nonce' ); ?>
				<button type="submit" class="button button-primary button-hero">
					<?php esc_html_e( 'Seed / Sync Site Structure Now', 'thr-importer' ); ?>
				</button>
			</form>
		</div>
	</div>
	<?php
}
