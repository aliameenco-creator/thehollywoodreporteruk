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

	<div class="thr-ad-slot thr-ad-slot--leaderboard">
		<div class="thr-container">
			<div class="thr-ad-slot__label"><?php esc_html_e( 'Advertisement', 'thr-theme' ); ?></div>
			<div style="min-height:90px; display:flex; align-items:center; justify-content:center; color:var(--grey); font-family:var(--font-sans); font-size:12px;">
				<?php esc_html_e( 'Reserved Leaderboard Slot (728×90 / 970×250)', 'thr-theme' ); ?>
			</div>
		</div>
	</div>

	<header id="masthead" class="thr-header">
		<div class="thr-container">
			<div class="thr-header__top-row">
				<div class="thr-header__left">
					<button class="thr-header__menu-btn" aria-controls="thrMegaDrawer" aria-expanded="false">
						<span class="thr-header__menu-icon">
							<span></span>
							<span></span>
							<span></span>
						</span>
						<span class="thr-header__menu-text"><?php esc_html_e( 'Menu', 'thr-theme' ); ?></span>
					</button>

					<a href="<?php echo esc_url( home_url( '/tip-line/' ) ); ?>" class="thr-header__tip-link">
						<?php esc_html_e( 'Got a Tip?', 'thr-theme' ); ?>
					</a>
				</div>

				<div class="thr-header__logo-container">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="thr-header__logo">
						<?php
						$custom_logo_url = get_option( 'thr_logo_url' );
						if ( ! empty( $custom_logo_url ) ) :
							?>
							<img src="<?php echo esc_url( $custom_logo_url ); ?>" alt="<?php bloginfo( 'name' ); ?>" style="max-height:42px; width:auto;" />
						<?php else : ?>
							The Hollywood Reporter
						<?php endif; ?>
					</a>
				</div>

				<div class="thr-header__right">
					<button class="thr-search-toggle" aria-expanded="false" aria-label="<?php esc_attr_e( 'Open Search', 'thr-theme' ); ?>" style="background:none; border:none; cursor:pointer; padding:6px;">
						<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
					</button>

					<a href="<?php echo esc_url( get_option( 'thr_newsletter_url', '#' ) ); ?>" class="thr-header__tip-link">
						<?php esc_html_e( 'Newsletters', 'thr-theme' ); ?>
					</a>

					<a href="<?php echo esc_url( get_option( 'thr_magazine_url', '#' ) ); ?>" class="thr-header__subscribe-btn">
						<?php esc_html_e( 'Subscribe', 'thr-theme' ); ?>
					</a>
				</div>
			</div>

			<div class="thr-search-bar" style="display:none; padding:12px 0; border-top:1px solid var(--grey-light);">
				<form action="<?php echo esc_url( home_url( '/results/' ) ); ?>" method="get" style="display:flex; gap:10px; position:relative;">
					<input type="search" name="q" class="thr-search-bar__input" placeholder="<?php esc_attr_e( 'Search movies, TV shows, actors, reviews...', 'thr-theme' ); ?>" style="flex:1; padding:10px 14px; font-family:var(--font-serif); font-size:16px; border:1px solid var(--black);" />
					<button type="submit" style="background:var(--brand-primary); color:#fff; border:none; padding:10px 20px; font-weight:700; text-transform:uppercase; cursor:pointer;">
						<?php esc_html_e( 'Search', 'thr-theme' ); ?>
					</button>
				</form>
				<div class="thr-search-autocomplete-results" style="background:#fff; border:1px solid #ccc; margin-top:4px;"></div>
			</div>

			<nav class="thr-section-bar" aria-label="<?php esc_attr_e( 'Primary Sections', 'thr-theme' ); ?>">
				<ul class="thr-section-bar__list">
					<li><a href="<?php echo esc_url( home_url( '/c/news/' ) ); ?>" class="thr-section-bar__link"><?php esc_html_e( 'News', 'thr-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/c/movies/' ) ); ?>" class="thr-section-bar__link"><?php esc_html_e( 'Film', 'thr-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/c/tv/' ) ); ?>" class="thr-section-bar__link"><?php esc_html_e( 'TV', 'thr-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/c/music/' ) ); ?>" class="thr-section-bar__link"><?php esc_html_e( 'Music', 'thr-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/t/awards/' ) ); ?>" class="thr-section-bar__link"><?php esc_html_e( 'Awards', 'thr-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/c/lifestyle/' ) ); ?>" class="thr-section-bar__link"><?php esc_html_e( 'Lifestyle', 'thr-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/c/business/' ) ); ?>" class="thr-section-bar__link"><?php esc_html_e( 'Business', 'thr-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/t/international/' ) ); ?>" class="thr-section-bar__link"><?php esc_html_e( 'International', 'thr-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/t/thr-cover-story/' ) ); ?>" class="thr-section-bar__link"><?php esc_html_e( 'Covers', 'thr-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/lists/' ) ); ?>" class="thr-section-bar__link"><?php esc_html_e( 'Lists', 'thr-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/video/' ) ); ?>" class="thr-section-bar__link"><?php esc_html_e( 'Video', 'thr-theme' ); ?></a></li>
				</ul>
			</nav>
		</div>
	</header>

	<?php get_template_part( 'template-parts/header/breaking-bar' ); ?>
	<?php get_template_part( 'template-parts/header/mega-menu' ); ?>

	<div id="content" class="site-content">
