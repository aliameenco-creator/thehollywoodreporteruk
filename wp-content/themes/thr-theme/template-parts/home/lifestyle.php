<?php
/**
 * Lifestyle (big framed lead, two small cards, one wide card) beside Podcasts.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$life = thr_module_posts( array( 'source' => 'section', 'term' => 'lifestyle', 'posts_per_page' => 4 ) );

$pods = thr_module_posts( array( 'source' => 'latest', 'article_type' => 'podcast', 'posts_per_page' => 2 ) );
if ( ! $pods ) {
	$pods = thr_module_posts( array( 'source' => 'topic', 'term' => 'awards-chatter-podcast', 'posts_per_page' => 2 ) );
}

if ( ! $life && ! $pods ) {
	return;
}
$life_term = get_category_by_slug( 'lifestyle' );
?>
<section class="thr-split thr-split--ruled">
	<div class="thr-split__main">
		<?php if ( $life ) : ?>
			<?php thr_module_head( __( 'Lifestyle', 'thr-theme' ), $life_term ? get_category_link( $life_term ) : '', '', __( 'How Hollywood Lives', 'thr-theme' ) ); ?>
			<div class="thr-lifestyle">
				<div class="thr-lifestyle__lead">
					<?php thr_story_card( $life[0], array( 'mod' => 'life-lead', 'image' => 'thr-card-m', 'framed' => true, 'meta' => 'section', 'by' => true ) ); ?>
				</div>
				<div class="thr-lifestyle__more">
					<div class="thr-lifestyle__pair">
						<?php
						foreach ( array_slice( $life, 1, 2 ) as $id ) {
							thr_story_card( $id, array( 'mod' => 'life-small', 'image' => 'thr-card-s', 'meta' => 'section', 'by' => true ) );
						}
						?>
					</div>
					<?php if ( isset( $life[3] ) ) : ?>
						<?php thr_story_card( $life[3], array( 'mod' => 'life-wide', 'image' => 'thr-avatar-m', 'meta' => 'section', 'by' => true ) ); ?>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $pods ) : ?>
		<aside class="thr-split__rail">
			<div class="thr-mhead thr-mhead--large">
				<h2 class="thr-mhead__title"><?php esc_html_e( 'Podcasts', 'thr-theme' ); ?></h2>
			</div>
			<?php
			foreach ( $pods as $id ) {
				thr_story_card( $id, array( 'mod' => 'podcast', 'image' => 'thr-card-s', 'meta' => false ) );
			}
			?>
		</aside>
	<?php endif; ?>
</section>
