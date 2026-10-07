<?php
/**
 * The main template file (fallback loop).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main">
	<div class="thr-container">
		<div class="thr-layout-main">
			<div class="thr-layout-grid">
				<div class="thr-content-area">
					<?php if ( have_posts() ) : ?>
						<div class="thr-archive-river">
							<?php
							while ( have_posts() ) :
								the_post();
								get_template_part( 'template-parts/cards/card', null, array( 'variant' => 'river' ) );
							endwhile;
							?>
						</div>

						<div class="thr-pagination" style="margin:40px 0; text-align:center;">
							<?php the_posts_pagination(); ?>
						</div>
					<?php else : ?>
						<p><?php esc_html_e( 'No content available.', 'thr-theme' ); ?></p>
					<?php endif; ?>
				</div>

				<aside class="thr-rail thr-rail--sticky">
					<?php get_template_part( 'template-parts/rail/most-popular' ); ?>
					<?php get_template_part( 'template-parts/rail/magazine-promo' ); ?>
				</aside>
			</div>
		</div>
	</div>
</main>

<?php
get_footer();
