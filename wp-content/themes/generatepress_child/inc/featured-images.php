<?php
/**
 * Featured image customizations for the child theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Match GeneratePress page header images to the established SMPT banner ratio.
 */
function smpt_register_page_header_image_size() {
	add_image_size( 'smpt-page-header-banner', 1200, 240, true );
}
add_action( 'after_setup_theme', 'smpt_register_page_header_image_size', 20 );

/**
 * Determine if the current page should use the custom title band below header media.
 *
 * This keeps ordinary pages on the native GeneratePress title flow and only
 * switches to the custom band when a featured image/video actually exists.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function smpt_page_uses_custom_title_band( $post_id = 0 ) {
	if ( ! $post_id ) {
		$post_id = get_queried_object_id();
	}

	if ( ! $post_id || ! is_page( $post_id ) ) {
		return false;
	}

	if ( has_post_thumbnail( $post_id ) ) {
		return true;
	}

	return function_exists( 'smpt_page_has_featured_video' ) && smpt_page_has_featured_video( $post_id );
}

/**
 * Inject the page title below the featured image block.
 *
 * Runs after generate_featured_page_header (priority 10) on the same hook.
 * Reuses the existing .single-post-title class for styling.
 */
function smpt_page_title_below_featured_image() {
	if ( ! is_page() || ! smpt_page_uses_custom_title_band() ) {
		return;
	}
	printf(
		'<div class="grid-container grid-parent smpt-page-title-band"><h1 class="single-post-title">%s</h1></div>',
		esc_html( get_the_title() )
	);
}
add_action( 'generate_after_header', 'smpt_page_title_below_featured_image', 15 );

/**
 * Hide the native entry title on pages that already show it below the featured image.
 *
 * @param bool $show Whether to show the title.
 * @return bool
 */
function smpt_hide_native_page_title( $show ) {
	if ( is_page() && smpt_page_uses_custom_title_band() ) {
		return false;
	}
	return $show;
}
add_filter( 'generate_show_title', 'smpt_hide_native_page_title' );

/**
 * Use the cropped banner size for GeneratePress page featured images.
 *
 * GeneratePress already renders the image at the content container width via its
 * native `.grid-container` wrapper, so only the image size needs to be swapped.
 *
 * @param string $size Requested image size.
 * @return string
 */
function smpt_filter_generate_page_header_image_size( $size ) {
	if ( is_admin() || ! is_page() ) {
		return $size;
	}

	return 'smpt-page-header-banner';
}
add_filter( 'generate_page_header_default_size', 'smpt_filter_generate_page_header_image_size' );
