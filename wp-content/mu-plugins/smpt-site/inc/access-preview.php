<?php
/**
 * Admin preview tools for access-control testing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'SMPT_ACCESS_PREVIEW_COOKIE' ) ) {
	define( 'SMPT_ACCESS_PREVIEW_COOKIE', 'smpt_preview_blocked' );
}

if ( ! defined( 'SMPT_ACCESS_PREVIEW_TTL' ) ) {
	define( 'SMPT_ACCESS_PREVIEW_TTL', 2 * HOUR_IN_SECONDS );
}

/**
 * Determine whether the current user can use the blocked preview toggle.
 *
 * @return bool
 */
function smpt_can_use_blocked_preview() {
	return is_user_logged_in() && current_user_can( 'manage_options' );
}

/**
 * Check whether the blocked preview is enabled for the current user.
 *
 * @return bool
 */
function smpt_is_blocked_preview_enabled() {
	if ( ! smpt_can_use_blocked_preview() ) {
		return false;
	}

	$cookie_name = SMPT_ACCESS_PREVIEW_COOKIE;
	return ! empty( $_COOKIE[ $cookie_name ] ) && '1' === (string) wp_unslash( $_COOKIE[ $cookie_name ] );
}

/**
 * Store the blocked preview cookie.
 *
 * @param bool $enabled Whether preview mode should be enabled.
 * @return void
 */
function smpt_set_blocked_preview_cookie( $enabled ) {
	$value   = $enabled ? '1' : '0';
	$expires = $enabled ? time() + SMPT_ACCESS_PREVIEW_TTL : time() - HOUR_IN_SECONDS;

	setcookie(
		SMPT_ACCESS_PREVIEW_COOKIE,
		$value,
		array(
			'expires'  => $expires,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => false,
			'samesite' => 'Lax',
		)
	);

	$_COOKIE[ SMPT_ACCESS_PREVIEW_COOKIE ] = $value;
}

/**
 * Handle blocked preview toggles from the front end.
 *
 * @return void
 */
function smpt_handle_blocked_preview_toggle() {
	if ( is_admin() || ! smpt_can_use_blocked_preview() ) {
		return;
	}

	if ( empty( $_GET['smpt_preview_blocked'] ) ) {
		return;
	}

	$action = sanitize_key( wp_unslash( $_GET['smpt_preview_blocked'] ) );
	if ( ! in_array( $action, array( 'on', 'off' ), true ) ) {
		return;
	}

	check_admin_referer( 'smpt_toggle_blocked_preview' );

	smpt_set_blocked_preview_cookie( 'on' === $action );

	$redirect_url = wp_get_referer();
	if ( ! $redirect_url ) {
		$redirect_url = remove_query_arg( array( 'smpt_preview_blocked', '_wpnonce' ) );
	}

	$redirect_url = remove_query_arg( array( 'smpt_preview_blocked', '_wpnonce' ), $redirect_url );

	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'template_redirect', 'smpt_handle_blocked_preview_toggle', 0 );

/**
 * Add the blocked preview toggle to the admin bar.
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
 * @return void
 */
function smpt_add_blocked_preview_admin_bar_toggle( $wp_admin_bar ) {
	if ( is_admin() || ! smpt_can_use_blocked_preview() ) {
		return;
	}

	$enabled = smpt_is_blocked_preview_enabled();
	$action  = $enabled ? 'off' : 'on';
	$title   = $enabled ? 'Exit blocked preview' : 'Preview blocked user';
	$url     = wp_nonce_url(
		add_query_arg( 'smpt_preview_blocked', $action, home_url( add_query_arg( array() ) ) ),
		'smpt_toggle_blocked_preview'
	);

	$wp_admin_bar->add_node(
		array(
			'id'    => 'smpt-preview-blocked',
			'title' => $title,
			'href'  => $url,
			'meta'  => array(
				'class' => $enabled ? 'smpt-preview-blocked-enabled' : 'smpt-preview-blocked-disabled',
			),
		)
	);
}
add_action( 'admin_bar_menu', 'smpt_add_blocked_preview_admin_bar_toggle', 90 );
