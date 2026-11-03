<?php
/**
 * Turning an article into the text that is read aloud.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full script: headline, dek, then the body without images, captions, embeds or code.
 * The byline is left out: it is on the page, and a name read aloud adds nothing to the story.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function thr_audio_script( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return '';
	}

	$parts = array( thr_audio_sentence( get_the_title( $post ) ) );

	$dek = get_post_meta( $post_id, 'thr_dek', true );
	if ( $dek ) {
		$parts[] = thr_audio_sentence( $dek );
	}

	$parts[] = thr_audio_body_text( $post->post_content );

	$script = implode( "\n\n", array_filter( $parts ) );
	return (string) apply_filters( 'thr_audio_script', $script, $post_id );
}

/**
 * Plain text of the article body, one paragraph per line.
 *
 * @param string $content Raw post content.
 * @return string
 */
function thr_audio_body_text( $content ) {
	$html = strip_shortcodes( $content );

	// Things that are not read: images and their captions, embeds, scripts, code and tables.
	$html = preg_replace( '#<(figure|figcaption|script|style|iframe|video|audio|object|embed|pre|code|table|noscript|form)\b[^>]*>.*?</\1>#is', ' ', $html );
	$html = preg_replace( '#<!--.*?-->#s', ' ', $html );

	// Headings and list items become sentences so the voice pauses after them.
	$html = preg_replace( '#</(h[1-6]|li)>#i', '.</$1>' . "\n", $html );
	$html = preg_replace( '#</(p|div|blockquote|ul|ol)>|<br\s*/?>#i', "\n", $html );

	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

	// Bare links read badly ("h t t p s colon …").
	$text = preg_replace( '#https?://\S+#i', '', $text );

	$lines = array();
	foreach ( preg_split( '/\n+/', $text ) as $line ) {
		$line = trim( preg_replace( '/[ \t\x{00A0}]+/u', ' ', $line ) );
		$line = preg_replace( '/([.!?…:;])\.+$/u', '$1', $line );
		if ( '' !== $line && '.' !== $line ) {
			$lines[] = $line;
		}
	}

	return implode( "\n", $lines );
}

/**
 * End a line with punctuation so it is read as a sentence.
 *
 * @param string $text Text.
 * @return string
 */
function thr_audio_sentence( $text ) {
	$text = trim( wp_strip_all_tags( html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
	if ( '' === $text ) {
		return '';
	}
	return preg_match( '/[.!?…"”’\']$/u', $text ) ? $text : $text . '.';
}

/**
 * The opening of the script for the short clip: whole sentences up to about $chars characters.
 *
 * @param string $script Full script.
 * @param int    $chars  Target length.
 * @return string
 */
function thr_audio_opening( $script, $chars ) {
	$out = '';
	foreach ( thr_audio_sentences( $script ) as $sentence ) {
		if ( '' !== $out && mb_strlen( $out ) + mb_strlen( $sentence ) > $chars ) {
			break;
		}
		$out .= ( '' === $out ? '' : ' ' ) . $sentence;
	}
	return $out;
}

/**
 * Sentences of a text.
 *
 * @param string $text Text.
 * @return string[]
 */
function thr_audio_sentences( $text ) {
	$sentences = preg_split( '/(?<=[.!?…])["”’\']?\s+/u', trim( $text ) );
	return array_values( array_filter( array_map( 'trim', (array) $sentences ), 'strlen' ) );
}

/**
 * Split a script into pieces of at most $max characters, on paragraph then sentence boundaries.
 *
 * @param string $script Script.
 * @param int    $max    Max characters per piece.
 * @return string[]
 */
function thr_audio_pieces( $script, $max = 2500 ) {
	$units = array();
	foreach ( preg_split( '/\n+/', $script ) as $para ) {
		if ( mb_strlen( $para ) <= $max ) {
			$units[] = $para;
			continue;
		}
		foreach ( thr_audio_sentences( $para ) as $sentence ) {
			// A single sentence over the limit (rare) is cut on a space.
			while ( mb_strlen( $sentence ) > $max ) {
				$cut      = mb_strrpos( mb_substr( $sentence, 0, $max ), ' ' );
				$cut      = $cut ? $cut : $max;
				$units[]  = mb_substr( $sentence, 0, $cut );
				$sentence = ltrim( mb_substr( $sentence, $cut ) );
			}
			$units[] = $sentence;
		}
	}

	$pieces  = array();
	$current = '';
	foreach ( $units as $unit ) {
		if ( '' !== $current && mb_strlen( $current ) + mb_strlen( $unit ) + 1 > $max ) {
			$pieces[] = $current;
			$current  = '';
		}
		$current .= ( '' === $current ? '' : "\n" ) . $unit;
	}
	if ( '' !== $current ) {
		$pieces[] = $current;
	}

	return $pieces;
}
