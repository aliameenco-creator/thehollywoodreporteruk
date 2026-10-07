<?php
/**
 * Review Summary Box Template Part (Honey Background #F7F1E7).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id     = get_the_ID();
$subject     = get_post_meta( $post_id, 'thr_review_subject', true ) ?: get_the_title();
$bottom_line = get_post_meta( $post_id, 'thr_review_bottom_line', true );
$director    = get_post_meta( $post_id, 'thr_review_director', true );
$cast        = get_post_meta( $post_id, 'thr_review_cast', true );
$venue       = get_post_meta( $post_id, 'thr_review_venue', true );
$rating_time = get_post_meta( $post_id, 'thr_review_rating_time', true );
?>
<div class="thr-review-box">
	<div class="thr-review-box__left">
		<div class="thr-review-box__subject"><?php echo esc_html( $subject ); ?></div>
		<?php if ( ! empty( $rating_time ) ) : ?>
			<div style="font-family:var(--font-sans); font-size:12px; color:var(--grey-dark); font-weight:700;">
				<?php echo esc_html( $rating_time ); ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="thr-review-box__right">
		<?php if ( ! empty( $bottom_line ) ) : ?>
			<div class="thr-review-box__bottom-line">
				<strong><?php esc_html_e( 'The Bottom Line:', 'thr-theme' ); ?></strong> <?php echo esc_html( $bottom_line ); ?>
			</div>
		<?php endif; ?>

		<ul class="thr-review-box__details">
			<?php if ( ! empty( $director ) ) : ?>
				<li><strong><?php esc_html_e( 'Director:', 'thr-theme' ); ?></strong> <?php echo esc_html( $director ); ?></li>
			<?php endif; ?>
			<?php if ( ! empty( $cast ) ) : ?>
				<li><strong><?php esc_html_e( 'Cast:', 'thr-theme' ); ?></strong> <?php echo esc_html( $cast ); ?></li>
			<?php endif; ?>
			<?php if ( ! empty( $venue ) ) : ?>
				<li><strong><?php esc_html_e( 'Venue / Studio:', 'thr-theme' ); ?></strong> <?php echo esc_html( $venue ); ?></li>
			<?php endif; ?>
		</ul>
	</div>
</div>
