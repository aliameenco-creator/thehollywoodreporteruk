<?php
/**
 * Most Popular: a sideways-scrolling row of numbered stories.
 */

if ( ! defined( 'ABSPATH' ) || ! function_exists( 'thr_get_popular_post_ids' ) ) {
	return;
}

$ids = thr_get_popular_post_ids( 9 );
if ( ! $ids ) {
	return;
}
?>
<section class="thr-popular" aria-labelledby="thrPopularTitle">
	<div class="thr-mhead">
		<h2 class="thr-mhead__title" id="thrPopularTitle"><?php esc_html_e( 'Most Popular', 'thr-theme' ); ?></h2>
		<div class="thr-popular__nav">
			<button type="button" class="thr-popular__btn js-thr-scroll" data-dir="-1" aria-label="<?php esc_attr_e( 'Previous', 'thr-theme' ); ?>"><?php echo thr_icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			<button type="button" class="thr-popular__btn js-thr-scroll" data-dir="1" aria-label="<?php esc_attr_e( 'Next', 'thr-theme' ); ?>"><?php echo thr_icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
		</div>
	</div>
	<ol class="thr-popular__track js-thr-scroll-track">
		<?php foreach ( $ids as $id ) : ?>
			<li class="thr-popular__item">
				<?php thr_story_card( $id, array( 'mod' => 'popular', 'image' => false, 'meta' => false, 'by' => true ) ); ?>
			</li>
		<?php endforeach; ?>
	</ol>
</section>
