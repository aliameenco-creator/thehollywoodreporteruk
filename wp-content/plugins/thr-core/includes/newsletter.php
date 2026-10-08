<?php
/**
 * Newsletter sign-ups: the [thr_newsletter_signup] form, a private subscriber
 * list in wp-admin (admins only) with CSV export, and a hook for mail providers.
 *
 * Sending is left to a mail service (Brevo, Mailchimp...): export the CSV, or
 * hook `thr_newsletter_signup` to push each sign-up to the provider's API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Newsletters readers can choose. Filter `thr_newsletters` to change the line-up.
 *
 * @return array[] slug => [ name, description, frequency ].
 */
function thr_newsletters() {
	return apply_filters(
		'thr_newsletters',
		array(
			'today'          => array( __( 'Today in Entertainment', 'thr-core' ), __( 'The day’s biggest film, TV and music stories, every weekday morning.', 'thr-core' ), __( 'Weekdays', 'thr-core' ) ),
			'weekender'      => array( __( 'Weekender', 'thr-core' ), __( 'The best of the week: features, reviews and what to watch.', 'thr-core' ), __( 'Saturdays', 'thr-core' ) ),
			'awards'         => array( __( 'Awards Forecast', 'thr-core' ), __( 'BAFTA and Oscar season analysis, predictions and results.', 'thr-core' ), __( 'Weekly in season', 'thr-core' ) ),
			'heat-vision'    => array( __( 'Heat Vision', 'thr-core' ), __( 'Superheroes, sci-fi, fantasy and horror news.', 'thr-core' ), __( 'Weekly', 'thr-core' ) ),
			'now-see-this'   => array( __( 'Now See This', 'thr-core' ), __( 'What to stream and see in cinemas this week.', 'thr-core' ), __( 'Weekly', 'thr-core' ) ),
			'london-calling' => array( __( 'London Calling', 'thr-core' ), __( 'The UK and European film and TV business.', 'thr-core' ), __( 'Weekly', 'thr-core' ) ),
			'breaking'       => array( __( 'Breaking News Alerts', 'thr-core' ), __( 'Major stories as they break.', 'thr-core' ), __( 'As it happens', 'thr-core' ) ),
		)
	);
}

function thr_register_subscriber_type() {
	$admin_only = array(
		'create_posts'           => 'do_not_allow',
		'edit_posts'             => 'manage_options',
		'edit_others_posts'      => 'manage_options',
		'edit_private_posts'     => 'manage_options',
		'edit_published_posts'   => 'manage_options',
		'delete_posts'           => 'manage_options',
		'delete_others_posts'    => 'manage_options',
		'delete_private_posts'   => 'manage_options',
		'delete_published_posts' => 'manage_options',
		'publish_posts'          => 'manage_options',
		'read_private_posts'     => 'manage_options',
	);

	register_post_type(
		'thr_subscriber',
		array(
			'labels'          => array(
				'name'          => __( 'Newsletter Subscribers', 'thr-core' ),
				'singular_name' => __( 'Subscriber', 'thr-core' ),
				'menu_name'     => __( 'Subscribers', 'thr-core' ),
				'search_items'  => __( 'Search subscribers', 'thr-core' ),
				'not_found'     => __( 'No subscribers yet.', 'thr-core' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_position'   => 26,
			'menu_icon'       => 'dashicons-email-alt',
			'supports'        => array( 'title' ),
			'capabilities'    => $admin_only,
			'map_meta_cap'    => true,
			'rewrite'         => false,
			'query_var'       => false,
			'show_in_rest'    => false,
		)
	);
}
add_action( 'init', 'thr_register_subscriber_type' );

/**
 * Find a subscriber post by email.
 *
 * @param string $email Email address.
 * @return int Post ID or 0.
 */
function thr_find_subscriber( $email ) {
	$ids = get_posts(
		array(
			'post_type'              => 'thr_subscriber',
			'post_status'            => 'any',
			'title'                  => $email,
			'fields'                 => 'ids',
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

function thr_handle_newsletter_signup() {
	if ( ! isset( $_POST['thr_newsletter_action'] ) || 'subscribe' !== $_POST['thr_newsletter_action'] ) {
		return;
	}

	$back = remove_query_arg( array( 'nl_status', 'email' ), wp_get_referer() ?: home_url( '/newsletters/' ) );

	if ( ! isset( $_POST['thr_newsletter_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['thr_newsletter_nonce'] ), 'thr_newsletter_subscribe' ) ) {
		wp_safe_redirect( add_query_arg( 'nl_status', 'expired', $back ) );
		exit;
	}

	if ( ! empty( $_POST['thr_hp_phone'] ) ) {
		wp_safe_redirect( add_query_arg( 'nl_status', 'sent', $back ) );
		exit;
	}

	$ip        = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	$transient = 'thr_nl_rate_' . md5( wp_salt() . $ip );
	$count     = (int) get_transient( $transient );
	if ( $count >= 10 ) {
		wp_safe_redirect( add_query_arg( 'nl_status', 'limit', $back ) );
		exit;
	}

	$email     = isset( $_POST['nl_email'] ) ? sanitize_email( wp_unslash( $_POST['nl_email'] ) ) : '';
	$available = thr_newsletters();
	$chosen    = isset( $_POST['nl_lists'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['nl_lists'] ) ) : array();
	$chosen    = array_values( array_intersect( $chosen, array_keys( $available ) ) );
	$consent   = ! empty( $_POST['nl_consent'] );

	if ( ! is_email( $email ) || ! $chosen || ! $consent ) {
		wp_safe_redirect( add_query_arg( 'nl_status', 'missing', $back ) );
		exit;
	}

	set_transient( $transient, $count + 1, HOUR_IN_SECONDS );

	$email   = strtolower( $email );
	$post_id = thr_find_subscriber( $email );
	if ( $post_id ) {
		$lists = array_values( array_unique( array_merge( (array) get_post_meta( $post_id, 'thr_lists', true ), $chosen ) ) );
	} else {
		$lists   = $chosen;
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'thr_subscriber',
				'post_status' => 'private',
				'post_title'  => $email,
			)
		);
	}

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, 'thr_lists', $lists );
		// Consent record (UK GDPR): when, and on which page.
		update_post_meta( $post_id, 'thr_consent_at', current_time( 'mysql', true ) );
		update_post_meta( $post_id, 'thr_source', esc_url_raw( $back ) );

		/**
		 * Fires after a newsletter sign-up is saved; hook here to send it to a mail provider.
		 *
		 * @param string   $email Subscriber email.
		 * @param string[] $lists Newsletter slugs they are now subscribed to.
		 */
		do_action( 'thr_newsletter_signup', $email, $lists );
	}

	wp_safe_redirect( add_query_arg( 'nl_status', 'sent', $back ) );
	exit;
}
add_action( 'template_redirect', 'thr_handle_newsletter_signup' );

function thr_render_newsletter_signup() {
	$status   = isset( $_GET['nl_status'] ) ? sanitize_key( $_GET['nl_status'] ) : '';
	$prefill  = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
	// A channel's newsletter box links here with ?list=heat-vision; pre-tick that one.
	$preset   = isset( $_GET['list'] ) ? sanitize_key( $_GET['list'] ) : 'today';
	$messages = array(
		'sent'    => __( 'You’re signed up. Thanks for subscribing!', 'thr-core' ),
		'missing' => __( 'Please enter a valid email, choose at least one newsletter and tick the consent box.', 'thr-core' ),
		'expired' => __( 'The form expired. Please try again.', 'thr-core' ),
		'limit'   => __( 'Too many attempts. Please try again in an hour.', 'thr-core' ),
	);

	ob_start();
	?>
	<div class="thr-form-container thr-newsletters">
		<?php if ( isset( $messages[ $status ] ) ) : ?>
			<div class="thr-tip-alert<?php echo 'sent' === $status ? ' thr-tip-alert--success' : ''; ?>" role="status"><?php echo esc_html( $messages[ $status ] ); ?></div>
		<?php endif; ?>

		<form method="post" action="" class="thr-newsletters__form">
			<?php wp_nonce_field( 'thr_newsletter_subscribe', 'thr_newsletter_nonce' ); ?>
			<input type="hidden" name="thr_newsletter_action" value="subscribe" />
			<div class="thr-tip-form__hp" aria-hidden="true">
				<label for="thr_hp_phone">Leave this field blank</label>
				<input type="text" name="thr_hp_phone" id="thr_hp_phone" autocomplete="off" tabindex="-1" />
			</div>

			<fieldset class="thr-newsletters__grid">
				<legend class="screen-reader-text"><?php esc_html_e( 'Choose newsletters', 'thr-core' ); ?></legend>
				<?php foreach ( thr_newsletters() as $slug => $nl ) : ?>
					<label class="thr-newsletter-card">
						<input type="checkbox" name="nl_lists[]" value="<?php echo esc_attr( $slug ); ?>" class="thr-newsletter-card__check" <?php checked( $preset, $slug ); ?> />
						<span class="thr-newsletter-card__body">
							<span class="thr-newsletter-card__freq"><?php echo esc_html( $nl[2] ); ?></span>
							<span class="thr-newsletter-card__name"><?php echo esc_html( $nl[0] ); ?></span>
							<span class="thr-newsletter-card__desc"><?php echo esc_html( $nl[1] ); ?></span>
						</span>
					</label>
				<?php endforeach; ?>
			</fieldset>

			<div class="thr-newsletters__submit">
				<label for="nl_email" class="thr-tip-form__label"><?php esc_html_e( 'Email address', 'thr-core' ); ?></label>
				<div class="thr-newsletters__row">
					<input type="email" name="nl_email" id="nl_email" required autocomplete="email" value="<?php echo esc_attr( $prefill ); ?>" class="thr-tip-form__input" placeholder="<?php esc_attr_e( 'you@example.com', 'thr-core' ); ?>" />
					<button type="submit" class="thr-button"><?php esc_html_e( 'Sign Up', 'thr-core' ); ?></button>
				</div>
				<label class="thr-newsletters__consent">
					<input type="checkbox" name="nl_consent" value="1" required />
					<span>
						<?php
						printf(
							/* translators: 1: Terms of Use link, 2: Privacy Policy link. */
							esc_html__( 'Yes, email me the newsletters I picked. I agree to the %1$s and %2$s, and can unsubscribe at any time.', 'thr-core' ),
							'<a href="' . esc_url( home_url( '/terms-of-use/' ) ) . '">' . esc_html__( 'Terms of Use', 'thr-core' ) . '</a>',
							'<a href="' . esc_url( home_url( '/privacy-policy/' ) ) . '">' . esc_html__( 'Privacy Policy', 'thr-core' ) . '</a>'
						);
						?>
					</span>
				</label>
			</div>
		</form>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'thr_newsletter_signup', 'thr_render_newsletter_signup' );

/**
 * Subscriber list columns.
 *
 * @return string[]
 */
function thr_subscriber_columns() {
	return array(
		'cb'         => '<input type="checkbox" />',
		'title'      => __( 'Email', 'thr-core' ),
		'thr_lists'  => __( 'Newsletters', 'thr-core' ),
		'thr_signed' => __( 'Signed up (UTC)', 'thr-core' ),
	);
}
add_filter( 'manage_thr_subscriber_posts_columns', 'thr_subscriber_columns' );

/**
 * Render subscriber list columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function thr_render_subscriber_column( $column, $post_id ) {
	if ( 'thr_lists' === $column ) {
		$names = array();
		$all   = thr_newsletters();
		foreach ( (array) get_post_meta( $post_id, 'thr_lists', true ) as $slug ) {
			$names[] = isset( $all[ $slug ] ) ? $all[ $slug ][0] : $slug;
		}
		echo esc_html( implode( ', ', $names ) );
	} elseif ( 'thr_signed' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, 'thr_consent_at', true ) );
	}
}
add_action( 'manage_thr_subscriber_posts_custom_column', 'thr_render_subscriber_column', 10, 2 );

/**
 * "Export CSV" link above the subscriber list.
 *
 * @param string[] $views List table views.
 * @return string[]
 */
function thr_subscriber_export_link( $views ) {
	$url             = wp_nonce_url( admin_url( 'admin-post.php?action=thr_export_subscribers' ), 'thr_export_subscribers' );
	$views['export'] = '<a href="' . esc_url( $url ) . '"><strong>' . esc_html__( 'Export CSV', 'thr-core' ) . '</strong></a>';
	return $views;
}
add_filter( 'views_edit-thr_subscriber', 'thr_subscriber_export_link' );

function thr_export_subscribers() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to export subscribers.', 'thr-core' ) );
	}
	check_admin_referer( 'thr_export_subscribers' );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=thr-subscribers-' . gmdate( 'Y-m-d' ) . '.csv' );

	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'email', 'newsletters', 'consent_at_utc', 'source' ) );

	$paged = 1;
	do {
		$query = new WP_Query(
			array(
				'post_type'      => 'thr_subscriber',
				'post_status'    => 'any',
				'posts_per_page' => 500,
				'paged'          => $paged,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);
		foreach ( $query->posts as $sub ) {
			fputcsv(
				$out,
				array(
					$sub->post_title,
					implode( '|', (array) get_post_meta( $sub->ID, 'thr_lists', true ) ),
					get_post_meta( $sub->ID, 'thr_consent_at', true ),
					get_post_meta( $sub->ID, 'thr_source', true ),
				)
			);
		}
		++$paged;
	} while ( $paged <= $query->max_num_pages );

	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
add_action( 'admin_post_thr_export_subscribers', 'thr_export_subscribers' );

/**
 * Pages with forms carry a nonce, so they must never be served from the page cache.
 */
function thr_form_pages_nocache() {
	if ( ! is_page( array( 'contact', 'newsletters', 'tip-line' ) ) ) {
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	do_action( 'litespeed_control_set_nocache', 'THR form page' );
	nocache_headers();
}
add_action( 'template_redirect', 'thr_form_pages_nocache', 0 );
