<?php
/**
 * The template for displaying static pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Pages that always carry a THR Core form, even if an editor leaves the shortcode out.
$thr_page_forms = array(
	'contact'     => 'thr_contact_form',
	'newsletters' => 'thr_newsletter_signup',
	'tip-line'    => 'thr_tip_form',
);

get_header();

while ( have_posts() ) :
	the_post();
	$slug = get_post_field( 'post_name' );
	$form = isset( $thr_page_forms[ $slug ] ) ? $thr_page_forms[ $slug ] : '';
	?>

	<main id="primary" class="site-main">
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'thr-page-article' . ( $form ? ' thr-page-article--' . sanitize_html_class( $slug ) : '' ) ); ?>>
			<div class="thr-container">
				<header class="thr-page-header">
					<h1 class="thr-page-header__title"><?php the_title(); ?></h1>
				</header>

				<div class="thr-page-content">
					<?php the_content(); ?>
					<?php
					if ( $form && shortcode_exists( $form ) && ! has_shortcode( get_post_field( 'post_content' ), $form ) ) {
						echo do_shortcode( '[' . $form . ']' );
					}
					?>
				</div>
			</div>
		</article>
	</main>

	<?php
endwhile;

get_footer();
