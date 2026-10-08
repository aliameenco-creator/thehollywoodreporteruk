<?php
/**
 * Story card and module helpers for the homepage and vertical pages.
 *
 * One markup shape for every card; the modifier class decides the layout in
 * modules.css. Keeps homepage templates short and the HTML consistent.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print a story card.
 *
 * @param int   $post_id Post ID.
 * @param array $args {
 *     @type string      $mod    Modifier: thr-story--{mod}.
 *     @type string|bool $image  Image size, or false for none.
 *     @type bool        $framed Thin frame with white inset around the image.
 *     @type string|bool $meta   'section' (subsection label), 'parent' (top section), 'time',
 *                               'label-time' (label + time) or false.
 *     @type bool        $dek    Show the dek / excerpt.
 *     @type bool        $by     Show the byline.
 *     @type string      $tag    Title element.
 *     @type bool        $play   Show a play badge on the image (videos).
 * }
 */
function thr_story_card( $post_id, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'mod'    => 'grid',
			'image'  => 'thr-card-s',
			'framed' => false,
			'meta'   => 'section',
			'dek'    => false,
			'by'     => false,
			'tag'    => 'h3',
			'play'   => false,
		)
	);

	$url   = get_permalink( $post_id );
	$title = get_the_title( $post_id );
	$tag   = tag_escape( $args['tag'] );
	?>
	<article class="thr-story thr-story--<?php echo esc_attr( $args['mod'] ); ?>">
		<?php if ( $args['image'] && has_post_thumbnail( $post_id ) ) : ?>
			<a href="<?php echo esc_url( $url ); ?>" class="thr-story__media<?php echo $args['framed'] ? ' thr-framed-media' : ''; ?>" tabindex="-1" aria-hidden="true">
				<?php echo get_the_post_thumbnail( $post_id, $args['image'], array( 'alt' => '', 'loading' => 'lazy' ) ); ?>
				<?php if ( $args['play'] ) : ?>
					<span class="thr-story__play"><?php echo thr_icon( 'play-solid', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php endif; ?>
			</a>
		<?php endif; ?>
		<div class="thr-story__body">
			<?php thr_story_meta( $post_id, $args['meta'] ); ?>
			<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag_escape() above. ?> class="thr-story__title"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a></<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php if ( $args['dek'] ) : ?>
				<?php $dek = thr_story_dek( $post_id ); ?>
				<?php if ( $dek ) : ?>
					<p class="thr-story__dek"><?php echo esc_html( $dek ); ?></p>
				<?php endif; ?>
			<?php endif; ?>
			<?php if ( $args['by'] && function_exists( 'thr_get_byline' ) ) : ?>
				<div class="thr-story__by"><?php echo wp_kses_post( thr_get_byline( $post_id ) ); ?></div>
			<?php endif; ?>
		</div>
	</article>
	<?php
}

/**
 * Card meta line above the title.
 *
 * @param int         $post_id Post ID.
 * @param string|bool $type    See thr_story_card().
 */
function thr_story_meta( $post_id, $type ) {
	if ( ! $type ) {
		return;
	}

	$cat   = function_exists( 'thr_get_primary_category' ) ? thr_get_primary_category( $post_id ) : null;
	$label = '';
	$link  = '';
	if ( $cat ) {
		$show = $cat;
		if ( 'parent' === $type && $cat->parent ) {
			$parent = get_term( $cat->parent, 'category' );
			$show   = ( $parent && ! is_wp_error( $parent ) ) ? $parent : $cat;
		}
		$label = $show->name;
		$link  = get_category_link( $show );
	}

	echo '<div class="thr-story__meta">';
	if ( $label && in_array( $type, array( 'section', 'parent', 'label-time' ), true ) ) {
		echo '<a class="thr-story__label" href="' . esc_url( $link ) . '">' . esc_html( $label ) . '</a>';
	}
	if ( in_array( $type, array( 'time', 'label-time' ), true ) ) {
		echo '<time class="thr-story__time" datetime="' . esc_attr( get_the_date( 'c', $post_id ) ) . '">' . esc_html( thr_story_time( $post_id ) ) . '</time>';
	}
	echo '</div>';
}

/**
 * THR-style timestamp: clock time today ("1:59 PM"), "x hours ago" up to a day, then the date.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function thr_story_time( $post_id ) {
	$published = (int) get_post_time( 'U', true, $post_id );
	$age       = time() - $published;

	if ( $age < DAY_IN_SECONDS && wp_date( 'Ymd', $published ) === wp_date( 'Ymd' ) ) {
		return wp_date( 'g:i A', $published );
	}
	if ( $age < 3 * DAY_IN_SECONDS ) {
		/* translators: %s: human time difference, e.g. "2 days". */
		return sprintf( __( '%s ago', 'thr-theme' ), human_time_diff( $published ) );
	}
	return wp_date( 'M j, Y', $published );
}

/**
 * Dek for cards: the dek field, else a short excerpt.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function thr_story_dek( $post_id ) {
	$dek = (string) get_post_meta( $post_id, 'thr_dek', true );
	if ( '' !== $dek ) {
		return $dek;
	}
	return wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post_id ) ), 28 );
}

/**
 * Module heading with an optional SEE ALL link.
 *
 * @param string $title   Heading text.
 * @param string $link    SEE ALL URL ('' for none).
 * @param string $mod     Modifier class (e.g. 'large', 'red').
 * @param string $tagline Optional italic tagline.
 */
function thr_module_head( $title, $link = '', $mod = '', $tagline = '' ) {
	?>
	<div class="thr-mhead<?php echo $mod ? ' thr-mhead--' . esc_attr( $mod ) : ''; ?>">
		<h2 class="thr-mhead__title"><?php echo esc_html( $title ); ?></h2>
		<?php if ( $tagline ) : ?>
			<span class="thr-mhead__tagline"><?php echo esc_html( $tagline ); ?></span>
		<?php endif; ?>
		<?php if ( $link ) : ?>
			<a class="thr-mhead__all" href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'See All', 'thr-theme' ); ?></a>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * "VIEW ALL ➜➜" link in the given colour.
 *
 * @param string $link  URL.
 * @param string $label Link text.
 */
function thr_view_all( $link, $label = '' ) {
	?>
	<a class="thr-viewall" href="<?php echo esc_url( $link ); ?>">
		<span><?php echo esc_html( $label ?: __( 'View All', 'thr-theme' ) ); ?></span>
		<?php echo thr_icon( 'double-arrow', 30 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</a>
	<?php
}

/**
 * Posts for a module as an array of IDs (empty when the source has none).
 *
 * @param array $args THR_Query::get() arguments.
 * @return int[]
 */
function thr_module_posts( $args ) {
	if ( ! class_exists( 'THR_Query' ) ) {
		return array();
	}
	$args['dedupe'] = $args['dedupe'] ?? false;
	$query          = THR_Query::get( $args );
	return wp_list_pluck( $query->posts, 'ID' );
}

/**
 * Brand colour of a vertical term.
 *
 * @param WP_Term $term Vertical.
 * @return string Hex colour.
 */
function thr_vertical_color( $term ) {
	$color = sanitize_hex_color( (string) get_term_meta( $term->term_id, 'thr_vertical_color', true ) );
	return $color ?: '#D92128';
}

/**
 * Subsection label without the section's own word, as THR shows it:
 * "Movie News" under Movies → "News", "TV Reviews" under TV → "Reviews".
 *
 * @param string $name    Subsection name.
 * @param string $section Parent section name.
 * @return string
 */
function thr_subsection_label( $name, $section ) {
	$words = preg_split( '/\s+/', trim( $name ) );
	if ( count( $words ) < 2 || '' === $section ) {
		return $name;
	}
	$stem = strtolower( substr( $section, 0, 4 ) );
	return 0 === strpos( strtolower( $words[0] ), $stem ) ? implode( ' ', array_slice( $words, 1 ) ) : $name;
}
