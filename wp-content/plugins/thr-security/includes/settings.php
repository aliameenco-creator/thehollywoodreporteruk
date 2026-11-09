<?php
/**
 * Settings → THR Security: one switch per protection, all off by default.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array
 */
function thr_security_defaults() {
	return array(
		'login_on'        => 0,
		'login_slug'      => '',
		'limit_on'        => 0,
		'max_attempts'    => 5,
		'lock_minutes'    => 30,
		'allow_ips'       => '',
		'ip_source'       => 'remote',
		'generic_errors'  => 0,
		'notify_admin'    => 0,
		'xmlrpc_off'      => 0,
		'hide_users'      => 0,
		'headers_on'      => 0,
		'hsts_on'         => 0,
		'editor_off'      => 0,
		'hide_version'    => 0,
		'app_pw_limited'  => 0,
	);
}

/**
 * @return array
 */
function thr_security_settings() {
	$saved = get_option( 'thr_security_settings', array() );
	return array_merge( thr_security_defaults(), is_array( $saved ) ? $saved : array() );
}

/**
 * Whether a protection is on. Everything is off while THR_SECURITY_OFF is set.
 *
 * @param string $key Setting.
 * @return bool
 */
function thr_security_on( $key ) {
	if ( defined( 'THR_SECURITY_OFF' ) && THR_SECURITY_OFF ) {
		return false;
	}
	return ! empty( thr_security_settings()[ $key ] );
}

/**
 * Words that can't be the login address: WordPress's own paths and the ones bots try first.
 *
 * @return string[]
 */
function thr_security_reserved_slugs() {
	return array( 'login', 'admin', 'wp-admin', 'wp-login', 'wp-login-php', 'dashboard', 'signin', 'sign-in', 'wp-content', 'wp-includes', 'wp-json', 'feed', 'comments', 'author', 'search', 'page', 'user', 'account', 'register' );
}

/**
 * Why a login address can't be used, or '' when it can.
 *
 * @param string $slug Candidate.
 * @return string
 */
function thr_security_slug_problem( $slug ) {
	if ( strlen( $slug ) < 6 ) {
		return __( 'Use at least 6 characters.', 'thr-security' );
	}
	if ( in_array( $slug, thr_security_reserved_slugs(), true ) ) {
		return __( 'That word is reserved or too easy to guess.', 'thr-security' );
	}
	if ( get_page_by_path( $slug, OBJECT, array( 'page', 'post' ) ) || term_exists( $slug ) ) {
		return __( 'A page, post or section already uses that address.', 'thr-security' );
	}
	return '';
}

function thr_security_register_settings() {
	register_setting(
		'thr_security_group',
		'thr_security_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'thr_security_sanitize',
		)
	);
}
add_action( 'admin_init', 'thr_security_register_settings' );

/**
 * @param mixed $input Raw input.
 * @return array
 */
function thr_security_sanitize( $input ) {
	$input = is_array( $input ) ? $input : array();
	$old   = thr_security_settings();
	$clean = array();

	foreach ( array( 'login_on', 'limit_on', 'generic_errors', 'notify_admin', 'xmlrpc_off', 'hide_users', 'headers_on', 'hsts_on', 'editor_off', 'hide_version', 'app_pw_limited' ) as $key ) {
		$clean[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
	}
	$clean['max_attempts'] = isset( $input['max_attempts'] ) ? min( 20, max( 3, absint( $input['max_attempts'] ) ) ) : 5;
	$clean['lock_minutes'] = isset( $input['lock_minutes'] ) ? min( 1440, max( 5, absint( $input['lock_minutes'] ) ) ) : 30;
	$clean['ip_source']    = isset( $input['ip_source'] ) && in_array( $input['ip_source'], array( 'remote', 'cloudflare', 'forwarded' ), true ) ? $input['ip_source'] : 'remote';

	$ips = array();
	foreach ( preg_split( '/[\s,]+/', isset( $input['allow_ips'] ) ? (string) $input['allow_ips'] : '' ) as $ip ) {
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$ips[] = $ip;
		}
	}
	$clean['allow_ips'] = implode( "\n", array_unique( $ips ) );

	$slug                = isset( $input['login_slug'] ) ? sanitize_title( $input['login_slug'] ) : '';
	$problem             = thr_security_slug_problem( $slug );
	$clean['login_slug'] = $problem ? $old['login_slug'] : $slug;
	if ( $clean['login_on'] && ( '' === $clean['login_slug'] || $problem ) ) {
		add_settings_error( 'thr_security_settings', 'thr_slug', __( 'Private login address not changed: ', 'thr-security' ) . ( $problem ? $problem : __( 'enter an address first.', 'thr-security' ) ) );
		if ( '' === $clean['login_slug'] ) {
			$clean['login_on'] = 0;
		}
	}

	// Never leave someone guessing: email the new login address whenever it is switched on or changed.
	if ( $clean['login_on'] && ( ! $old['login_on'] || $old['login_slug'] !== $clean['login_slug'] ) ) {
		thr_security_email_login_url( $clean['login_slug'] );
	}

	// HSTS only makes sense on top of the other headers.
	if ( ! $clean['headers_on'] ) {
		$clean['hsts_on'] = 0;
	}
	return $clean;
}

/**
 * @param string $slug Login address.
 */
function thr_security_email_login_url( $slug ) {
	// WordPress runs the sanitizer twice when the option is first created; send one email.
	static $sent = array();
	if ( isset( $sent[ $slug ] ) ) {
		return;
	}
	$sent[ $slug ] = true;
	$url        = home_url( '/' . $slug . '/' );
	$recipients = array_unique( array_filter( array( get_option( 'admin_email' ), wp_get_current_user()->user_email ) ) );
	foreach ( $recipients as $to ) {
		wp_mail(
			$to,
			sprintf( /* translators: %s: site name. */ __( '[%s] New login address', 'thr-security' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ),
			sprintf(
				/* translators: 1: login URL. */
				__( "The login page has moved. Bookmark this address:\n\n%1\$s\n\nwp-login.php and /wp-admin/ now show \"not found\" to anyone who isn't logged in.\n\nLocked out? Add define( 'THR_SECURITY_OFF', true ); to wp-config.php, or rename the thr-security plugin folder.", 'thr-security' ),
				$url
			)
		);
	}
}

function thr_security_menu() {
	add_options_page( __( 'THR Security', 'thr-security' ), __( 'THR Security', 'thr-security' ), 'manage_options', 'thr-security', 'thr_security_render' );
}
add_action( 'admin_menu', 'thr_security_menu' );

/**
 * Clear lockouts.
 */
function thr_security_clear_lockouts() {
	check_admin_referer( 'thr_security_clear' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'thr-security' ), 403 );
	}
	foreach ( array_keys( thr_security_lockouts() ) as $ip ) {
		delete_transient( 'thr_sec_lock_' . md5( $ip ) );
		delete_transient( 'thr_sec_fail_' . md5( $ip ) );
	}
	delete_option( 'thr_security_lockouts' );
	wp_safe_redirect( admin_url( 'options-general.php?page=thr-security&cleared=1' ) );
	exit;
}
add_action( 'admin_post_thr_security_clear', 'thr_security_clear_lockouts' );

/**
 * Current lockouts: IP => unlock time.
 *
 * @return array
 */
function thr_security_lockouts() {
	$list = get_option( 'thr_security_lockouts', array() );
	$list = is_array( $list ) ? $list : array();
	return array_filter(
		$list,
		static function ( $until ) {
			return (int) $until > time();
		}
	);
}

/**
 * One switch row.
 *
 * @param string $key   Setting.
 * @param string $label Title.
 * @param string $help  What it does.
 * @param array  $s     Settings.
 */
function thr_security_switch( $key, $label, $help, $s ) {
	?>
	<tr>
		<th scope="row"><?php echo esc_html( $label ); ?></th>
		<td>
			<label><input type="checkbox" name="thr_security_settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $s[ $key ] ); ?> /> <?php esc_html_e( 'On', 'thr-security' ); ?></label>
			<p class="description"><?php echo esc_html( $help ); ?></p>
		</td>
	</tr>
	<?php
}

function thr_security_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s        = thr_security_settings();
	$off      = defined( 'THR_SECURITY_OFF' ) && THR_SECURITY_OFF;
	$lockouts = thr_security_lockouts();
	$suggest  = 'thr-' . strtolower( wp_generate_password( 8, false ) );
	// phpcs:disable WordPress.Security.ValidatedSanitizedInput -- shown to an administrator only, escaped below.
	$seen = array(
		'remote'     => isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '',
		'cloudflare' => isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) : '',
		'forwarded'  => isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) : '',
	);
	// phpcs:enable
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'THR Security', 'thr-security' ); ?></h1>

		<?php if ( $off ) : ?>
			<div class="notice notice-warning"><p><strong><?php esc_html_e( 'All protections are off because THR_SECURITY_OFF is set in wp-config.php. Remove that line to turn them back on.', 'thr-security' ); ?></strong></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['cleared'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Lockouts cleared.', 'thr-security' ); ?></p></div>
		<?php endif; ?>
		<?php settings_errors( 'thr_security_settings' ); ?>

		<div class="notice notice-info inline" style="padding:8px 12px">
			<p><strong><?php esc_html_e( 'If you are ever locked out:', 'thr-security' ); ?></strong> <?php esc_html_e( 'add', 'thr-security' ); ?> <code>define( 'THR_SECURITY_OFF', true );</code> <?php esc_html_e( 'to wp-config.php in Hostinger\'s File Manager, or rename the folder wp-content/plugins/thr-security. Every protection switches off at once.', 'thr-security' ); ?></p>
		</div>

		<form method="post" action="options.php">
			<?php settings_fields( 'thr_security_group' ); ?>

			<h2 class="title"><?php esc_html_e( 'Login', 'thr-security' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Private login address', 'thr-security' ); ?></th>
					<td>
						<label><input type="checkbox" name="thr_security_settings[login_on]" value="1" <?php checked( $s['login_on'] ); ?> /> <?php esc_html_e( 'On', 'thr-security' ); ?></label>
						<p>
							<code><?php echo esc_html( home_url( '/' ) ); ?></code><input type="text" class="regular-text code" name="thr_security_settings[login_slug]" value="<?php echo esc_attr( $s['login_slug'] ? $s['login_slug'] : $suggest ); ?>" />
						</p>
						<p class="description"><?php esc_html_e( 'Logging in moves to this address. wp-login.php and /wp-admin/ show "not found" to anyone not logged in, which stops most automated password guessing. The new address is emailed to you and the site admin when you save. Bookmark it.', 'thr-security' ); ?></p>
						<?php if ( $s['login_on'] && $s['login_slug'] && ! $off ) : ?>
							<p><strong><?php esc_html_e( 'Current login address:', 'thr-security' ); ?></strong> <a href="<?php echo esc_url( home_url( '/' . $s['login_slug'] . '/' ) ); ?>"><?php echo esc_html( home_url( '/' . $s['login_slug'] . '/' ) ); ?></a></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Limit login attempts', 'thr-security' ); ?></th>
					<td>
						<label><input type="checkbox" name="thr_security_settings[limit_on]" value="1" <?php checked( $s['limit_on'] ); ?> /> <?php esc_html_e( 'On', 'thr-security' ); ?></label>
						<p>
							<?php esc_html_e( 'Lock an address out after', 'thr-security' ); ?>
							<input type="number" min="3" max="20" class="small-text" name="thr_security_settings[max_attempts]" value="<?php echo esc_attr( $s['max_attempts'] ); ?>" />
							<?php esc_html_e( 'wrong passwords, for', 'thr-security' ); ?>
							<input type="number" min="5" max="1440" class="small-text" name="thr_security_settings[lock_minutes]" value="<?php echo esc_attr( $s['lock_minutes'] ); ?>" />
							<?php esc_html_e( 'minutes.', 'thr-security' ); ?>
						</p>
						<p>
							<label for="thr-sec-allow"><?php esc_html_e( 'Never lock out these IP addresses (office, VPN), one per line:', 'thr-security' ); ?></label><br />
							<textarea id="thr-sec-allow" class="large-text code" rows="2" name="thr_security_settings[allow_ips]"><?php echo esc_textarea( $s['allow_ips'] ); ?></textarea>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Visitor address comes from', 'thr-security' ); ?></th>
					<td>
						<?php
						$sources = array(
							'remote'     => __( 'The connection (REMOTE_ADDR). Right while the site is not behind a proxy.', 'thr-security' ),
							'cloudflare' => __( 'Cloudflare (CF-Connecting-IP). Use once the main site is behind Cloudflare\'s orange cloud.', 'thr-security' ),
							'forwarded'  => __( 'X-Forwarded-For. Only if the host\'s CDN sends the real address this way.', 'thr-security' ),
						);
						foreach ( $sources as $value => $label ) :
							?>
							<label><input type="radio" name="thr_security_settings[ip_source]" value="<?php echo esc_attr( $value ); ?>" <?php checked( $s['ip_source'], $value ); ?> /> <?php echo esc_html( $label ); ?></label>
							<?php if ( $seen[ $value ] ) : ?>
								<code><?php echo esc_html( $seen[ $value ] ); ?></code>
							<?php endif; ?>
							<br />
						<?php endforeach; ?>
						<p class="description"><?php esc_html_e( 'Pick the option that shows your own address (search "what is my IP" to check). If it shows a server address instead, every visitor would share one lockout.', 'thr-security' ); ?></p>
					</td>
				</tr>
				<?php
				thr_security_switch( 'generic_errors', __( 'Vague login errors', 'thr-security' ), __( 'Say "the details are incorrect" instead of telling a guesser whether the user name exists.', 'thr-security' ), $s );
				thr_security_switch( 'notify_admin', __( 'Email on administrator login', 'thr-security' ), __( 'Email the site admin whenever an administrator logs in, with the time and address, so a stolen password is noticed.', 'thr-security' ), $s );
				thr_security_switch( 'app_pw_limited', __( 'Limit application passwords', 'thr-security' ), __( 'Only administrators and the Distribution automation account (n8n) can create application passwords for API access.', 'thr-security' ), $s );
				?>
			</table>

			<h2 class="title"><?php esc_html_e( 'Site', 'thr-security' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				thr_security_switch( 'xmlrpc_off', __( 'Turn off XML-RPC', 'thr-security' ), __( 'Blocks xmlrpc.php, an old remote-publishing door that bots use to guess passwords in bulk. The site, n8n and the REST API don\'t need it.', 'thr-security' ), $s );
				thr_security_switch( 'hide_users', __( 'Hide user names', 'thr-security' ), __( 'Stops logged-out visitors listing accounts through the REST API (/wp-json/wp/v2/users), the ?author=1 trick and the users sitemap. Author pages and bylines keep working.', 'thr-security' ), $s );
				thr_security_switch( 'headers_on', __( 'Security headers', 'thr-security' ), __( 'Tells browsers not to let other sites frame these pages, not to guess file types, to send less referrer detail, and to block camera, microphone and location access.', 'thr-security' ), $s );
				thr_security_switch( 'hsts_on', __( 'Force HTTPS in browsers (HSTS)', 'thr-security' ), __( 'Browsers remember to use HTTPS only, for a year. Hard to undo: only switch on once HTTPS works everywhere on the site. Needs security headers on.', 'thr-security' ), $s );
				thr_security_switch( 'editor_off', __( 'Turn off the code editors', 'thr-security' ), __( 'Removes Appearance → Theme File Editor and Plugins → Plugin File Editor, so a stolen admin login can\'t edit PHP. Installing plugins and themes from zips still works.', 'thr-security' ), $s );
				thr_security_switch( 'hide_version', __( 'Hide the WordPress version', 'thr-security' ), __( 'Removes the version number from the page source and feeds.', 'thr-security' ), $s );
				?>
			</table>

			<?php submit_button(); ?>
		</form>

		<h2><?php esc_html_e( 'Locked-out addresses', 'thr-security' ); ?></h2>
		<?php if ( ! $lockouts ) : ?>
			<p><?php esc_html_e( 'None right now.', 'thr-security' ); ?></p>
		<?php else : ?>
			<table class="widefat striped" style="max-width:600px">
				<thead><tr><th><?php esc_html_e( 'Address', 'thr-security' ); ?></th><th><?php esc_html_e( 'Unlocks', 'thr-security' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $lockouts as $ip => $until ) : ?>
					<tr><td><code><?php echo esc_html( $ip ); ?></code></td><td><?php echo esc_html( wp_date( 'j M H:i', (int) $until ) ); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:8px">
				<input type="hidden" name="action" value="thr_security_clear" />
				<?php wp_nonce_field( 'thr_security_clear' ); ?>
				<button type="submit" class="button"><?php esc_html_e( 'Clear all lockouts', 'thr-security' ); ?></button>
			</form>
		<?php endif; ?>
	</div>
	<?php
}
