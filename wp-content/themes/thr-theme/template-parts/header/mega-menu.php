<?php
/**
 * Mega Menu: full-width column panel on desktop, slide-in accordion on mobile.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$columns = thr_get_mega_menu();
?>
<div class="thr-mega" id="thrMegaMenu" hidden>
	<div class="thr-mega__backdrop js-thr-menu-close"></div>

	<div class="thr-mega__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Site menu', 'thr-theme' ); ?>">
		<div class="thr-mega__head">
			<div class="thr-mega__logo"><?php thr_the_logo( 'menu' ); ?></div>

			<form class="thr-mega__search" action="<?php echo esc_url( home_url( '/results/' ) ); ?>" method="get" role="search">
				<label for="thrMegaSearch" class="screen-reader-text"><?php esc_html_e( 'Search', 'thr-theme' ); ?></label>
				<button type="submit" class="thr-mega__search-btn" aria-label="<?php esc_attr_e( 'Submit search', 'thr-theme' ); ?>">
					<?php echo thr_icon( 'search', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
				<input type="search" id="thrMegaSearch" name="q" class="thr-mega__search-input js-thr-search-input" placeholder="<?php esc_attr_e( 'Search', 'thr-theme' ); ?>" autocomplete="off" />
				<div class="thr-search-results js-thr-search-results" aria-live="polite"></div>
			</form>

			<button type="button" class="thr-mega__close js-thr-menu-close" aria-label="<?php esc_attr_e( 'Close menu', 'thr-theme' ); ?>">
				<?php echo thr_icon( 'close', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>

		<nav class="thr-mega__nav" aria-label="<?php esc_attr_e( 'All sections', 'thr-theme' ); ?>">
			<ul class="thr-mega__cols">
				<?php foreach ( $columns as $i => $col ) : ?>
					<?php $list_id = 'thrMegaCol' . (int) $i; ?>
					<li class="thr-mega__col">
						<h2 class="thr-mega__heading">
							<?php if ( ! empty( $col['url'] ) ) : ?>
								<a class="thr-mega__heading-link" href="<?php echo esc_url( thr_menu_url( $col['url'] ) ); ?>"><?php echo esc_html( $col['label'] ); ?></a>
							<?php else : ?>
								<span class="thr-mega__heading-link"><?php echo esc_html( $col['label'] ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $col['children'] ) ) : ?>
								<button type="button" class="thr-mega__toggle js-thr-accordion" aria-expanded="false" aria-controls="<?php echo esc_attr( $list_id ); ?>">
									<span class="thr-mega__toggle-label"><?php echo esc_html( $col['label'] ); ?></span>
									<?php echo thr_icon( 'chevron', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</button>
							<?php endif; ?>
						</h2>
						<?php if ( ! empty( $col['children'] ) ) : ?>
							<ul class="thr-mega__links" id="<?php echo esc_attr( $list_id ); ?>">
								<?php foreach ( $col['children'] as $child ) : ?>
									<li><a href="<?php echo esc_url( thr_menu_url( $child[1] ) ); ?>"><?php echo esc_html( $child[0] ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<div class="thr-mega__foot">
			<div class="thr-mega__follow">
				<span class="thr-mega__foot-title"><?php esc_html_e( 'Follow Us', 'thr-theme' ); ?></span>
				<ul class="thr-social thr-social--icons">
					<?php foreach ( thr_get_social_links() as $social ) : ?>
						<li><a href="<?php echo esc_url( $social[2] ); ?>" aria-label="<?php echo esc_attr( $social[1] ); ?>" target="_blank" rel="noopener"><?php echo thr_icon( $social[0], 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<form class="thr-mega__newsletter" action="<?php echo esc_url( thr_newsletter_action() ); ?>" method="<?php echo esc_attr( thr_newsletter_method() ); ?>">
				<label for="thrMegaEmail" class="thr-mega__foot-title"><?php esc_html_e( 'Alerts & Newsletters', 'thr-theme' ); ?></label>
				<div class="thr-mega__newsletter-row">
					<input type="email" id="thrMegaEmail" name="email" required placeholder="<?php esc_attr_e( 'Your e-mail', 'thr-theme' ); ?>" />
					<button type="submit"><?php esc_html_e( 'Subscribe', 'thr-theme' ); ?></button>
				</div>
			</form>

			<ul class="thr-mega__legal">
				<li><a href="<?php echo esc_url( home_url( '/masthead/' ) ); ?>"><?php esc_html_e( 'About Us', 'thr-theme' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Careers', 'thr-theme' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact Us', 'thr-theme' ); ?></a></li>
			</ul>
		</div>
	</div>
</div>
