<?php
/**
 * The template for displaying Ranked Lists and Guides (thr_list).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$post_id = get_the_ID();
	?>

	<main id="primary" class="site-main">
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'thr-list-article' ); ?> data-post-id="<?php echo esc_attr( $post_id ); ?>">
			<div class="thr-container">
				<?php thr_the_breadcrumbs( $post_id ); ?>

				<header class="thr-article__header">
					<div class="u-kicker"><?php esc_html_e( 'Rankings & Guides', 'thr-theme' ); ?></div>
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
								</figure>
							<?php endif; ?>

							<?php get_template_part( 'template-parts/single/share-bar' ); ?>

							<div class="thr-article__body">
								<?php the_content(); ?>
							</div>
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
