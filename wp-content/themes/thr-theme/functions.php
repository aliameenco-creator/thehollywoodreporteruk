<?php
/**
 * The Hollywood Reporter UK Theme Functions and Definitions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'THR_THEME_VERSION', '1.1.0' );

require_once get_template_directory() . '/inc/template-tags.php';
require_once get_template_directory() . '/inc/icons.php';
require_once get_template_directory() . '/inc/navigation.php';

function thr_theme_setup() {
	load_theme_textdomain( 'thr-theme', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 120,
			'width'       => 480,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'Section Bar (Top Navigation)', 'thr-theme' ),
			'mega'    => __( 'Mega Menu Drawer (2 levels: column → links)', 'thr-theme' ),
			'footer'  => __( 'Footer Main Menu', 'thr-theme' ),
		)
	);
}
add_action( 'after_setup_theme', 'thr_theme_setup' );

/**
 * Typography: free preset (Newsreader + Karla) by default, or Adobe Fonts
 * (Kepler Std Semicondensed Display, as THR uses) when chosen in Settings → THR Settings.
 */
function thr_theme_fonts() {
	$preset = get_option( 'thr_font_preset', 'free' );
	$kit_id = sanitize_key( (string) get_option( 'thr_adobe_kit_id', '' ) );

	wp_enqueue_style(
		'thr-fonts',
		'https://fonts.googleapis.com/css2?family=Karla:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400&display=swap',
		array(),
		null
	);

	if ( 'adobe' === $preset && '' !== $kit_id ) {
		wp_enqueue_style( 'thr-adobe-fonts', 'https://use.typekit.net/' . $kit_id . '.css', array(), null );
		wp_add_inline_style(
			'thr-adobe-fonts',
			':root{--font-display:"kepler-std-semicondensed-dis","kepler-std",Georgia,serif;--font-serif:"kepler-std",Georgia,serif;}'
		);
	}
}
add_action( 'wp_enqueue_scripts', 'thr_theme_fonts' );

function thr_theme_scripts() {
	$uri = get_template_directory_uri() . '/assets/css/';
	wp_enqueue_style( 'thr-variables', $uri . 'variables.css', array( 'thr-fonts' ), THR_THEME_VERSION );
	wp_enqueue_style( 'thr-base', $uri . 'base.css', array( 'thr-variables' ), THR_THEME_VERSION );
	wp_enqueue_style( 'thr-layout', $uri . 'layout.css', array( 'thr-base' ), THR_THEME_VERSION );
	wp_enqueue_style( 'thr-components', $uri . 'components.css', array( 'thr-layout' ), THR_THEME_VERSION );

	wp_enqueue_script(
		'thr-main-js',
		get_template_directory_uri() . '/assets/js/main.js',
		array(),
		THR_THEME_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	wp_localize_script(
		'thr-main-js',
		'thrSettings',
		array(
			'searchApi' => esc_url_raw( rest_url( 'thr/v1/search' ) ),
			'trackApi'  => esc_url_raw( rest_url( 'thr/v1/track-view' ) ),
			'noResults' => __( 'No matching stories found', 'thr-theme' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'thr_theme_scripts' );

/**
 * Site wordmark: the uploaded custom logo or THR Settings logo, else a typeset wordmark.
 *
 * @param string $context Class modifier: header, menu, footer.
 */
function thr_the_logo( $context = 'header' ) {
	$classes = 'thr-logo thr-logo--' . sanitize_html_class( $context );
	$logo_id = get_theme_mod( 'custom_logo' );
	$img_url = get_option( 'thr_logo_url' );
	?>
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="<?php echo esc_attr( $classes ); ?>" aria-label="<?php esc_attr_e( 'The Hollywood Reporter UK — Home', 'thr-theme' ); ?>">
		<?php if ( $logo_id ) : ?>
			<?php echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'thr-logo__img', 'alt' => '' ) ); ?>
		<?php elseif ( $img_url ) : ?>
			<img class="thr-logo__img" src="<?php echo esc_url( $img_url ); ?>" alt="" />
		<?php else : ?>
			<?php thr_the_wordmark(); ?>
		<?php endif; ?>
	</a>
	<?php
}

/**
 * Typeset wordmark spans, for use inside a .thr-logo element (no link).
 */
function thr_the_wordmark() {
	?>
	<span class="thr-logo__the" aria-hidden="true">The</span>
	<span class="thr-logo__main" aria-hidden="true">Hollywood</span>
	<span class="thr-logo__reporter" aria-hidden="true">Reporter</span>
	<?php
}
