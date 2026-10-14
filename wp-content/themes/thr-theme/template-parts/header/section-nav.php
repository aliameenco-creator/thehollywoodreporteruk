<?php
/**
 * Section links with hover dropdowns (Appearance → Menus → Main Navigation).
 * Sub-items of a top-level item become its dropdown; the chevron button opens
 * it for keyboard and touch users.
 *
 * @param array $args { context: header|sticky }.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$context = isset( $args['context'] ) ? sanitize_key( $args['context'] ) : 'header';
$list    = 'sticky' === $context ? 'thr-sticky__list' : 'thr-section-bar__list';
?>
<ul class="<?php echo esc_attr( $list ); ?>">
	<?php foreach ( thr_get_section_bar() as $i => $item ) : ?>
		<?php
		$has_children = ! empty( $item['children'] );
		$dropdown_id  = 'thrNav-' . $context . '-' . (int) $i;
		?>
		<li class="thr-nav__item<?php echo $has_children ? ' has-dropdown' : ''; ?>">
			<?php if ( '' !== $item['url'] ) : ?>
				<a href="<?php echo esc_url( thr_menu_url( $item['url'] ) ); ?>" class="thr-section-bar__link"><?php echo esc_html( $item['label'] ); ?></a>
			<?php else : ?>
				<span class="thr-section-bar__link"><?php echo esc_html( $item['label'] ); ?></span>
			<?php endif; ?>

			<?php if ( $has_children ) : ?>
				<button type="button" class="thr-nav__toggle js-thr-dropdown" aria-expanded="false" aria-controls="<?php echo esc_attr( $dropdown_id ); ?>">
					<span class="screen-reader-text">
						<?php
						/* translators: %s: section name. */
						printf( esc_html__( '%s: more sections', 'thr-theme' ), esc_html( $item['label'] ) );
						?>
					</span>
					<?php echo thr_icon( 'chevron', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
				<ul class="thr-nav__dropdown" id="<?php echo esc_attr( $dropdown_id ); ?>">
					<?php foreach ( $item['children'] as $child ) : ?>
						<li><a href="<?php echo esc_url( thr_menu_url( $child[1] ) ); ?>"><?php echo esc_html( $child[0] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
