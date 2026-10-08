<?php
/**
 * Homepage top: top story, three secondary stories and the channel blocks,
 * beside the long Latest News rail.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lead = thr_module_posts( array( 'source' => 'featured', 'posts_per_page' => 1, 'dedupe' => true ) );
if ( ! $lead ) {
	$lead = thr_module_posts( array( 'source' => 'latest', 'posts_per_page' => 1, 'dedupe' => true ) );
}
$secondary = thr_module_posts( array( 'source' => 'latest', 'posts_per_page' => 3, 'dedupe' => true ) );
$latest    = thr_module_posts( array( 'source' => 'latest', 'posts_per_page' => 16 ) );

if ( ! $lead ) {
	echo '<p class="thr-empty">' . esc_html__( 'No stories published yet.', 'thr-theme' ) . '</p>';
	return;
}
?>
<section class="thr-home-top">
	<div class="thr-home-top__main">
		<?php
		global $post;
		$post = get_post( $lead[0] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );
		get_template_part( 'template-parts/cards/card', null, array( 'variant' => 'top-story' ) );
		wp_reset_postdata();
		?>

		<?php if ( $secondary ) : ?>
			<div class="thr-secondary">
				<?php
				// THR's centre card is the larger, framed one.
				foreach ( $secondary as $i => $id ) {
					$centre = 1 === $i;
					thr_story_card(
						$id,
						array(
							'mod'    => $centre ? 'secondary-centre' : 'secondary',
							'image'  => $centre ? 'thr-card-m' : 'thr-card-s',
							'framed' => $centre,
							'meta'   => 'parent',
							'dek'    => true,
							'by'     => true,
						)
					);
				}
				?>
			</div>
		<?php endif; ?>

		<?php get_template_part( 'template-parts/home/channels' ); ?>
	</div>

	<?php if ( $latest ) : ?>
		<aside class="thr-home-top__rail" aria-labelledby="thrLatestNews">
			<h2 class="thr-rail-title thr-latest__title" id="thrLatestNews"><?php esc_html_e( 'Latest News', 'thr-theme' ); ?></h2>
			<div class="thr-latest">
				<?php
				foreach ( $latest as $id ) {
					thr_story_card( $id, array( 'mod' => 'latest', 'image' => false, 'meta' => 'label-time' ) );
				}
				?>
			</div>
			<a class="thr-latest__more" href="<?php echo esc_url( home_url( '/c/news/' ) ); ?>">
				<?php esc_html_e( 'More News', 'thr-theme' ); ?>
				<?php echo thr_icon( 'double-arrow', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		</aside>
	<?php endif; ?>
</section>
