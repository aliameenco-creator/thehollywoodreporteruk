<?php
/**
 * Highlights from the Magazine: the current cover + Subscribe, then cover-story cards.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ids = thr_module_posts( array( 'source' => 'topic', 'term' => 'thr-cover-story', 'posts_per_page' => 4 ) );
if ( ! $ids ) {
	return;
}
$cover = get_option( 'thr_magazine_cover_url' );
$sub   = get_option( 'thr_magazine_url' ) ?: home_url( '/newsletters/' );
?>
<section class="thr-magazine">
	<?php thr_module_head( __( 'Highlights from the Magazine', 'thr-theme' ) ); ?>
	<div class="thr-magazine__grid<?php echo $cover ? ' has-cover' : ''; ?>">
		<?php if ( $cover ) : ?>
			<div class="thr-magazine__cover">
				<a href="<?php echo esc_url( $sub ); ?>"><img src="<?php echo esc_url( $cover ); ?>" alt="<?php esc_attr_e( 'Latest issue of The Hollywood Reporter UK', 'thr-theme' ); ?>" loading="lazy" /></a>
				<a class="thr-button thr-magazine__subscribe" href="<?php echo esc_url( $sub ); ?>"><?php esc_html_e( 'Subscribe', 'thr-theme' ); ?></a>
			</div>
		<?php endif; ?>
		<?php
		foreach ( $ids as $id ) {
			thr_story_card( $id, array( 'mod' => 'magazine', 'image' => 'thr-avatar-m', 'meta' => 'section', 'by' => true ) );
		}
		?>
	</div>
</section>
