<?php
/**
 * Core Structure Seeder.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class THR_Structure_Seeder {

	public static function seed_structure() {
		if ( ! taxonomy_exists( 'vertical' ) || ! taxonomy_exists( 'vcategory' ) ) {
			return array(
				'success' => false,
				'message' => 'Activate the THR Core plugin first: verticals and video categories are registered there.',
			);
		}

		$json_file = THR_IMPORTER_PATH . 'data/site-structure.json';
		if ( ! file_exists( $json_file ) ) {
			return array(
				'success' => false,
				'message' => 'Manifest data/site-structure.json not found.',
			);
		}

		$data = json_decode( file_get_contents( $json_file ), true );
		if ( ! $data ) {
			return array(
				'success' => false,
				'message' => 'Invalid JSON in site-structure.json.',
			);
		}

		$report = array(
			'categories_created' => 0,
			'categories_skipped' => 0,
			'topics_created'     => 0,
			'topics_skipped'     => 0,
			'verticals_created'  => 0,
			'verticals_skipped'  => 0,
			'vcategories_created'=> 0,
			'vcategories_skipped'=> 0,
			'pages_created'      => 0,
			'pages_skipped'      => 0,
		);

		if ( isset( $data['settings'] ) ) {
			if ( ! empty( $data['settings']['permalink_structure'] ) && function_exists( 'thr_apply_permalink_settings' ) ) {
				thr_apply_permalink_settings(
					$data['settings']['permalink_structure'],
					isset( $data['settings']['category_base'] ) ? $data['settings']['category_base'] : 'c',
					isset( $data['settings']['tag_base'] ) ? $data['settings']['tag_base'] : 't'
				);
			}
			if ( ! empty( $data['settings']['timezone_string'] ) ) {
				update_option( 'timezone_string', $data['settings']['timezone_string'] );
			}
		}

		$category_map = array();

		if ( ! empty( $data['categories'] ) ) {
			foreach ( $data['categories'] as $cat ) {
				if ( empty( $cat['parent'] ) ) {
					$existing = get_term_by( 'slug', $cat['slug'], 'category' );
					if ( ! $existing ) {
						$res = wp_insert_term( $cat['name'], 'category', array( 'slug' => $cat['slug'] ) );
						if ( ! is_wp_error( $res ) ) {
							$term_id = $res['term_id'];
							$category_map[ $cat['key'] ] = $term_id;
							$report['categories_created']++;
							if ( ! empty( $cat['heading'] ) ) {
								update_term_meta( $term_id, 'thr_heading', sanitize_text_field( $cat['heading'] ) );
							}
						}
					} else {
						$category_map[ $cat['key'] ] = $existing->term_id;
						$report['categories_skipped']++;
					}
				}
			}

			foreach ( $data['categories'] as $cat ) {
				if ( ! empty( $cat['parent'] ) ) {
					$parent_id = isset( $category_map[ $cat['parent'] ] ) ? $category_map[ $cat['parent'] ] : 0;
					$existing  = get_term_by( 'slug', $cat['slug'], 'category' );
					if ( ! $existing ) {
						$res = wp_insert_term( $cat['name'], 'category', array(
							'slug'   => $cat['slug'],
							'parent' => $parent_id,
						) );
						if ( ! is_wp_error( $res ) ) {
							$term_id = $res['term_id'];
							$category_map[ $cat['key'] ] = $term_id;
							$report['categories_created']++;
							if ( ! empty( $cat['heading'] ) ) {
								update_term_meta( $term_id, 'thr_heading', sanitize_text_field( $cat['heading'] ) );
							}
							if ( ! empty( $cat['subtitle'] ) ) {
								update_term_meta( $term_id, 'thr_subtitle', sanitize_text_field( $cat['subtitle'] ) );
							}
						}
					} else {
						$category_map[ $cat['key'] ] = $existing->term_id;
						$report['categories_skipped']++;
					}
				}
			}
		}

		if ( ! empty( $data['topics'] ) ) {
			foreach ( $data['topics'] as $topic ) {
				$existing = get_term_by( 'slug', $topic['slug'], 'post_tag' );
				if ( ! $existing ) {
					$res = wp_insert_term( $topic['name'], 'post_tag', array( 'slug' => $topic['slug'] ) );
					if ( ! is_wp_error( $res ) ) {
						$report['topics_created']++;
						if ( ! empty( $topic['intro'] ) ) {
							update_term_meta( $res['term_id'], 'thr_topic_intro', wp_kses_post( $topic['intro'] ) );
						}
					}
				} else {
					$report['topics_skipped']++;
				}
			}
		}

		if ( ! empty( $data['verticals'] ) ) {
			foreach ( $data['verticals'] as $vert ) {
				$existing = get_term_by( 'slug', $vert['slug'], 'vertical' );
				$term_id  = 0;
				if ( ! $existing ) {
					$res = wp_insert_term( $vert['name'], 'vertical', array( 'slug' => $vert['slug'] ) );
					if ( ! is_wp_error( $res ) ) {
						$term_id = $res['term_id'];
						$report['verticals_created']++;
					}
				} else {
					$term_id = $existing->term_id;
					$report['verticals_skipped']++;
				}

				// Fill brand fields only where empty, so editor changes are never overwritten.
				if ( $term_id ) {
					$fields = array(
						'thr_vertical_color'     => ! empty( $vert['color'] ) ? sanitize_hex_color( $vert['color'] ) : '',
						'thr_vertical_tagline_1' => ! empty( $vert['tagline_1'] ) ? sanitize_text_field( $vert['tagline_1'] ) : '',
						'thr_vertical_tagline_2' => ! empty( $vert['tagline_2'] ) ? sanitize_text_field( $vert['tagline_2'] ) : '',
					);
					foreach ( $fields as $meta_key => $value ) {
						if ( $value && ! get_term_meta( $term_id, $meta_key, true ) ) {
							update_term_meta( $term_id, $meta_key, $value );
						}
					}
				}
			}
		}

		if ( ! empty( $data['video_categories'] ) ) {
			foreach ( $data['video_categories'] as $vcat ) {
				$existing = get_term_by( 'slug', $vcat['slug'], 'vcategory' );
				if ( ! $existing ) {
					$res = wp_insert_term( $vcat['name'], 'vcategory', array( 'slug' => $vcat['slug'] ) );
					if ( ! is_wp_error( $res ) ) {
						$report['vcategories_created']++;
					}
				} else {
					$report['vcategories_skipped']++;
				}
			}
		}

		if ( ! empty( $data['pages'] ) ) {
			foreach ( $data['pages'] as $pg ) {
				$existing = get_page_by_path( $pg['slug'] );
				$title    = isset( $pg['name'] ) ? $pg['name'] : ( isset( $pg['title'] ) ? $pg['title'] : '' );
				if ( ! $existing ) {
					// Home and Tip Line are linked from every page, so they go live; the rest stay drafts for editors.
					$status = in_array( $pg['slug'], array( 'home', 'tip-line' ), true ) ? 'publish' : 'draft';
					$content = '';
					if ( 'tip-line' === $pg['slug'] ) {
						$content = "<!-- wp:paragraph -->\n<p>Have a scoop, inside document, or breaking entertainment story? Send it securely and confidentially to our investigative editorial desk.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:shortcode -->\n[thr_tip_form]\n<!-- /wp:shortcode -->";
					} elseif ( 'masthead' === $pg['slug'] ) {
						$content = "<!-- wp:heading -->
<h2>Editorial & Publishing Leadership</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Add the UK editorial team here.</p>
<!-- /wp:paragraph -->";
					}

					$page_id = wp_insert_post( array(
						'post_title'   => $title,
						'post_name'    => $pg['slug'],
						'post_type'    => 'page',
						'post_status'  => $status,
						'post_content' => $content,
					) );

					if ( $page_id && ! is_wp_error( $page_id ) ) {
						$report['pages_created']++;

						if ( 'home' === $pg['slug'] ) {
							update_option( 'show_on_front', 'page' );
							update_option( 'page_on_front', $page_id );
						}
					}
				} else {
					// Earlier versions inserted pages without titles and left the Tip Line as a draft; repair both.
					$repair = array();
					if ( '' === $existing->post_title && '' !== $title ) {
						$repair['post_title'] = $title;
					}
					if ( 'tip-line' === $pg['slug'] && 'draft' === $existing->post_status ) {
						$repair['post_status'] = 'publish';
					}
					if ( $repair ) {
						$repair['ID'] = $existing->ID;
						wp_update_post( $repair );
					}
					$report['pages_skipped']++;
				}
			}
		}

		if ( ! function_exists( 'thr_apply_permalink_settings' ) ) {
			flush_rewrite_rules();
		}

		return array(
			'success' => true,
			'message' => 'Site structure successfully seeded.',
			'report'  => $report,
		);
	}
}
