<?php
/**
 * Anonymous News Tip Submission Form Handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_handle_tip_submission() {
	if ( ! isset( $_POST['thr_tip_action'] ) || 'submit_tip' !== $_POST['thr_tip_action'] ) {
		return;
	}

	if ( ! isset( $_POST['thr_tip_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['thr_tip_nonce'] ), 'thr_submit_tip_nonce' ) ) {
		wp_die( esc_html__( 'Security check failed. Please reload and try again.', 'thr-core' ) );
	}

	if ( ! empty( $_POST['thr_hp_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'tip_status', 'sent', wp_get_referer() ?: home_url( '/tip-line/' ) ) );
		exit;
	}

	// The IP is only hashed for rate limiting; it is never stored or emailed, to protect sources.
	$ip         = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	$transient  = 'thr_tip_rate_' . md5( wp_salt() . $ip );
	$rate_count = (int) get_transient( $transient );

	if ( $rate_count >= 5 ) {
		wp_die( esc_html__( 'You have submitted multiple tips recently. Please wait before sending another.', 'thr-core' ) );
	}
	set_transient( $transient, $rate_count + 1, HOUR_IN_SECONDS );

	$name    = isset( $_POST['tip_name'] ) ? sanitize_text_field( wp_unslash( $_POST['tip_name'] ) ) : __( 'Anonymous', 'thr-core' );
	$email   = isset( $_POST['tip_email'] ) ? sanitize_email( wp_unslash( $_POST['tip_email'] ) ) : '';
	$subject = isset( $_POST['tip_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['tip_subject'] ) ) : __( 'News Tip', 'thr-core' );
	$message = isset( $_POST['tip_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tip_message'] ) ) : '';

	if ( empty( $message ) ) {
		wp_die( esc_html__( 'Please provide the details of your tip.', 'thr-core' ) );
	}

	$tip_recipient = get_option( 'thr_tip_email', get_option( 'admin_email' ) );
	$email_subject = sprintf( '[THR Tip Line] %s', $subject );

	$email_body  = "A news tip was submitted via the THR UK Tip Line:\n\n";
	$email_body .= "From: " . ( $name ?: 'Anonymous' ) . "\n";
	$email_body .= "Email: " . ( $email ?: 'Not provided' ) . "\n";
	$email_body .= "Date/Time: " . current_time( 'mysql' ) . "\n\n";
	$email_body .= "--- TIP MESSAGE ---\n\n";
	$email_body .= $message . "\n";

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	if ( ! empty( $email ) ) {
		$headers[] = 'Reply-To: ' . $email;
	}

	// Answer the visitor first; a slow mail server must not hold their request (504).
	thr_redirect_and_continue( add_query_arg( 'tip_status', 'success', wp_get_referer() ?: home_url( '/tip-line/' ) ) );

	if ( ! wp_mail( $tip_recipient, $email_subject, $email_body, $headers ) ) {
		error_log( 'THR tip line: wp_mail() failed for a tip to ' . $tip_recipient ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
	exit;
}
add_action( 'template_redirect', 'thr_handle_tip_submission' );

function thr_render_tip_form() {
	ob_start();
	$status = isset( $_GET['tip_status'] ) ? sanitize_key( $_GET['tip_status'] ) : '';
	?>
	<div class="thr-tip-form-container">
		<?php if ( 'success' === $status ) : ?>
			<div class="thr-tip-alert thr-tip-alert--success" role="status">
				<strong><?php esc_html_e( 'Thank you.', 'thr-core' ); ?></strong> <?php esc_html_e( 'Your tip has been securely and confidentially delivered to our newsroom editors.', 'thr-core' ); ?>
			</div>
		<?php endif; ?>

		<form method="post" action="" class="thr-tip-form">
			<?php wp_nonce_field( 'thr_submit_tip_nonce', 'thr_tip_nonce' ); ?>
			<input type="hidden" name="thr_tip_action" value="submit_tip" />
			
			<div class="thr-tip-form__hp" aria-hidden="true">
				<label for="thr_hp_website">Leave this field blank</label>
				<input type="text" name="thr_hp_website" id="thr_hp_website" autocomplete="off" tabindex="-1" />
			</div>

			<div class="thr-tip-form__field">
				<label for="tip_name" class="thr-tip-form__label"><?php esc_html_e( 'Your Name (Optional)', 'thr-core' ); ?></label>
				<input type="text" name="tip_name" id="tip_name" placeholder="<?php esc_attr_e( 'Leave blank to remain completely anonymous', 'thr-core' ); ?>" class="thr-tip-form__input" />
			</div>

			<div class="thr-tip-form__field">
				<label for="tip_email" class="thr-tip-form__label"><?php esc_html_e( 'Contact Email (Optional)', 'thr-core' ); ?></label>
				<input type="email" name="tip_email" id="tip_email" placeholder="<?php esc_attr_e( 'If you would like us to follow up with you', 'thr-core' ); ?>" class="thr-tip-form__input" />
			</div>

			<div class="thr-tip-form__field">
				<label for="tip_subject" class="thr-tip-form__label"><?php esc_html_e( 'Subject / Story Topic *', 'thr-core' ); ?></label>
				<input type="text" name="tip_subject" id="tip_subject" required class="thr-tip-form__input" />
			</div>

			<div class="thr-tip-form__field">
				<label for="tip_message" class="thr-tip-form__label"><?php esc_html_e( 'Tip Details / Information *', 'thr-core' ); ?></label>
				<textarea name="tip_message" id="tip_message" rows="6" required placeholder="<?php esc_attr_e( 'Provide as much specific information as possible...', 'thr-core' ); ?>" class="thr-tip-form__input"></textarea>
			</div>

			<div class="thr-tip-form__field">
				<button type="submit" class="thr-button">
					<?php esc_html_e( 'Submit Confidential Tip', 'thr-core' ); ?>
				</button>
			</div>
		</form>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'thr_tip_form', 'thr_render_tip_form' );
