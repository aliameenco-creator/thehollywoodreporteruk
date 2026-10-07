<?php
/**
 * The template for displaying Vertical Hubs (/e/{slug}/).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$current_vert = get_queried_object();
$vert_id      = $current_vert->term_id;
$color        = get_term_meta( $vert_id, 'thr_vertical_color', true ) ?: '#D92128';
$tagline_1    = get_term_meta( $vert_id, 'thr_vertical_tagline_1', true );
$tagline_2    = get_term_meta( $vert_id, 'thr_vertical_tagline_2', true );
?>

<main id="primary" class="site-main">
	<div class="thr-container">
		<header class="thr-vertical-header" style="text-align:center; padding:40px 0 30px; border-bottom:3px solid <?php echo esc_attr( $color ); ?>; margin-bottom:35px;">
			<div style="font-family:var(--font-sans); font-size:12px; font-weight:800; letter-spacing:0.2em; text-transform:uppercase; color:<?php echo esc_attr( $color ); ?>; margin-bottom:10px;">
				<?php esc_html_e( 'A THR Channel', 'thr-theme' ); ?>
			</div>
			<h1 style="font-family:var(--font-sans); font-size:48px; font-weight:900; letter-spacing:0.12em; text-transform:uppercase; color:<?php echo esc_attr( $color ); ?>; margin:0 0 12px; line-height:1;">
				<?php single_term_title(); ?>
			</h1>
			<?php if ( ! empty( $tagline_1 ) || ! empty( $tagline_2 ) ) : ?>
				<div style="font-family:var(--font-serif); font-style:italic; font-size:18px; color:var(--grey-dark);">
					<?php echo esc_html( $tagline_1 ); ?>
					<?php if ( ! empty( $tagline_1 ) && ! empty( $tagline_2 ) ) echo ' &bull; '; ?>
					<?php echo esc_html( $tagline_2 ); ?>
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
						<p><?php esc_html_e( 'No stories published in this vertical channel yet.', 'thr-theme' ); ?></p>
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
