<?php
/**
 * Custom Template Tags and Utility Functions for THR Theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_the_kicker( $post_id = null ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	$override = get_post_meta( $post_id, 'thr_kicker', true );
	if ( ! empty( $override ) ) {
		echo '<span class="u-kicker">' . esc_html( $override ) . '</span>';
		return;
	}

	// THR kickers name the subsection (MOVIE NEWS, TV FEATURES).
	$cat = thr_get_primary_category( $post_id );
	if ( $cat ) {
		echo '<span class="u-kicker"><a href="' . esc_url( get_category_link( $cat->term_id ) ) . '">' . esc_html( $cat->name ) . '</a></span>';
	}
}

/**
 * The category a story belongs to, matching its URL: Yoast's primary category,
 * else the first subsection (child category), else the first category.
 *
 * @param int $post_id Post ID.
 * @return WP_Term|null
 */
function thr_get_primary_category( $post_id ) {
	$categories = get_the_category( $post_id );
	if ( empty( $categories ) ) {
		return null;
	}

	if ( class_exists( 'WPSEO_Primary_Term' ) ) {
		$primary_id = (int) ( new WPSEO_Primary_Term( 'category', $post_id ) )->get_primary_term();
		foreach ( $categories as $cat ) {
			if ( (int) $cat->term_id === $primary_id ) {
				return $cat;
			}
		}
	}

	foreach ( $categories as $cat ) {
		if ( $cat->parent ) {
			return $cat;
		}
	}
	return $categories[0];
}

/**
 * Breadcrumb trail for single stories: Home > Section > Subsection, or
 * Home > Lists / Videos for post types with an archive. The last crumb is bold.
 *
 * @param int $post_id Post ID.
 */
function thr_the_breadcrumbs( $post_id ) {
	$crumbs = array( array( __( 'Home', 'thr-theme' ), home_url( '/' ) ) );

	$post_type = get_post_type( $post_id );
	$archive   = 'post' === $post_type ? false : get_post_type_archive_link( $post_type );
	if ( ! $archive ) {
		$cat = thr_get_primary_category( $post_id );
		if ( $cat ) {
			foreach ( array_reverse( get_ancestors( $cat->term_id, 'category', 'taxonomy' ) ) as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'category' );
				if ( $ancestor && ! is_wp_error( $ancestor ) ) {
					$crumbs[] = array( $ancestor->name, get_category_link( $ancestor ) );
				}
			}
			$crumbs[] = array( $cat->name, get_category_link( $cat ) );
		}
	} else {
		$crumbs[] = array( get_post_type_object( $post_type )->labels->name, $archive );
	}

	if ( count( $crumbs ) < 2 ) {
		return;
	}
	?>
	<nav class="thr-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'thr-theme' ); ?>">
		<ol class="thr-breadcrumbs__list">
			<?php foreach ( $crumbs as $i => $crumb ) : ?>
				<li class="thr-breadcrumbs__item">
					<a href="<?php echo esc_url( $crumb[1] ); ?>"<?php echo count( $crumbs ) - 1 === $i ? ' class="is-current"' : ''; ?>><?php echo esc_html( $crumb[0] ); ?></a>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
}

/**
 * Vertical banner (e.g. THE FIEN PRINT) above a story that belongs to a vertical.
 *
 * @param int $post_id Post ID.
 */
function thr_the_vertical_banner( $post_id ) {
	$terms = get_the_terms( $post_id, 'vertical' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return;
	}
	$term  = $terms[0];
	$color = sanitize_hex_color( (string) get_term_meta( $term->term_id, 'thr_vertical_color', true ) );
	?>
	<div class="thr-vertical-banner"<?php echo $color ? ' style="--vert-color:' . esc_attr( $color ) . '"' : ''; ?>>
		<a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="thr-vertical-banner__name"><?php echo esc_html( $term->name ); ?></a>
	</div>
	<?php
}

function thr_the_dek( $post_id = null, $class = 'u-dek' ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}
	$dek = get_post_meta( $post_id, 'thr_dek', true );
	if ( ! empty( $dek ) ) {
		echo '<div class="' . esc_attr( $class ) . '">' . esc_html( $dek ) . '</div>';
	}
}

function thr_time_ago( $post_id = null ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}
	$post_time = get_post_time( 'U', true, $post_id );
	$diff      = time() - $post_time;

	if ( $diff < 3 * DAY_IN_SECONDS ) {
		/* translators: %s: relative time */
		return sprintf( esc_html__( '%s ago', 'thr-theme' ), human_time_diff( $post_time, time() ) );
	}
	return get_the_date( 'M j, Y g:i a', $post_id );
}

function thr_get_image_credit( $attachment_id ) {
	return get_post_meta( $attachment_id, 'thr_credit', true );
}

function thr_get_vertical_color( $post_id = null ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}
	$terms = get_the_terms( $post_id, 'vertical' );
	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		$color = get_term_meta( $terms[0]->term_id, 'thr_vertical_color', true );
		if ( ! empty( $color ) ) {
			return $color;
		}
	}
	return '#D92128';
}
