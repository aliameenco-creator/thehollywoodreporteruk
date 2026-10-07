<?php
/**
 * The template for displaying Topic archives (/t/) and generic taxonomy archives.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$queried_obj = get_queried_object();
$banner_url  = '';
$intro_text  = '';

if ( is_tag() && $queried_obj ) {
	$banner_url = get_term_meta( $queried_obj->term_id, 'thr_topic_banner', true );
	$intro_text = get_term_meta( $queried_obj->term_id, 'thr_topic_intro', true );
}
?>

<main id="primary" class="site-main">
	<div class="thr-container">
		<?php if ( ! empty( $banner_url ) ) : ?>
			<div class="thr-topic-banner" style="margin:20px 0;">
				<img src="<?php echo esc_url( $banner_url ); ?>" alt="<?php single_term_title(); ?>" style="width:100%; height:auto; max-height:400px; object-fit:cover;" />
			</div>
		<?php endif; ?>

		<header class="thr-archive-header" style="padding:24px 0 16px; border-bottom:2px solid var(--black); margin-bottom:30px;">
			<div class="u-kicker"><?php esc_html_e( 'Topic', 'thr-theme' ); ?></div>
			<h1 style="font-size:var(--scale-primary-xl); line-height:1; text-transform:uppercase; margin:4px 0 12px;">
				<?php single_term_title(); ?>
			</h1>
			<?php if ( ! empty( $intro_text ) ) : ?>
				<div style="font-family:var(--font-serif); font-size:18px; line-height:1.4; color:var(--grey-darkest); max-width:800px;">
					<?php echo wp_kses_post( $intro_text ); ?>
				</div>
			<?php endif; ?>
		</header>

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
							<?php
							the_posts_pagination( array(
								'mid_size'  => 2,
								'prev_text' => __( '&larr; Previous', 'thr-theme' ),
								'next_text' => __( 'More Stories &rarr;', 'thr-theme' ),
							) );
							?>
						</div>
					<?php else : ?>
						<p><?php esc_html_e( 'No stories found for this topic.', 'thr-theme' ); ?></p>
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
