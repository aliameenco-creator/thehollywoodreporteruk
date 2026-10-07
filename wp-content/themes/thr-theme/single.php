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
	$categories   = get_the_category();
	?>

	<main id="primary" class="site-main">
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'thr-article' ); ?> data-post-id="<?php echo esc_attr( $post_id ); ?>">
			<div class="thr-container">
				<nav class="thr-breadcrumbs" style="padding:16px 0 8px; font-family:var(--font-sans); font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:var(--brand-primary);">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:var(--brand-primary);"><?php esc_html_e( 'Home', 'thr-theme' ); ?></a>
					<?php if ( ! empty( $categories ) ) : ?>
						<span style="color:var(--grey); margin:0 6px;">&gt;</span>
						<a href="<?php echo esc_url( get_category_link( $categories[0]->term_id ) ); ?>" style="color:var(--brand-primary);"><?php echo esc_html( $categories[0]->name ); ?></a>
					<?php endif; ?>
				</nav>

				<header class="thr-article__header" style="margin-bottom:20px;">
					<?php thr_the_kicker( $post_id ); ?>
					<h1 class="thr-article__title" style="font-size:var(--scale-primary-xl); line-height:var(--line-primary-xl); margin:8px 0 14px;">
						<?php the_title(); ?>
					</h1>
					<?php thr_the_dek( $post_id, 'u-dek' ); ?>
					<?php get_template_part( 'template-parts/single/byline' ); ?>
				</header>

				<div class="thr-layout-main">
					<div class="thr-layout-grid">
						<div class="thr-content-area">
							<?php if ( has_post_thumbnail() ) : ?>
								<figure class="thr-article__hero-media" style="margin:0 0 24px;">
									<?php the_post_thumbnail( 'thr-hero', array( 'style' => 'width:100%; height:auto;' ) ); ?>
									<?php
									$thumb_id = get_post_thumbnail_id();
									$caption  = wp_get_attachment_caption( $thumb_id );
									$credit   = get_post_meta( $thumb_id, 'thr_credit', true );
									if ( $caption || $credit ) :
										?>
										<figcaption style="font-family:var(--font-serif); font-size:13px; color:var(--grey-dark); margin-top:8px; line-height:1.4;">
											<?php if ( $caption ) echo esc_html( $caption ) . ' '; ?>
											<?php if ( $credit ) : ?>
												<span style="font-family:var(--font-sans); text-transform:uppercase; font-size:11px; color:var(--grey); font-weight:700;">
													<?php echo esc_html( $credit ); ?>
												</span>
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

							<div class="thr-article__body" style="font-family:var(--font-serif); font-size:var(--scale-body-m); line-height:var(--line-body-m); color:var(--accent);">
								<?php the_content(); ?>
							</div>

							<?php if ( 'review' === $article_type && ! empty( $credits ) ) : ?>
								<div class="thr-article__full-credits" style="margin:30px 0; padding:20px; background:var(--grey-lightest); border-left:4px solid var(--black); font-family:var(--font-sans); font-size:13px; line-height:1.6;">
									<h4 style="font-size:14px; text-transform:uppercase; font-weight:800; margin-top:0;"><?php esc_html_e( 'Full Credits', 'thr-theme' ); ?></h4>
									<?php echo wp_kses_post( $credits ); ?>
								</div>
							<?php endif; ?>

							<?php
							$tags = get_the_tags();
							if ( ! empty( $tags ) ) :
								?>
								<div class="thr-article__tags" style="margin:30px 0; padding-top:20px; border-top:1px solid var(--grey-light);">
									<strong style="font-family:var(--font-sans); font-size:12px; text-transform:uppercase; letter-spacing:0.06em; margin-right:10px;">
										<?php esc_html_e( 'Read More About:', 'thr-theme' ); ?>
									</strong>
									<?php foreach ( $tags as $tag ) : ?>
										<a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>" style="display:inline-block; background:var(--grey-lightest); padding:4px 10px; margin:4px 4px 4px 0; font-family:var(--font-sans); font-size:12px; font-weight:700; color:var(--black); border-radius:2px;">
											<?php echo esc_html( $tag->name ); ?>
										</a>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<?php
							$related = function_exists( 'thr_get_related_posts' ) ? thr_get_related_posts( $post_id, 2 ) : array();
							if ( ! empty( $related ) ) :
								?>
								<div class="thr-related-stories" style="margin:40px 0;">
									<h3 style="font-family:var(--font-sans); font-size:16px; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; border-top:2px solid var(--black); padding-top:10px; margin-bottom:16px;">
										<?php esc_html_e( 'Related Stories', 'thr-theme' ); ?>
									</h3>
									<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:20px;">
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
