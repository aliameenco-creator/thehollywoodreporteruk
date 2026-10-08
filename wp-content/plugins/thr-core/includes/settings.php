<?php
/**
 * THR Global Settings Page (Settings -> THR).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_register_settings_menu() {
	add_options_page(
		__( 'THR Publication Settings', 'thr-core' ),
		__( 'THR Settings', 'thr-core' ),
		'manage_options',
		'thr-settings',
		'thr_render_settings_page'
	);
}
add_action( 'admin_menu', 'thr_register_settings_menu' );

function thr_register_settings_fields() {
	register_setting( 'thr_settings_group', 'thr_font_preset', array( 'sanitize_callback' => 'sanitize_key', 'default' => 'free' ) );
	register_setting( 'thr_settings_group', 'thr_adobe_kit_id', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'thr_settings_group', 'thr_logo_url', array( 'sanitize_callback' => 'esc_url_raw' ) );
	register_setting( 'thr_settings_group', 'thr_white_logo_url', array( 'sanitize_callback' => 'esc_url_raw' ) );
	register_setting( 'thr_settings_group', 'thr_tip_email', array( 'sanitize_callback' => 'sanitize_email' ) );
	register_setting( 'thr_settings_group', 'thr_contact_email', array( 'sanitize_callback' => 'sanitize_email' ) );
	register_setting( 'thr_settings_group', 'thr_newsletter_url', array( 'sanitize_callback' => 'esc_url_raw' ) );
	register_setting(
		'thr_settings_group',
		'thr_mobile_tabbar',
		array(
			'sanitize_callback' => function ( $value ) {
				return $value ? '1' : '0';
			},
			'default'           => '1',
		)
	);
	register_setting( 'thr_settings_group', 'thr_magazine_url', array( 'sanitize_callback' => 'esc_url_raw' ) );
	register_setting( 'thr_settings_group', 'thr_magazine_cover_url', array( 'sanitize_callback' => 'esc_url_raw' ) );

	$socials = array( 'facebook', 'instagram', 'linkedin', 'threads', 'tiktok', 'twitter', 'youtube' );
	foreach ( $socials as $soc ) {
		register_setting( 'thr_settings_group', 'thr_social_' . $soc, array( 'sanitize_callback' => 'esc_url_raw' ) );
	}
}
add_action( 'admin_init', 'thr_register_settings_fields' );

function thr_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$font_preset = get_option( 'thr_font_preset', 'free' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'The Hollywood Reporter UK — Publication Settings', 'thr-core' ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'thr_settings_group' );
			do_settings_sections( 'thr-settings' );
			?>

			<h2 class="title"><?php esc_html_e( 'Typography & Fonts', 'thr-core' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="thr_font_preset"><?php esc_html_e( 'Font Preset', 'thr-core' ); ?></label></th>
					<td>
						<select name="thr_font_preset" id="thr_font_preset">
							<option value="free" <?php selected( $font_preset, 'free' ); ?>><?php esc_html_e( 'Free (Instrument Serif headlines + Newsreader + Karla)', 'thr-core' ); ?></option>
							<option value="adobe" <?php selected( $font_preset, 'adobe' ); ?>><?php esc_html_e( 'Adobe Fonts (Kepler Std + Karla via Kit ID)', 'thr-core' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Instantly swaps font variables across the entire design system.', 'thr-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="thr_adobe_kit_id"><?php esc_html_e( 'Adobe Fonts Project ID', 'thr-core' ); ?></label></th>
					<td>
						<input type="text" name="thr_adobe_kit_id" id="thr_adobe_kit_id" value="<?php echo esc_attr( get_option( 'thr_adobe_kit_id' ) ); ?>" class="regular-text" />
					</td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'Brand Assets & Newsroom', 'thr-core' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="thr_logo_url"><?php esc_html_e( 'Red Logo URL (SVG)', 'thr-core' ); ?></label></th>
					<td><input type="url" name="thr_logo_url" id="thr_logo_url" value="<?php echo esc_url( get_option( 'thr_logo_url' ) ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="thr_white_logo_url"><?php esc_html_e( 'White Logo URL (SVG for dark drawer)', 'thr-core' ); ?></label></th>
					<td><input type="url" name="thr_white_logo_url" id="thr_white_logo_url" value="<?php echo esc_url( get_option( 'thr_white_logo_url' ) ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="thr_tip_email"><?php esc_html_e( 'Tip Line Email Recipient', 'thr-core' ); ?></label></th>
					<td><input type="email" name="thr_tip_email" id="thr_tip_email" value="<?php echo esc_attr( get_option( 'thr_tip_email', 'tips@thehollywoodreporter.co.uk' ) ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="thr_contact_email"><?php esc_html_e( 'Contact Form Email Recipient', 'thr-core' ); ?></label></th>
					<td>
						<input type="email" name="thr_contact_email" id="thr_contact_email" value="<?php echo esc_attr( get_option( 'thr_contact_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Messages from the Contact Us page go here.', 'thr-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="thr_newsletter_url"><?php esc_html_e( 'Newsletter Signup Action URL', 'thr-core' ); ?></label></th>
					<td>
						<input type="url" name="thr_newsletter_url" id="thr_newsletter_url" value="<?php echo esc_url( get_option( 'thr_newsletter_url' ) ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Leave blank to collect sign-ups in WordPress (Newsletter Subscribers menu).', 'thr-core' ); ?></p>
					</td>
				</tr>
			</table>

			<?php thr_render_homepage_settings(); ?>

			<h2 class="title"><?php esc_html_e( 'Mobile', 'thr-core' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Bottom Tab Bar', 'thr-core' ); ?></th>
					<td>
						<input type="hidden" name="thr_mobile_tabbar" value="0" />
						<label><input type="checkbox" name="thr_mobile_tabbar" value="1" <?php checked( get_option( 'thr_mobile_tabbar', '1' ), '1' ); ?> /> <?php esc_html_e( 'Show the Home / Sections / Search / Video / Newsletters bar on phones', 'thr-core' ); ?></label>
						<p class="description"><?php esc_html_e( 'Shown on the homepage, section pages and search. Hidden while reading an article.', 'thr-core' ); ?></p>
					</td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( '"Get the Magazine" Promotion', 'thr-core' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="thr_magazine_url"><?php esc_html_e( 'Subscription URL', 'thr-core' ); ?></label></th>
					<td><input type="url" name="thr_magazine_url" id="thr_magazine_url" value="<?php echo esc_url( get_option( 'thr_magazine_url' ) ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="thr_magazine_cover_url"><?php esc_html_e( 'Latest Issue Cover Image URL', 'thr-core' ); ?></label></th>
					<td><input type="url" name="thr_magazine_cover_url" id="thr_magazine_cover_url" value="<?php echo esc_url( get_option( 'thr_magazine_cover_url' ) ); ?>" class="regular-text" /></td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'Social Media Profiles', 'thr-core' ); ?></h2>
			<table class="form-table">
				<?php
				$socials = array(
					'twitter'   => 'X / Twitter',
					'instagram' => 'Instagram',
					'facebook'  => 'Facebook',
					'linkedin'  => 'LinkedIn',
					'threads'   => 'Threads',
					'tiktok'    => 'TikTok',
					'youtube'   => 'YouTube',
				);
				foreach ( $socials as $slug => $label ) :
					$val = get_option( 'thr_social_' . $slug, '' );
					?>
					<tr>
						<th scope="row"><label for="thr_social_<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><input type="url" name="thr_social_<?php echo esc_attr( $slug ); ?>" id="thr_social_<?php echo esc_attr( $slug ); ?>" value="<?php echo esc_url( $val ); ?>" class="regular-text" /></td>
					</tr>
				<?php endforeach; ?>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
