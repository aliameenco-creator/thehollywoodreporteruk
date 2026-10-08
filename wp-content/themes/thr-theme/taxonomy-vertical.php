<?php
/**
 * Vertical channel pages (/e/{slug}/), e.g. LIVE FEED or HEAT VISION:
 * brand header, lead story + three cards, then "{Vertical}'s Latest News".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$term      = get_queried_object();
$color     = thr_vertical_color( $term );
$tagline_1 = get_term_meta( $term->term_id, 'thr_vertical_tagline_1', true );
$tagline_2 = get_term_meta( $term->term_id, 'thr_vertical_tagline_2', true );
$logo      = get_term_meta( $term->term_id, 'thr_vertical_logo', true );
?>

<main id="primary" class="site-main thr-vertical" style="--vert-color: <?php echo esc_attr( $color ); ?>">
	<div class="thr-container">
		<header class="thr-vhead">
			<div class="thr-vhead__rule">
				<?php if ( $logo ) : ?>
					<img class="thr-vhead__icon" src="<?php echo esc_url( $logo ); ?>" alt="" />
				<?php endif; ?>
			</div>
			<div class="thr-vhead__row">
				<p class="thr-vhead__tagline"><?php echo esc_html( $tagline_1 ); ?></p>
				<h1 class="thr-vhead__name"><?php single_term_title(); ?></h1>
				<p class="thr-vhead__tagline"><?php echo esc_html( $tagline_2 ); ?></p>
			</div>
		</header>

		<div class="thr-layout-grid thr-vertical__grid">
			<div class="thr-content-area">
				<?php
				get_template_part(
					'template-parts/archive/stream',
					null,
					/* translators: %s: vertical name, e.g. Live Feed. */
					array( 'river_title' => sprintf( __( '%s’s Latest News', 'thr-theme' ), $term->name ) )
				);
				?>
			</div>

			<aside class="thr-rail thr-rail--sticky">
				<?php
				$lists = function_exists( 'thr_newsletters' ) ? thr_newsletters() : array();
				$list  = isset( $lists[ $term->slug ] ) ? $term->slug : '';
				?>
				<form class="thr-vnews" action="<?php echo esc_url( thr_newsletter_action() ); ?>" method="<?php echo esc_attr( thr_newsletter_method() ); ?>">
					<p class="thr-vnews__kicker"><?php esc_html_e( 'Weekly Newsletter', 'thr-theme' ); ?></p>
					<p class="thr-vnews__text">
						<?php
						/* translators: %s: vertical name. */
						echo esc_html( $tagline_1 ?: sprintf( __( 'The best of %s, straight to your inbox.', 'thr-theme' ), $term->name ) );
						?>
					</p>
					<?php if ( $list ) : ?>
						<input type="hidden" name="list" value="<?php echo esc_attr( $list ); ?>" />
					<?php endif; ?>
					<label class="screen-reader-text" for="thrVnewsEmail"><?php esc_html_e( 'Email address', 'thr-theme' ); ?></label>
					<input class="thr-vnews__input" type="email" id="thrVnewsEmail" name="email" required placeholder="<?php esc_attr_e( 'Email', 'thr-theme' ); ?>" />
					<button type="submit" class="thr-vnews__btn"><?php esc_html_e( 'Subscribe Today', 'thr-theme' ); ?></button>
				</form>
				<?php get_template_part( 'template-parts/rail/most-popular' ); ?>
			</aside>
		</div>
	</div>
</main>

<?php
get_footer();
