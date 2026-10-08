<?php
/**
 * Featured Videos: large player-style lead in the centre, small videos either side.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ids = thr_module_posts( array( 'post_type' => array( 'thr_video' ), 'source' => 'latest', 'posts_per_page' => 6 ) );
if ( ! $ids ) {
	return;
}

$lead  = array_shift( $ids );
$right = array_slice( $ids, 0, 3 );
$left  = array_slice( $ids, 3, 2 );
$small = array( 'mod' => 'video-small', 'image' => 'thr-card-s', 'meta' => false, 'play' => true );
?>
<section class="thr-videos">
	<div class="thr-videos__side thr-videos__side--left">
		<div class="thr-mhead thr-mhead--large">
			<h2 class="thr-mhead__title"><?php esc_html_e( 'Featured Videos', 'thr-theme' ); ?></h2>
			<a class="thr-mhead__all" href="<?php echo esc_url( home_url( '/video/' ) ); ?>"><?php esc_html_e( 'See All', 'thr-theme' ); ?></a>
		</div>
		<?php
		foreach ( $left as $id ) {
			thr_story_card( $id, $small );
		}
		?>
	</div>

	<div class="thr-videos__lead">
		<?php thr_story_card( $lead, array( 'mod' => 'video-lead', 'image' => 'thr-lead', 'framed' => true, 'meta' => false, 'dek' => true, 'play' => true, 'tag' => 'h3' ) ); ?>
	</div>

	<div class="thr-videos__side thr-videos__side--right">
		<?php
		foreach ( $right as $id ) {
			thr_story_card( $id, $small );
		}
		?>
	</div>
</section>
