<?php
/**
 * Plugin Name: WP MCP Abilities
 * Description: Registers content-management abilities (posts, comments, media and WooCommerce variable products) exposed through the MCP Adapter default server.
 * Version:     1.3.2
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
	$slug     = strtolower( preg_replace( '/^pa_/', '', sanitize_title( $name ) ) );
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
	foreach ( (array) $options as $option ) {
		$option = trim( (string) $option );
		if ( '' === $option ) {
			continue;
		}
		$term = wp_insert_term( $option, $taxonomy );
		if ( is_wp_error( $term ) ) {
			if ( 'term_exists' === $term->get_error_code() ) {
				$term_data = $term->get_error_data();
				$term_id   = isset( $term_data['term_id'] ) ? (int) $term_data['term_id'] : 0;
			} else {
				return $term;
			}
		} else {
			$term_id = (int) $term['term_id'];
		}
		$term_obj = $term_id ? get_term( $term_id, $taxonomy ) : null;
		if ( $term_obj && ! is_wp_error( $term_obj ) ) {
			$slug_map[ $option ] = $term_obj->slug;
		}
	}

	return array(
		'slug'       => $slug,
		'taxonomy'   => $taxonomy,
		'attribute'  => $name,
		'attribute_id' => $attribute_id,
		'slug_map'   => $slug_map,
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
							array( 'timeout' => 40 )
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
					$wc_attr->set_options( array_values( $attr['slug_map'] ) );
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
							return new WP_Error( 'ning_mcp_unknown_option', sprintf( 'Variant %d references unknown option "%s" for attribute "%s".', $i, (string) $option, $attr_name ) );
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
} );
