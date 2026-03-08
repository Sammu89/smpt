<?php
/**
 * Core theme customizations.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * Add AVIF support in media uploads.
 */
function smpt_filter_allowed_mimes_avif( $mime_types ) {
	$mime_types['avif'] = 'image/avif';
	return $mime_types;
}
add_filter( 'upload_mimes', 'smpt_filter_allowed_mimes_avif', 1000 );
