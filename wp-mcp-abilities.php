<?php
/**
 * Plugin Name: WP MCP Abilities
 * Description: Registers content-management abilities (posts, comments, media and WooCommerce variable products) exposed through the MCP Adapter default server.
 * Version:     1.7.3
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Requires Plugins: mcp-adapter
 * Author: mrjas0n
 * License: GPL-2.0-or-later
 * Text Domain: wp-mcp-abilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/early-defs.php';

const WP_MCP_ABILITIES_UPDATE_URL = 'https://raw.githubusercontent.com/mrjas0n/wp-mcp-abilities/main/update.json';
const WP_MCP_ABILITIES_REPO_URL   = 'https://github.com/mrjas0n/wp-mcp-abilities';
const WP_MCP_ABILITIES_UPDATE_TTL = 600;

add_filter( 'pre_set_site_transient_update_plugins', function ( $transient ) {
	if ( empty( $transient->checked ) ) {
		return $transient;
	}
	$plugin_file = plugin_basename( __FILE__ );
	if ( isset( $transient->response[ $plugin_file ] ) ) {
		return $transient;
	}

	$cache = get_transient( 'wp_mcp_abilities_update_check' );
	if ( false === $cache ) {
		$response = wp_remote_get( WP_MCP_ABILITIES_UPDATE_URL, array( 'timeout' => 10 ) );
		$cache    = array();
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $data ) && ! empty( $data['version'] ) ) {
				$cache = $data;
			}
		}
		set_transient( 'wp_mcp_abilities_update_check', $cache, WP_MCP_ABILITIES_UPDATE_TTL );
	}
	if ( empty( $cache['version'] ) ) {
		return $transient;
	}

	if ( ! function_exists( 'get_plugin_data' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$current_version = get_plugin_data( __FILE__, false, false )['Version'];
	if ( ! version_compare( $cache['version'], $current_version, '>' ) ) {
		return $transient;
	}

	$transient->response[ $plugin_file ] = (object) array(
		'slug'         => dirname( $plugin_file ),
		'plugin'       => $plugin_file,
		'new_version'  => $cache['version'],
		'url'          => isset( $cache['url'] ) ? $cache['url'] : WP_MCP_ABILITIES_REPO_URL,
		'package'      => isset( $cache['package'] ) ? $cache['package'] : '',
		'tested'       => isset( $cache['tested'] ) ? $cache['tested'] : '',
		'requires_php' => isset( $cache['requires_php'] ) ? $cache['requires_php'] : '',
	);
	return $transient;
} );

add_filter( 'plugins_api', function ( $result, $action, $args ) {
	if ( 'plugin_information' !== $action || empty( $args->slug ) || 'wp-mcp-abilities' !== $args->slug ) {
		return $result;
	}
	$cache = get_transient( 'wp_mcp_abilities_update_check' );
	if ( ! is_array( $cache ) || empty( $cache['version'] ) ) {
		return $result;
	}
	return (object) array(
		'name'          => 'WP MCP Abilities',
		'slug'          => 'wp-mcp-abilities',
		'version'       => $cache['version'],
		'author'        => 'mrjas0n',
		'homepage'      => WP_MCP_ABILITIES_REPO_URL,
		'download_link' => isset( $cache['package'] ) ? $cache['package'] : '',
		'sections'      => array(
			'description' => 'Registers content-management abilities (posts, comments, media and WooCommerce variable products) exposed through the MCP Adapter default server.',
		),
		'last_updated'  => isset( $cache['last_updated'] ) ? $cache['last_updated'] : '',
	);
}, 10, 3 );

add_filter( 'mcp_adapter_default_server_config', function ( $config ) {
	$existing = ( isset( $config['tools'] ) && is_array( $config['tools'] ) ) ? $config['tools'] : array();
	$config['tools'] = array_values( array_unique( array_merge(
		$existing,
		array(
			'ning-content/create-post',
			'ning-content/update-post',
			'ning-content/get-post',
			'ning-content/list-posts',
			'ning-content/trash-post',
			'ning-content/list-comments',
			'ning-content/moderate-comment',
			'ning-content/upload-media',
			'ning-content/create-variable-product',
			'ning-content/set-product-image',
			'woocommerce/orders-query',
			'woocommerce/order-add-note',
			'woocommerce/order-update-status',
			'woocommerce/products-query',
			'woocommerce/product-create',
			'woocommerce/product-delete',
			'woocommerce/product-update',
			'theme-design/get-active-theme',
			'theme-design/list-files',
			'theme-design/read-file',
			'theme-design/write-file',
			'elementor-design/get-page-data',
			'elementor-design/add-handmade-hero',
			'elementor-design/add-html-hero',
			'elementor-design/add-threejs-module',
			'elementor-design/add-gallery-module',
			'elementor-design/editor-visibility',
			'elementor-design/build-pattern-library',
		)
	) ) );
	return $config;
} );

function ning_mcp_can_manage() {
	return current_user_can( 'manage_options' );
}

function ning_mcp_mcp_meta( $annotations ) {
	return array(
		'mcp'         => array( 'public' => true ),
		'annotations' => $annotations,
	);
}

function ning_mcp_format_post( $post ) {
	return array(
		'id'         => $post->ID,
		'title'      => get_the_title( $post ),
		'status'     => $post->post_status,
		'url'        => get_permalink( $post ),
		'date'       => $post->post_date,
		'modified'   => $post->post_modified,
		'author_id'  => (int) $post->post_author,
		'excerpt'    => get_the_excerpt( $post ),
		'content'    => $post->post_content,
		'categories' => wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) ),
		'tags'       => wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) ),
	);
}

function ning_mcp_format_post_brief( $post ) {
	return array(
		'id'      => $post->ID,
		'title'   => get_the_title( $post ),
		'status'  => $post->post_status,
		'url'     => get_permalink( $post ),
		'date'    => $post->post_date,
		'excerpt' => wp_trim_words( wp_strip_all_tags( $post->post_content ), 40 ),
	);
}

function ning_mcp_format_comment( $comment ) {
	return array(
		'id'      => (int) $comment->comment_ID,
		'post_id' => (int) $comment->comment_post_ID,
		'author'  => $comment->comment_author,
		'email'   => $comment->comment_author_email,
		'date'    => $comment->comment_date,
		'status'  => wp_get_comment_status( $comment->comment_ID ),
		'content' => wp_strip_all_tags( $comment->comment_content ),
	);
}

function ning_mcp_require_post( $id ) {
	$post = get_post( $id );
	if ( ! $post || 'post' !== $post->post_type ) {
		return new WP_Error( 'ning_mcp_post_not_found', sprintf( 'Post %d not found.', $id ) );
	}
	return $post;
}

function ning_mcp_upload_image_file( $filename, $data ) {
	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}
	if ( empty( $data ) || strlen( $data ) > 20 * 1024 * 1024 ) {
		return new WP_Error( 'ning_mcp_upload_invalid', 'Empty or oversized file (max 20MB).' );
	}
	$magic_ok = false;
	$mime     = '';
	$ext      = '';
	$first    = substr( $data, 0, 12 );
	if ( "\x89PNG\r\n\x1a\n" === substr( $first, 0, 8 ) ) {
		$magic_ok = true;
		$mime     = 'image/png';
		$ext      = 'png';
	} elseif ( "\xFF\xD8\xFF" === substr( $first, 0, 3 ) ) {
		$magic_ok = true;
		$mime     = 'image/jpeg';
		$ext      = 'jpeg';
	} elseif ( 0 === strpos( $first, 'RIFF' ) && 'WEBP' === substr( $first, 8, 4 ) ) {
		$magic_ok = true;
		$mime     = 'image/webp';
		$ext      = 'webp';
	}
	if ( ! $magic_ok ) {
		return new WP_Error( 'ning_mcp_upload_type', 'File content is not a valid PNG/JPEG/WebP image.' );
	}

	$base = '';
	if ( '' !== $filename ) {
		$base = pathinfo( sanitize_file_name( $filename ), PATHINFO_FILENAME );
	}
	if ( '' === $base ) {
		$base = 'upload-' . gmdate( 'Ymd-His' ) . '-' . wp_rand( 1000, 9999 );
	}
	$target_name = $base . '.' . $ext;

	$upload = wp_upload_bits( $target_name, null, $data );
	if ( ! empty( $upload['error'] ) ) {
		return new WP_Error( 'ning_mcp_upload_write', $upload['error'] );
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $mime,
			'post_title'     => $base,
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}

	$meta = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
	wp_update_attachment_metadata( $attachment_id, $meta );

	return array(
		'attachment_id' => (int) $attachment_id,
		'url'           => wp_get_attachment_url( $attachment_id ),
		'width'         => isset( $meta['width'] ) ? (int) $meta['width'] : null,
		'height'        => isset( $meta['height'] ) ? (int) $meta['height'] : null,
		'filename'      => $target_name,
	);
}

function ning_mcp_flush_attribute_cache() {
	delete_transient( 'wc_attribute_taxonomies' );
	delete_option( '_transient_wc_attribute_taxonomies' );
	delete_option( '_transient_timeout_wc_attribute_taxonomies' );
	if ( class_exists( 'WC_Cache_Helper' ) && method_exists( 'WC_Cache_Helper', 'invalidate_cache_group' ) ) {
		WC_Cache_Helper::invalidate_cache_group( 'attributes' );
	}
	if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
		wc_get_attribute_taxonomies();
	}
}

function ning_mcp_attribute_row_exists( $slug ) {
	if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
		return false;
	}
	foreach ( (array) wc_get_attribute_taxonomies() as $tax_obj ) {
		if ( isset( $tax_obj->attribute_name ) && $slug === $tax_obj->attribute_name ) {
			return $tax_obj;
		}
	}
	return false;
}

function ning_mcp_prepare_attribute( $name, $options ) {
	if ( ! function_exists( 'wc_attribute_taxonomy_name' ) ) {
		return new WP_Error( 'ning_mcp_wc_missing', 'WooCommerce is not active.' );
	}
	$raw_name = trim( preg_replace( '/^pa_/i', '', $name ) );
	$slug     = function_exists( 'wc_sanitize_taxonomy_name' ) ? wc_sanitize_taxonomy_name( $raw_name ) : sanitize_title( $raw_name );
	$taxonomy = wc_attribute_taxonomy_name( $slug );

	$attribute_id = 0;
	if ( function_exists( 'wc_attribute_taxonomy_id_by_name' ) ) {
		$attribute_id = (int) wc_attribute_taxonomy_id_by_name( $slug );
	}

	if ( ! $attribute_id ) {
		if ( ! function_exists( 'wc_create_attribute' ) ) {
			$admin_attributes_file = defined( 'WC_ABSPATH' ) ? WC_ABSPATH . 'includes/admin/class-wc-admin-attributes.php' : '';
			if ( ! $admin_attributes_file && function_exists( 'WC' ) && WC() && WC()->plugin_path() ) {
				$admin_attributes_file = WC()->plugin_path() . '/includes/admin/class-wc-admin-attributes.php';
			}
			if ( $admin_attributes_file && file_exists( $admin_attributes_file ) ) {
				include_once $admin_attributes_file;
			}
		}

		if ( function_exists( 'wc_create_attribute' ) ) {
			$created = wc_create_attribute(
				array(
					'name'         => $name,
					'slug'         => $slug,
					'type'         => 'select',
					'order_by'     => 'menu_order',
					'has_archives' => false,
				)
			);
			if ( is_wp_error( $created ) ) {
				if ( 'woocommerce_taxonomy_already_exists' !== $created->get_error_code() ) {
					return $created;
				}
			} else {
				$attribute_id = (int) $created;
			}
		} else {
			global $wpdb;
			$inserted = $wpdb->insert(
				$wpdb->prefix . 'wc_attribute_taxonomies',
				array(
					'attribute_name'    => $slug,
					'attribute_label'   => $name,
					'attribute_type'    => 'select',
					'attribute_orderby' => 'menu_order',
					'attribute_public'  => 0,
				)
			);
			$attribute_id = $inserted ? (int) $wpdb->insert_id : 0;
		}

		ning_mcp_flush_attribute_cache();

		if ( ! $attribute_id ) {
			$existing_row = ning_mcp_attribute_row_exists( $slug );
			if ( $existing_row && isset( $existing_row->attribute_id ) ) {
				$attribute_id = (int) $existing_row->attribute_id;
			}
		}
		if ( ! $attribute_id ) {
			return new WP_Error( 'ning_mcp_attribute_create_failed', sprintf( 'Failed to register attribute "%s" in the wc_attribute_taxonomies table. Attribute row was not found after creation attempt.', $name ) );
		}
		$verify_row = ning_mcp_attribute_row_exists( $slug );
		if ( ! $verify_row ) {
			return new WP_Error( 'ning_mcp_attribute_verify_failed', sprintf( 'Attribute "%s" was created (id %d) but is not visible in wc_get_attribute_taxonomies().', $name, $attribute_id ) );
		}
	}

	if ( ! taxonomy_exists( $taxonomy ) ) {
		register_taxonomy(
			$taxonomy,
			'product',
			array(
				'label'        => $name,
				'hierarchical' => false,
				'show_ui'      => true,
				'show_in_rest' => true,
				'query_var'    => true,
				'rewrite'      => array( 'slug' => function_exists( 'wc_slugify' ) ? wc_slugify( $name ) : sanitize_title( $name ) ),
				'public'       => false,
			)
		);
	}

	$slug_map = array();
	$id_map   = array();
	foreach ( (array) $options as $option ) {
		$option = trim( (string) $option );
		if ( '' === $option ) {
			continue;
		}
		$term = wp_insert_term( $option, $taxonomy );
		if ( is_wp_error( $term ) ) {
			if ( 'term_exists' === $term->get_error_code() ) {
				$term_data = $term->get_error_data();
				if ( is_array( $term_data ) && isset( $term_data['term_id'] ) ) {
					$term_id = (int) $term_data['term_id'];
				} elseif ( is_numeric( $term_data ) ) {
					$term_id = (int) $term_data;
				} else {
					$by_name = get_term_by( 'name', $option, $taxonomy );
					$term_id = $by_name ? (int) $by_name->term_id : 0;
				}
			} else {
				return new WP_Error( 'ning_mcp_term_insert_failed', sprintf( 'Failed to insert option "%s" into %s: [%s] %s', $option, $taxonomy, $term->get_error_code(), $term->get_error_message() ) );
			}
		} else {
			$term_id = isset( $term['term_id'] ) ? (int) $term['term_id'] : 0;
		}
		$term_obj = $term_id ? get_term( $term_id, $taxonomy ) : null;
		if ( ! $term_obj || is_wp_error( $term_obj ) ) {
			$term_obj = get_term_by( 'name', $option, $taxonomy );
		}
		if ( ! $term_obj || is_wp_error( $term_obj ) ) {
			return new WP_Error( 'ning_mcp_term_lookup_failed', sprintf( 'Option "%s" (term_id %d) could not be loaded from %s after insert.', $option, $term_id, $taxonomy ) );
		}
		$slug_map[ $option ] = $term_obj->slug;
		$id_map[ $option ]   = (int) $term_obj->term_id;
	}

	return array(
		'slug'       => $slug,
		'taxonomy'   => $taxonomy,
		'attribute'  => $name,
		'attribute_id' => $attribute_id,
		'slug_map'   => $slug_map,
		'id_map'     => $id_map,
	);
}

add_action( 'wp_abilities_api_categories_init', function () {
	wp_register_ability_category(
		'ning-content',
		array(
			'label'       => 'Ning Content Management',
			'description' => 'Abilities for managing posts and comments on this site.',
		)
	);
	wp_register_ability_category(
		'theme-design',
		array(
			'label'       => 'Theme Design',
			'description' => 'Abilities for managing theme files and design tokens in the active child theme.',
		)
	);
	wp_register_ability_category(
		'elementor-design',
		array(
			'label'       => 'Elementor Design',
			'description' => 'Abilities for managing Elementor pages and handmade style modules.',
		)
	);
} );

add_action( 'wp_abilities_api_init', function () {

	wp_register_ability(
		'ning-content/create-post',
		array(
			'label'       => 'Create Post',
			'description' => 'Creates a new blog post. Returns the new post ID, permalink and edit URL. Defaults to draft status; set status to publish to make it public.',
			'category'    => 'ning-content',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'title'        => array( 'type' => 'string', 'description' => 'Post title.' ),
					'content'      => array( 'type' => 'string', 'description' => 'Post body content. HTML allowed.' ),
					'status'       => array( 'type' => 'string', 'enum' => array( 'draft', 'publish', 'pending', 'private' ), 'default' => 'draft', 'description' => 'Post status. Use draft unless the user explicitly asks to publish.' ),
					'excerpt'      => array( 'type' => 'string', 'description' => 'Optional post excerpt.' ),
					'slug'         => array( 'type' => 'string', 'description' => 'Optional URL slug.' ),
					'category_ids' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'description' => 'Optional category term IDs to assign.' ),
					'tags'         => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => 'Optional tag names to assign (created if missing).' ),
				),
				'required'             => array( 'title', 'content' ),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'id'       => array( 'type' => 'integer' ),
					'status'   => array( 'type' => 'string' ),
					'url'      => array( 'type' => 'string' ),
					'edit_url' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( $input ) {
				$args = array(
					'post_title'   => sanitize_text_field( $input['title'] ),
					'post_content' => $input['content'],
					'post_status'  => isset( $input['status'] ) ? $input['status'] : 'draft',
					'post_type'    => 'post',
				);
				if ( ! empty( $input['excerpt'] ) ) {
					$args['post_excerpt'] = sanitize_textarea_field( $input['excerpt'] );
				}
				if ( ! empty( $input['slug'] ) ) {
					$args['post_name'] = sanitize_title( $input['slug'] );
				}
				$post_id = wp_insert_post( $args, true );
				if ( is_wp_error( $post_id ) ) {
					return $post_id;
				}
				if ( ! empty( $input['category_ids'] ) ) {
					wp_set_post_categories( $post_id, array_map( 'intval', $input['category_ids'] ) );
				}
				if ( ! empty( $input['tags'] ) ) {
					wp_set_post_tags( $post_id, array_map( 'sanitize_text_field', $input['tags'] ), true );
				}
				return array(
					'id'       => (int) $post_id,
					'status'   => get_post_status( $post_id ),
					'url'      => get_permalink( $post_id ),
					'edit_url' => admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
				);
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array(
				'readonly'   => false,
				'destructive' => false,
				'idempotent' => false,
			) ),
		)
	);

	wp_register_ability(
		'ning-content/update-post',
		array(
			'label'       => 'Update Post',
			'description' => 'Updates an existing post by ID. Only the provided fields are changed; omitted fields keep their current value.',
			'category'    => 'ning-content',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'id'      => array( 'type' => 'integer', 'description' => 'Post ID.' ),
					'title'   => array( 'type' => 'string', 'description' => 'New title.' ),
					'content' => array( 'type' => 'string', 'description' => 'New body content (replaces existing).' ),
					'status'  => array( 'type' => 'string', 'enum' => array( 'draft', 'publish', 'pending', 'private' ), 'description' => 'New status.' ),
					'excerpt' => array( 'type' => 'string', 'description' => 'New excerpt.' ),
				),
				'required'             => array( 'id' ),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'id'     => array( 'type' => 'integer' ),
					'status' => array( 'type' => 'string' ),
					'url'    => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( $input ) {
				$post = ning_mcp_require_post( $input['id'] );
				if ( is_wp_error( $post ) ) {
					return $post;
				}
				$args = array( 'ID' => (int) $input['id'] );
				if ( isset( $input['title'] ) ) {
					$args['post_title'] = sanitize_text_field( $input['title'] );
				}
				if ( isset( $input['content'] ) ) {
					$args['post_content'] = $input['content'];
				}
				if ( isset( $input['status'] ) ) {
					$args['post_status'] = $input['status'];
				}
				if ( isset( $input['excerpt'] ) ) {
					$args['post_excerpt'] = sanitize_textarea_field( $input['excerpt'] );
				}
				$result = wp_update_post( $args, true );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
				$updated = get_post( $input['id'] );
				return array(
					'id'     => (int) $updated->ID,
					'status' => $updated->post_status,
					'url'    => get_permalink( $updated ),
				);
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array(
				'readonly'   => false,
				'destructive' => true,
				'idempotent' => true,
			) ),
		)
	);

	wp_register_ability(
		'ning-content/get-post',
		array(
			'label'       => 'Get Post',
			'description' => 'Returns full details of a single post by ID, including content, categories and tags.',
			'category'    => 'ning-content',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'id' => array( 'type' => 'integer', 'description' => 'Post ID.' ),
				),
				'required'             => array( 'id' ),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'id'     => array( 'type' => 'integer' ),
					'title'  => array( 'type' => 'string' ),
					'status' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( $input ) {
				$post = ning_mcp_require_post( $input['id'] );
				if ( is_wp_error( $post ) ) {
					return $post;
				}
				return ning_mcp_format_post( $post );
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array(
				'readonly'   => true,
				'idempotent' => true,
			) ),
		)
	);

	wp_register_ability(
		'ning-content/list-posts',
		array(
			'label'       => 'List Posts',
			'description' => 'Lists posts with brief info (no full content). Supports search, status filter and pagination via numberposts.',
			'category'    => 'ning-content',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'search'      => array( 'type' => 'string', 'description' => 'Optional keyword search in title/content.' ),
					'numberposts' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10, 'description' => 'Max posts to return.' ),
					'post_status' => array( 'type' => 'string', 'enum' => array( 'publish', 'draft', 'pending', 'private', 'future', 'any' ), 'default' => 'publish', 'description' => 'Filter by status.' ),
					'orderby'     => array( 'type' => 'string', 'enum' => array( 'date', 'modified', 'title', 'ID' ), 'default' => 'date' ),
					'order'       => array( 'type' => 'string', 'enum' => array( 'ASC', 'DESC' ), 'default' => 'DESC' ),
				),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'id'     => array( 'type' => 'integer' ),
						'title'  => array( 'type' => 'string' ),
						'status' => array( 'type' => 'string' ),
					),
				),
			),
			'execute_callback'    => function ( $input ) {
				$args = array(
					'post_type'      => 'post',
					'post_status'    => isset( $input['post_status'] ) ? $input['post_status'] : 'publish',
					'numberposts'    => min( isset( $input['numberposts'] ) ? (int) $input['numberposts'] : 10, 50 ),
					'orderby'        => isset( $input['orderby'] ) ? $input['orderby'] : 'date',
					'order'          => isset( $input['order'] ) ? $input['order'] : 'DESC',
					'suppress_filters' => false,
				);
				if ( ! empty( $input['search'] ) ) {
					$args['s'] = $input['search'];
				}
				return array_map( 'ning_mcp_format_post_brief', get_posts( $args ) );
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array(
				'readonly'   => true,
				'idempotent' => true,
			) ),
		)
	);

	wp_register_ability(
		'ning-content/trash-post',
		array(
			'label'       => 'Trash Post',
			'description' => 'Moves a post to the trash (recoverable, not permanent deletion). Use only when the user explicitly asks to delete a post.',
			'category'    => 'ning-content',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'id' => array( 'type' => 'integer', 'description' => 'Post ID.' ),
				),
				'required'             => array( 'id' ),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'id'      => array( 'type' => 'integer' ),
					'trashed' => array( 'type' => 'boolean' ),
				),
			),
			'execute_callback'    => function ( $input ) {
				$post = ning_mcp_require_post( $input['id'] );
				if ( is_wp_error( $post ) ) {
					return $post;
				}
				$trashed = wp_trash_post( $input['id'] );
				return array(
					'id'              => (int) $input['id'],
					'trashed'         => (bool) $trashed,
					'previous_status' => $post->post_status,
				);
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array(
				'readonly'   => false,
				'destructive' => true,
				'idempotent' => true,
			) ),
		)
	);

	wp_register_ability(
		'ning-content/list-comments',
		array(
			'label'       => 'List Comments',
			'description' => 'Lists comments with author, date, status and plain-text content. Can filter by status or post.',
			'category'    => 'ning-content',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'number'  => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20, 'description' => 'Max comments to return.' ),
					'status'  => array( 'type' => 'string', 'enum' => array( 'all', 'hold', 'approve', 'spam', 'trash' ), 'default' => 'all', 'description' => 'Filter by moderation status. hold = pending approval.' ),
					'post_id' => array( 'type' => 'integer', 'description' => 'Only comments on this post ID.' ),
				),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'id'     => array( 'type' => 'integer' ),
						'status' => array( 'type' => 'string' ),
					),
				),
			),
			'execute_callback'    => function ( $input ) {
				$args = array(
					'status' => isset( $input['status'] ) ? $input['status'] : 'all',
					'number' => min( isset( $input['number'] ) ? (int) $input['number'] : 20, 100 ),
				);
				if ( ! empty( $input['post_id'] ) ) {
					$args['post_id'] = (int) $input['post_id'];
				}
				return array_map( 'ning_mcp_format_comment', get_comments( $args ) );
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array(
				'readonly'   => true,
				'idempotent' => true,
			) ),
		)
	);

	wp_register_ability(
		'ning-content/moderate-comment',
		array(
			'label'       => 'Moderate Comment',
			'description' => 'Sets the moderation status of a comment. Actions: approve, hold (unapprove/pending), spam, trash.',
			'category'    => 'ning-content',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'id'    => array( 'type' => 'integer', 'description' => 'Comment ID.' ),
					'action' => array( 'type' => 'string', 'enum' => array( 'approve', 'hold', 'spam', 'trash' ), 'description' => 'Moderation action.' ),
				),
				'required'             => array( 'id', 'action' ),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'id'     => array( 'type' => 'integer' ),
					'status' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( $input ) {
				$comment = get_comment( $input['id'] );
				if ( ! $comment ) {
					return new WP_Error( 'ning_mcp_comment_not_found', sprintf( 'Comment %d not found.', $input['id'] ) );
				}
				$action = $input['action'];
				if ( 'trash' === $action ) {
					$result = wp_trash_comment( $input['id'] );
				} else {
					$result = wp_set_comment_status( $input['id'], $action );
				}
				if ( ! $result ) {
					return new WP_Error( 'ning_mcp_moderation_failed', 'Failed to update comment status.' );
				}
				return array(
					'id'     => (int) $input['id'],
					'action' => $action,
					'status' => wp_get_comment_status( $input['id'] ),
				);
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array(
				'readonly'   => false,
				'destructive' => true,
				'idempotent' => true,
			) ),
		)
	);

	wp_register_ability(
		'ning-content/upload-media',
		array(
			'label'       => 'Upload Images',
			'description' => 'Uploads image files to the WordPress media library. Accepts a list of files, each as raw base64 (with optional filename) or a public image URL. Supports PNG, JPEG and WebP, max 20 images and 20MB per image. Returns attachment ID, URL and dimensions for each file.',
			'category'    => 'ning-content',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'files' => array(
						'type'        => 'array',
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'filename' => array( 'type' => 'string', 'description' => 'Optional filename with extension (.png/.jpg/.webp).' ),
								'base64'   => array( 'type' => 'string', 'description' => 'Raw base64 encoded image data (no data: URI prefix needed).' ),
								'url'      => array( 'type' => 'string', 'description' => 'Alternative: public URL of the image to download.' ),
							),
							'description' => 'Provide either base64+filename, or url.',
						),
						'maxItems'    => 20,
						'description' => 'Images to upload. Provide either base64+filename, or url, per item.',
					),
				),
				'required'             => array( 'files' ),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'results' => array(
						'type'        => 'array',
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'index'         => array( 'type' => 'integer' ),
								'attachment_id' => array( 'type' => 'integer' ),
								'url'           => array( 'type' => 'string' ),
								'width'         => array( 'type' => 'integer' ),
								'height'        => array( 'type' => 'integer' ),
								'error'         => array( 'type' => 'string' ),
							),
						),
					),
				),
			),
			'execute_callback'    => function ( $input ) {
				$files = isset( $input['files'] ) ? $input['files'] : array();
				if ( empty( $files ) ) {
					return new WP_Error( 'ning_mcp_no_files', 'Provide at least one file (base64 or url).' );
				}
				if ( count( $files ) > 20 ) {
					return new WP_Error( 'ning_mcp_too_many_files', 'Max 20 images per call.' );
				}
				$allowed_ext = array( 'jpg', 'jpeg', 'png', 'webp' );
				$results     = array();
				foreach ( $files as $i => $file ) {
					$filename = isset( $file['filename'] ) ? sanitize_file_name( $file['filename'] ) : '';
					$ext      = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
					$data     = null;
					if ( ! empty( $file['base64'] ) ) {
						$data = $file['base64'];
						if ( 0 === strpos( ltrim( $data ), 'data:' ) && false !== strpos( $data, ',' ) ) {
							$parts = explode( ',', $data, 2 );
							$data  = $parts[1];
						}
						$data = base64_decode( $data, true );
					} elseif ( ! empty( $file['url'] ) ) {
						$response = wp_remote_get(
							esc_url_raw( $file['url'] ),
							array(
								'timeout'           => 40,
								'reject_unsafe_urls' => true,
							)
						);
						if ( is_wp_error( $response ) ) {
							$results[] = array( 'index' => $i, 'error' => 'Download failed: ' . $response->get_error_message() );
							continue;
						}
						$code = wp_remote_retrieve_response_code( $response );
						if ( 200 !== $code ) {
							$results[] = array( 'index' => $i, 'error' => 'Download failed (HTTP ' . $code . ').' );
							continue;
						}
						$data = wp_remote_retrieve_body( $response );
					}
					if ( null === $data ) {
						$results[] = array( 'index' => $i, 'error' => 'Missing base64 or url content.' );
						continue;
					}
					if ( '' !== $ext && ! in_array( $ext, $allowed_ext, true ) ) {
						$results[] = array( 'index' => $i, 'error' => sprintf( 'Unsupported extension .%s', $ext ) );
						continue;
					}

					$upload = ning_mcp_upload_image_file( $filename, $data );
					if ( is_wp_error( $upload ) ) {
						$results[] = array( 'index' => $i, 'error' => $upload->get_error_message() );
						continue;
					}
					$upload['index'] = $i;
					$results[]       = $upload;
				}
				return array( 'results' => $results );
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array(
				'readonly'   => false,
				'destructive' => false,
				'idempotent' => false,
			) ),
		)
	);

	wp_register_ability(
		'ning-content/create-variable-product',
		array(
			'label'       => 'Create Variable Product',
			'description' => 'Creates a WooCommerce variable product with custom attributes and variations. Attributes (e.g. color, size) are created automatically as standard product attributes. Each variation can have its own price, sale price, SKU, stock quantity and image. Only the listed attribute combinations become variations. Defaults to draft status. Requires WooCommerce.',
			'category'    => 'ning-content',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'name'                => array( 'type' => 'string', 'description' => 'Product name.' ),
					'description'         => array( 'type' => 'string', 'description' => 'Long description. HTML allowed.' ),
					'short_description'   => array( 'type' => 'string', 'description' => 'Short description. HTML allowed.' ),
					'status'              => array( 'type' => 'string', 'enum' => array( 'draft', 'publish', 'pending', 'private' ), 'default' => 'draft', 'description' => 'Product status. Use draft unless the user explicitly asks to publish.' ),
					'sku'                 => array( 'type' => 'string', 'description' => 'Optional product-level SKU.' ),
					'regular_price'       => array( 'type' => 'string', 'description' => 'Fallback price string for variations that do not provide their own regular_price.' ),
					'attributes'          => array(
						'type'        => 'array',
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'name'    => array( 'type' => 'string', 'description' => 'Attribute name, e.g. "Color" or "颜色".' ),
								'options' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => 'Available option values, e.g. red, blue.' ),
							),
							'required'   => array( 'name', 'options' ),
						),
						'minItems'    => 1,
						'description' => 'Product attributes. Created as standard attributes if they do not exist.',
					),
					'variants'            => array(
						'type'        => 'array',
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'attributes'          => array( 'type' => 'object', 'description' => 'Map of attribute name to option value for this variation, e.g. {"颜色":"红色","尺寸":"L"}.' ),
								'regular_price'       => array( 'type' => 'string', 'description' => 'Variation price as decimal string.' ),
								'sale_price'          => array( 'type' => 'string', 'description' => 'Optional sale price.' ),
								'sku'                 => array( 'type' => 'string', 'description' => 'Optional variation SKU.' ),
								'stock_quantity'      => array( 'type' => 'integer', 'description' => 'Optional stock quantity; enables stock management.' ),
								'image_attachment_id' => array( 'type' => 'integer', 'description' => 'Optional attachment ID from upload-media.' ),
							),
							'required'   => array( 'attributes' ),
						),
						'minItems'    => 1,
						'description' => 'Variations. Only these combinations are created.',
					),
					'featured_image_attachment_id' => array( 'type' => 'integer', 'description' => 'Optional main image attachment ID.' ),
					'gallery_attachment_ids'       => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'description' => 'Optional gallery attachment IDs.' ),
				),
				'required'             => array( 'name', 'attributes', 'variants' ),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'product_id' => array( 'type' => 'integer' ),
					'status'     => array( 'type' => 'string' ),
					'url'        => array( 'type' => 'string' ),
					'edit_url'   => array( 'type' => 'string' ),
					'variants'   => array( 'type' => 'array' ),
				),
			),
			'execute_callback'    => function ( $input ) {
				if ( ! function_exists( 'wc_get_product' ) || ! function_exists( 'wc_attribute_taxonomy_name' ) ) {
					return new WP_Error( 'ning_mcp_wc_missing', 'WooCommerce is not active.' );
				}
				$attributes_input = isset( $input['attributes'] ) ? $input['attributes'] : array();
				$variants_input   = isset( $input['variants'] ) ? $input['variants'] : array();
				if ( empty( $attributes_input ) ) {
					return new WP_Error( 'ning_mcp_missing_attributes', 'At least one attribute is required.' );
				}
				if ( empty( $variants_input ) ) {
					return new WP_Error( 'ning_mcp_missing_variants', 'At least one variant is required.' );
				}

				$prepared = array();
				foreach ( $attributes_input as $attr ) {
					if ( empty( $attr['name'] ) ) {
						return new WP_Error( 'ning_mcp_missing_attribute_name', 'Attribute name is required.' );
					}
					$res = ning_mcp_prepare_attribute( $attr['name'], $attr['options'] );
					if ( is_wp_error( $res ) ) {
						return $res;
					}
					$prepared[ $res['attribute'] ] = $res;
				}

				$product = new WC_Product_Variable();
				$product->set_name( sanitize_text_field( $input['name'] ) );
				if ( ! empty( $input['description'] ) ) {
					$product->set_description( $input['description'] );
				}
				if ( ! empty( $input['short_description'] ) ) {
					$product->set_short_description( $input['short_description'] );
				}
				$product->set_status( isset( $input['status'] ) ? $input['status'] : 'draft' );
				if ( ! empty( $input['sku'] ) ) {
					$product->set_sku( $input['sku'] );
				}

				$attributes = array();
				foreach ( $prepared as $attr ) {
					$wc_attr = new WC_Product_Attribute();
					if ( ! empty( $attr['attribute_id'] ) ) {
						$wc_attr->set_id( $attr['attribute_id'] );
					}
					$wc_attr->set_name( $attr['taxonomy'] );
					$wc_attr->set_options( array_values( $attr['id_map'] ) );
					$wc_attr->set_position( 0 );
					$wc_attr->set_visible( true );
					$wc_attr->set_variation( true );
					$attributes[] = $wc_attr;
				}
				$product->set_attributes( $attributes );

				if ( ! empty( $input['featured_image_attachment_id'] ) ) {
					$product->set_image_id( (int) $input['featured_image_attachment_id'] );
				}
				if ( ! empty( $input['gallery_attachment_ids'] ) ) {
					$product->set_gallery_image_ids( array_map( 'intval', $input['gallery_attachment_ids'] ) );
				}

				$product_id = $product->save();
				if ( ! $product_id || is_wp_error( $product_id ) ) {
					return new WP_Error( 'ning_mcp_product_save_failed', 'Failed to save variable product.' );
				}

				$fallback_price = isset( $input['regular_price'] ) ? (string) $input['regular_price'] : null;
				$variant_results = array();

				foreach ( $variants_input as $i => $variant ) {
					if ( empty( $variant['attributes'] ) || ! is_array( $variant['attributes'] ) ) {
						return new WP_Error( 'ning_mcp_variant_no_attributes', sprintf( 'Variant %d has no attributes.', $i ) );
					}
					$var_attrs = array();
					foreach ( $variant['attributes'] as $attr_name => $option ) {
						$known = isset( $prepared[ $attr_name ] ) ? $prepared[ $attr_name ] : null;
						if ( ! $known ) {
							return new WP_Error( 'ning_mcp_unknown_attribute', sprintf( 'Variant %d references unknown attribute "%s".', $i, $attr_name ) );
						}
					if ( ! isset( $known['slug_map'][ $option ] ) ) {
						return new WP_Error( 'ning_mcp_unknown_option', sprintf( 'Variant %d references unknown option "%s" for attribute "%s". Available slug_map: %s', $i, (string) $option, $attr_name, wp_json_encode( $known['slug_map'] ) ) );
					}
						$var_attrs[ $known['taxonomy'] ] = $known['slug_map'][ $option ];
					}

					$variation = new WC_Product_Variation();
					$variation->set_parent_id( $product_id );
					$variation->set_attributes( $var_attrs );

					$price = isset( $variant['regular_price'] ) ? (string) $variant['regular_price'] : $fallback_price;
					if ( null !== $price && '' !== $price ) {
						$variation->set_regular_price( $price );
					}
					if ( ! empty( $variant['sale_price'] ) ) {
						$variation->set_sale_price( (string) $variant['sale_price'] );
					}
					if ( ! empty( $variant['sku'] ) ) {
						$variation->set_sku( (string) $variant['sku'] );
					}
					if ( ! empty( $variant['image_attachment_id'] ) ) {
						$variation->set_image_id( (int) $variant['image_attachment_id'] );
					}
					if ( isset( $variant['stock_quantity'] ) ) {
						$variation->set_manage_stock( true );
						$variation->set_stock_quantity( (int) $variant['stock_quantity'] );
						$variation->set_stock_status( (int) $variant['stock_quantity'] > 0 ? 'instock' : 'outofstock' );
					}

					$variation_id = $variation->save();
					if ( ! $variation_id || is_wp_error( $variation_id ) ) {
						return new WP_Error( 'ning_mcp_variation_save_failed', sprintf( 'Failed to save variant %d (%s).', $i, wp_json_encode( $var_attrs ) ) );
					}
					$variant_results[] = array(
						'id'     => (int) $variation_id,
						'attrs'  => $variant['attributes'],
						'price'  => $price,
						'sku'    => isset( $variant['sku'] ) ? (string) $variant['sku'] : null,
						'stock'  => isset( $variant['stock_quantity'] ) ? (int) $variant['stock_quantity'] : null,
					);
				}

				return array(
					'product_id' => (int) $product_id,
					'status'     => $product->get_status(),
					'url'        => get_permalink( $product_id ),
					'edit_url'   => admin_url( 'post.php?post=' . $product_id . '&action=edit' ),
					'variants'   => $variant_results,
				);
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array(
				'readonly'   => false,
				'destructive' => false,
				'idempotent' => false,
			) ),
		)
	);

	wp_register_ability(
		'ning-content/set-product-image',
		array(
			'label'       => 'Set Product Image',
			'description' => 'Attaches an existing media-library image to a WooCommerce product (main image and optional gallery) or to a single variation (its own image). Requires WooCommerce.',
			'category'    => 'ning-content',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'product_id'           => array( 'type' => 'integer', 'description' => 'Product ID (use this or variation_id).' ),
					'variation_id'         => array( 'type' => 'integer', 'description' => 'Variation ID (use this instead of product_id to set a variation image).' ),
					'attachment_id'        => array( 'type' => 'integer', 'description' => 'Attachment ID from upload-media.' ),
					'gallery_attachment_ids' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'description' => 'Optional gallery attachment IDs (product only).' ),
				),
				'required'             => array( 'attachment_id' ),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'applied' => array( 'type' => 'boolean' ),
				),
			),
			'execute_callback'    => function ( $input ) {
				if ( ! function_exists( 'wc_get_product' ) ) {
					return new WP_Error( 'ning_mcp_wc_missing', 'WooCommerce is not active.' );
				}
				$target_id = ! empty( $input['variation_id'] ) ? (int) $input['variation_id'] : ( ! empty( $input['product_id'] ) ? (int) $input['product_id'] : 0 );
				if ( ! $target_id ) {
					return new WP_Error( 'ning_mcp_missing_id', 'Provide product_id or variation_id.' );
				}
				$object = wc_get_product( $target_id );
				if ( ! $object ) {
					return new WP_Error( 'ning_mcp_not_found', sprintf( 'Product/variation %d not found.', $target_id ) );
				}
				$attachment_id = (int) $input['attachment_id'];
				if ( ! wp_get_attachment_url( $attachment_id ) ) {
					return new WP_Error( 'ning_mcp_bad_attachment', sprintf( 'Attachment %d not found in media library.', $attachment_id ) );
				}
				if ( 'variation' === $object->get_type() ) {
					$object->set_image_id( $attachment_id );
					$object->save();
					return array( 'applied' => true, 'variation_id' => $target_id, 'image_attachment_id' => $attachment_id );
				}
				$object->set_image_id( $attachment_id );
				if ( ! empty( $input['gallery_attachment_ids'] ) ) {
					$object->set_gallery_image_ids( array_map( 'intval', $input['gallery_attachment_ids'] ) );
				}
				$object->save();
				return array( 'applied' => true, 'product_id' => $target_id, 'image_attachment_id' => $attachment_id, 'gallery_attachment_ids' => isset( $input['gallery_attachment_ids'] ) ? array_map( 'intval', $input['gallery_attachment_ids'] ) : array() );
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array(
				'readonly'   => false,
				'destructive' => true,
				'idempotent' => true,
			) ),
		)
	);

	wp_register_ability(
		'theme-design/get-active-theme',
		array(
			'label'       => 'Get Active Theme',
			'description' => 'Returns active theme info: stylesheet, template, name, version, is_child, is_block_theme.',
			'category'    => 'theme-design',
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'stylesheet'     => array( 'type' => 'string' ),
					'template'       => array( 'type' => 'string' ),
					'name'           => array( 'type' => 'string' ),
					'version'        => array( 'type' => 'string' ),
					'is_child'       => array( 'type' => 'boolean' ),
					'is_block_theme' => array( 'type' => 'boolean' ),
				),
			),
			'execute_callback'    => function () {
				$theme = wp_get_theme();
				return array(
					'stylesheet'     => $theme->get_stylesheet(),
					'template'       => $theme->get_template(),
					'name'           => $theme->get( 'Name' ),
					'version'        => $theme->get( 'Version' ),
					'is_child'       => is_child_theme(),
					'is_block_theme' => function_exists( 'wp_is_block_theme' ) ? wp_is_block_theme() : false,
				);
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array( 'readonly' => true, 'idempotent' => true ) ),
		)
	);

	wp_register_ability(
		'theme-design/list-files',
		array(
			'label'       => 'List Theme Files',
			'description' => 'Lists files in the active child theme (syron-child) under allowed paths: style.css, functions.php, template-parts, woocommerce.',
			'category'    => 'theme-design',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'subpath' => array( 'type' => 'string', 'description' => 'Optional subpath e.g. template-parts' ),
				),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'  => 'array',
				'items' => array( 'type' => 'string' ),
			),
			'execute_callback'    => function ( $input ) {
				$theme_dir = get_stylesheet_directory();
				$subpath   = isset( $input['subpath'] ) ? trim( $input['subpath'], '/' ) : '';
				if ( '' !== $subpath && false !== strpos( $subpath, '..' ) ) {
					return new WP_Error( 'ning_mcp_bad_path', 'Invalid subpath.' );
				}
				$target = '' === $subpath ? $theme_dir : $theme_dir . '/' . $subpath;
				if ( ! file_exists( $target ) || ! is_dir( $target ) ) {
					return new WP_Error( 'ning_mcp_not_found', 'Directory not found.' );
				}
				$files = array();
				$iter  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $target, FilesystemIterator::SKIP_DOTS ) );
				foreach ( $iter as $file ) {
					if ( $file->isFile() ) {
						$rel = substr( $file->getPathname(), strlen( $theme_dir ) + 1 );
						$rel = str_replace( '\\', '/', $rel );
						if ( preg_match( '#^(style\.css|functions\.php|theme\.json|template-parts/.*\.php|woocommerce/.*\.php)$#', $rel ) ) {
							$files[] = $rel;
						}
					}
				}
				sort( $files );
				return $files;
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array( 'readonly' => true, 'idempotent' => true ) ),
		)
	);

	wp_register_ability(
		'theme-design/read-file',
		array(
			'label'       => 'Read Theme File',
			'description' => 'Reads a single file from the active child theme. Allowed: style.css, functions.php, template-parts/*, woocommerce/*.',
			'category'    => 'theme-design',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'path' => array( 'type' => 'string', 'description' => 'Relative path e.g. style.css' ),
				),
				'required'             => array( 'path' ),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'path'    => array( 'type' => 'string' ),
					'content' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( $input ) {
				$rel = ltrim( $input['path'], '/' );
				if ( false !== strpos( $rel, '..' ) || ! preg_match( '#^(style\.css|functions\.php|theme\.json|template-parts/.*\.php|woocommerce/.*\.php)$#', $rel ) ) {
					return new WP_Error( 'ning_mcp_bad_path', 'Path not allowed.' );
				}
				$full = get_stylesheet_directory() . '/' . $rel;
				if ( ! file_exists( $full ) ) {
					return new WP_Error( 'ning_mcp_not_found', 'File not found.' );
				}
				return array( 'path' => $rel, 'content' => file_get_contents( $full ) );
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array( 'readonly' => true, 'idempotent' => true ) ),
		)
	);

	wp_register_ability(
		'theme-design/write-file',
		array(
			'label'       => 'Write Theme File',
			'description' => 'Writes a file in the active child theme with automatic backup. Allowed paths same as read-file. Creates backups/ subfolder.',
			'category'    => 'theme-design',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'path'    => array( 'type' => 'string', 'description' => 'Relative path e.g. style.css' ),
					'content' => array( 'type' => 'string', 'description' => 'Full file content to write.' ),
				),
				'required'             => array( 'path', 'content' ),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'path'        => array( 'type' => 'string' ),
					'backup_path' => array( 'type' => 'string' ),
					'bytes'       => array( 'type' => 'integer' ),
				),
			),
			'execute_callback'    => function ( $input ) {
				if ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) {
					return new WP_Error( 'ning_mcp_file_mods_disabled', 'File modifications are disabled via DISALLOW_FILE_MODS.' );
				}
				$rel = ltrim( $input['path'], '/' );
				if ( false !== strpos( $rel, '..' ) || ! preg_match( '#^(style\.css|functions\.php|theme\.json|template-parts/.*\.php|woocommerce/.*\.php)$#', $rel ) ) {
					return new WP_Error( 'ning_mcp_bad_path', 'Path not allowed.' );
				}
				$full = get_stylesheet_directory() . '/' . $rel;
				$dir  = dirname( $full );
				if ( ! file_exists( $dir ) ) {
					wp_mkdir_p( $dir );
				}
				$backup_path = '';
				if ( file_exists( $full ) ) {
					$upload_dir = wp_upload_dir();
					$backup_dir = $upload_dir['basedir'] . '/wp-mcp-abilities-backups';
					if ( ! file_exists( $backup_dir ) ) {
						wp_mkdir_p( $backup_dir );
						file_put_contents( $backup_dir . '/.htaccess', "Require all denied\nDeny from all\n" );
						file_put_contents( $backup_dir . '/index.html', '' );
					}
					$backup_name = gmdate( 'Ymd-His' ) . '-' . wp_rand( 1000, 9999 ) . '-' . str_replace( '/', '-', $rel ) . '.bak';
					$backup_path = $backup_dir . '/' . $backup_name;
					copy( $full, $backup_path );
				}
				$bytes = file_put_contents( $full, $input['content'] );
				if ( false === $bytes ) {
					return new WP_Error( 'ning_mcp_write_failed', 'Failed to write file.' );
				}
				if ( function_exists( 'wp_cache_flush' ) ) {
					wp_cache_flush();
				}
				return array( 'path' => $rel, 'backup_path' => $backup_path ? basename( $backup_path ) : '', 'bytes' => (int) $bytes );
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ) ),
		)
	);

	wp_register_ability(
		'elementor-design/get-page-data',
		array(
			'label'       => 'Get Elementor Page Data',
			'description' => 'Returns Elementor _elementor_data for a page (decoded) and edit mode.',
			'category'    => 'elementor-design',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'post_id' => array( 'type' => 'integer', 'description' => 'Page/post ID.' ),
				),
				'required'             => array( 'post_id' ),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'post_id'   => array( 'type' => 'integer' ),
					'has_data'  => array( 'type' => 'boolean' ),
					'count'     => array( 'type' => 'integer' ),
				),
			),
			'execute_callback'    => function ( $input ) {
				$post_id = (int) $input['post_id'];
				if ( ! get_post( $post_id ) ) {
					return new WP_Error( 'ning_mcp_not_found', 'Post not found.' );
				}
				$raw = get_post_meta( $post_id, '_elementor_data', true );
				$edit_mode = get_post_meta( $post_id, '_elementor_edit_mode', true );
				$data = $raw ? json_decode( $raw, true ) : array();
				return array(
					'post_id'   => $post_id,
					'has_data'  => ! empty( $raw ),
					'edit_mode' => $edit_mode ? $edit_mode : '',
					'count'     => is_array( $data ) ? count( $data ) : 0,
					'title'     => get_the_title( $post_id ),
					'permalink' => get_permalink( $post_id ),
				);
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array( 'readonly' => true, 'idempotent' => true ) ),
		)
	);

	wp_register_ability(
		'elementor-design/add-handmade-hero',
		array(
			'label'       => 'Create Handmade Hero Template',
			'description' => 'Creates an isolated Elementor library section with warm handmade style (safe, does not touch homepage). Returns shortcode [elementor-template id=X] to insert manually or via preview.',
			'category'    => 'elementor-design',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'title'                 => array( 'type' => 'string', 'description' => 'Hero heading, e.g. Handmade with Love' ),
					'subtitle'              => array( 'type' => 'string', 'description' => 'Hero subtitle.' ),
					'cta_text'              => array( 'type' => 'string', 'description' => 'Button text e.g. Shop New In' ),
					'cta_url'               => array( 'type' => 'string', 'description' => 'Button URL.' ),
					'image_attachment_id'   => array( 'type' => 'integer', 'description' => 'Optional image attachment ID for background.' ),
				),
				'required'             => array( 'title' ),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'template_id' => array( 'type' => 'integer' ),
					'shortcode'   => array( 'type' => 'string' ),
					'edit_url'    => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( $input ) {
				$title    = isset( $input['title'] ) ? $input['title'] : 'Handmade with Love';
				$subtitle = isset( $input['subtitle'] ) ? $input['subtitle'] : 'Warm crochet for everyday cozy';
				$cta_text = isset( $input['cta_text'] ) ? $input['cta_text'] : 'Shop New In';
				$cta_url  = isset( $input['cta_url'] ) ? $input['cta_url'] : home_url( '/shop/' );
				$image_url = '';
				$image_id  = 0;
				if ( ! empty( $input['image_attachment_id'] ) ) {
					$image_id  = (int) $input['image_attachment_id'];
					$image_url = wp_get_attachment_url( $image_id );
				}
				$uid = function ( $prefix ) {
					return $prefix . substr( md5( uniqid( (string) mt_rand(), true ) ), 0, 7 );
				};
				$hero = array(
					'id'       => $uid( 'hero' ),
					'elType'   => 'container',
					'settings' => array(
						'content_width'        => 'boxed',
						'boxed_width'          => array( 'unit' => 'px', 'size' => 1280, 'sizes' => array() ),
						'padding'              => array( 'unit' => 'px', 'top' => '80', 'right' => '24', 'bottom' => '80', 'left' => '24', 'isLinked' => false ),
						'background_background'=> 'classic',
						'background_color'     => '#FDF6EE',
						'border_radius'        => array( 'unit' => 'px', 'top' => '24', 'right' => '24', 'bottom' => '24', 'left' => '24', 'isLinked' => true ),
						'flex_direction'       => 'column',
						'align_items'          => 'center',
						'justify_content'      => 'center',
						'gap'                  => array( 'unit' => 'px', 'size' => '24', 'sizes' => array() ),
					),
					'elements' => array(
						array(
							'id'         => $uid( 'h1' ),
							'elType'     => 'widget',
							'widgetType' => 'heading',
							'settings'   => array(
								'title'       => $title,
								'header_size' => 'h1',
								'align'       => 'center',
								'text_color'  => '#5B4A3F',
							),
							'elements'   => array(),
							'isInner'    => false,
							'isLocked'   => false,
						),
						array(
							'id'         => $uid( 'sub' ),
							'elType'     => 'widget',
							'widgetType' => 'text-editor',
							'settings'   => array(
								'editor' => '<p style="text-align:center;">' . esc_html( $subtitle ) . '</p>',
								'align'  => 'center',
								'text_color' => '#8B7355',
							),
							'elements'   => array(),
							'isInner'    => false,
							'isLocked'   => false,
						),
						array(
							'id'         => $uid( 'btn' ),
							'elType'     => 'widget',
							'widgetType' => 'button',
							'settings'   => array(
								'text'  => $cta_text,
								'link'  => array( 'url' => $cta_url, 'is_external' => false, 'nofollow' => false ),
								'align' => 'center',
							),
							'elements'   => array(),
							'isInner'    => false,
							'isLocked'   => false,
						),
					),
					'isInner'  => false,
					'isLocked' => false,
				);
				if ( $image_url ) {
					$hero['settings']['background_image'] = array( 'url' => $image_url, 'id' => $image_id );
					$hero['settings']['background_position'] = 'center center';
					$hero['settings']['background_size'] = 'cover';
				}
				$template_id = wp_insert_post( array(
					'post_type'   => 'elementor_library',
					'post_title'  => $title . ' - Handmade Hero',
					'post_status' => 'publish',
					'post_content'=> '',
				), true );
				if ( is_wp_error( $template_id ) ) {
					return $template_id;
				}
				update_post_meta( $template_id, '_elementor_data', wp_slash( wp_json_encode( array( $hero ) ) ) );
				update_post_meta( $template_id, '_elementor_template_type', 'section' );
				update_post_meta( $template_id, '_elementor_edit_mode', 'builder' );
				$ver = defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '4.2.3';
				update_post_meta( $template_id, '_elementor_version', $ver );
				if ( class_exists( '\\Elementor\\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
					\Elementor\Plugin::$instance->files_manager->clear_cache();
				}
				return array(
					'template_id' => (int) $template_id,
					'shortcode'   => '[elementor-template id="' . $template_id . '"]',
					'edit_url'    => admin_url( 'post.php?post=' . $template_id . '&action=elementor' ),
					'preview_url' => get_permalink( $template_id ),
				);
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array( 'readonly' => false, 'destructive' => false, 'idempotent' => false ) ),
		)
	);

	wp_register_ability(
		'elementor-design/add-html-hero',
		array(
			'label'       => 'Add Handmade HTML Hero (Free)',
			'description' => 'Inserts a warm handmade-style hero at the top of an Elementor page using a free HTML widget (no Pro required). Backs up existing data.',
			'category'    => 'elementor-design',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'post_id'               => array( 'type' => 'integer', 'description' => 'Target page ID (defaults to front page).' ),
					'title'                 => array( 'type' => 'string', 'description' => 'Hero heading' ),
					'subtitle'              => array( 'type' => 'string', 'description' => 'Hero subtitle' ),
					'cta_text'              => array( 'type' => 'string', 'description' => 'Button text' ),
					'cta_url'               => array( 'type' => 'string', 'description' => 'Button URL' ),
					'image_attachment_id'   => array( 'type' => 'integer', 'description' => 'Optional image ID for inline <img>' ),
				),
				'required'             => array( 'title' ),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'post_id'    => array( 'type' => 'integer' ),
					'permalink'  => array( 'type' => 'string' ),
					'backup_key' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( $input ) {
				$post_id = ! empty( $input['post_id'] ) ? (int) $input['post_id'] : (int) get_option( 'page_on_front' );
				if ( ! $post_id || ! get_post( $post_id ) ) {
					return new WP_Error( 'ning_mcp_not_found', 'Target page not found.' );
				}
				$raw  = get_post_meta( $post_id, '_elementor_data', true );
				$data = $raw ? json_decode( $raw, true ) : array();
				if ( ! is_array( $data ) ) {
					$data = array();
				}
				$backup_key = '_elementor_data_backup_' . gmdate( 'YmdHis' );
				if ( $raw ) {
					update_post_meta( $post_id, $backup_key, $raw );
				}
				$uid = function ( $p ) {
					return $p . substr( md5( uniqid( (string) mt_rand(), true ) ), 0, 7 );
				};
				$title    = isset( $input['title'] ) ? $input['title'] : 'Handmade with Love';
				$subtitle = isset( $input['subtitle'] ) ? $input['subtitle'] : 'Warm crochet for everyday cozy';
				$cta_text = isset( $input['cta_text'] ) ? $input['cta_text'] : 'Shop New In';
				$cta_url  = isset( $input['cta_url'] ) ? $input['cta_url'] : home_url( '/shop/' );
				$img_html = '';
				if ( ! empty( $input['image_attachment_id'] ) ) {
					$url = wp_get_attachment_url( (int) $input['image_attachment_id'] );
					if ( $url ) {
						$img_html = '<img src="' . esc_url( $url ) . '" alt="" style="max-width:100%;height:auto;border-radius:16px;margin-top:16px;" />';
					}
				}
				$html = '<div style="background:#FDF6EE;border-radius:24px;padding:80px 24px;text-align:center;max-width:1280px;margin:0 auto;">'
					. '<h1 style="font-family:Caveat, cursive; font-size:48px; color:#5B4A3F; margin:0 0 16px;">' . esc_html( $title ) . '</h1>'
					. '<p style="color:#8B7355; font-size:18px; margin:0 0 24px;">' . esc_html( $subtitle ) . '</p>'
					. '<a href="' . esc_url( $cta_url ) . '" style="display:inline-block; background:#A67C52; color:#fff; border-radius:999px; padding:16px 32px; text-decoration:none; font-weight:600;">' . esc_html( $cta_text ) . '</a>'
					. $img_html . '</div>';
				$hero = array(
					'id'       => $uid( 'hero' ),
					'elType'   => 'container',
					'settings' => array(
						'content_width' => 'boxed',
						'boxed_width'   => array( 'unit' => 'px', 'size' => 1280, 'sizes' => array() ),
						'padding'       => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => false ),
						'gap'           => array( 'unit' => 'px', 'size' => '0', 'sizes' => array() ),
					),
					'elements' => array(
						array(
							'id'         => $uid( 'html' ),
							'elType'     => 'widget',
							'widgetType' => 'html',
							'settings'   => array( 'html' => $html ),
							'elements'   => array(),
							'isInner'    => false,
							'isLocked'   => false,
						),
					),
					'isInner'  => false,
					'isLocked' => false,
				);
				array_unshift( $data, $hero );
				update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
				if ( ! get_post_meta( $post_id, '_elementor_edit_mode', true ) ) {
					update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
				}
				if ( class_exists( '\\Elementor\\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
					\Elementor\Plugin::$instance->files_manager->clear_cache();
				}
				return array(
					'post_id'    => $post_id,
					'permalink'  => get_permalink( $post_id ),
					'backup_key' => $backup_key,
				);
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ) ),
		)
	);


	wp_register_ability(
		'elementor-design/add-threejs-module',
		array(
			'label'       => 'Add Three.js Module',
			'description' => 'Creates an isolated Elementor library section with a parameterized Three.js scene. Presets: yarn_ball (handmade brand) or knot. Optional GLB model overrides preset. Warm lighting, environment, knit bump, particles, lazy load, reduced-motion poster fallback, editor notice. Free HTML widget.',
			'category'    => 'elementor-design',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'title'                      => array( 'type' => 'string', 'description' => 'Template title.' ),
					'preset'                     => array( 'type' => 'string', 'enum' => array( 'yarn_ball', 'knot' ), 'default' => 'yarn_ball', 'description' => 'Scene preset. yarn_ball fits handmade brands.' ),
					'palette'                    => array( 'type' => 'object', 'properties' => array( 'primary' => array( 'type' => 'string', 'description' => 'Hex e.g. #A67C52' ), 'bg' => array( 'type' => 'string', 'description' => 'Hex background e.g. #FDF6EE' ) ), 'description' => 'Optional colors.' ),
					'particles'                  => array( 'type' => 'boolean', 'default' => true, 'description' => 'Floating dust particles.' ),
					'rotation_speed'             => array( 'type' => 'number', 'minimum' => 0.1, 'maximum' => 3, 'default' => 0.6, 'description' => 'Self-rotation speed.' ),
					'height'                     => array( 'type' => 'integer', 'minimum' => 300, 'maximum' => 800, 'default' => 500, 'description' => 'Canvas height px.' ),
					'poster_image_attachment_id' => array( 'type' => 'integer', 'description' => 'Optional poster image ID (shown pre-load and for reduced motion).' ),
					'model_attachment_id'        => array( 'type' => 'integer', 'description' => 'Optional .glb/.gltf attachment ID; overrides preset geometry.' ),
				),
				'required'             => array( 'title' ),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'template_id' => array( 'type' => 'integer' ),
					'shortcode'   => array( 'type' => 'string' ),
					'edit_url'    => array( 'type' => 'string' ),
					'preview_url' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( $input ) {
				$title  = isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : 'Three.js Module';
				$preset = ( isset( $input['preset'] ) && in_array( $input['preset'], array( 'yarn_ball', 'knot' ), true ) ) ? $input['preset'] : 'yarn_ball';
				$height = isset( $input['height'] ) ? max( 300, min( 800, (int) $input['height'] ) ) : 500;
				$speed  = isset( $input['rotation_speed'] ) ? max( 0.1, min( 3, (float) $input['rotation_speed'] ) ) : 0.6;
				$particles = isset( $input['particles'] ) ? (bool) $input['particles'] : true;
				$primary = '#A67C52';
				$bg      = '#FDF6EE';
				if ( isset( $input['palette']['primary'] ) && preg_match( '/^#[0-9a-fA-F]{6}$/', $input['palette']['primary'] ) ) {
					$primary = $input['palette']['primary'];
				}
				if ( isset( $input['palette']['bg'] ) && preg_match( '/^#[0-9a-fA-F]{6}$/', $input['palette']['bg'] ) ) {
					$bg = $input['palette']['bg'];
				}
				$poster_url = '';
				if ( ! empty( $input['poster_image_attachment_id'] ) ) {
					$poster_url = wp_get_attachment_url( (int) $input['poster_image_attachment_id'] );
				}
				$model_url = '';
				if ( ! empty( $input['model_attachment_id'] ) ) {
					$model_url = wp_get_attachment_url( (int) $input['model_attachment_id'] );
					if ( $model_url && ! preg_match( '/\.(glb|gltf)$/i', $model_url ) ) {
						return new WP_Error( 'ning_mcp_bad_model', 'Model attachment must be .glb or .gltf' );
					}
				}
				$uid = function ( $p ) {
					return $p . substr( md5( uniqid( (string) mt_rand(), true ) ), 0, 7 );
				};
				$rootId = $uid( 'three' );
				$poster_img = $poster_url ? '<img src="' . esc_url( $poster_url ) . '" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0.35;" />' : '';
				$model_js   = $model_url ? wp_json_encode( $model_url ) : '""';
				$particles_js = $particles ? 'true' : 'false';
				$speed_js   = wp_json_encode( $speed );
				$preset_js  = wp_json_encode( $preset );
				$primary_js = wp_json_encode( $primary );
				$bg_js      = wp_json_encode( $bg );

				$html = <<<HTMLEOF
<div id="{$rootId}" style="position:relative;height:{$height}px;border-radius:24px;overflow:hidden;background:linear-gradient(180deg,{$bg} 0%,#F5E9DA 100%);">{$poster_img}<div class="hh-shadow"></div><canvas style="display:block;width:100%;height:100%;"></canvas><div class="hh-notice">3D renders on the live site</div><noscript><p style="text-align:center;padding:40px;color:#8B7355;">Enable JavaScript to view 3D</p></noscript></div>
<style>#{$rootId} .hh-shadow{position:absolute;left:50%;bottom:24px;transform:translateX(-50%);width:230px;height:34px;background:radial-gradient(ellipse at center,rgba(91,74,63,0.30),rgba(91,74,63,0) 70%);filter:blur(2px);}#{$rootId} .hh-notice{position:absolute;top:12px;right:12px;background:rgba(91,74,63,0.88);color:#FDF6EE;font:12px/1.4 -apple-system,sans-serif;padding:6px 10px;border-radius:8px;pointer-events:none;}</style>
<script async src="https://unpkg.com/es-module-shims@1.8.0/dist/es-module-shims.js"></script>
<script type="importmap">{"imports":{"three":"https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js","three/addons/":"https://cdn.jsdelivr.net/npm/three@0.160.0/examples/jsm/"}}</script>
<script type="module">
const root=document.getElementById("{$rootId}");
if(!root) throw new Error("three root missing");
const canvas=root.querySelector("canvas");
const notice=root.querySelector(".hh-notice"); if(notice) notice.remove();
const reduced=window.matchMedia("(prefers-reduced-motion: reduce)").matches;
if(!reduced){
  let started=false;
  const start=()=>{ if(started) return; started=true; init().catch(err=>{ const d=document.createElement("div"); d.style.cssText="position:absolute;left:12px;bottom:12px;background:rgba(140,40,40,0.92);color:#fff;font:12px sans-serif;padding:6px 10px;border-radius:8px;max-width:80%;"; d.textContent="3D failed: "+String(err&&err.message||err); root.appendChild(d); }); };
  const obs=new IntersectionObserver(es=>{ es.forEach(e=>{ if(e.isIntersecting){ start(); obs.disconnect(); } }); },{rootMargin:"200px"});
  obs.observe(root);
  setTimeout(()=>{ if(!started) start(); },3000);
}
async function init(){
  const THREE=await import("three");
  const {OrbitControls}=await import("three/addons/controls/OrbitControls.js");
  const {RoomEnvironment}=await import("three/addons/environments/RoomEnvironment.js");
  const renderer=new THREE.WebGLRenderer({canvas:canvas,antialias:true,alpha:true});
  renderer.setPixelRatio(Math.min(window.devicePixelRatio||1,1.5));
  renderer.toneMapping=THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure=1.05;
  const scene=new THREE.Scene();
  const pmrem=new THREE.PMREMGenerator(renderer);
  scene.environment=pmrem.fromScene(new RoomEnvironment(),0.04).texture;
  const camera=new THREE.PerspectiveCamera(42,root.clientWidth/{$height},0.1,100);
  camera.position.set(0,0.6,3.4);
  const controls=new OrbitControls(camera,renderer.domElement);
  controls.enableDamping=true; controls.enablePan=false;
  controls.minDistance=2.2; controls.maxDistance=6; controls.target.set(0,0.15,0);
  scene.add(new THREE.AmbientLight(0xFFF2E2,0.4));
  const key=new THREE.DirectionalLight(0xFFE8CF,1.1); key.position.set(3,5,2); scene.add(key);
  const rim=new THREE.DirectionalLight(0xD9A679,0.65); rim.position.set(-3,2,-3); scene.add(rim);
  function knitTex(){
    const c=document.createElement("canvas"); c.width=c.height=256;
    const g=c.getContext("2d");
    g.fillStyle="#8a8a8a"; g.fillRect(0,0,256,256);
    g.strokeStyle="#c6c6c6"; g.lineWidth=11; g.lineCap="round";
    const s=32;
    for(let y=0;y<8;y++){ for(let x=0;x<8;x++){
      const cx=x*s+s/2, cy=y*s+s/2;
      g.beginPath(); g.moveTo(cx-s/2+5, cy+s/2-7); g.quadraticCurveTo(cx, cy-s/2+7, cx+s/2-5, cy+s/2-7); g.stroke();
      g.beginPath(); g.moveTo(cx-s/2+5, cy-s/2+7); g.quadraticCurveTo(cx, cy+s/2-7, cx+s/2-5, cy-s/2+7); g.stroke();
    }}
    const t=new THREE.CanvasTexture(c); t.wrapS=t.wrapT=THREE.RepeatWrapping; t.repeat.set(5,5); return t;
  }
  const bump=knitTex();
  const mat=new THREE.MeshPhysicalMaterial({color:{$primary_js},roughness:0.78,sheen:1,sheenColor:new THREE.Color(0xE8D5BE),sheenRoughness:0.55,bumpMap:bump,bumpScale:0.5});
  const matB=new THREE.MeshPhysicalMaterial({color:0xD9A679,roughness:0.8,sheen:1,sheenColor:new THREE.Color(0xF0E2CC),sheenRoughness:0.6,bumpMap:bump,bumpScale:0.4});
  const group=new THREE.Group(); scene.add(group);
  const orbiters=[];
  const PRESET={$preset_js};
  function fallback(){
    if(PRESET==="knot"){ group.add(new THREE.Mesh(new THREE.TorusKnotGeometry(0.75,0.24,160,24),mat)); }
    else{
      group.add(new THREE.Mesh(new THREE.SphereGeometry(0.95,64,64),mat));
      const thread=(pts,r)=>{ const cur=new THREE.CatmullRomCurve3(pts); return new THREE.Mesh(new THREE.TubeGeometry(cur,48,r,8,false),matB); };
      group.add(thread([new THREE.Vector3(-0.2,0.95,0.3),new THREE.Vector3(0.4,1.25,0.2),new THREE.Vector3(0.9,1.0,-0.1)],0.045));
      group.add(thread([new THREE.Vector3(0.1,-0.95,-0.2),new THREE.Vector3(-0.5,-1.2,0.15),new THREE.Vector3(-1.0,-0.9,0.35)],0.04));
      for(let i=0;i<3;i++){
        const m=new THREE.Mesh(new THREE.SphereGeometry(0.13+i*0.045,32,32),i%2?matB:mat);
        m.userData.orbit={r:1.55+i*0.28,s:0.25+i*0.09,ph:i*2.1};
        orbiters.push(m); scene.add(m);
      }
    }
  }
  const MODEL={$model_js};
  if(MODEL){
    const {GLTFLoader}=await import("three/addons/loaders/GLTFLoader.js");
    const {DRACOLoader}=await import("three/addons/loaders/DRACOLoader.js");
    const loader=new GLTFLoader();
    const draco=new DRACOLoader(); draco.setDecoderPath("https://www.gstatic.com/draco/v1/decoders/"); loader.setDRACOLoader(draco);
    loader.load(MODEL,g=>{ const loaded=g.scene; const box=new THREE.Box3().setFromObject(loaded); const size=box.getSize(new THREE.Vector3()).length(); const s=2.2/Math.max(size,0.001); loaded.scale.setScalar(s); const center=box.getCenter(new THREE.Vector3()).multiplyScalar(s); loaded.position.sub(center); group.add(loaded); },undefined,e=>{ console.warn("GLB load failed",e); fallback(); });
  } else { fallback(); }
  let points=null;
  if({$particles_js}){
    const n=140, pos=new Float32Array(n*3);
    for(let i=0;i<n;i++){ pos[i*3]=(Math.random()-0.5)*7; pos[i*3+1]=(Math.random()-0.5)*4; pos[i*3+2]=(Math.random()-0.5)*4; }
    const pg=new THREE.BufferGeometry(); pg.setAttribute("position",new THREE.BufferAttribute(pos,3));
    const sc=document.createElement("canvas"); sc.width=sc.height=64;
    const sg=sc.getContext("2d"); const gr=sg.createRadialGradient(32,32,0,32,32,30);
    gr.addColorStop(0,"rgba(217,166,121,0.9)"); gr.addColorStop(1,"rgba(217,166,121,0)");
    sg.fillStyle=gr; sg.fillRect(0,0,64,64);
    points=new THREE.Points(pg,new THREE.PointsMaterial({size:0.09,map:new THREE.CanvasTexture(sc),transparent:true,opacity:0.55,depthWrite:false,color:0xD9A679}));
    scene.add(points);
  }
  function resize(){ const w=root.clientWidth||1; renderer.setSize(w,{$height},false); camera.aspect=w/{$height}; camera.updateProjectionMatrix(); }
  window.addEventListener("resize",resize); resize();
  const clock=new THREE.Clock();
  function animate(){
    requestAnimationFrame(animate);
    const t=clock.getElapsedTime();
    if(!MODEL) group.rotation.y+={$speed_js}*0.01;
    group.position.y=Math.sin(t*0.8)*0.07;
    orbiters.forEach(o=>{ const ob=o.userData.orbit; o.position.set(Math.cos(t*ob.s+ob.ph)*ob.r,Math.sin(t*ob.s*0.8+ob.ph)*0.5,Math.sin(t*ob.s+ob.ph)*ob.r*0.6); o.rotation.y=t*0.4; });
    if(points) points.rotation.y=t*0.02;
    controls.update(); renderer.render(scene,camera);
  }
  animate();
}
</script>
HTMLEOF;
				$container = array(
					'id'       => $uid( 'three' ),
					'elType'   => 'container',
					'settings' => array(
						'content_width' => 'boxed',
						'boxed_width'   => array( 'unit' => 'px', 'size' => 1280, 'sizes' => array() ),
						'padding'       => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => false ),
					),
					'elements' => array(
						array(
							'id'         => $uid( 'el' ),
							'elType'     => 'widget',
							'widgetType' => 'html',
							'settings'   => array( 'html' => $html ),
							'elements'   => array(),
							'isInner'    => false,
							'isLocked'   => false,
						),
					),
					'isInner'  => false,
					'isLocked' => false,
				);
				$template_id = wp_insert_post( array(
					'post_type'   => 'elementor_library',
					'post_title'  => $title,
					'post_status' => 'publish',
					'post_content'=> '',
				), true );
				if ( is_wp_error( $template_id ) ) {
					return $template_id;
				}
				update_post_meta( $template_id, '_elementor_data', wp_slash( wp_json_encode( array( $container ) ) ) );
				update_post_meta( $template_id, '_elementor_template_type', 'section' );
				update_post_meta( $template_id, '_elementor_edit_mode', 'builder' );
				$ver = defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '4.2.3';
				update_post_meta( $template_id, '_elementor_version', $ver );
				if ( class_exists( '\\Elementor\\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
					\Elementor\Plugin::$instance->files_manager->clear_cache();
				}
				return array(
					'template_id' => (int) $template_id,
					'shortcode'   => '[elementor-template id="' . $template_id . '"]',
					'edit_url'    => admin_url( 'post.php?post=' . $template_id . '&action=elementor' ),
					'preview_url' => get_permalink( $template_id ),
				);
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array( 'readonly' => false, 'destructive' => false, 'idempotent' => false ) ),
		)
	);

	wp_register_ability(
		'elementor-design/add-gallery-module',
		array(
			'label'       => 'Add Interactive Gallery',
			'description' => 'Inserts a 3D ring gallery of published WooCommerce products into an Elementor page (free HTML widget). Cards use product images, drag to rotate with inertia, click opens product. Placeholder cards fill missing images. Lazy, responsive, reduced-motion grid fallback. Backs up page data.',
			'category'    => 'elementor-design',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'title'          => array( 'type' => 'string', 'description' => 'Template/section title.' ),
					'post_id'        => array( 'type' => 'integer', 'description' => 'Target page ID (defaults to front page).' ),
					'position'       => array( 'type' => 'string', 'enum' => array( 'top', 'after_hero' ), 'default' => 'after_hero', 'description' => 'Insert at page top or after the first container (hero).' ),
					'height'         => array( 'type' => 'integer', 'minimum' => 360, 'maximum' => 520, 'default' => 460, 'description' => 'Canvas height px.' ),
					'count'          => array( 'type' => 'integer', 'minimum' => 4, 'maximum' => 10, 'default' => 8, 'description' => 'Max products on the ring.' ),
					'rotation_speed' => array( 'type' => 'number', 'minimum' => 0.1, 'maximum' => 2, 'default' => 0.5, 'description' => 'Auto-rotation speed.' ),
					'palette'        => array( 'type' => 'object', 'properties' => array( 'primary' => array( 'type' => 'string', 'description' => 'Hex e.g. #A67C52' ), 'bg' => array( 'type' => 'string', 'description' => 'Hex background e.g. #FDF6EE' ) ), 'description' => 'Optional colors.' ),
				),
				'required'             => array( 'title' ),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'post_id'     => array( 'type' => 'integer' ),
					'backup_key'  => array( 'type' => 'string' ),
					'cards'       => array( 'type' => 'integer' ),
					'placeholders'=> array( 'type' => 'integer' ),
				),
			),
			'execute_callback'    => function ( $input ) {
				if ( ! function_exists( 'wc_get_products' ) ) {
					return new WP_Error( 'ning_mcp_wc_missing', 'WooCommerce is not active.' );
				}
				$title  = isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : 'Interactive Gallery';
				$post_id = ! empty( $input['post_id'] ) ? (int) $input['post_id'] : (int) get_option( 'page_on_front' );
				if ( ! $post_id || ! get_post( $post_id ) ) {
					return new WP_Error( 'ning_mcp_not_found', 'Target page not found.' );
				}
				$position = ( isset( $input['position'] ) && 'top' === $input['position'] ) ? 'top' : 'after_hero';
				$height = isset( $input['height'] ) ? max( 360, min( 520, (int) $input['height'] ) ) : 460;
				$count  = isset( $input['count'] ) ? max( 4, min( 10, (int) $input['count'] ) ) : 8;
				$speed  = isset( $input['rotation_speed'] ) ? max( 0.1, min( 2, (float) $input['rotation_speed'] ) ) : 0.5;
				$primary = '#A67C52';
				$bg      = '#FDF6EE';
				if ( isset( $input['palette']['primary'] ) && preg_match( '/^#[0-9a-fA-F]{6}$/', $input['palette']['primary'] ) ) {
					$primary = $input['palette']['primary'];
				}
				if ( isset( $input['palette']['bg'] ) && preg_match( '/^#[0-9a-fA-F]{6}$/', $input['palette']['bg'] ) ) {
					$bg = $input['palette']['bg'];
				}

				$products = wc_get_products( array(
					'status'   => 'publish',
					'limit'    => $count,
					'orderby'  => 'date',
					'order'    => 'DESC',
				) );
				$cards = array();
				foreach ( $products as $product ) {
					$img_id = $product->get_image_id();
					$img    = $img_id ? wp_get_attachment_url( $img_id ) : '';
					$cards[] = array(
						'name' => $product->get_name(),
						'url'  => get_permalink( $product->get_id() ),
						'img'  => $img ? $img : '',
					);
				}
				$placeholders = 0;
				$ring_total   = max( 6, count( $cards ) );
				$shop_url     = home_url( '/shop/' );
				while ( count( $cards ) < $ring_total ) {
					$cards[] = array( 'name' => 'Handmade Piece', 'url' => $shop_url, 'img' => '' );
					$placeholders++;
				}
				$cards = array_slice( $cards, 0, $ring_total );

				$raw  = get_post_meta( $post_id, '_elementor_data', true );
				$data = $raw ? json_decode( $raw, true ) : array();
				if ( ! is_array( $data ) ) {
					$data = array();
				}
				$backup_key = '_elementor_data_backup_' . gmdate( 'YmdHis' );
				if ( $raw ) {
					update_post_meta( $post_id, $backup_key, $raw );
				}
				$uid = function ( $p ) {
					return $p . substr( md5( uniqid( (string) mt_rand(), true ) ), 0, 7 );
				};
				$rootId      = $uid( 'gal' );
				$cards_js    = wp_json_encode( array_values( $cards ) );
				$speed_js    = wp_json_encode( $speed );
				$primary_js  = wp_json_encode( $primary );
				$bg_js       = wp_json_encode( $bg );

				$grid_html = '';
				foreach ( $cards as $card ) {
					$grid_html .= '<a href="' . esc_url( $card['url'] ) . '" target="_blank" rel="noopener">';
					if ( $card['img'] ) {
						$grid_html .= '<img src="' . esc_url( $card['img'] ) . '" alt="' . esc_attr( $card['name'] ) . '" loading="lazy" />';
					} else {
						$grid_html .= '<span class="hh-g-ph">' . esc_html( $card['name'] ) . '</span>';
					}
					$grid_html .= '</a>';
				}

				$html = <<<HTMLEOF
<div id="{$rootId}" style="position:relative;height:{$height}px;border-radius:24px;overflow:hidden;background:linear-gradient(180deg,{$bg} 0%,#F5E9DA 100%);"><div class="hh-shadow"></div><canvas style="display:block;width:100%;height:100%;cursor:grab;"></canvas><div class="hh-grid" hidden>{$grid_html}</div><div class="hh-notice">3D renders on the live site — drag to rotate</div><noscript><p style="text-align:center;padding:40px;color:#8B7355;">Enable JavaScript to view the gallery</p></noscript></div>
<style>#{$rootId} .hh-shadow{position:absolute;left:50%;bottom:18px;transform:translateX(-50%);width:280px;height:30px;background:radial-gradient(ellipse at center,rgba(91,74,63,0.26),rgba(91,74,63,0) 70%);filter:blur(2px);}#{$rootId} .hh-notice{position:absolute;top:12px;right:12px;background:rgba(91,74,63,0.88);color:#FDF6EE;font:12px/1.4 -apple-system,sans-serif;padding:6px 10px;border-radius:8px;pointer-events:none;}#{$rootId} .hh-grid:not([hidden]){position:absolute;inset:0;display:flex;flex-wrap:wrap;gap:14px;align-content:center;justify-content:center;padding:20px;}#{$rootId} .hh-grid a{width:132px;text-decoration:none;}#{$rootId} .hh-grid img,#{$rootId} .hh-grid .hh-g-ph{display:block;width:132px;height:165px;object-fit:cover;border-radius:16px;border:2px dashed {$primary};background:#FDF6EE;color:#8B7355;font:14px/1.5 -apple-system,sans-serif;text-align:center;padding:8px;box-sizing:border-box;}</style>
<script async src="https://unpkg.com/es-module-shims@1.8.0/dist/es-module-shims.js"></script>
<script type="importmap">{"imports":{"three":"https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js","three/addons/":"https://cdn.jsdelivr.net/npm/three@0.160.0/examples/jsm/"}}</script>
<script type="module">
const root=document.getElementById("{$rootId}");
if(!root) throw new Error("gallery root missing");
const canvas=root.querySelector("canvas");
const notice=root.querySelector(".hh-notice"); if(notice) notice.remove();
const CARDS={$cards_js};
const reduced=window.matchMedia("(prefers-reduced-motion: reduce)").matches;
const grid=root.querySelector(".hh-grid");
if(reduced && grid){ grid.hidden=false; canvas.remove(); const sh=root.querySelector(".hh-shadow"); if(sh) sh.remove(); }
else {
  let started=false;
  const start=()=>{ if(started) return; started=true; init().catch(err=>{ const d=document.createElement("div"); d.style.cssText="position:absolute;left:12px;bottom:12px;background:rgba(140,40,40,0.92);color:#fff;font:12px sans-serif;padding:6px 10px;border-radius:8px;max-width:80%;z-index:5;"; d.textContent="3D failed: "+String(err&&err.message||err); root.appendChild(d); }); };
  const obs=new IntersectionObserver(es=>{ es.forEach(e=>{ if(e.isIntersecting){ start(); obs.disconnect(); } }); },{rootMargin:"250px"});
  obs.observe(root);
  setTimeout(()=>{ if(!started) start(); },3000);
}
async function init(){
  const THREE=await import("three");
  const {RoomEnvironment}=await import("three/addons/environments/RoomEnvironment.js");
  const N=CARDS.length;
  const cardW=1.35, cardH=1.69;
  const radius=Math.max(2.5,(cardW/2)/Math.tan(Math.PI/N)+0.6);
  const renderer=new THREE.WebGLRenderer({canvas:canvas,antialias:true,alpha:true});
  renderer.setPixelRatio(Math.min(window.devicePixelRatio||1,1.5));
  renderer.toneMapping=THREE.ACESFilmicToneMapping;
  const scene=new THREE.Scene();
  const pmrem=new THREE.PMREMGenerator(renderer);
  scene.environment=pmrem.fromScene(new RoomEnvironment(),0.04).texture;
  const camera=new THREE.PerspectiveCamera(42,root.clientWidth/{$height},0.1,100);
  const baseZ=radius+2.3;
  camera.position.set(0,0.25,baseZ);
  scene.add(new THREE.AmbientLight(0xFFF2E2,0.45));
  const key=new THREE.DirectionalLight(0xFFE8CF,1.0); key.position.set(3,5,3); scene.add(key);
  const rim=new THREE.DirectionalLight(0xD9A679,0.55); rim.position.set(-3,2,-3); scene.add(rim);
  function rr(g,x,y,w,h,r){ g.beginPath(); g.moveTo(x+r,y); g.arcTo(x+w,y,x+w,y+h,r); g.lineTo(x+w,y+h-r); g.arcTo(x+w,y+h,x,y+h,r); g.lineTo(x,y+h-r); g.arcTo(x,y,x+w,y,r); g.closePath(); }
  function cardTexture(card){
    const c=document.createElement("canvas"); c.width=512; c.height=640;
    const g=c.getContext("2d");
    g.fillStyle="#FDF6EE"; rr(g,0,0,512,640,36); g.fill();
    g.strokeStyle="{$primary_js}".replace(/"/g,""); g.lineWidth=6; g.setLineDash([16,10]); rr(g,16,16,480,608,26); g.stroke(); g.setLineDash([]);
    const tex=new THREE.CanvasTexture(c); tex.anisotropy=4;
    if(card.img){
      const im=new Image(); im.crossOrigin="anonymous";
      im.onload=()=>{ g.save(); rr(g,34,34,444,470,20); g.clip(); const s=Math.max(444/im.width,470/im.height); const w=im.width*s, h=im.height*s; g.drawImage(im,34+(444-w)/2,34+(470-h)/2,w,h); g.restore(); tex.needsUpdate=true; };
      im.src=card.img;
    } else {
      g.fillStyle="#F5E9DA"; rr(g,34,34,444,470,20); g.fill();
      g.strokeStyle="#D9A679"; g.lineWidth=10; g.lineCap="round";
      for(let y=90;y<470;y+=46){ g.beginPath(); g.moveTo(64,y); g.quadraticCurveTo(256,y+26,448,y); g.stroke(); }
      tex.needsUpdate=true;
    }
    g.fillStyle="rgba(91,74,63,0.93)"; rr(g,26,532,460,76,18); g.fill();
    g.fillStyle="#FDF6EE"; g.font="600 30px -apple-system,Segoe UI,sans-serif"; g.textAlign="center"; g.textBaseline="middle";
    let nm=card.name||"Handmade"; if(nm.length>18) nm=nm.slice(0,17)+"…";
    g.fillText(nm,256,571);
    tex.needsUpdate=true;
    return tex;
  }
  const group=new THREE.Group(); scene.add(group);
  const meshes=[];
  for(let i=0;i<N;i++){
    const m=new THREE.Mesh(new THREE.PlaneGeometry(cardW,cardH),new THREE.MeshBasicMaterial({map:cardTexture(CARDS[i]),transparent:true,side:THREE.DoubleSide}));
    const th=i*2*Math.PI/N;
    m.position.set(Math.sin(th)*radius,0,Math.cos(th)*radius);
    m.rotation.y=th;
    m.userData.idx=i;
    group.add(m); meshes.push(m);
  }
  let points=null;
  const n=120, pos=new Float32Array(n*3);
  for(let i=0;i<n;i++){ pos[i*3]=(Math.random()-0.5)*8; pos[i*3+1]=(Math.random()-0.5)*4; pos[i*3+2]=(Math.random()-0.5)*5; }
  const pg=new THREE.BufferGeometry(); pg.setAttribute("position",new THREE.BufferAttribute(pos,3));
  const sc=document.createElement("canvas"); sc.width=sc.height=64;
  const sg=sc.getContext("2d"); const gr=sg.createRadialGradient(32,32,0,32,32,30);
  gr.addColorStop(0,"rgba(217,166,121,0.9)"); gr.addColorStop(1,"rgba(217,166,121,0)");
  sg.fillStyle=gr; sg.fillRect(0,0,64,64);
  points=new THREE.Points(pg,new THREE.PointsMaterial({size:0.08,map:new THREE.CanvasTexture(sc),transparent:true,opacity:0.5,depthWrite:false,color:0xD9A679}));
  scene.add(points);
  let dragging=false,lastX=0,vel=0,moved=0;
  const el=renderer.domElement; el.style.touchAction="pan-y";
  el.addEventListener("pointerdown",e=>{ dragging=true; lastX=e.clientX; moved=0; el.setPointerCapture(e.pointerId); });
  el.addEventListener("pointermove",e=>{ if(!dragging) return; const dx=e.clientX-lastX; lastX=e.clientX; moved+=Math.abs(dx); group.rotation.y+=dx*0.005; vel=dx*0.005; });
  el.addEventListener("pointerup",e=>{ dragging=false; if(moved<6){ const rect=el.getBoundingClientRect(); const mx=((e.clientX-rect.left)/rect.width)*2-1; const my=-((e.clientY-rect.top)/rect.height)*2+1; const rc=new THREE.Raycaster(); rc.setFromCamera({x:mx,y:my},camera); const hit=rc.intersectObjects(meshes)[0]; if(hit){ const card=CARDS[hit.object.userData.idx]; if(card&&card.url) window.open(card.url,"_blank"); } } });
  function resize(){ const w=root.clientWidth||1; renderer.setSize(w,{$height},false); camera.aspect=w/{$height}; camera.updateProjectionMatrix(); camera.position.z=(camera.aspect<0.8)?baseZ+1.4:baseZ; }
  window.addEventListener("resize",resize); resize();
  const clock=new THREE.Clock();
  let raf=null;
  function frame(){
    raf=requestAnimationFrame(frame);
    const t=clock.getElapsedTime();
    if(!dragging){ vel*=0.94; group.rotation.y+=vel+{$speed_js}*0.0012; }
    if(points) points.rotation.y=t*0.02;
    renderer.render(scene,camera);
  }
  const vio=new IntersectionObserver(es=>{ es.forEach(e=>{ if(e.isIntersecting){ if(raf===null){ clock.getDelta(); frame(); } } else { if(raf!==null){ cancelAnimationFrame(raf); raf=null; } } }); },{threshold:0.05});
  vio.observe(root);
}
</script>
HTMLEOF;
				$gallery = array(
					'id'       => $uid( 'galw' ),
					'elType'   => 'container',
					'settings' => array(
						'content_width' => 'boxed',
						'boxed_width'   => array( 'unit' => 'px', 'size' => 1280, 'sizes' => array() ),
						'padding'       => array( 'unit' => 'px', 'top' => '24', 'right' => '0', 'bottom' => '24', 'left' => '0', 'isLinked' => false ),
					),
					'elements' => array(
						array(
							'id'         => $uid( 'el' ),
							'elType'     => 'widget',
							'widgetType' => 'html',
							'settings'   => array( 'html' => $html ),
							'elements'   => array(),
							'isInner'    => false,
							'isLocked'   => false,
						),
					),
					'isInner'  => false,
					'isLocked' => false,
				);
				$index = ( 'top' === $position ) ? 0 : min( 1, count( $data ) );
				array_splice( $data, $index, 0, array( $gallery ) );
				update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
				if ( ! get_post_meta( $post_id, '_elementor_edit_mode', true ) ) {
					update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
				}
				if ( class_exists( '\\Elementor\\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
					\Elementor\Plugin::$instance->files_manager->clear_cache();
				}
				return array(
					'post_id'      => $post_id,
					'backup_key'   => $backup_key,
					'position'     => $position,
					'cards'        => count( $cards ) - $placeholders,
					'placeholders' => $placeholders,
				);
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array( 'readonly' => false, 'destructive' => true, 'idempotent' => false ) ),
		)
	);
} );

/**
 * Hide locked (Pro) widgets and empty Pro categories from the Elementor
 * editor panel. Toggle via wp_mcp_hide_locked_widgets option.
 */
add_action( 'elementor/editor/after_enqueue_scripts', function () {
	if ( ! get_option( 'wp_mcp_hide_locked_widgets', 1 ) ) {
		return;
	}
	$css = '#elementor-panel .elementor-element-wrapper.elementor-element--promotion{display:none!important}'
		. '#elementor-panel .elementor-panel-category-title:has(.elementor-panel-heading-promotion){display:none!important}'
		. '#elementor-panel [id^="elementor-panel-category-"]:not(:has(.elementor-element-wrapper:not(.elementor-element--promotion))){display:none!important}';
	echo '<style id="wp-mcp-hide-locked-widgets">' . $css . '</style>' . "\n";
} );

add_action( 'wp_abilities_api_init', function () {
	wp_register_ability(
		'elementor-design/editor-visibility',
		array(
			'label'       => 'Editor Visibility',
			'description' => 'Controls whether locked (Pro) widgets and fully-locked categories are hidden from the Elementor editor panel. action=get returns the current state; action=set toggles it via the enabled flag.',
			'category'    => 'elementor-design',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'action'  => array( 'type' => 'string', 'enum' => array( 'get', 'set' ), 'default' => 'get', 'description' => 'get returns state; set updates the enabled flag.' ),
					'enabled' => array( 'type' => 'boolean', 'description' => 'Required when action=set. true hides locked widgets.' ),
				),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'enabled'           => array( 'type' => 'boolean' ),
					'hidden_categories' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					'note'              => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( $input ) {
				$key    = 'wp_mcp_hide_locked_widgets';
				$action = isset( $input['action'] ) ? $input['action'] : 'get';
				if ( 'set' === $action ) {
					update_option( $key, ! empty( $input['enabled'] ) ? 1 : 0 );
				}
				return array(
					'enabled'           => (bool) get_option( $key, 1 ),
					'hidden_categories' => array( 'Pro', 'Site', 'Single', 'WooCommerce', 'Hello+', 'Atomic Elements' ),
					'note'              => 'Editor panel hides .elementor-element--promotion widgets and categories whose widgets are all locked.',
				);
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array( 'readonly' => false, 'destructive' => false, 'idempotent' => false ) ),
		)
	);

	if (!function_exists('ning_mcp_design_tokens')) {
	// ─────────────────────────────────────────────────────────────
	// v1.7.0 — Design tokens + pattern library (Elementor component trees)
	// ─────────────────────────────────────────────────────────────

	function ning_mcp_design_tokens() {
		$defaults = array(
			'palette'    => array(
				'primary' => '#A67C52',
				'accent'  => '#D9A679',
				'bg'      => '#FDF6EE',
				'text'    => '#3B3B3B',
				'muted'   => '#8A7A6A',
				'border'  => '#E8DDD0',
			),
			'typography' => array(
				'heading_font' => 'Playfair Display, serif',
				'body_font'    => 'Inter, sans-serif',
			),
			'spacing'    => array(
				'section' => '72',
				'gap'     => '24',
			),
			'radius'     => array(
				'card'   => '18',
				'button' => '999',
			),
			'shadow'     => array(
				'card' => '0 4px 20px rgba(166,124,82,.12)',
			),
		);
		$saved = get_option( 'wp_mcp_design_tokens', array() );
		if ( is_array( $saved ) ) {
			foreach ( $saved as $group => $values ) {
				if ( isset( $defaults[ $group ] ) && is_array( $values ) ) {
					$defaults[ $group ] = array_merge( $defaults[ $group ], $values );
				}
			}
		}
		return $defaults;
	}

	function ning_mcp_widget_catalog() {
		return array(
			'heading'       => array( 'title', 'header_size', 'align', 'link', 'title_color', 'title_typography_*' ),
			'text-editor'   => array( 'editor', 'text_color', 'typography_*' ),
			'button'        => array( 'text', 'link', 'size', 'align', 'button_text_color', 'background_color', 'border_radius', 'button_hover_color', 'typography_*' ),
			'image'         => array( 'image{url,id,size}', 'size', 'opacity' ),
			'icon-box'      => array( 'selected_icon', 'view', 'shape', 'title_text', 'description_text', 'primary_color', 'icon_size' ),
			'divider'       => array( 'look', 'text', 'html_tag', 'color', 'gap' ),
			'counter'       => array( 'starting_number', 'ending_number', 'prefix', 'suffix', 'duration', 'number_color', 'title', 'title_color' ),
			'testimonial'   => array( 'testimonial_content', 'testimonial_name', 'testimonial_job', 'content_content_color' ),
			'star-rating'   => array( 'rating_scale', 'rating', 'stars_color' ),
			'html'          => array( 'html' ),
			'shortcode'     => array( 'shortcode' ),
			'container'     => array( 'content_width', 'boxed_width', 'flex_direction', 'justify_content', 'align_items', 'width(+_tablet/_mobile)', 'padding', 'gap', 'background_background', 'background_color', 'border_radius' ),
		);
	}

	function ning_mcp_tree_uid_next() {
		static $counter = 0;
		$counter++;
		return substr( 'w' . base_convert( (string) ( 268435456 + $counter * 7919 ), 10, 36 ), 0, 8 );
	}

	function ning_mcp_tree_to_elementor_data( $tree ) {
		$walk = function ( $node ) use ( &$walk ) {
			if ( 'widget' === $node['type'] ) {
				return array(
					'id'         => ning_mcp_tree_uid_next(),
					'elType'     => 'widget',
					'widgetType' => $node['widget'],
					'settings'   => isset( $node['settings'] ) ? $node['settings'] : array(),
					'elements'   => array(),
					'isInner'    => false,
					'isLocked'   => false,
				);
			}
			$out = array(
				'id'       => ning_mcp_tree_uid_next(),
				'elType'   => 'container',
				'settings' => isset( $node['settings'] ) ? $node['settings'] : array(),
				'elements' => array(),
				'isInner'  => false,
				'isLocked' => false,
			);
			foreach ( $node['children'] as $child ) {
				$out['elements'][] = $walk( $child );
			}
			return $out;
		};
		return array_map( $walk, $tree );
	}

	function ning_mcp_px( $size ) {
		return array( 'unit' => 'px', 'size' => (int) $size, 'sizes' => array() );
	}

	function ning_mcp_box( $top, $right, $bottom, $left ) {
		return array( 'unit' => 'px', 'top' => (int) $top, 'right' => (int) $right, 'bottom' => (int) $bottom, 'left' => (int) $left, 'isLinked' => false );
	}

	function ning_mcp_t_heading( $text, $tag, $t, $extra = array() ) {
		$base = array(
			'title'                       => $text,
			'header_size'                 => $tag,
			'title_color'                 => $t['palette']['text'],
			'typography_typography'       => 'custom',
			'typography_font_family'      => $t['typography']['heading_font'],
			'typography_font_size'        => ning_mcp_px( 'h1' === $tag ? 46 : 32 ),
			'typography_line_height'      => array( 'unit' => 'em', 'size' => 1.15, 'sizes' => array() ),
		);
		return array_merge( $base, $extra );
	}

	function ning_mcp_t_text( $html, $t, $extra = array() ) {
		$base = array(
			'editor'                 => $html,
			'text_color'             => $t['palette']['muted'],
			'typography_typography'  => 'custom',
			'typography_font_family' => $t['typography']['body_font'],
			'typography_font_size'   => ning_mcp_px( 17 ),
			'typography_line_height' => array( 'unit' => 'em', 'size' => 1.6, 'sizes' => array() ),
		);
		return array_merge( $base, $extra );
	}

	function ning_mcp_t_button( $text, $url, $t, $extra = array() ) {
		$base = array(
			'text'                   => $text,
			'link'                   => array( 'url' => $url, 'is_external' => '', 'nofollow' => '', 'custom_attributes' => '' ),
			'size'                   => 'lg',
			'button_text_color'      => '#FFFFFF',
			'background_color'       => $t['palette']['primary'],
			'border_radius'          => ning_mcp_box( $t['radius']['button'], $t['radius']['button'], $t['radius']['button'], $t['radius']['button'] ),
			'typography_typography'  => 'custom',
			'typography_font_family' => $t['typography']['body_font'],
			'typography_font_size'   => ning_mcp_px( 16 ),
			'button_hover_color'     => $t['palette']['accent'],
		);
		return array_merge( $base, $extra );
	}

	function ning_mcp_t_image( $url ) {
		return array(
			'image' => array( 'url' => $url, 'id' => '', 'size' => 'full' ),
			'size'  => 'full',
		);
	}

	function ning_mcp_t_container( $settings, $children ) {
		return array( 'type' => 'container', 'settings' => $settings, 'children' => $children );
	}

	function ning_mcp_t_widget( $widget, $settings ) {
		return array( 'type' => 'widget', 'widget' => $widget, 'settings' => $settings );
	}

	function ning_mcp_section_base( $t, $pad = null, $extra = array() ) {
		$s = array_merge(
			array(
				'content_width' => 'boxed',
				'flex_direction' => 'column',
				'padding'       => ning_mcp_box( $pad ? $pad : $t['spacing']['section'], 24, $pad ? $pad : $t['spacing']['section'], 24 ),
				'gap'           => ning_mcp_px( $t['spacing']['gap'] ),
			),
			$extra
		);
		return $s;
	}

	function ning_mcp_row( $children ) {
		return ning_mcp_t_container(
			array( 'content_width' => 'full', 'flex_direction' => 'row', 'gap' => ning_mcp_px( 24 ) ),
			$children
		);
	}

	function ning_mcp_col( $width_pct, $children ) {
		return ning_mcp_t_container(
			array(
				'content_width'   => 'full',
				'flex_direction'  => 'column',
				'justify_content' => 'center',
				'width'           => array( 'unit' => '%', 'size' => (int) $width_pct, 'sizes' => array() ),
				'width_tablet'    => array( 'unit' => '%', 'size' => 100, 'sizes' => array() ),
			),
			$children
		);
	}

	function ning_mcp_pattern_definitions() {
		$defs = array();

		$defs['hero-classic'] = array(
			'title'       => 'Hero Classic',
			'description' => 'Two-column hero: headline + text + CTA left, image right.',
			'params'      => array(
				'title'     => array( 'type' => 'string', 'default' => 'Handmade with Love' ),
				'subtitle'  => array( 'type' => 'string', 'default' => 'Thoughtfully crafted pieces for a warm home.' ),
				'cta_text'  => array( 'type' => 'string', 'default' => 'Shop New In' ),
				'cta_url'   => array( 'type' => 'string', 'default' => '#' ),
				'image_url' => array( 'type' => 'string', 'default' => '' ),
			),
			'build'       => function ( $p, $t ) {
				$img = $p['image_url'] ? $p['image_url'] : 'https://placehold.co/600x720/FDF6EE/A67C52?text=Hero+Image';
				return array(
					ning_mcp_t_container(
						ning_mcp_section_base( $t, null, array( 'flex_direction' => 'row', 'align_items' => 'center' ) ),
						array(
							ning_mcp_col( 50, array(
								ning_mcp_t_widget( 'heading', ning_mcp_t_heading( $p['title'], 'h1', $t ) ),
								ning_mcp_t_widget( 'text-editor', ning_mcp_t_text( '<p>' . esc_html( $p['subtitle'] ) . '</p>', $t ) ),
								ning_mcp_t_widget( 'button', ning_mcp_t_button( $p['cta_text'], $p['cta_url'], $t ) ),
							) ),
							ning_mcp_col( 50, array(
								ning_mcp_t_widget( 'image', ning_mcp_t_image( $img ) ),
							) ),
						)
					),
				);
			},
		);

		$defs['hero-split'] = array(
			'title'       => 'Hero Split',
			'description' => 'Two-column hero: image left, copy right.',
			'params'      => array(
				'title'     => array( 'type' => 'string', 'default' => 'Crafted Stories' ),
				'subtitle'  => array( 'type' => 'string', 'default' => 'Every piece begins with intention and ends with a story.' ),
				'cta_text'  => array( 'type' => 'string', 'default' => 'Explore' ),
				'cta_url'   => array( 'type' => 'string', 'default' => '#' ),
				'image_url' => array( 'type' => 'string', 'default' => '' ),
			),
			'build'       => function ( $p, $t ) {
				$img = $p['image_url'] ? $p['image_url'] : 'https://placehold.co/600x720/FDF6EE/A67C52?text=Hero+Image';
				return array(
					ning_mcp_t_container(
						ning_mcp_section_base( $t, null, array( 'flex_direction' => 'row', 'align_items' => 'center' ) ),
						array(
							ning_mcp_col( 50, array(
								ning_mcp_t_widget( 'image', ning_mcp_t_image( $img ) ),
							) ),
							ning_mcp_col( 50, array(
								ning_mcp_t_widget( 'heading', ning_mcp_t_heading( $p['title'], 'h1', $t ) ),
								ning_mcp_t_widget( 'text-editor', ning_mcp_t_text( '<p>' . esc_html( $p['subtitle'] ) . '</p>', $t ) ),
								ning_mcp_t_widget( 'button', ning_mcp_t_button( $p['cta_text'], $p['cta_url'], $t ) ),
							) ),
						)
					),
				);
			},
		);

		$defs['hero-minimal'] = array(
			'title'       => 'Hero Minimal',
			'description' => 'Centered headline + short text + one CTA.',
			'params'      => array(
				'title'    => array( 'type' => 'string', 'default' => 'Simple & Warm' ),
				'subtitle' => array( 'type' => 'string', 'default' => 'A quiet statement for every space.' ),
				'cta_text' => array( 'type' => 'string', 'default' => 'Discover' ),
				'cta_url'  => array( 'type' => 'string', 'default' => '#' ),
			),
			'build'       => function ( $p, $t ) {
				return array(
					ning_mcp_t_container(
						ning_mcp_section_base( $t, 96, array( 'align_items' => 'center' ) ),
						array(
							ning_mcp_t_widget( 'heading', ning_mcp_t_heading( $p['title'], 'h1', $t, array( 'align' => 'center' ) ) ),
							ning_mcp_t_widget( 'text-editor', ning_mcp_t_text( '<p style="text-align:center">' . esc_html( $p['subtitle'] ) . '</p>', $t ) ),
							ning_mcp_t_widget( 'button', ning_mcp_t_button( $p['cta_text'], $p['cta_url'], $t, array( 'align' => 'center' ) ) ),
						)
					),
				);
			},
		);

		$defs['features-grid'] = array(
			'title'       => 'Features Grid',
			'description' => 'Section heading + 4-column icon-box features.',
			'params'      => array(
				'title'    => array( 'type' => 'string', 'default' => 'Why Choose Us' ),
				'feature1' => array( 'type' => 'string', 'default' => 'Quality Craft' ),
				'feature2' => array( 'type' => 'string', 'default' => 'Natural Materials' ),
				'feature3' => array( 'type' => 'string', 'default' => 'Fair Trade' ),
				'feature4' => array( 'type' => 'string', 'default' => 'Made to Last' ),
			),
			'build'       => function ( $p, $t ) {
				$cells = array();
				foreach ( array( $p['feature1'], $p['feature2'], $p['feature3'], $p['feature4'] ) as $f ) {
					$cells[] = ning_mcp_col( 25, array(
						ning_mcp_t_widget( 'icon-box', array(
							'selected_icon'    => array( 'value' => 'fas fa-heart', 'library' => 'fa-solid' ),
							'view'             => 'stacked',
							'shape'            => 'circle',
							'title_text'       => $f,
							'description_text' => 'Thoughtfully designed with care and intention.',
							'primary_color'    => $t['palette']['primary'],
							'icon_size'        => ning_mcp_px( 26 ),
							'title_typography_typography'  => 'custom',
							'title_typography_font_family' => $t['typography']['heading_font'],
							'content_typography_typography'=> 'custom',
							'content_typography_font_family'=> $t['typography']['body_font'],
						) ),
					) );
				}
				return array(
					ning_mcp_t_container(
						ning_mcp_section_base( $t, null, array( 'align_items' => 'center' ) ),
						array(
							ning_mcp_t_widget( 'heading', ning_mcp_t_heading( $p['title'], 'h2', $t, array( 'align' => 'center' ) ) ),
							ning_mcp_row( $cells ),
						)
					),
				);
			},
		);

		$defs['banner-cta'] = array(
			'title'       => 'Banner CTA',
			'description' => 'Full-width CTA band on the primary color.',
			'params'      => array(
				'title'    => array( 'type' => 'string', 'default' => 'Join Our Community' ),
				'subtitle' => array( 'type' => 'string', 'default' => 'Be the first to know about new arrivals.' ),
				'cta_text' => array( 'type' => 'string', 'default' => 'Subscribe' ),
				'cta_url'  => array( 'type' => 'string', 'default' => '#' ),
			),
			'build'       => function ( $p, $t ) {
				return array(
					ning_mcp_t_container(
						ning_mcp_section_base( $t, 64, array(
							'background_background' => 'classic',
							'background_color'      => $t['palette']['primary'],
							'border_radius'         => ning_mcp_box( 24, 24, 24, 24 ),
							'align_items'           => 'center',
						) ),
						array(
							ning_mcp_t_widget( 'heading', ning_mcp_t_heading( $p['title'], 'h2', $t, array( 'align' => 'center', 'title_color' => '#FFFFFF' ) ) ),
							ning_mcp_t_widget( 'text-editor', ning_mcp_t_text( '<p style="text-align:center">' . esc_html( $p['subtitle'] ) . '</p>', $t, array( 'text_color' => 'rgba(255,255,255,.92)' ) ) ),
							ning_mcp_t_widget( 'button', ning_mcp_t_button( $p['cta_text'], $p['cta_url'], $t, array( 'align' => 'center', 'background_color' => '#FFFFFF', 'button_text_color' => $t['palette']['primary'] ) ) ),
						)
					),
				);
			},
		);

		$defs['testimonials'] = array(
			'title'       => 'Testimonials',
			'description' => 'Section heading + 3 testimonial cards with star ratings.',
			'params'      => array(
				'title' => array( 'type' => 'string', 'default' => 'Kind Words' ),
			),
			'build'       => function ( $p, $t ) {
				$quotes = array(
					array( 'Anna K.', 'Happy Customer', 'Absolutely love my handmade piece — you can feel the care in every stitch.' ),
					array( 'Tom B.', 'Collector', 'Beautiful quality and quick delivery. Will definitely order again.' ),
					array( 'Mia L.', 'Gift Buyer', 'The perfect gift. So unique and well made.' ),
				);
				$cells = array();
				foreach ( $quotes as $q ) {
					$cells[] = ning_mcp_col( 33, array(
						ning_mcp_t_widget( 'star-rating', array(
							'rating_scale' => 5,
							'rating'       => 5,
							'stars_color'  => $t['palette']['accent'],
						) ),
						ning_mcp_t_widget( 'testimonial', array(
							'testimonial_content'           => $q[2],
							'testimonial_name'              => $q[0],
							'testimonial_job'               => $q[1],
							'content_content_color'         => $t['palette']['text'],
							'content_typography_typography' => 'custom',
							'content_typography_font_family'=> $t['typography']['body_font'],
						) ),
					) );
				}
				return array(
					ning_mcp_t_container(
						ning_mcp_section_base( $t, null, array( 'align_items' => 'center' ) ),
						array(
							ning_mcp_t_widget( 'heading', ning_mcp_t_heading( $p['title'], 'h2', $t, array( 'align' => 'center' ) ) ),
							ning_mcp_row( $cells ),
						)
					),
				);
			},
		);

		$defs['stats-band'] = array(
			'title'       => 'Stats Band',
			'description' => 'Four animated counters.',
			'params'      => array(
				'stat1' => array( 'type' => 'integer', 'default' => 1200 ),
				'stat2' => array( 'type' => 'integer', 'default' => 50 ),
				'stat3' => array( 'type' => 'integer', 'default' => 30 ),
				'stat4' => array( 'type' => 'integer', 'default' => 15 ),
			),
			'build'       => function ( $p, $t ) {
				$stats = array(
					array( $p['stat1'], '+', 'Happy Customers' ),
					array( $p['stat2'], '+', 'Artisans' ),
					array( $p['stat3'], '+', 'Countries' ),
					array( $p['stat4'], '+', 'Years' ),
				);
				$cells = array();
				foreach ( $stats as $s ) {
					$cells[] = ning_mcp_col( 25, array(
						ning_mcp_t_widget( 'counter', array(
							'starting_number' => 0,
							'ending_number'   => (int) $s[0],
							'suffix'          => $s[1],
							'duration'        => 2000,
							'number_color'    => $t['palette']['primary'],
							'title'           => $s[2],
							'title_color'     => $t['palette']['muted'],
						) ),
					) );
				}
				return array(
					ning_mcp_t_container( ning_mcp_section_base( $t, 56 ), array( ning_mcp_row( $cells ) ) ),
				);
			},
		);

		$defs['newsletter'] = array(
			'title'       => 'Newsletter',
			'description' => 'Centered signup band with inline email input + button.',
			'params'      => array(
				'title'    => array( 'type' => 'string', 'default' => 'Stay in the Loop' ),
				'subtitle' => array( 'type' => 'string', 'default' => 'Sign up for our newsletter.' ),
			),
			'build'       => function ( $p, $t ) {
				$form = '<form class="wpmp-newsletter" action="#" method="post" style="display:flex;gap:10px;max-width:460px;margin:20px auto 0;flex-wrap:wrap;justify-content:center">'
					. '<input type="email" required placeholder="Your email" style="flex:1;min-width:220px;padding:14px 18px;border:1px solid ' . esc_attr( $t['palette']['border'] ) . ';border-radius:' . esc_attr( $t['radius']['card'] ) . 'px;font-family:' . esc_attr( $t['typography']['body_font'] ) . ';font-size:15px">'
					. '<button type="submit" style="padding:14px 26px;border:0;border-radius:' . esc_attr( $t['radius']['button'] ) . 'px;background:' . esc_attr( $t['palette']['primary'] ) . ';color:#fff;font-family:' . esc_attr( $t['typography']['body_font'] ) . ';font-size:15px;font-weight:600;cursor:pointer">Subscribe</button>'
					. '</form>';
				return array(
					ning_mcp_t_container(
						ning_mcp_section_base( $t, null, array( 'align_items' => 'center' ) ),
						array(
							ning_mcp_t_widget( 'heading', ning_mcp_t_heading( $p['title'], 'h2', $t, array( 'align' => 'center' ) ) ),
							ning_mcp_t_widget( 'text-editor', ning_mcp_t_text( '<p style="text-align:center">' . esc_html( $p['subtitle'] ) . '</p>', $t ) ),
							ning_mcp_t_widget( 'html', array( 'html' => $form ) ),
						)
					),
				);
			},
		);

		$defs['marquee'] = array(
			'title'       => 'Marquee',
			'description' => 'Scrolling text strip (pure CSS).',
			'params'      => array(
				'text' => array( 'type' => 'string', 'default' => 'Handmade with Love • Natural Materials • Fair Trade' ),
			),
			'build'       => function ( $p, $t ) {
				$line = str_repeat( esc_html( $p['text'] ) . ' &nbsp;•&nbsp; ', 3 );
				$html = '<div class="wpmp-marquee" style="overflow:hidden;white-space:nowrap;background:' . esc_attr( $t['palette']['primary'] ) . ';color:#fff;padding:14px 0">'
					. '<div style="display:inline-block;font-family:' . esc_attr( $t['typography']['heading_font'] ) . ';font-size:18px;letter-spacing:.05em;animation:wpmp-marquee 18s linear infinite">' . $line . '</div></div>'
					. '<style>@keyframes wpmp-marquee{from{transform:translateX(0)}to{transform:translateX(-33.333%)}}@media(prefers-reduced-motion:reduce){.wpmp-marquee div{animation:none!important}}</style>';
				return array(
					ning_mcp_t_container( array( 'content_width' => 'full' ), array(
						ning_mcp_t_widget( 'html', array( 'html' => $html ) ),
					) ),
				);
			},
		);

		$defs['filament-divider'] = array(
			'title'       => 'Filament Divider',
			'description' => 'Ornamental divider with star SVG center.',
			'params'      => array(),
			'build'       => function ( $p, $t ) {
				$html = '<div style="display:flex;align-items:center;gap:14px;max-width:420px;margin:0 auto">'
					. '<span style="flex:1;height:1px;background:linear-gradient(90deg,transparent,' . esc_attr( $t['palette']['border'] ) . ')"></span>'
					. '<svg width="16" height="16" viewBox="0 0 24 24" fill="' . esc_attr( $t['palette']['accent'] ) . '"><path d="M12 0l2.6 9.4L24 12l-9.4 2.6L12 24l-2.6-9.4L0 12l9.4-2.6z"/></svg>'
					. '<span style="flex:1;height:1px;background:linear-gradient(270deg,transparent,' . esc_attr( $t['palette']['border'] ) . ')"></span>'
					. '</div>';
				return array(
					ning_mcp_t_container( array( 'content_width' => 'full', 'padding' => ning_mcp_box( 24, 0, 24, 0 ) ), array(
						ning_mcp_t_widget( 'html', array( 'html' => $html ) ),
					) ),
				);
			},
		);

		$defs['gallery-strip'] = array(
			'title'       => 'Gallery Strip',
			'description' => 'Responsive image grid (works with external URLs, token-styled hover).',
			'params'      => array(
				'images' => array( 'type' => 'string', 'default' => 'https://placehold.co/400x400/FDF6EE/A67C52?text=1|https://placehold.co/400x400/FDF6EE/A67C52?text=2|https://placehold.co/400x400/FDF6EE/A67C52?text=3|https://placehold.co/400x400/FDF6EE/A67C52?text=4' ),
			),
			'build'       => function ( $p, $t ) {
				$urls = array_values( array_filter( array_map( 'trim', explode( '|', $p['images'] ) ) ) );
				if ( empty( $urls ) ) {
					$urls = array( 'https://placehold.co/400x400/FDF6EE/A67C52?text=Gallery' );
				}
				$imgs = '';
				foreach ( $urls as $u ) {
					$imgs .= '<img loading="lazy" src="' . esc_url( $u ) . '" alt="">';
				}
				$html = '<div class="wpmp-gallery" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:' . esc_attr( $t['spacing']['gap'] ) . 'px">' . $imgs . '</div>'
					. '<style>.wpmp-gallery img{width:100%;aspect-ratio:1;object-fit:cover;display:block;border-radius:' . esc_attr( $t['radius']['card'] ) . 'px;transition:transform .3s ease}.wpmp-gallery img:hover{transform:scale(1.02)}</style>';
				return array(
					ning_mcp_t_container( ning_mcp_section_base( $t ), array(
						ning_mcp_t_widget( 'html', array( 'html' => $html ) ),
					) ),
				);
			},
		);

		$defs['product-cards'] = array(
			'title'       => 'Product Cards',
			'description' => 'Dynamic product grid — JS fetches WooCommerce Store API at page load (realtime data).',
			'params'      => array(
				'count' => array( 'type' => 'integer', 'default' => 4 ),
			),
			'build'       => function ( $p, $t ) {
				$count = max( 1, min( 8, (int) $p['count'] ) );
				$tpl   = <<<'WPMPTPL'
<div class="wpmp-products" data-count="{COUNT}" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px;font-family:{BODYFONT}">
<div style="grid-column:1/-1;text-align:center;color:{MUTED};padding:40px 0">Loading products…</div>
</div>
<style>
.wpmp-products .wpmp-card{background:#fff;border:1px solid {BORDER};border-radius:{RADIUS}px;overflow:hidden;display:flex;flex-direction:column;text-decoration:none;color:inherit;transition:transform .3s ease,box-shadow .3s ease}
.wpmp-products .wpmp-card:hover{transform:translateY(-4px);box-shadow:{SHADOW}}
.wpmp-products .wpmp-card img{width:100%;aspect-ratio:1;object-fit:cover;display:block}
.wpmp-products .wpmp-body{padding:14px}
.wpmp-products h3{margin:0 0 6px;font-family:{HEADFONT};font-size:17px;color:{TEXT}}
.wpmp-price{color:{PRIMARY};font-weight:600}
.wpmp-placeholder{background:{BG};display:flex;align-items:center;justify-content:center;aspect-ratio:1;color:{MUTED};font-size:13px}
.wpmp-empty{grid-column:1/-1;text-align:center;color:{MUTED};padding:30px 0}
</style>
<script>
(function(){
var root=document.querySelector('.wpmp-products');if(!root)return;
var n=parseInt(root.getAttribute('data-count')||'4',10);
var url='{HOME}/wp-json/wc/store/v1/products?per_page='+n;
fetch(url).then(function(r){return r.ok?r.json():[]}).then(function(items){
if(!items||!items.length){root.innerHTML='<div class="wpmp-empty">No products yet.</div>';return}
root.innerHTML='';
items.forEach(function(p){
var img=p.images&&p.images[0]?p.images[0].src:'';
var price=p.prices&&p.prices.price?(parseInt(p.prices.price,10)/Math.pow(10,p.prices.currency_decimals||2)).toFixed(2):null;
var cur=p.prices&&p.prices.currency_symbol?p.prices.currency_symbol:'$';
var card=document.createElement('a');card.className='wpmp-card';card.href=p.permalink||'#';
var im=document.createElement('img');im.loading='lazy';im.alt=p.name||'';im.src=img||'{PH}';
card.appendChild(im);
var body=document.createElement('div');body.className='wpmp-body';
var h=document.createElement('h3');h.textContent=p.name||'';
var pr=document.createElement('span');pr.className='wpmp-price';pr.textContent=null===price?'':cur+price;
body.appendChild(h);body.appendChild(pr);card.appendChild(body);
root.appendChild(card);
});
}).catch(function(){root.innerHTML='<div class="wpmp-empty">Could not load products.</div>'});
})();
</script>
WPMPTPL;
				$html = strtr( $tpl, array(
					'{COUNT}'    => (string) $count,
					'{HOME}'     => esc_url( untrailingslashit( home_url() ) ),
					'{PH}'       => 'https://placehold.co/600x600/FDF6EE/A67C52?text=Product',
					'{PRIMARY}'  => esc_attr( $t['palette']['primary'] ),
					'{ACCENT}'   => esc_attr( $t['palette']['accent'] ),
					'{BG}'       => esc_attr( $t['palette']['bg'] ),
					'{TEXT}'     => esc_attr( $t['palette']['text'] ),
					'{MUTED}'    => esc_attr( $t['palette']['muted'] ),
					'{BORDER}'   => esc_attr( $t['palette']['border'] ),
					'{RADIUS}'   => esc_attr( $t['radius']['card'] ),
					'{SHADOW}'   => esc_attr( $t['shadow']['card'] ),
					'{BODYFONT}' => esc_attr( $t['typography']['body_font'] ),
					'{HEADFONT}' => esc_attr( $t['typography']['heading_font'] ),
				) );
				return array(
					ning_mcp_t_container( ning_mcp_section_base( $t ), array(
						ning_mcp_t_widget( 'html', array( 'html' => $html ) ),
					) ),
				);
			},
		);

		return $defs;
	}

	function ning_mcp_build_patterns( $patterns, $palette_overrides, $param_overrides ) {
		$defs   = ning_mcp_pattern_definitions();
		$tokens = ning_mcp_design_tokens();
		if ( is_array( $palette_overrides ) ) {
			foreach ( $palette_overrides as $k => $v ) {
				if ( array_key_exists( $k, $tokens['palette'] ) ) {
					$tokens['palette'][ $k ] = sanitize_hex_color( $v );
				}
			}
		}
		if ( 'all' === $patterns ) {
			$patterns = array_keys( $defs );
		}
		$created = array();
		foreach ( (array) $patterns as $id ) {
			if ( ! isset( $defs[ $id ] ) ) {
				$created[] = array( 'id' => $id, 'error' => 'Unknown pattern id.' );
				continue;
			}
			$def    = $defs[ $id ];
			$params = array();
			foreach ( $def['params'] as $pk => $pv ) {
				$params[ $pk ] = isset( $pv['default'] ) ? $pv['default'] : '';
			}
			if ( isset( $param_overrides[ $id ] ) && is_array( $param_overrides[ $id ] ) ) {
				$params = array_merge( $params, $param_overrides[ $id ] );
			}
			$data = ning_mcp_tree_to_elementor_data( $def['build']( $params, $tokens ) );

			$existing = get_posts( array(
				'post_type'      => 'elementor_library',
				'post_status'    => 'any',
				'title'          => 'Pattern: ' . $def['title'],
				'posts_per_page' => 1,
			) );
			if ( ! empty( $existing ) ) {
				$post_id = (int) $existing[0]->ID;
				wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
			} else {
				$post_id = (int) wp_insert_post( array(
					'post_type'   => 'elementor_library',
					'post_status' => 'publish',
					'post_title'  => 'Pattern: ' . $def['title'],
				) );
			}
			if ( ! $post_id ) {
				$created[] = array( 'id' => $id, 'error' => 'Could not create template post.' );
				continue;
			}
			update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
			update_post_meta( $post_id, '_elementor_template_type', 'section' );
			update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $post_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );
			$created[] = array(
				'id'          => $id,
				'title'       => $def['title'],
				'template_id' => $post_id,
				'shortcode'   => '[elementor-template id="' . $post_id . '"]',
				'edit_url'    => admin_url( 'post.php?post=' . $post_id . '&action=elementor' ),
				'preview_url' => home_url( '/?elementor_library=pattern-' . sanitize_title( $def['title'] ) . '&p=' . $post_id . '&preview=true' ),
			);
		}
		return $created;
	}
	}

	wp_register_ability(
		'elementor-design/build-pattern-library',
		array(
			'label'       => 'Build Pattern Library',
			'description' => 'Generates Elementor section templates from a component-tree pattern library (12 sections) styled by design tokens. action=build creates/updates library templates and returns shortcode + edit URLs; action=info lists patterns/tokens/widget catalog without writing; action=set-tokens persists palette overrides into wp_mcp_design_tokens.',
			'category'    => 'elementor-design',
			'input_schema' => array(
				'type'                 => 'object',
				'properties'           => array(
					'action'   => array( 'type' => 'string', 'enum' => array( 'build', 'info', 'set-tokens' ), 'default' => 'info' ),
					'patterns' => array( 'description' => 'Pattern ids or "all" (action=build).' ),
					'palette'  => array( 'type' => 'object', 'description' => 'Optional token overrides {primary,accent,bg,text,muted,border} applied to build/set-tokens.' ),
					'params'   => array( 'type' => 'object', 'description' => 'Per-pattern param overrides keyed by pattern id (action=build).' ),
				),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'action'  => array( 'type' => 'string' ),
					'created' => array( 'type' => 'array' ),
					'tokens'  => array( 'type' => 'object' ),
					'catalog' => array( 'type' => 'object' ),
				),
			),
			'execute_callback'    => function ( $input ) {
				$action = isset( $input['action'] ) ? $input['action'] : 'info';
				if ( 'set-tokens' === $action ) {
					$current = get_option( 'wp_mcp_design_tokens', array() );
					if ( ! is_array( $current ) ) {
						$current = array();
					}
					$palette = isset( $input['palette'] ) && is_array( $input['palette'] ) ? $input['palette'] : array();
					if ( empty( $palette ) ) {
						return array( 'action' => $action, 'created' => array(), 'tokens' => ning_mcp_design_tokens(), 'catalog' => new stdClass() );
					}
					$merged = array();
					$base   = ning_mcp_design_tokens();
					foreach ( $palette as $k => $v ) {
						if ( array_key_exists( $k, $base['palette'] ) && sanitize_hex_color( $v ) ) {
							$merged[ $k ] = sanitize_hex_color( $v );
						}
					}
					$current['palette'] = array_merge( isset( $current['palette'] ) && is_array( $current['palette'] ) ? $current['palette'] : array(), $merged );
					update_option( 'wp_mcp_design_tokens', $current );
					return array( 'action' => $action, 'created' => array(), 'tokens' => ning_mcp_design_tokens(), 'catalog' => new stdClass() );
				}
				if ( 'build' === $action ) {
					$patterns = isset( $input['patterns'] ) ? $input['patterns'] : 'all';
					$palette  = isset( $input['palette'] ) && is_array( $input['palette'] ) ? $input['palette'] : array();
					$params   = isset( $input['params'] ) && is_array( $input['params'] ) ? $input['params'] : array();
					$created  = ning_mcp_build_patterns( $patterns, $palette, $params );
					return array( 'action' => $action, 'created' => $created, 'tokens' => ning_mcp_design_tokens(), 'catalog' => new stdClass() );
				}
				$defs = ning_mcp_pattern_definitions();
				$list = array();
				foreach ( $defs as $id => $d ) {
					$list[ $id ] = array(
						'title'       => $d['title'],
						'description' => $d['description'],
						'params'      => array_map( function ( $pv ) {
							return isset( $pv['default'] ) ? $pv['default'] : '';
						}, $d['params'] ),
					);
				}
				return array( 'action' => 'info', 'created' => array(), 'tokens' => ning_mcp_design_tokens(), 'catalog' => ning_mcp_widget_catalog() + $list );
			},
			'permission_callback' => 'ning_mcp_can_manage',
			'meta'                => ning_mcp_mcp_meta( array( 'readonly' => false, 'destructive' => false, 'idempotent' => true ) ),
		)
	);
} );

// v1.7.3 — Custom Elements category (Rivax-style, bottom of panel).
add_action( 'elementor/elements/categories_registered', function ( $elements_manager ) {
	$elements_manager->add_category(
		'custom-elements',
		array(
			'title' => esc_html__( 'Custom Elements', 'wp-mcp-abilities' ),
			'icon'  => 'eicon-apps',
		)
	);
} );

// v1.7.2 — Pattern picker widget (hidden, kept for BC; use Custom Elements now).
add_action( 'elementor/widgets/register', function ( $widgets_manager ) {
	if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
		return;
	}
	require_once __DIR__ . '/includes/widgets/class-ning-pattern-widget.php';
	$widgets_manager->register( new \Ning_Pattern_Widget() );
	require_once __DIR__ . '/includes/widgets/class-ning-custom-elements.php';
	$widgets_manager->register( new \Ning_Banner_Widget() );
	$widgets_manager->register( new \Ning_Features_Widget() );
	$widgets_manager->register( new \Ning_Cta_Banner_Widget() );
	$widgets_manager->register( new \Ning_Testimonials_Widget() );
	$widgets_manager->register( new \Ning_Stats_Widget() );
	$widgets_manager->register( new \Ning_Newsletter_Widget() );
	$widgets_manager->register( new \Ning_Marquee_Widget() );
	$widgets_manager->register( new \Ning_Divider_Widget() );
	$widgets_manager->register( new \Ning_Gallery_Widget() );
	$widgets_manager->register( new \Ning_Product_Cards_Widget() );
} );

add_action( 'wp_ajax_ning_pattern_preview', function () {
	check_ajax_referer( 'ning_pattern_preview' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( '', '', array( 'response' => 403 ) );
	}
	$logical = isset( $_GET['pattern'] ) ? sanitize_key( wp_unslash( $_GET['pattern'] ) ) : '';
	if ( ! function_exists( 'ning_mcp_pattern_definitions' ) ) {
		wp_die( 'not ready', '', array( 'response' => 500 ) );
	}
	$defs = ning_mcp_pattern_definitions();
	if ( ! isset( $defs[ $logical ] ) ) {
		wp_die( 'unknown pattern', '', array( 'response' => 404 ) );
	}
	$title = 'Pattern: ' . $defs[ $logical ]['title'];
	$posts = get_posts( array(
		'post_type'      => 'elementor_library',
		'title'          => $title,
		'posts_per_page' => 1,
		'post_status'    => 'any',
	) );
	if ( empty( $posts ) ) {
		wp_die( 'not built', '', array( 'response' => 404 ) );
	}
	$real_id = (int) $posts[0]->ID;
	if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->frontend ) ) {
		$frontend = \Elementor\Plugin::$instance->frontend;
		if ( method_exists( $frontend, 'get_builder_content_for_display' ) ) {
			echo $frontend->get_builder_content_for_display( $real_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wp_die();
		}
		if ( method_exists( $frontend, 'get_builder_content' ) ) {
			echo $frontend->get_builder_content( $real_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wp_die();
		}
	}
	wp_die( '', '', array( 'response' => 500 ) );
} );

add_action( 'elementor/editor/after_enqueue_scripts', function () {
	$nonce = wp_create_nonce( 'ning_pattern_preview' );
	echo '<script>window.ningPatternNonce=' . wp_json_encode( $nonce ) . ';window.ajaxurl=' . wp_json_encode( admin_url( 'admin-ajax.php' ) ) . ';</script>' . "\n";
}, 20 );

// v1.7.1 — [elementor-template] shortcode (Elementor 4.2.3 does not ship it; needed for Shortcode-widget and manual placement of pattern templates).
add_action( 'init', function () {
	if ( shortcode_exists( 'elementor-template' ) ) {
		return;
	}
	add_shortcode( 'elementor-template', function ( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'elementor-template' );
		$id   = (int) $atts['id'];
		if ( ! $id || 'elementor_library' !== get_post_type( $id ) ) {
			return '';
		}
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->frontend ) ) {
			$frontend = \Elementor\Plugin::$instance->frontend;
			if ( method_exists( $frontend, 'get_builder_content_for_display' ) ) {
				return $frontend->get_builder_content_for_display( $id );
			}
			if ( method_exists( $frontend, 'get_builder_content' ) ) {
				return $frontend->get_builder_content( $id );
			}
		}
		return '';
	} );
} );
