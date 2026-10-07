<?php
/**
 * Off-canvas Mega Menu / Drawer Template Part.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="thr-mega-drawer" id="thrMegaDrawer" role="dialog" aria-modal="true" aria-hidden="true" style="display:none;">
	<div class="thr-mega-drawer__backdrop"></div>
	<div class="thr-mega-drawer__panel" style="background:#101010; color:#fff; padding:30px; position:fixed; top:0; left:0; width:100%; max-width:480px; height:100vh; overflow-y:auto; z-index:9999;">
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; border-bottom:1px solid #333; padding-bottom:15px;">
			<div style="font-family:var(--font-serif); font-size:24px; font-weight:900; color:#D92128; letter-spacing:-0.04em; text-transform:uppercase;">
				<?php bloginfo( 'name' ); ?>
			</div>
			<button class="thr-mega-drawer__close" aria-label="<?php esc_attr_e( 'Close menu', 'thr-theme' ); ?>" style="background:none; border:none; color:#fff; font-size:28px; cursor:pointer;">&times;</button>
		</div>

		<div style="background:#1a1a1a; padding:16px; border:1px solid #333; margin-bottom:30px;">
			<div style="font-family:var(--font-sans); font-size:12px; font-weight:800; text-transform:uppercase; color:#D92128; margin-bottom:4px; letter-spacing:0.06em;">
				<?php esc_html_e( 'Stay Informed', 'thr-theme' ); ?>
			</div>
			<div style="font-family:var(--font-serif); font-size:16px; margin-bottom:10px;">
				<?php esc_html_e( 'Get entertainment news & analysis delivered to your inbox.', 'thr-theme' ); ?>
			</div>
			<form action="<?php echo esc_url( get_option( 'thr_newsletter_url', '#' ) ); ?>" method="post" style="display:flex;">
				<input type="email" name="email" required placeholder="<?php esc_attr_e( 'Enter your email', 'thr-theme' ); ?>" style="flex:1; padding:8px 12px; border:none; font-family:var(--font-sans); font-size:13px;" />
				<button type="submit" style="background:#D92128; color:#fff; border:none; padding:8px 14px; font-family:var(--font-sans); font-weight:700; text-transform:uppercase; font-size:12px; cursor:pointer;">
					<?php esc_html_e( 'Sign Up', 'thr-theme' ); ?>
				</button>
			</form>
		</div>

		<nav class="thr-mega-drawer__nav" style="display:flex; flex-direction:column; gap:20px; font-family:var(--font-sans);">
			<div>
				<h4 style="color:#D92128; font-size:13px; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px;"><?php esc_html_e( 'Sections', 'thr-theme' ); ?></h4>
				<ul style="list-style:none; padding:0; margin:0; line-height:2;">
					<li><a href="<?php echo esc_url( home_url( '/c/news/' ) ); ?>" style="color:#fff;"><?php esc_html_e( 'News', 'thr-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/c/movies/' ) ); ?>" style="color:#fff;"><?php esc_html_e( 'Film', 'thr-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/c/tv/' ) ); ?>" style="color:#fff;"><?php esc_html_e( 'Television', 'thr-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/c/music/' ) ); ?>" style="color:#fff;"><?php esc_html_e( 'Music', 'thr-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/c/lifestyle/' ) ); ?>" style="color:#fff;"><?php esc_html_e( 'Lifestyle', 'thr-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/c/business/' ) ); ?>" style="color:#fff;"><?php esc_html_e( 'Business', 'thr-theme' ); ?></a></li>
				</ul>
			</div>

			<div>
				<h4 style="color:#D92128; font-size:13px; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px;"><?php esc_html_e( 'Channels & Verticals', 'thr-theme' ); ?></h4>
				<ul style="list-style:none; padding:0; margin:0; line-height:2;">
					<li><a href="<?php echo esc_url( home_url( '/e/heat-vision/' ) ); ?>" style="color:#6442AC; font-weight:700;">HEAT VISION</a></li>
					<li><a href="<?php echo esc_url( home_url( '/e/live-feed/' ) ); ?>" style="color:#008080; font-weight:700;">LIVE FEED</a></li>
					<li><a href="<?php echo esc_url( home_url( '/e/the-race/' ) ); ?>" style="color:#956E37; font-weight:700;">THE RACE</a></li>
					<li><a href="<?php echo esc_url( home_url( '/e/thr-esq/' ) ); ?>" style="color:#3454DB; font-weight:700;">THR, ESQ</a></li>
					<li><a href="<?php echo esc_url( home_url( '/video/' ) ); ?>" style="color:#fff;"><?php esc_html_e( 'Video Hub', 'thr-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/lists/' ) ); ?>" style="color:#fff;"><?php esc_html_e( 'Lists & Rankings', 'thr-theme' ); ?></a></li>
				</ul>
			</div>

			<div style="border-top:1px solid #333; padding-top:16px;">
				<ul style="list-style:none; padding:0; margin:0; line-height:1.8; font-size:13px;">
					<li><a href="<?php echo esc_url( home_url( '/tip-line/' ) ); ?>" style="color:#D92128; font-weight:700;"><?php esc_html_e( 'Send a Confidential News Tip', 'thr-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/masthead/' ) ); ?>" style="color:#aaa;"><?php esc_html_e( 'Masthead', 'thr-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" style="color:#aaa;"><?php esc_html_e( 'Contact Us', 'thr-theme' ); ?></a></li>
				</ul>
			</div>
		</nav>
	</div>
</div>
<style>
.thr-mega-drawer.is-active {
	display: block !important;
}
.thr-mega-drawer__backdrop {
	position: fixed;
	top: 0;
	left: 0;
	width: 100vw;
	height: 100vh;
	background: rgba(0, 0, 0, 0.6);
	z-index: 9998;
}
</style>
