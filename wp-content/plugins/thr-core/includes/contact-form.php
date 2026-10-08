<?php
/**
 * Contact Us form ([thr_contact_form] shortcode). Emails the newsroom; nothing is stored.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Departments a message can be routed to (label shown in the email subject).
 *
 * @return string[] slug => label.
 */
function thr_contact_departments() {
	return array(
		'general'       => __( 'General enquiry', 'thr-core' ),
		'editorial'     => __( 'Editorial / story pitch', 'thr-core' ),
		'corrections'   => __( 'Corrections', 'thr-core' ),
		'partnerships'  => __( 'Advertising & partnerships', 'thr-core' ),
		'subscriptions' => __( 'Subscriptions & newsletters', 'thr-core' ),
		'careers'       => __( 'Careers', 'thr-core' ),
	);
}

function thr_handle_contact_submission() {
	if ( ! isset( $_POST['thr_contact_action'] ) || 'send' !== $_POST['thr_contact_action'] ) {
		return;
	}

	$back = wp_get_referer() ?: home_url( '/contact/' );

	if ( ! isset( $_POST['thr_contact_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['thr_contact_nonce'] ), 'thr_contact_send' ) ) {
		wp_safe_redirect( add_query_arg( 'contact_status', 'expired', $back ) );
		exit;
	}

	// Bots fill the hidden field; pretend success so they don't retry.
	if ( ! empty( $_POST['thr_hp_company'] ) ) {
		wp_safe_redirect( add_query_arg( 'contact_status', 'sent', $back ) );
		exit;
	}

	$ip        = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	$transient = 'thr_contact_rate_' . md5( wp_salt() . $ip );
	$count     = (int) get_transient( $transient );
	if ( $count >= 5 ) {
		wp_safe_redirect( add_query_arg( 'contact_status', 'limit', $back ) );
		exit;
	}

	$departments = thr_contact_departments();
	$name        = isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '';
	$email       = isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '';
	$department  = isset( $_POST['contact_department'] ) ? sanitize_key( $_POST['contact_department'] ) : 'general';
	$subject     = isset( $_POST['contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_subject'] ) ) : '';
	$message     = isset( $_POST['contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contact_message'] ) ) : '';

	if ( '' === $name || ! is_email( $email ) || '' === $message ) {
		wp_safe_redirect( add_query_arg( 'contact_status', 'missing', $back ) );
		exit;
	}
	if ( ! isset( $departments[ $department ] ) ) {
		$department = 'general';
	}

	set_transient( $transient, $count + 1, HOUR_IN_SECONDS );

	$recipient = get_option( 'thr_contact_email' ) ?: get_option( 'admin_email' );
	$title     = sprintf( '[THR UK Contact] %s: %s', $departments[ $department ], $subject ?: __( '(no subject)', 'thr-core' ) );
	$body      = sprintf(
		"Name: %s\nEmail: %s\nDepartment: %s\nSent: %s\n\n%s\n",
		$name,
		$email,
		$departments[ $department ],
		current_time( 'mysql' ),
		$message
	);

	// Answer the visitor first; a slow mail server must not hold their request (504).
	thr_redirect_and_continue( add_query_arg( 'contact_status', 'sent', $back ) );

	$sent = wp_mail( $recipient, $title, $body, array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $name . ' <' . $email . '>' ) );
	if ( ! $sent ) {
		error_log( 'THR contact form: wp_mail() failed for a message to ' . $recipient ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
	exit;
}
add_action( 'template_redirect', 'thr_handle_contact_submission' );

function thr_render_contact_form() {
	$status   = isset( $_GET['contact_status'] ) ? sanitize_key( $_GET['contact_status'] ) : '';
	$messages = array(
		'sent'    => __( 'Thank you — your message has been sent. We aim to reply within two working days.', 'thr-core' ),
		'missing' => __( 'Please add your name, a valid email address and a message.', 'thr-core' ),
		'expired' => __( 'The form expired. Please send your message again.', 'thr-core' ),
		'limit'   => __( 'You have sent several messages recently. Please try again in an hour.', 'thr-core' ),
	);

	ob_start();
	?>
	<div class="thr-form-container">
		<?php if ( isset( $messages[ $status ] ) ) : ?>
			<div class="thr-tip-alert<?php echo 'sent' === $status ? ' thr-tip-alert--success' : ''; ?>" role="status"><?php echo esc_html( $messages[ $status ] ); ?></div>
		<?php endif; ?>

		<form method="post" action="" class="thr-tip-form thr-contact-form">
			<?php wp_nonce_field( 'thr_contact_send', 'thr_contact_nonce' ); ?>
			<input type="hidden" name="thr_contact_action" value="send" />

			<div class="thr-tip-form__hp" aria-hidden="true">
				<label for="thr_hp_company">Leave this field blank</label>
				<input type="text" name="thr_hp_company" id="thr_hp_company" autocomplete="off" tabindex="-1" />
			</div>

			<div class="thr-form-row">
				<div class="thr-tip-form__field">
					<label for="contact_name" class="thr-tip-form__label"><?php esc_html_e( 'Your name *', 'thr-core' ); ?></label>
					<input type="text" name="contact_name" id="contact_name" required autocomplete="name" class="thr-tip-form__input" />
				</div>
				<div class="thr-tip-form__field">
					<label for="contact_email" class="thr-tip-form__label"><?php esc_html_e( 'Email address *', 'thr-core' ); ?></label>
					<input type="email" name="contact_email" id="contact_email" required autocomplete="email" class="thr-tip-form__input" />
				</div>
			</div>

			<div class="thr-tip-form__field">
				<label for="contact_department" class="thr-tip-form__label"><?php esc_html_e( 'What is it about?', 'thr-core' ); ?></label>
				<select name="contact_department" id="contact_department" class="thr-tip-form__input">
					<?php foreach ( thr_contact_departments() as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="thr-tip-form__field">
				<label for="contact_subject" class="thr-tip-form__label"><?php esc_html_e( 'Subject', 'thr-core' ); ?></label>
				<input type="text" name="contact_subject" id="contact_subject" class="thr-tip-form__input" />
			</div>

			<div class="thr-tip-form__field">
				<label for="contact_message" class="thr-tip-form__label"><?php esc_html_e( 'Message *', 'thr-core' ); ?></label>
				<textarea name="contact_message" id="contact_message" rows="7" required class="thr-tip-form__input"></textarea>
			</div>

			<p class="thr-form-note">
				<?php
				printf(
					/* translators: %s: Privacy Policy link. */
					esc_html__( 'We only use your details to reply to you. See our %s.', 'thr-core' ),
					'<a href="' . esc_url( home_url( '/privacy-policy/' ) ) . '">' . esc_html__( 'Privacy Policy', 'thr-core' ) . '</a>'
				);
				?>
			</p>

			<div class="thr-tip-form__field">
				<button type="submit" class="thr-button"><?php esc_html_e( 'Send Message', 'thr-core' ); ?></button>
			</div>
		</form>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'thr_contact_form', 'thr_render_contact_form' );
