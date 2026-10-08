<?php
/**
 * Featured Channels: two vertical blocks side by side (e.g. HEAT VISION / LIVE FEED).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$config = function_exists( 'thr_homepage_config' ) ? thr_homepage_config() : array( 'channels' => array( 'heat-vision', 'live-feed' ) );

$blocks = array();
foreach ( $config['channels'] as $slug ) {
	$term = $slug ? get_term_by( 'slug', $slug, 'vertical' ) : false;
	if ( ! $term ) {
		continue;
	}
	$ids = thr_module_posts( array( 'source' => 'vertical', 'term' => $slug, 'posts_per_page' => 3 ) );
	if ( $ids ) {
		$blocks[] = array( $term, $ids );
	}
}

if ( ! $blocks ) {
	return;
}
?>
<section class="thr-channels thr-channels--<?php echo count( $blocks ); ?>">
	<?php foreach ( $blocks as $block ) : ?>
		<?php
		list( $term, $ids ) = $block;
		$tagline            = get_term_meta( $term->term_id, 'thr_vertical_tagline_1', true );
		?>
		<div class="thr-channel" style="--vert-color: <?php echo esc_attr( thr_vertical_color( $term ) ); ?>">
			<a class="thr-channel__name" href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php echo esc_html( $term->name ); ?></a>
			<?php if ( $tagline ) : ?>
				<p class="thr-channel__tagline"><?php echo esc_html( $tagline ); ?></p>
			<?php endif; ?>
			<?php
			foreach ( $ids as $i => $id ) {
				thr_story_card(
					$id,
					0 === $i
						? array( 'mod' => 'channel-lead', 'image' => 'thr-card-m', 'framed' => true, 'meta' => 'time', 'dek' => true )
						: array( 'mod' => 'channel-text', 'image' => false, 'meta' => 'time' )
				);
			}
			thr_view_all( get_term_link( $term ) );
			?>
		</div>
	<?php endforeach; ?>
</section>
