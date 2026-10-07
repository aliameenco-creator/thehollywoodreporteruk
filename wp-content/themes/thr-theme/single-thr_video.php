<?php
/**
 * The template for displaying Video posts (thr_video).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$post_id   = get_the_ID();
	$media_url = get_post_meta( $post_id, 'thr_media_url', true );

	$yt_id = '';
	if ( preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $media_url, $match ) ) {
		$yt_id = $match[1];
	}
	?>

	<main id="primary" class="site-main">
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'thr-video-single' ); ?>>
			<div class="thr-container">
				<nav class="thr-breadcrumbs" style="padding:16px 0 8px; font-family:var(--font-sans); font-size:12px; font-weight: var(--fw-accent); text-transform:uppercase; letter-spacing:0.06em; color:var(--brand-primary);">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:var(--brand-primary);"><?php esc_html_e( 'Home', 'thr-theme' ); ?></a>
					<span style="color:var(--grey); margin:0 6px;">&gt;</span>
					<a href="<?php echo esc_url( home_url( '/video/' ) ); ?>" style="color:var(--brand-primary);"><?php esc_html_e( 'Video', 'thr-theme' ); ?></a>
				</nav>

				<header class="thr-article__header" style="margin-bottom:20px;">
					<div class="u-kicker"><?php esc_html_e( 'THR Video', 'thr-theme' ); ?></div>
					<h1 class="thr-article__title" style="font-size:var(--scale-primary-xl); line-height:var(--line-primary-xl); margin:8px 0 14px;">
						<?php the_title(); ?>
					</h1>
					<?php thr_the_dek( $post_id, 'u-dek' ); ?>
					<?php get_template_part( 'template-parts/single/byline' ); ?>
				</header>

				<div class="thr-video-player-container" style="position:relative; width:100%; padding-top:56.25%; background:#000; margin-bottom:30px;">
					<?php if ( ! empty( $yt_id ) ) : ?>
						<div class="thr-video-facade" data-youtube-id="<?php echo esc_attr( $yt_id ); ?>" style="position:absolute; top:0; left:0; width:100%; height:100%; cursor:pointer; display:flex; align-items:center; justify-content:center;">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'thr-hero', array( 'style' => 'width:100%; height:100%; object-fit:cover;' ) ); ?>
							<?php endif; ?>
							<div style="position:absolute; width:68px; height:48px; background:rgba(217,33,40,0.9); border-radius:12px; display:flex; align-items:center; justify-content:center;">
								<svg width="24" height="24" fill="#fff" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
							</div>
						</div>
					<?php else : ?>
						<div style="position:absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#fff;">
							<?php esc_html_e( 'Video unavailable', 'thr-theme' ); ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="thr-layout-main">
					<div class="thr-layout-grid">
						<div class="thr-content-area">
							<?php get_template_part( 'template-parts/single/share-bar' ); ?>

							<div class="thr-article__body" style="font-family:var(--font-serif); font-size:var(--scale-body-m); line-height:var(--line-body-m);">
								<?php the_content(); ?>
							</div>
						</div>

						<aside class="thr-rail thr-rail--sticky">
							<?php get_template_part( 'template-parts/rail/most-popular' ); ?>
							<?php get_template_part( 'template-parts/rail/magazine-promo' ); ?>
						</aside>
					</div>
				</div>
			</div>
		</article>
	</main>

	<?php
endwhile;

get_footer();
