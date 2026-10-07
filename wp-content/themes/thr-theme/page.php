<?php
/**
 * The template for displaying static pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	?>

	<main id="primary" class="site-main">
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'thr-page-article' ); ?>>
			<div class="thr-container">
				<header class="thr-page-header" style="padding:30px 0 20px; border-bottom:2px solid var(--black); margin-bottom:30px;">
					<h1 style="font-size:var(--scale-primary-xl); margin:0;">
						<?php the_title(); ?>
					</h1>
				</header>

				<div class="thr-page-content" style="max-width:800px; font-family:var(--font-serif); font-size:var(--scale-body-m); line-height:var(--line-body-m); margin-bottom:60px;">
					<?php the_content(); ?>
				</div>
			</div>
		</article>
	</main>

	<?php
endwhile;

get_footer();
