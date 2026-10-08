<?php
/**
 * THR section/channel story stream: on page 1 a lead story beside its image and
 * three cards, then the "Latest …" river with images on the left, then pagination.
 *
 * @param array $args {
 *     @type string $river_title Heading above the river, e.g. "Latest Movies".
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = max( 1, (int) get_query_var( 'paged' ) );
$posts = $GLOBALS['wp_query']->posts;
$top   = 1 === $paged ? array_splice( $posts, 0, 4 ) : array();
$lead  = $top ? array_shift( $top ) : null;

if ( ! $lead && ! $posts ) {
	echo '<p class="thr-empty">' . esc_html__( 'No stories here yet.', 'thr-theme' ) . '</p>';
	return;
}
?>

<?php if ( $lead ) : ?>
	<div class="thr-vlead">
		<?php thr_story_card( $lead->ID, array( 'mod' => 'vlead', 'image' => 'thr-card-m', 'framed' => true, 'meta' => 'parent', 'dek' => true, 'by' => true, 'tag' => 'h2' ) ); ?>
	</div>
<?php endif; ?>

<?php if ( $top ) : ?>
	<div class="thr-vcards">
		<?php
		foreach ( $top as $p ) {
			thr_story_card( $p->ID, array( 'mod' => 'vcard', 'image' => 'thr-card-s', 'meta' => 'parent', 'dek' => true, 'by' => true ) );
		}
		?>
	</div>
<?php endif; ?>

<?php if ( $posts ) : ?>
	<h2 class="thr-vriver__title"><?php echo esc_html( $args['river_title'] ?? __( 'Latest News', 'thr-theme' ) ); ?></h2>
	<div class="thr-vriver">
		<?php
		foreach ( $posts as $p ) {
			thr_story_card( $p->ID, array( 'mod' => 'vriver', 'image' => 'thr-card-m', 'meta' => 'label-time', 'dek' => true, 'by' => true ) );
		}
		?>
	</div>
<?php endif; ?>

<div class="thr-pagination">
	<?php
	the_posts_pagination(
		array(
			'mid_size'  => 2,
			'prev_text' => __( '&larr; Previous', 'thr-theme' ),
			'next_text' => __( 'More Stories &rarr;', 'thr-theme' ),
		)
	);
	?>
</div>
