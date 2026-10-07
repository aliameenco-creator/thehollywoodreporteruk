<?php
/**
 * Breaking News Bar Template Part.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$breaking_query = new WP_Query(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'meta_key'       => 'thr_breaking',
		'meta_value'     => '1',
		'no_found_rows'  => true,
	)
);

if ( $breaking_query->have_posts() ) :
	$breaking_query->the_post();
	?>
	<div class="thr-breaking-bar" data-story-id="<?php echo esc_attr( get_the_ID() ); ?>" role="alert">
		<div class="thr-container">
			<div class="thr-breaking-bar__inner">
				<span class="thr-breaking-bar__label"><?php esc_html_e( 'Breaking News', 'thr-theme' ); ?></span>
				<a href="<?php the_permalink(); ?>" class="thr-breaking-bar__headline">
					<?php the_title(); ?>
				</a>
				<button class="thr-breaking-bar__close" aria-label="<?php esc_attr_e( 'Dismiss breaking news', 'thr-theme' ); ?>">&times;</button>
			</div>
		</div>
	</div>
	<?php
	wp_reset_postdata();
endif;
