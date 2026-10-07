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
				<nav class="thr-breadcrumbs" style="padding:16px 0 8px; font-family:var(--font-sans); font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:var(--brand-primary);">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:var(--brand-primary);"><?php esc_html_e( 'Home', 'thr-theme' ); ?></a>
					<span style="color:var(--grey); margin:0 6px;">&gt;</span>
					<a href="<?php echo esc_url( home_url( '/lists/' ) ); ?>" style="color:var(--brand-primary);"><?php esc_html_e( 'Lists', 'thr-theme' ); ?></a>
				</nav>

				<header class="thr-article__header" style="margin-bottom:20px;">
					<div class="u-kicker"><?php esc_html_e( 'Rankings & Guides', 'thr-theme' ); ?></div>
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
								</figure>
							<?php endif; ?>

							<?php get_template_part( 'template-parts/single/share-bar' ); ?>

							<div class="thr-article__body" style="font-family:var(--font-serif); font-size:var(--scale-body-m); line-height:var(--line-body-m); color:var(--accent);">
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
