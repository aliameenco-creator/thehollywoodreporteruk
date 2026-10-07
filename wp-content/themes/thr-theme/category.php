<?php
/**
 * The template for displaying Category Section archives (/c/).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$current_cat  = get_queried_object();
$cat_id       = $current_cat->term_id;
$heading      = get_term_meta( $cat_id, 'thr_heading', true ) ?: $current_cat->name;
$subtitle     = get_term_meta( $cat_id, 'thr_subtitle', true );
$children     = get_terms( array( 'taxonomy' => 'category', 'parent' => $cat_id, 'hide_empty' => false ) );
?>

<main id="primary" class="site-main">
	<div class="thr-container">
		<header class="thr-archive-header" style="padding:30px 0 20px; border-bottom:2px solid var(--black); margin-bottom:30px;">
			<h1 style="font-size:var(--scale-primary-xl); line-height:1; text-transform:uppercase; margin:0 0 10px;">
				<?php echo esc_html( $heading ); ?>
			</h1>
			<?php if ( ! empty( $subtitle ) ) : ?>
				<div style="font-family:var(--font-serif); font-size:18px; color:var(--grey-dark); margin-bottom:12px;">
					<?php echo esc_html( $subtitle ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $children ) && ! is_wp_error( $children ) ) : ?>
				<div class="thr-subsection-pills" style="display:flex; flex-wrap:wrap; gap:8px; margin-top:14px;">
					<a href="<?php echo esc_url( get_category_link( $cat_id ) ); ?>" style="background:var(--brand-primary); color:#fff; padding:4px 12px; font-family:var(--font-sans); font-size:12px; font-weight:800; text-transform:uppercase;">
						<?php esc_html_e( 'All', 'thr-theme' ); ?>
					</a>
					<?php foreach ( $children as $child ) : ?>
						<a href="<?php echo esc_url( get_category_link( $child->term_id ) ); ?>" style="background:var(--grey-lightest); color:var(--black); padding:4px 12px; font-family:var(--font-sans); font-size:12px; font-weight:700; text-transform:uppercase;">
							<?php echo esc_html( $child->name ); ?>
						</a>
					<?php endforeach; ?>
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
						<p><?php esc_html_e( 'No stories found in this section.', 'thr-theme' ); ?></p>
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
