<?php
/**
 * Custom Template Tags and Utility Functions for THR Theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function thr_the_kicker( $post_id = null ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	$override = get_post_meta( $post_id, 'thr_kicker', true );
	if ( ! empty( $override ) ) {
		echo '<span class="u-kicker">' . esc_html( $override ) . '</span>';
		return;
	}

	$categories = get_the_category( $post_id );
	if ( ! empty( $categories ) ) {
		$cat = $categories[0];
		echo '<span class="u-kicker"><a href="' . esc_url( get_category_link( $cat->term_id ) ) . '">' . esc_html( $cat->name ) . '</a></span>';
	}
}

function thr_the_dek( $post_id = null, $class = 'u-dek' ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}
	$dek = get_post_meta( $post_id, 'thr_dek', true );
	if ( ! empty( $dek ) ) {
		echo '<div class="' . esc_attr( $class ) . '">' . esc_html( $dek ) . '</div>';
	}
}

function thr_time_ago( $post_id = null ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}
	$post_time = get_post_time( 'U', true, $post_id );
	$diff      = time() - $post_time;

	if ( $diff < 3 * DAY_IN_SECONDS ) {
		/* translators: %s: relative time */
		return sprintf( esc_html__( '%s ago', 'thr-theme' ), human_time_diff( $post_time, time() ) );
	}
	return get_the_date( 'M j, Y g:i a', $post_id );
}

function thr_get_image_credit( $attachment_id ) {
	return get_post_meta( $attachment_id, 'thr_credit', true );
}

function thr_get_vertical_color( $post_id = null ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}
	$terms = get_the_terms( $post_id, 'vertical' );
	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		$color = get_term_meta( $terms[0]->term_id, 'thr_vertical_color', true );
		if ( ! empty( $color ) ) {
			return $color;
		}
	}
	return '#D92128';
}
