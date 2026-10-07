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
			<header class="thr-gallery-header" style="padding:24px 0 16px; border-bottom:2px solid var(--black); margin-bottom:30px;">
				<div class="u-kicker"><?php esc_html_e( 'Photo Gallery', 'thr-theme' ); ?></div>
				<h1 style="font-size:var(--scale-primary-xl); line-height:var(--line-primary-xl); margin:6px 0 14px;">
					<?php the_title(); ?>
				</h1>
				<?php thr_the_dek( $post_id, 'u-dek' ); ?>
				<?php get_template_part( 'template-parts/single/byline' ); ?>
				<?php get_template_part( 'template-parts/single/share-bar' ); ?>
			</header>

			<div class="thr-gallery-body" style="margin-bottom:50px;">
				<?php if ( has_post_thumbnail() ) : ?>
					<figure style="margin:0 0 30px;">
						<?php the_post_thumbnail( 'full', array( 'style' => 'width:100%; height:auto;' ) ); ?>
						<?php
						$thumb_id = get_post_thumbnail_id();
						$caption  = wp_get_attachment_caption( $thumb_id );
						$credit   = get_post_meta( $thumb_id, 'thr_credit', true );
						?>
						<figcaption style="font-family:var(--font-serif); font-size:14px; color:var(--grey-dark); margin-top:10px;">
							<?php if ( $caption ) echo esc_html( $caption ) . ' '; ?>
							<?php if ( $credit ) : ?>
								<span style="font-family:var(--font-sans); text-transform:uppercase; font-size:11px; color:var(--grey); font-weight:700;">
									<?php echo esc_html( $credit ); ?>
								</span>
							<?php endif; ?>
						</figcaption>
					</figure>
				<?php endif; ?>

				<div class="thr-article__body" style="font-family:var(--font-serif); font-size:var(--scale-body-m); line-height:var(--line-body-m);">
					<?php the_content(); ?>
				</div>
			</div>
		</div>
	</main>

	<?php
endwhile;

get_footer();
