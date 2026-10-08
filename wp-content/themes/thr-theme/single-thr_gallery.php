<?php
/**
 * The template for displaying Photo Galleries (thr_gallery).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$post_id = get_the_ID();
	?>

	<main id="primary" class="site-main thr-gallery-shell">
		<div class="thr-container">
			<?php thr_the_breadcrumbs( $post_id ); ?>
			<header class="thr-article__header thr-gallery-header">
				<div class="u-kicker"><?php esc_html_e( 'Photo Gallery', 'thr-theme' ); ?></div>
				<h1 class="thr-article__title"><?php the_title(); ?></h1>
				<?php thr_the_dek( $post_id, 'thr-article__dek' ); ?>
				<?php get_template_part( 'template-parts/single/byline' ); ?>
				<?php get_template_part( 'template-parts/single/share-bar' ); ?>
			</header>

			<div class="thr-gallery-body">
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="thr-article__hero-media">
						<?php the_post_thumbnail( 'full' ); ?>
						<?php
						$thumb_id = get_post_thumbnail_id();
						$caption  = wp_get_attachment_caption( $thumb_id );
						$credit   = get_post_meta( $thumb_id, 'thr_credit', true );
						?>
						<figcaption class="thr-article__caption">
							<?php echo $caption ? esc_html( $caption ) . ' ' : ''; ?>
							<?php if ( $credit ) : ?>
								<span class="thr-article__credit"><?php echo esc_html( $credit ); ?></span>
							<?php endif; ?>
						</figcaption>
					</figure>
				<?php endif; ?>

				<div class="thr-article__body">
					<?php the_content(); ?>
				</div>
			</div>
		</div>
	</main>

	<?php
endwhile;

get_footer();
