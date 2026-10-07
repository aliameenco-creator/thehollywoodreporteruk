<?php
/**
 * Image Credit field for WordPress Attachments.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_attachment_credit_field( $form_fields, $post ) {
	$credit = get_post_meta( $post->ID, 'thr_credit', true );

	$form_fields['thr_credit'] = array(
		'label' => __( 'Image Credit', 'thr-core' ),
		'input' => 'text',
		'value' => $credit,
		'helps' => __( 'Credit/photographer attribution (e.g. "Getty Images", "Warner Bros.").', 'thr-core' ),
	);

	return $form_fields;
}
add_filter( 'attachment_fields_to_edit', 'thr_attachment_credit_field', 10, 2 );

function thr_save_attachment_credit_field( $post, $attachment ) {
	if ( isset( $attachment['thr_credit'] ) ) {
		update_post_meta( $post['ID'], 'thr_credit', sanitize_text_field( $attachment['thr_credit'] ) );
	}
	return $post;
}
add_filter( 'attachment_fields_to_save', 'thr_save_attachment_credit_field', 10, 2 );

function thr_register_rest_attachment_credit() {
	register_rest_field(
		'attachment',
		'thr_credit',
		array(
			'get_callback' => function( $post ) {
				return get_post_meta( $post['id'], 'thr_credit', true );
			},
			'update_callback' => function( $value, $post ) {
				return update_post_meta( $post->ID, 'thr_credit', sanitize_text_field( $value ) );
			},
			'schema' => array(
				'description' => __( 'Image Credit / Attribution', 'thr-core' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		)
	);
}
add_action( 'rest_api_init', 'thr_register_rest_attachment_credit' );
