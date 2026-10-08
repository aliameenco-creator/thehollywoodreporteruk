<?php
/**
 * The header for THR Theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'thr-theme' ); ?></a>

	<header id="masthead" class="thr-header">
		<div class="thr-container thr-header__bar">
			<div class="thr-header__left">
				<button type="button" class="thr-header__icon-btn js-thr-menu-open" aria-controls="thrMegaMenu" aria-expanded="false" aria-label="<?php esc_attr_e( 'Open menu', 'thr-theme' ); ?>">
					<?php echo thr_icon( 'menu', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
				<button type="button" class="thr-header__icon-btn thr-header__search-btn js-thr-menu-open" data-thr-focus-search aria-controls="thrMegaMenu" aria-expanded="false" aria-label="<?php esc_attr_e( 'Search', 'thr-theme' ); ?>">
					<?php echo thr_icon( 'search', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
				<a href="<?php echo esc_url( home_url( '/tip-line/' ) ); ?>" class="thr-header__text-link thr-header__tip"><?php esc_html_e( 'Got a Tip?', 'thr-theme' ); ?></a>
			</div>

			<div class="thr-header__logo">
				<?php thr_the_logo( 'header' ); ?>
			</div>

			<div class="thr-header__right">
				<a href="<?php echo esc_url( home_url( '/newsletters/' ) ); ?>" class="thr-header__text-link"><?php esc_html_e( 'Newsletters', 'thr-theme' ); ?></a>
				<a href="<?php echo esc_url( get_option( 'thr_magazine_url' ) ?: home_url( '/newsletters/' ) ); ?>" class="thr-header__text-link thr-header__subscribe"><?php esc_html_e( 'Subscribe', 'thr-theme' ); ?></a>
			</div>
		</div>

		<nav class="thr-section-bar" aria-label="<?php esc_attr_e( 'Sections', 'thr-theme' ); ?>">
			<ul class="thr-section-bar__list">
				<?php foreach ( thr_get_section_bar() as $link ) : ?>
					<li><a href="<?php echo esc_url( thr_menu_url( $link[1] ) ); ?>" class="thr-section-bar__link"><?php echo esc_html( $link[0] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
	</header>

	<div class="thr-sticky js-thr-sticky" inert>
		<button type="button" class="thr-header__icon-btn thr-sticky__menu js-thr-menu-open" aria-controls="thrMegaMenu" aria-expanded="false">
			<?php echo thr_icon( 'menu', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php esc_html_e( 'Menu', 'thr-theme' ); ?></span>
		</button>
		<button type="button" class="thr-header__icon-btn js-thr-menu-open" data-thr-focus-search aria-controls="thrMegaMenu" aria-expanded="false" aria-label="<?php esc_attr_e( 'Search', 'thr-theme' ); ?>">
			<?php echo thr_icon( 'search', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
		<?php thr_the_logo( 'sticky' ); ?>
		<nav class="thr-sticky__nav" aria-label="<?php esc_attr_e( 'Sections', 'thr-theme' ); ?>">
			<ul class="thr-sticky__list">
				<?php foreach ( thr_get_section_bar() as $link ) : ?>
					<li><a href="<?php echo esc_url( thr_menu_url( $link[1] ) ); ?>" class="thr-section-bar__link"><?php echo esc_html( $link[0] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<a href="<?php echo esc_url( get_option( 'thr_magazine_url' ) ?: home_url( '/newsletters/' ) ); ?>" class="thr-sticky__subscribe"><?php esc_html_e( 'Subscribe', 'thr-theme' ); ?></a>
	</div>

	<?php get_template_part( 'template-parts/header/breaking-bar' ); ?>
	<?php get_template_part( 'template-parts/header/mega-menu' ); ?>

	<div id="content" class="site-content">
