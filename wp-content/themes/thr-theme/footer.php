<?php
/**
 * The template for displaying the footer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
	</div><!-- #content -->

	<footer id="colophon" class="thr-footer">
		<div class="thr-container">
			<div class="thr-footer__grid">
				<div>
					<h4 class="thr-footer__title"><?php esc_html_e( 'Subscriber Support', 'thr-theme' ); ?></h4>
					<ul class="thr-footer__list">
						<li class="thr-footer__item"><a href="<?php echo esc_url( get_option( 'thr_magazine_url', '#' ) ); ?>" class="thr-footer__link"><?php esc_html_e( 'Get the Magazine', 'thr-theme' ); ?></a></li>
						<li class="thr-footer__item"><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="thr-footer__link"><?php esc_html_e( 'Customer Service', 'thr-theme' ); ?></a></li>
						<li class="thr-footer__item"><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="thr-footer__link"><?php esc_html_e( 'Back Issues', 'thr-theme' ); ?></a></li>
					</ul>
				</div>

				<div>
					<h4 class="thr-footer__title"><?php esc_html_e( 'The Hollywood Reporter', 'thr-theme' ); ?></h4>
					<ul class="thr-footer__list">
						<li class="thr-footer__item"><a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>" class="thr-footer__link"><?php esc_html_e( 'About Us', 'thr-theme' ); ?></a></li>
						<li class="thr-footer__item"><a href="<?php echo esc_url( home_url( '/masthead/' ) ); ?>" class="thr-footer__link"><?php esc_html_e( 'Masthead', 'thr-theme' ); ?></a></li>
						<li class="thr-footer__item"><a href="<?php echo esc_url( home_url( '/careers/' ) ); ?>" class="thr-footer__link"><?php esc_html_e( 'Careers', 'thr-theme' ); ?></a></li>
						<li class="thr-footer__item"><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="thr-footer__link"><?php esc_html_e( 'Contact Us', 'thr-theme' ); ?></a></li>
						<li class="thr-footer__item"><a href="<?php echo esc_url( home_url( '/accessibility/' ) ); ?>" class="thr-footer__link"><?php esc_html_e( 'Accessibility', 'thr-theme' ); ?></a></li>
					</ul>
				</div>

				<div>
					<h4 class="thr-footer__title"><?php esc_html_e( 'Legal', 'thr-theme' ); ?></h4>
					<ul class="thr-footer__list">
						<li class="thr-footer__item"><a href="<?php echo esc_url( home_url( '/terms-of-use/' ) ); ?>" class="thr-footer__link"><?php esc_html_e( 'Terms of Use', 'thr-theme' ); ?></a></li>
						<li class="thr-footer__item"><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" class="thr-footer__link"><?php esc_html_e( 'Privacy Policy', 'thr-theme' ); ?></a></li>
						<li class="thr-footer__item"><a href="<?php echo esc_url( home_url( '/cookie-policy/' ) ); ?>" class="thr-footer__link"><?php esc_html_e( 'Cookie Policy (UK)', 'thr-theme' ); ?></a></li>
					</ul>
				</div>

				<div>
					<h4 class="thr-footer__title"><?php esc_html_e( 'Have a News Tip?', 'thr-theme' ); ?></h4>
					<p style="font-family:var(--font-sans); font-size:13px; color:var(--grey); line-height:1.4; margin-bottom:14px;">
						<?php esc_html_e( 'Send us a confidential scoop or story tip through our encrypted tipline.', 'thr-theme' ); ?>
					</p>
					<a href="<?php echo esc_url( home_url( '/tip-line/' ) ); ?>" style="display:inline-block; background:var(--brand-primary); color:#fff; padding:8px 16px; font-family:var(--font-sans); font-weight:800; font-size:11px; text-transform:uppercase; letter-spacing:0.06em;">
						<?php esc_html_e( 'Send Us a Tip', 'thr-theme' ); ?> &rarr;
					</a>
				</div>
			</div>

			<div class="thr-footer__bottom">
				<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All Rights Reserved.', 'thr-theme' ); ?></p>
			</div>
		</div>
	</footer>
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
