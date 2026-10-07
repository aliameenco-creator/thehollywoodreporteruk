<?php
/**
 * The template for displaying Category Section archives (/c/).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$current_cat = get_queried_object();
$cat_id      = $current_cat->term_id;
$heading     = get_term_meta( $cat_id, 'thr_heading', true ) ?: $current_cat->name;
$subtitle    = get_term_meta( $cat_id, 'thr_subtitle', true );

// Subsection row always lists the top-level section's children, so it stays put on child pages.
$ancestors = get_ancestors( $cat_id, 'category' );
$root_id   = $ancestors ? (int) end( $ancestors ) : $cat_id;
$children  = get_terms(
	array(
		'taxonomy'   => 'category',
		'parent'     => $root_id,
		'hide_empty' => false,
		'orderby'    => 'name',
	)
);
?>

<main id="primary" class="site-main">
	<div class="thr-container">
		<header class="thr-section-head">
			<h1 class="thr-section-head__title"><?php echo esc_html( $heading ); ?></h1>
			<?php if ( ! empty( $subtitle ) ) : ?>
				<p class="thr-section-head__subtitle"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $children ) && ! is_wp_error( $children ) ) : ?>
				<nav class="thr-subnav" aria-label="<?php esc_attr_e( 'Subsections', 'thr-theme' ); ?>">
					<ul class="thr-subnav__list">
						<li><a href="<?php echo esc_url( get_category_link( $root_id ) ); ?>" class="thr-subnav__link<?php echo $root_id === $cat_id ? ' is-active' : ''; ?>"<?php echo $root_id === $cat_id ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'All', 'thr-theme' ); ?></a></li>
						<?php foreach ( $children as $child ) : ?>
							<?php $is_current = ( (int) $child->term_id === $cat_id ); ?>
							<li><a href="<?php echo esc_url( get_category_link( $child->term_id ) ); ?>" class="thr-subnav__link<?php echo $is_current ? ' is-active' : ''; ?>"<?php echo $is_current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $child->name ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
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

						<div class="thr-pagination">
							<?php
							the_posts_pagination( array(
								'mid_size'  => 2,
								'prev_text' => __( '&larr; Previous', 'thr-theme' ),
								'next_text' => __( 'More Stories &rarr;', 'thr-theme' ),
							) );
							?>
						</div>
					<?php else : ?>
						<p class="thr-empty"><?php esc_html_e( 'No stories found in this section yet.', 'thr-theme' ); ?></p>
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
