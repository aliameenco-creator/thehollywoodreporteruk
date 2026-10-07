<?php
/**
 * Most Popular Rail Widget.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$popular_ids = function_exists( 'thr_get_popular_post_ids' ) ? thr_get_popular_post_ids( 5 ) : array();

if ( ! empty( $popular_ids ) ) :
	$pop_query = new WP_Query(
		array(
			'post_type'      => 'post',
			'post__in'       => $popular_ids,
			'orderby'        => 'post__in',
			'posts_per_page' => 5,
			'no_found_rows'  => true,
		)
	);

	if ( $pop_query->have_posts() ) :
		$count = 1;
		?>
		<div class="thr-rail-widget thr-rail-widget--popular" style="margin-bottom:30px;">
			<h3 style="font-family:var(--font-serif); font-size:18px; font-weight:900; text-transform:uppercase; letter-spacing:0.04em; border-top:2px solid var(--black); padding-top:10px; margin-bottom:16px;">
				<?php esc_html_e( 'Most Popular', 'thr-theme' ); ?>
			</h3>
			<div class="thr-rail-popular__list">
				<?php
				while ( $pop_query->have_posts() ) :
					$pop_query->the_post();
					get_template_part( 'template-parts/cards/card', null, array( 'variant' => 'numbered', 'number' => $count ) );
					$count++;
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</div>
		<?php
	endif;
endif;
