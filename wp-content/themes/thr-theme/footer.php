<?php
/**
 * The template for displaying the footer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cover_url = get_option( 'thr_magazine_cover_url' );
$mag_url   = get_option( 'thr_magazine_url' ) ?: home_url( '/newsletters/' );
?>
	</div><!-- #content -->

	<footer id="colophon" class="thr-footer">
		<div class="thr-container thr-footer__inner">
			<div class="thr-footer__cover<?php echo $cover_url ? '' : ' thr-footer__cover--placeholder'; ?>">
				<a href="<?php echo esc_url( $mag_url ); ?>" class="thr-footer__cover-link">
					<?php if ( $cover_url ) : ?>
						<img src="<?php echo esc_url( $cover_url ); ?>" alt="<?php esc_attr_e( 'Latest issue of The Hollywood Reporter', 'thr-theme' ); ?>" loading="lazy" />
					<?php else : ?>
						<span class="thr-footer__cover-placeholder">
							<span class="thr-logo thr-logo--footer"><?php thr_the_wordmark(); ?></span>
							<span class="thr-footer__cover-cta"><?php esc_html_e( 'Get the Magazine', 'thr-theme' ); ?></span>
						</span>
					<?php endif; ?>
				</a>
			</div>

			<div class="thr-footer__main">
				<div class="thr-footer__menus">
					<?php foreach ( thr_get_footer_columns() as $col ) : ?>
						<div class="thr-footer__col">
							<h2 class="thr-footer__title"><?php echo esc_html( $col['label'] ); ?></h2>
							<ul class="thr-footer__list">
								<?php foreach ( $col['children'] as $link ) : ?>
									<li><a href="<?php echo esc_url( thr_menu_url( $link[1] ) ); ?>"><?php echo esc_html( $link[0] ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endforeach; ?>

					<div class="thr-footer__col thr-footer__col--social">
						<h2 class="thr-footer__title"><?php esc_html_e( 'Follow Us', 'thr-theme' ); ?></h2>
						<ul class="thr-social thr-social--labelled">
							<?php foreach ( thr_get_social_links() as $social ) : ?>
								<li>
									<a href="<?php echo esc_url( $social[2] ); ?>" target="_blank" rel="noopener">
										<?php echo thr_icon( $social[0], 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<span><?php echo esc_html( $social[1] ); ?></span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</div>

				<div class="thr-footer__actions">
					<form class="thr-footer__newsletter" action="<?php echo esc_url( thr_newsletter_action() ); ?>" method="post">
						<h2 class="thr-footer__action-title"><?php esc_html_e( 'Newsletter Sign Up', 'thr-theme' ); ?></h2>
						<div class="thr-footer__newsletter-row">
							<label for="thrFooterEmail" class="screen-reader-text"><?php esc_html_e( 'Email address', 'thr-theme' ); ?></label>
							<input type="email" id="thrFooterEmail" name="email" required placeholder="<?php esc_attr_e( 'Enter Your Email', 'thr-theme' ); ?>" />
							<button type="submit" class="thr-arrow-link"><?php esc_html_e( 'Subscribe', 'thr-theme' ); ?> <?php echo thr_icon( 'arrow', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
						</div>
						<p class="thr-footer__fineprint">
							<?php
							printf(
								/* translators: 1: Terms of Use link, 2: Privacy Policy link. */
								esc_html__( 'By providing your information, you agree to our %1$s and our %2$s.', 'thr-theme' ),
								'<a href="' . esc_url( home_url( '/terms-of-use/' ) ) . '">' . esc_html__( 'Terms of Use', 'thr-theme' ) . '</a>',
								'<a href="' . esc_url( home_url( '/privacy-policy/' ) ) . '">' . esc_html__( 'Privacy Policy', 'thr-theme' ) . '</a>'
							);
							?>
						</p>
					</form>

					<div class="thr-footer__tip">
						<h2 class="thr-footer__action-title"><?php esc_html_e( 'Have a Tip?', 'thr-theme' ); ?></h2>
						<p><?php esc_html_e( 'Send us a tip using our anonymous form.', 'thr-theme' ); ?></p>
						<a href="<?php echo esc_url( home_url( '/tip-line/' ) ); ?>" class="thr-arrow-link"><?php esc_html_e( 'Send Us a Tip', 'thr-theme' ); ?> <?php echo thr_icon( 'arrow', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					</div>
				</div>
			</div>
		</div>

		<div class="thr-footer__bottom">
			<div class="thr-container">
				<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php esc_html_e( 'The Hollywood Reporter UK. All Rights Reserved.', 'thr-theme' ); ?></p>
			</div>
		</div>
	</footer>
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
