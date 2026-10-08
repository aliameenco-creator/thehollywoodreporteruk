<?php
/**
 * The template for displaying all single posts and reviews.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$post_id      = get_the_ID();
	$article_type = get_post_meta( $post_id, 'thr_article_type', true ) ?: 'standard';
	$credits      = get_post_meta( $post_id, 'thr_review_full_credits', true );
	$kicker       = get_post_meta( $post_id, 'thr_kicker', true );
	?>

	<main id="primary" class="site-main">
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'thr-article' ); ?> data-post-id="<?php echo esc_attr( $post_id ); ?>">
			<div class="thr-container">
				<?php thr_the_vertical_banner( $post_id ); ?>
				<?php thr_the_breadcrumbs( $post_id ); ?>

				<header class="thr-article__header">
					<?php if ( $kicker ) : ?>
						<span class="u-kicker"><?php echo esc_html( $kicker ); ?></span>
					<?php endif; ?>
					<h1 class="thr-article__title"><?php the_title(); ?></h1>
					<?php thr_the_dek( $post_id, 'thr-article__dek' ); ?>
					<?php get_template_part( 'template-parts/single/byline' ); ?>
				</header>

				<div class="thr-layout-main thr-layout-main--article">
					<div class="thr-layout-grid">
						<div class="thr-content-area">
							<?php if ( has_post_thumbnail() ) : ?>
								<figure class="thr-article__hero-media">
									<?php the_post_thumbnail( 'thr-hero' ); ?>
									<?php
									$thumb_id = get_post_thumbnail_id();
									$caption  = wp_get_attachment_caption( $thumb_id );
									$credit   = get_post_meta( $thumb_id, 'thr_credit', true );
									if ( $caption || $credit ) :
										?>
										<figcaption class="thr-article__caption">
											<?php echo $caption ? esc_html( $caption ) . ' ' : ''; ?>
											<?php if ( $credit ) : ?>
												<span class="thr-article__credit"><?php echo esc_html( $credit ); ?></span>
											<?php endif; ?>
										</figcaption>
									<?php endif; ?>
								</figure>
							<?php endif; ?>

							<?php get_template_part( 'template-parts/single/share-bar' ); ?>

							<?php
							if ( 'review' === $article_type ) {
								get_template_part( 'template-parts/single/review-box' );
							}
							?>

							<div class="thr-article__body">
								<?php the_content(); ?>
							</div>

							<?php if ( 'review' === $article_type && ! empty( $credits ) ) : ?>
								<div class="thr-article__full-credits">
									<h2 class="thr-article__full-credits-title"><?php esc_html_e( 'Full Credits', 'thr-theme' ); ?></h2>
									<?php echo wp_kses_post( $credits ); ?>
								</div>
							<?php endif; ?>

							<?php
							$tags = get_the_tags();
							if ( ! empty( $tags ) ) :
								?>
								<div class="thr-article__tags">
									<strong class="thr-article__tags-label"><?php esc_html_e( 'Read More About:', 'thr-theme' ); ?></strong>
									<?php foreach ( $tags as $tag ) : ?>
										<a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>" class="thr-article__tag"><?php echo esc_html( $tag->name ); ?></a>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<?php
							$related = function_exists( 'thr_get_related_posts' ) ? thr_get_related_posts( $post_id, 2 ) : array();
							if ( ! empty( $related ) ) :
								?>
								<div class="thr-related-stories">
									<h2 class="thr-related-stories__title"><?php esc_html_e( 'Related Stories', 'thr-theme' ); ?></h2>
									<div class="thr-related-stories__grid">
										<?php
										foreach ( $related as $rel_post ) {
											setup_postdata( $GLOBALS['post'] =& $rel_post ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
											get_template_part( 'template-parts/cards/card', null, array( 'variant' => 'secondary' ) );
										}
										wp_reset_postdata();
										?>
									</div>
								</div>
							<?php endif; ?>
						</div>

						<aside class="thr-rail thr-rail--sticky">
							<?php get_template_part( 'template-parts/rail/magazine-promo' ); ?>
							<?php get_template_part( 'template-parts/rail/most-popular' ); ?>
						</aside>
					</div>
				</div>
			</div>
		</article>
	</main>

	<?php
endwhile;

get_footer();
