<?php
/**
 * The template for displaying 404 pages (not found).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main">
	<div class="thr-container">
		<header class="thr-404-header" style="text-align:center; padding:60px 0 30px; border-bottom:1px solid var(--grey-light);">
			<div style="font-family:var(--font-sans); font-size:16px; font-weight: var(--fw-accent); color:var(--brand-primary); text-transform:uppercase; letter-spacing:0.1em; margin-bottom:8px;">
				404 ERROR
			</div>
			<h1 style="font-size:var(--scale-primary-xxl); margin-bottom:16px;">
				<?php esc_html_e( 'Page Not Found', 'thr-theme' ); ?>
			</h1>
			<p style="font-family:var(--font-serif); font-size:18px; color:var(--grey-dark); max-width:600px; margin:0 auto 30px;">
				<?php esc_html_e( 'The story or page you were looking for could not be found. Try searching below or explore latest news.', 'thr-theme' ); ?>
			</p>

			<form action="<?php echo esc_url( home_url( '/results/' ) ); ?>" method="get" style="display:flex; max-width:500px; margin:0 auto 30px; gap:8px;">
				<input type="search" name="q" placeholder="<?php esc_attr_e( 'Search the site...', 'thr-theme' ); ?>" style="flex:1; padding:10px; font-family:var(--font-serif); border:1px solid var(--black);" />
				<button type="submit" style="background:var(--brand-primary); color:#fff; border:none; padding:10px 20px; font-weight:700; text-transform:uppercase; cursor:pointer;">
					<?php esc_html_e( 'Search', 'thr-theme' ); ?>
				</button>
			</form>
		</header>

		<div style="padding:40px 0;">
			<h2 style="font-family:var(--font-serif); font-size:24px; font-weight: var(--fw-heading); text-transform:uppercase; margin-bottom:24px; text-align:center;">
				<?php esc_html_e( 'Latest Stories', 'thr-theme' ); ?>
			</h2>
			<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:24px;">
				<?php
				$recent_query = new WP_Query( array( 'posts_per_page' => 6, 'post_status' => 'publish' ) );
				if ( $recent_query->have_posts() ) :
					while ( $recent_query->have_posts() ) :
						$recent_query->the_post();
						get_template_part( 'template-parts/cards/card', null, array( 'variant' => 'secondary' ) );
					endwhile;
					wp_reset_postdata();
				endif;
				?>
			</div>
		</div>
	</div>
</main>

<?php
get_footer();
