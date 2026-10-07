<?php
/**
 * Universal Story Card Component.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$variant = ! empty( $args['variant'] ) ? $args['variant'] : 'river';
$post_id = get_the_ID();
$has_thumb = has_post_thumbnail( $post_id );
?>

<?php if ( 'top-story' === $variant ) : ?>
	<article class="thr-card thr-card--top-story" id="post-<?php the_ID(); ?>">
		<div class="thr-media-wrap">
			<span class="thr-badge-top-story"><?php esc_html_e( 'Top Story', 'thr-theme' ); ?></span>
			<?php if ( $has_thumb ) : ?>
				<div class="thr-framed-media">
					<a href="<?php the_permalink(); ?>">
						<?php the_post_thumbnail( 'thr-lead', array( 'loading' => 'eager' ) ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
		<h2 class="thr-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>
		<?php thr_the_dek( $post_id ); ?>
		<div class="u-byline">
			<?php echo function_exists( 'thr_get_byline' ) ? thr_get_byline( $post_id ) : get_the_author(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</article>

<?php elseif ( 'secondary' === $variant ) : ?>
	<article class="thr-card thr-card--secondary" id="post-<?php the_ID(); ?>">
		<?php if ( $has_thumb ) : ?>
			<div class="thr-framed-media">
				<a href="<?php the_permalink(); ?>">
					<?php the_post_thumbnail( 'thr-card-m' ); ?>
				</a>
			</div>
		<?php endif; ?>
		<div class="thr-card__meta">
			<?php thr_the_kicker( $post_id ); ?>
		</div>
		<h3 class="thr-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h3>
		<?php thr_the_dek( $post_id ); ?>
		<div class="u-byline">
			<?php echo function_exists( 'thr_get_byline' ) ? thr_get_byline( $post_id ) : get_the_author(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</article>

<?php elseif ( 'river' === $variant ) : ?>
	<article class="thr-card thr-card--river" id="post-<?php the_ID(); ?>">
		<div class="thr-card__body">
			<div class="thr-card__meta">
				<?php thr_the_kicker( $post_id ); ?>
				<span class="u-time"><?php echo esc_html( thr_time_ago( $post_id ) ); ?></span>
			</div>
			<h3 class="thr-card__title">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h3>
			<?php thr_the_dek( $post_id ); ?>
			<div class="u-byline">
				<?php echo function_exists( 'thr_get_byline' ) ? thr_get_byline( $post_id ) : get_the_author(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<?php if ( $has_thumb ) : ?>
			<div class="thr-card__media">
				<div class="thr-framed-media">
					<a href="<?php the_permalink(); ?>">
						<?php the_post_thumbnail( 'thr-card-s' ); ?>
					</a>
				</div>
			</div>
		<?php endif; ?>
	</article>

<?php elseif ( 'latest' === $variant ) : ?>
	<article class="thr-card thr-card--latest" id="post-<?php the_ID(); ?>">
		<div class="thr-card__meta">
			<?php thr_the_kicker( $post_id ); ?>
			<span class="u-time"><?php echo esc_html( thr_time_ago( $post_id ) ); ?></span>
		</div>
		<h4 class="thr-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h4>
	</article>

<?php elseif ( 'numbered' === $variant ) : ?>
	<article class="thr-card thr-card--numbered" id="post-<?php the_ID(); ?>">
		<div class="thr-card--numbered__number">
			<?php echo ! empty( $args['number'] ) ? esc_html( $args['number'] . '.' ) : '1.'; ?>
		</div>
		<div class="thr-card--numbered__content">
			<h4 class="thr-card--numbered__title">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h4>
			<div class="u-byline">
				<?php echo function_exists( 'thr_get_byline' ) ? thr_get_byline( $post_id ) : get_the_author(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
	</article>

<?php elseif ( 'rail-thumb' === $variant ) : ?>
	<article class="thr-card thr-card--rail" id="post-<?php the_ID(); ?>" style="display:flex; gap:12px; margin-bottom:14px; padding-bottom:14px; border-bottom:1px solid var(--grey-light);">
		<?php if ( $has_thumb ) : ?>
			<div style="flex:0 0 90px; width:90px;">
				<a href="<?php the_permalink(); ?>">
					<?php the_post_thumbnail( 'thr-avatar-s', array( 'style' => 'width:100%; height:auto;' ) ); ?>
				</a>
			</div>
		<?php endif; ?>
		<div>
			<h4 style="font-size:14px; line-height:1.25; margin:0 0 6px;">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h4>
			<div class="u-time"><?php echo esc_html( thr_time_ago( $post_id ) ); ?></div>
		</div>
	</article>

<?php endif; ?>
