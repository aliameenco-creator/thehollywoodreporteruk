<?php
/**
 * Shopping With THR: a double-bordered box of four stories from the Shopping section.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ids = thr_module_posts( array( 'source' => 'section', 'term' => 'shopping', 'posts_per_page' => 4 ) );
if ( ! $ids ) {
	return;
}
$term = get_category_by_slug( 'shopping' );
?>
<section class="thr-boxed">
	<?php thr_module_head( __( 'Shopping With THR', 'thr-theme' ), $term ? get_category_link( $term ) : '' ); ?>
	<div class="thr-boxed__grid">
		<?php
		foreach ( $ids as $id ) {
			thr_story_card( $id, array( 'mod' => 'row', 'image' => 'thr-card-s', 'meta' => false ) );
		}
		?>
	</div>
</section>
