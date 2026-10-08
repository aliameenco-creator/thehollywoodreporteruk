<?php
/**
 * "Get The Magazine" Promotion Box Widget for Rail.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mag_url   = get_option( 'thr_magazine_url', '#' );
$cover_url = get_option( 'thr_magazine_cover_url', '' );
?>
<div class="thr-promo-box">
	<div class="thr-promo-box__title"><?php esc_html_e( 'Get The Magazine', 'thr-theme' ); ?></div>
	<p style="font-family:var(--font-serif); font-size:15px; line-height:1.3; margin-bottom:14px; font-weight:700;">
		<?php esc_html_e( 'The definitive voice of entertainment news and culture.', 'thr-theme' ); ?>
	</p>
	<?php if ( ! empty( $cover_url ) ) : ?>
		<img src="<?php echo esc_url( $cover_url ); ?>" alt="<?php esc_attr_e( 'The Hollywood Reporter UK Magazine Cover', 'thr-theme' ); ?>" class="thr-promo-box__cover" />
	<?php endif; ?>
	<a href="<?php echo esc_url( $mag_url ); ?>" class="thr-promo-box__btn">
		<?php esc_html_e( 'See My Options', 'thr-theme' ); ?> &rarr;
	</a>
</div>
