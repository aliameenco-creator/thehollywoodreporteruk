<?php
/**
 * Reviews (Movies + TV columns) beside Featured Voices.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$columns = array();
foreach ( array( 'movie-reviews' => __( 'Movies', 'thr-theme' ), 'tv-reviews' => __( 'TV', 'thr-theme' ) ) as $slug => $label ) {
	$ids = thr_module_posts( array( 'source' => 'section', 'term' => $slug, 'posts_per_page' => 5 ) );
	if ( $ids ) {
		$term      = get_category_by_slug( $slug );
		$columns[] = array( $label, $term ? get_category_link( $term ) : '', $ids );
	}
}

$voices = thr_module_posts( array( 'source' => 'topic', 'term' => 'featured-voices', 'posts_per_page' => 4 ) );

if ( ! $columns && ! $voices ) {
	return;
}
?>
<section class="thr-split">
	<div class="thr-split__main">
		<?php if ( $columns ) : ?>
			<div class="thr-mhead thr-mhead--large">
				<h2 class="thr-mhead__title"><?php esc_html_e( 'Reviews', 'thr-theme' ); ?></h2>
			</div>
			<div class="thr-reviews thr-reviews--<?php echo count( $columns ); ?>">
				<?php foreach ( $columns as $col ) : ?>
					<div class="thr-reviews__col">
						<?php thr_module_head( $col[0], $col[1], 'red' ); ?>
						<?php
						foreach ( $col[2] as $id ) {
							thr_story_card( $id, array( 'mod' => 'review', 'image' => 'thr-avatar-s', 'meta' => false, 'by' => true ) );
						}
						?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $voices ) : ?>
		<aside class="thr-split__rail">
			<?php
			$voices_tag = get_term_by( 'slug', 'featured-voices', 'post_tag' );
			thr_module_head( __( 'Featured Voices', 'thr-theme' ), $voices_tag ? get_term_link( $voices_tag ) : '' );
			?>
			<div class="thr-voices">
				<?php foreach ( $voices as $id ) : ?>
					<?php $author_id = (int) get_post_field( 'post_author', $id ); ?>
					<article class="thr-voice">
						<a href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>" class="thr-voice__photo" tabindex="-1" aria-hidden="true">
							<?php echo get_avatar( $author_id, 65, '', '', array( 'loading' => 'lazy' ) ); ?>
						</a>
						<div>
							<a class="thr-voice__name" href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>"><?php echo esc_html( get_the_author_meta( 'display_name', $author_id ) ); ?></a>
							<h3 class="thr-voice__title"><a href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php echo esc_html( get_the_title( $id ) ); ?></a></h3>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</aside>
	<?php endif; ?>
</section>
