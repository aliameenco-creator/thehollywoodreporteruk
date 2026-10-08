<?php
/**
 * Single Article Byline and Date Row.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = get_the_ID();
?>
<div class="thr-article__byline-row">
	<div class="u-byline thr-article__byline">
		<?php echo function_exists( 'thr_get_byline' ) ? thr_get_byline( $post_id ) : get_the_author(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<time class="thr-article__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
		<?php echo esc_html( get_the_date( 'F j, Y g:ia' ) ); ?>
	</time>
</div>
