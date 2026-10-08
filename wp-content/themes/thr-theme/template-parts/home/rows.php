<?php
/**
 * Labelled rows ("What We're Watching", "NYFF 2026"): label column + four cards.
 * Titles, taglines and topics come from Settings → THR Settings → Homepage.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$config = function_exists( 'thr_homepage_config' ) ? thr_homepage_config() : array( 'rows' => array() );

foreach ( $config['rows'] as $row ) {
	if ( empty( $row['topic'] ) ) {
		continue;
	}
	$ids = thr_module_posts( array( 'source' => 'topic', 'term' => $row['topic'], 'posts_per_page' => 4 ) );
	if ( ! $ids ) {
		continue;
	}
	$tag  = get_term_by( 'slug', $row['topic'], 'post_tag' );
	$link = $tag ? get_term_link( $tag ) : '';
	?>
	<section class="thr-row">
		<div class="thr-row__label">
			<h2 class="thr-row__title"><?php echo esc_html( $row['title'] ?: ( $tag ? $tag->name : '' ) ); ?></h2>
			<?php if ( $row['tagline'] ) : ?>
				<p class="thr-row__tagline"><?php echo esc_html( $row['tagline'] ); ?></p>
			<?php endif; ?>
			<?php if ( $link && ! is_wp_error( $link ) ) : ?>
				<a class="thr-row__all" href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'See All', 'thr-theme' ); ?></a>
			<?php endif; ?>
		</div>
		<div class="thr-row__cards">
			<?php
			foreach ( $ids as $id ) {
				thr_story_card( $id, array( 'mod' => 'row', 'image' => 'thr-card-s', 'meta' => false ) );
			}
			?>
		</div>
	</section>
	<?php
}
