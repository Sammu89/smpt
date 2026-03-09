<?php
/**
 * Content-protection behaviors built on top of access-control decisions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check whether the current request is for a protected page template.
 *
 * @return bool
 */
function smpt_is_protected_page_template_request() {
	$config = smpt_get_access_control_config();
	return is_page_template( $config['protected_template'] );
}

/**
 * Check whether the current request is for the protected tag archive.
 *
 * @return bool
 */
function smpt_is_protected_tag_archive_request() {
	$config = smpt_get_access_control_config();
	return is_tag( $config['protected_tag'] );
}

/**
 * Check whether the current singular post is protected by tag.
 *
 * @return bool
 */
function smpt_is_protected_tagged_single_request() {
	$config = smpt_get_access_control_config();
	return is_singular( 'post' ) && has_tag( $config['protected_tag'], get_queried_object_id() );
}

/**
 * Redirect blocked visitors away from protected content.
 *
 * @return void
 */
function smpt_handle_protected_content_redirects() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( smpt_current_visitor_is_allowed() ) {
		return;
	}

	if ( smpt_is_protected_page_template_request() || smpt_is_protected_tag_archive_request() || smpt_is_protected_tagged_single_request() ) {
		if ( function_exists( 'smpt_access_log' ) ) {
			smpt_access_log(
				sprintf(
					'Protected redirect: request=%s target=%s',
					isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/',
					home_url( '/' )
				)
			);
		}
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
}
add_action( 'template_redirect', 'smpt_handle_protected_content_redirects', 1 );

/**
 * Exclude protected posts from archives for blocked visitors.
 *
 * @param WP_Query $query Query instance.
 * @return void
 */
function smpt_exclude_protected_posts_from_archives( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( smpt_current_visitor_is_allowed() ) {
		return;
	}

	if ( ! ( $query->is_home() || $query->is_archive() || $query->is_search() ) ) {
		return;
	}

	$config    = smpt_get_access_control_config();
	$tax_query = (array) $query->get( 'tax_query' );

	$tax_query[] = array(
		'taxonomy' => 'post_tag',
		'field'    => 'slug',
		'terms'    => array( $config['protected_tag'] ),
		'operator' => 'NOT IN',
	);

	$query->set( 'tax_query', $tax_query );
}
add_action( 'pre_get_posts', 'smpt_exclude_protected_posts_from_archives' );
