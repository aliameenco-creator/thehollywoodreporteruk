<?php
/**
 * The template for displaying the Homepage.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$lead_query = class_exists( 'THR_Query' )
	? THR_Query::get( array( 'source' => 'featured', 'posts_per_page' => 1 ) )
	: new WP_Query( array( 'posts_per_page' => 1 ) );

$secondary_query = class_exists( 'THR_Query' )
	? THR_Query::get( array( 'source' => 'latest', 'posts_per_page' => 3 ) )
	: new WP_Query( array( 'posts_per_page' => 3, 'offset' => 1 ) );

$latest_rail_query = class_exists( 'THR_Query' )
	? THR_Query::get( array( 'source' => 'latest', 'posts_per_page' => 6, 'dedupe' => false ) )
	: new WP_Query( array( 'posts_per_page' => 6 ) );
?>

<main id="primary" class="site-main">
	<div class="thr-container">
		<div class="thr-layout-main">
			<div class="thr-layout-grid">
				<div class="thr-content-area">
					<?php
					if ( $lead_query->have_posts() ) :
						while ( $lead_query->have_posts() ) :
							$lead_query->the_post();
							get_template_part( 'template-parts/cards/card', null, array( 'variant' => 'top-story' ) );
						endwhile;
						wp_reset_postdata();
					endif;
					?>

					<?php if ( $secondary_query->have_posts() ) : ?>
						<div class="thr-secondary-grid">
							<?php
							while ( $secondary_query->have_posts() ) :
								$secondary_query->the_post();
								get_template_part( 'template-parts/cards/card', null, array( 'variant' => 'secondary' ) );
							endwhile;
							wp_reset_postdata();
							?>
						</div>
					<?php endif; ?>
				</div>

				<aside class="thr-rail">
					<div class="thr-rail-widget">
						<h3 style="font-family:var(--font-serif); font-size:20px; font-weight:900; text-transform:uppercase; letter-spacing:0.04em; border-top:2px solid var(--black); padding-top:10px; margin-bottom:12px;">
							<?php esc_html_e( 'Latest News', 'thr-theme' ); ?>
						</h3>
						<div class="thr-latest-rail-list">
							<?php
							if ( $latest_rail_query->have_posts() ) :
								while ( $latest_rail_query->have_posts() ) :
									$latest_rail_query->the_post();
									get_template_part( 'template-parts/cards/card', null, array( 'variant' => 'latest' ) );
								endwhile;
								wp_reset_postdata();
							endif;
							?>
						</div>
						<a href="<?php echo esc_url( home_url( '/c/news/' ) ); ?>" style="display:inline-block; margin-top:14px; font-family:var(--font-sans); font-size:12px; font-weight:800; color:var(--brand-primary); text-transform:uppercase; letter-spacing:0.05em;">
							<?php esc_html_e( 'More News', 'thr-theme' ); ?> &rarr;
						</a>
					</div>

					<div style="margin-top:30px;">
						<?php get_template_part( 'template-parts/rail/magazine-promo' ); ?>
					</div>
				</aside>
			</div>
		</div>

		<div class="thr-ad-slot" style="background:var(--grey-lightest); padding:20px 0; text-align:center; margin:30px 0; border:1px solid var(--grey-light);">
			<div class="thr-ad-slot__label"><?php esc_html_e( 'Advertisement', 'thr-theme' ); ?></div>
			<div style="min-height:90px; display:flex; align-items:center; justify-content:center; color:var(--grey); font-family:var(--font-sans); font-size:12px;">
				<?php esc_html_e( 'Responsive Banner (728×90 / 970×250)', 'thr-theme' ); ?>
			</div>
		</div>

		<div class="thr-layout-main">
			<div class="thr-layout-grid">
				<div class="thr-content-area">
					<h2 style="font-family:var(--font-serif); font-size:26px; font-weight:900; text-transform:uppercase; letter-spacing:0.03em; border-top:2px solid var(--black); padding-top:10px; margin-bottom:20px;">
						<?php esc_html_e( 'Featured Reporting & Analysis', 'thr-theme' ); ?>
					</h2>
					<?php
					$river_query = class_exists( 'THR_Query' )
						? THR_Query::get( array( 'source' => 'latest', 'posts_per_page' => 8 ) )
						: new WP_Query( array( 'posts_per_page' => 8 ) );

					if ( $river_query->have_posts() ) :
						while ( $river_query->have_posts() ) :
							$river_query->the_post();
							get_template_part( 'template-parts/cards/card', null, array( 'variant' => 'river' ) );
						endwhile;
						wp_reset_postdata();
					endif;
					?>
				</div>

				<aside class="thr-rail">
					<?php get_template_part( 'template-parts/rail/most-popular' ); ?>
				</aside>
			</div>
		</div>
	</div>
</main>

<?php
get_footer();
