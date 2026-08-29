<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ning_mcp_get_pexels_key() {
	$key = get_option( 'wp_mcp_pexels_api_key', '' );
	if ( ! $key && defined( 'PEXELS_API_KEY' ) ) {
		$key = PEXELS_API_KEY;
	}
	if ( ! $key && defined( 'PEXELS_API_TOKEN' ) ) {
		$key = PEXELS_API_TOKEN;
	}
	return is_string( $key ) ? trim( $key ) : '';
}

function ning_mcp_pexels_search( $query, $per_page = 4, $orientation = '' ) {
	$query = trim( (string) $query );
	if ( '' === $query ) {
		$query = 'handmade crochet';
	}
	$per_page = max( 1, min( 15, (int) $per_page ) );
	$cache_key = 'ning_pexels_' . md5( $query . '|' . $per_page . '|' . $orientation );
	$cached = get_transient( $cache_key );
	if ( is_array( $cached ) && ! empty( $cached ) ) {
		return $cached;
	}
	$key = ning_mcp_get_pexels_key();
	if ( '' === $key ) {
		return array();
	}
	$url = add_query_arg(
		array(
			'query'    => $query,
			'per_page' => $per_page,
		),
		'https://api.pexels.com/v1/search'
	);
	if ( $orientation && in_array( $orientation, array( 'landscape', 'portrait', 'square' ), true ) ) {
		$url = add_query_arg( array( 'orientation' => $orientation ), $url );
	}
	$response = wp_remote_get(
		$url,
		array(
			'headers' => array( 'Authorization' => $key ),
			'timeout' => 8,
		)
	);
	if ( is_wp_error( $response ) ) {
		return array();
	}
	$code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		return array();
	}
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	if ( ! is_array( $data ) || empty( $data['photos'] ) || ! is_array( $data['photos'] ) ) {
		return array();
	}
	$photos = array();
	foreach ( $data['photos'] as $p ) {
		$src = isset( $p['src']['large'] ) ? $p['src']['large'] : ( isset( $p['src']['medium'] ) ? $p['src']['medium'] : '' );
		if ( ! $src && isset( $p['src']['original'] ) ) {
			$src = $p['src']['original'];
		}
		if ( ! $src ) {
			continue;
		}
		$photos[] = array(
			'url'          => $src,
			'photographer' => isset( $p['photographer'] ) ? $p['photographer'] : '',
			'avg_color'    => isset( $p['avg_color'] ) ? $p['avg_color'] : '',
			'width'        => isset( $p['width'] ) ? (int) $p['width'] : 0,
			'height'       => isset( $p['height'] ) ? (int) $p['height'] : 0,
		);
	}
	if ( ! empty( $photos ) ) {
		set_transient( $cache_key, $photos, HOUR_IN_SECONDS );
	}
	return $photos;
}

function ning_mcp_pexels_sideload( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return array( 'url' => '', 'id' => 0 );
	}
	// If already an attachment URL that exists, try to find its ID
	$existing_id = attachment_url_to_postid( $url );
	if ( $existing_id ) {
		return array( 'url' => $url, 'id' => $existing_id );
	}
	if ( ! function_exists( 'media_sideload_image' ) ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}
	// media_sideload_image with $return = 'id' returns attachment ID on success
	$tmp = download_url( $url, 15 );
	if ( is_wp_error( $tmp ) ) {
		return array( 'url' => $url, 'id' => 0 );
	}
	$file_array = array(
		'name'     => basename( parse_url( $url, PHP_URL_PATH ) ),
		'tmp_name' => $tmp,
	);
	if ( '' === $file_array['name'] ) {
		$file_array['name'] = 'pexels-' . wp_generate_password( 8, false ) . '.jpg';
	}
	$id = media_handle_sideload( $file_array, 0 );
	if ( is_wp_error( $id ) ) {
		@unlink( $tmp );
		return array( 'url' => $url, 'id' => 0 );
	}
	$src = wp_get_attachment_url( $id );
	return array( 'url' => $src ? $src : $url, 'id' => (int) $id );
}

function ning_mcp_pexels_fallback_url( $query, $fallback = 'https://placehold.co/600x720/FDF6EE/A67C52?text=Hero+Image' ) {
	$photos = ning_mcp_pexels_search( $query, 1 );
	if ( ! empty( $photos ) && ! empty( $photos[0]['url'] ) ) {
		$sideloaded = ning_mcp_pexels_sideload( $photos[0]['url'] );
		if ( ! empty( $sideloaded['url'] ) ) {
			return $sideloaded['url'];
		}
		return $photos[0]['url'];
	}
	return $fallback;
}

function ning_mcp_pexels_fallback_ids( $query, $count = 4, $fallback_url = 'https://placehold.co/400x400/FDF6EE/A67C52?text=Gallery' ) {
	$photos = ning_mcp_pexels_search( $query, $count );
	$urls = array();
	foreach ( $photos as $p ) {
		if ( empty( $p['url'] ) ) {
			continue;
		}
		$sideloaded = ning_mcp_pexels_sideload( $p['url'] );
		$urls[] = ! empty( $sideloaded['url'] ) ? $sideloaded['url'] : $p['url'];
		if ( count( $urls ) >= $count ) {
			break;
		}
	}
	if ( empty( $urls ) ) {
		// Fallback to placehold
		for ( $i = 0; $i < $count; $i++ ) {
			$urls[] = $fallback_url;
		}
	}
	return $urls;
}
