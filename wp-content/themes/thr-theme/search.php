<?php
/**
 * The template for displaying search results (/results/?q=...).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$search_query = get_search_query();
$total_posts  = $wp_query->found_posts;
?>

<main id="primary" class="site-main">
	<div class="thr-container">
		<header class="thr-search-header" style="padding:30px 0 20px; border-bottom:2px solid var(--black); margin-bottom:30px;">
			<div class="u-kicker"><?php esc_html_e( 'Search Results', 'thr-theme' ); ?></div>
			<h1 style="font-size:var(--scale-primary-xl); margin:6px 0 16px;">
				<?php
				printf( esc_html__( 'Showing %1$d results for "%2$s"', 'thr-theme' ), (int) $total_posts, esc_html( $search_query ) );
				?>
			</h1>

			<form action="<?php echo esc_url( home_url( '/results/' ) ); ?>" method="get" style="display:flex; max-width:600px; gap:10px;">
				<input type="search" name="q" value="<?php echo esc_attr( $search_query ); ?>" style="flex:1; padding:10px; font-family:var(--font-serif); font-size:16px; border:1px solid var(--black);" />
				<button type="submit" style="background:var(--brand-primary); color:#fff; border:none; padding:10px 20px; font-weight:700; text-transform:uppercase; cursor:pointer;">
					<?php esc_html_e( 'Search', 'thr-theme' ); ?>
				</button>
			</form>
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
								'next_text' => __( 'More Results &rarr;', 'thr-theme' ),
							) );
							?>
						</div>
					<?php else : ?>
						<p><?php esc_html_e( 'No matching stories were found. Try another search term.', 'thr-theme' ); ?></p>
					<?php endif; ?>
				</div>

				<aside class="thr-rail thr-rail--sticky">
					<?php get_template_part( 'template-parts/rail/most-popular' ); ?>
				</aside>
			</div>
		</div>
	</div>
</main>

<?php
get_footer();
