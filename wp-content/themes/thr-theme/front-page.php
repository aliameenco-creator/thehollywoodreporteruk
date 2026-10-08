<?php
/**
 * The Homepage: THR's module stack, top to bottom.
 *
 * Each module lives in template-parts/home/ and prints nothing when its
 * source has no stories. The channel blocks and labelled rows are chosen in
 * Settings → THR Settings → Homepage.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( class_exists( 'THR_Query' ) ) {
	THR_Query::reset_displayed();
}
?>

<main id="primary" class="site-main thr-home">
	<div class="thr-container">
		<?php
		get_template_part( 'template-parts/home/top' );
		get_template_part( 'template-parts/home/rows' );
		get_template_part( 'template-parts/home/videos' );
		get_template_part( 'template-parts/home/popular' );
		get_template_part( 'template-parts/home/reviews' );
		get_template_part( 'template-parts/home/shopping' );
		get_template_part( 'template-parts/home/lifestyle' );
		get_template_part( 'template-parts/home/magazine' );
		?>
	</div>
</main>

<?php
get_footer();
