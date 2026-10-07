<?php
/**
 * Single Article Byline and Date Row.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = get_the_ID();
?>
<div class="thr-article__byline-row" style="display:flex; align-items:center; gap:16px; margin-bottom:24px; padding-bottom:14px; border-bottom:1px solid var(--grey-light);">
	<div class="u-byline">
		<?php echo function_exists( 'thr_get_byline' ) ? thr_get_byline( $post_id ) : get_the_author(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<span style="color:var(--grey);">&bull;</span>
	<time class="u-time" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
		<?php echo esc_html( strtoupper( get_the_date( 'F j, Y g:i A' ) ) ); ?>
	</time>
</div>
