<?php
/**
 * Mail helpers for the public forms.
 *
 * Sending mail can stall for a long time when the mail server is slow or not
 * configured, and a visitor's request that waits on it ends in a 504 from
 * nginx. So the forms answer the visitor first and send the email afterwards.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirect the visitor and close their connection, leaving PHP running so the
 * caller can send email without keeping the browser waiting.
 *
 * @param string $url Where to send the visitor.
 */
function thr_redirect_and_continue( $url ) {
	wp_safe_redirect( $url );
	ignore_user_abort( true );

	while ( ob_get_level() > 0 ) {
		ob_end_flush();
	}

	if ( function_exists( 'litespeed_finish_request' ) ) {
		litespeed_finish_request();
	} elseif ( function_exists( 'fastcgi_finish_request' ) ) {
		fastcgi_finish_request();
	} else {
		flush();
	}
}

/**
 * Cap SMTP waits so an unreachable mail server can't hold a PHP worker for minutes.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer Mailer instance.
 */
function thr_mail_timeout( $phpmailer ) {
	$phpmailer->Timeout = 10; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
}
add_action( 'phpmailer_init', 'thr_mail_timeout' );
