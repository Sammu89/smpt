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

if ( ! defined( 'SMPT_DEBUG_TRACE_COOKIE' ) ) {
	define( 'SMPT_DEBUG_TRACE_COOKIE', 'smpt_debug_trace' );
}

if ( ! defined( 'SMPT_DEBUG_TRACE_TTL' ) ) {
	define( 'SMPT_DEBUG_TRACE_TTL', 2 * HOUR_IN_SECONDS );
}

/**
 * Get the current front-end URL for preview toggles.
 *
 * @return string
 */
function smpt_get_access_preview_current_url() {
	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		return home_url( '/' );
	}

	return home_url( wp_unslash( $_SERVER['REQUEST_URI'] ) );
}

/**
 * Get the front-end URL for blocked preview toggles.
 *
 * @param string $action      Toggle action.
 * @param string $redirect_to Redirect target.
 * @return string
 */
function smpt_get_access_preview_toggle_url( $action, $redirect_to = '' ) {
	$url = $redirect_to ? $redirect_to : smpt_get_access_preview_current_url();

	$url = add_query_arg(
		array(
			'smpt_preview_toggle' => $action,
		),
		$url
	);

	if ( $redirect_to ) {
		$url = add_query_arg( 'redirect_to', rawurlencode( $redirect_to ), $url );
	}

	return wp_nonce_url( $url, 'smpt_toggle_blocked_preview' );
}

/**
 * Log blocked-preview debug details.
 *
 * @param string $message Log message.
 * @return void
 */
function smpt_access_preview_log( $message ) {
	if ( function_exists( 'smpt_access_log' ) ) {
		smpt_access_log( '[preview] ' . $message );
		return;
	}

	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return;
	}

	error_log( '[smpt-access] [preview] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
}

/**
 * Get the front-end URL for debug trace toggles.
 *
 * @param string $action      Toggle action.
 * @param string $redirect_to Redirect target.
 * @return string
 */
function smpt_get_debug_trace_toggle_url( $action, $redirect_to = '' ) {
	$url = $redirect_to ? $redirect_to : smpt_get_access_preview_current_url();

	$url = add_query_arg(
		array(
			'smpt_debug_trace_toggle' => $action,
		),
		$url
	);

	if ( $redirect_to ) {
		$url = add_query_arg( 'redirect_to', rawurlencode( $redirect_to ), $url );
	}

	return wp_nonce_url( $url, 'smpt_toggle_debug_trace' );
}

/**
 * Determine whether the current user can use the blocked preview toggle.
 *
 * @return bool
 */
function smpt_can_use_blocked_preview() {
	if ( ! function_exists( 'is_user_logged_in' ) || ! function_exists( 'current_user_can' ) ) {
		return false;
	}

	return is_user_logged_in() && current_user_can( 'manage_options' );
}

/**
 * Check whether the current admin session has full debug tracing enabled.
 *
 * @return bool
 */
function smpt_is_debug_trace_enabled() {
	if ( ! smpt_can_use_blocked_preview() ) {
		return false;
	}

	return ! empty( $_COOKIE[ SMPT_DEBUG_TRACE_COOKIE ] ) && '1' === (string) wp_unslash( $_COOKIE[ SMPT_DEBUG_TRACE_COOKIE ] );
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
 * Determine whether blocked preview should affect the current request.
 *
 * Preview mode is intentionally front-end only so administrators retain
 * access to wp-admin, AJAX, REST, and login flows while testing.
 *
 * @return bool
 */
function smpt_should_apply_blocked_preview() {
	if ( ! smpt_is_blocked_preview_enabled() ) {
		return false;
	}

	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	return true;
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
 * Store the debug trace cookie.
 *
 * @param bool $enabled Whether debug tracing should be enabled.
 * @return void
 */
function smpt_set_debug_trace_cookie( $enabled ) {
	$value   = $enabled ? '1' : '0';
	$expires = $enabled ? time() + SMPT_DEBUG_TRACE_TTL : time() - HOUR_IN_SECONDS;

	setcookie(
		SMPT_DEBUG_TRACE_COOKIE,
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

	$_COOKIE[ SMPT_DEBUG_TRACE_COOKIE ] = $value;
}

/**
 * Handle blocked preview toggles from the front end.
 *
 * @return void
 */
function smpt_handle_blocked_preview_toggle() {
	if ( ! smpt_can_use_blocked_preview() ) {
		return;
	}

	if ( empty( $_GET['smpt_preview_toggle'] ) ) {
		return;
	}

	$action = sanitize_key( wp_unslash( $_GET['smpt_preview_toggle'] ) );
	if ( ! in_array( $action, array( 'on', 'off' ), true ) ) {
		return;
	}

	$current_url   = smpt_get_access_preview_current_url();
	$redirect_to   = isset( $_REQUEST['redirect_to'] ) ? wp_unslash( $_REQUEST['redirect_to'] ) : $current_url;
	$redirect_to   = wp_validate_redirect( $redirect_to, $current_url );
	$user_id      = get_current_user_id();
	$cookie_name  = SMPT_ACCESS_PREVIEW_COOKIE;
	$cookie_before = isset( $_COOKIE[ $cookie_name ] ) ? (string) wp_unslash( $_COOKIE[ $cookie_name ] ) : '(missing)';

	smpt_access_preview_log(
		sprintf(
			'Toggle request: action=%1$s user=%2$d current=%3$s cookie_before=%4$s referer=%5$s',
			$action,
			(int) $user_id,
			$current_url,
			$cookie_before,
			wp_get_referer() ? wp_get_referer() : '(missing)'
		)
	);
	if ( function_exists( 'smpt_access_trace' ) ) {
		smpt_access_trace(
			'preview_toggle_start',
			array(
				'action'        => $action,
				'current'       => $current_url,
				'redirect_to'   => $redirect_to,
				'cookie_before' => $cookie_before,
			)
		);
	}

	check_admin_referer( 'smpt_toggle_blocked_preview' );
	if ( function_exists( 'smpt_access_trace' ) ) {
		smpt_access_trace( 'preview_toggle_nonce_ok', array( 'action' => $action ) );
	}

	smpt_set_blocked_preview_cookie( 'on' === $action );
	if ( function_exists( 'smpt_access_trace' ) ) {
		smpt_access_trace(
			'preview_toggle_cookie_set',
			array(
				'action'       => $action,
				'cookie_after' => isset( $_COOKIE[ $cookie_name ] ) ? (string) wp_unslash( $_COOKIE[ $cookie_name ] ) : '(missing)',
			)
		);
	}

	$redirect_url = remove_query_arg(
		array( 'smpt_preview_toggle', '_wpnonce', 'redirect_to' ),
		$redirect_to
	);

	smpt_access_preview_log(
		sprintf(
			'Toggle redirect: action=%1$s user=%2$d redirect=%3$s cookie_after=%4$s',
			$action,
			(int) $user_id,
			$redirect_url,
			isset( $_COOKIE[ $cookie_name ] ) ? (string) wp_unslash( $_COOKIE[ $cookie_name ] ) : '(missing)'
		)
	);
	if ( function_exists( 'smpt_access_trace' ) ) {
		smpt_access_trace(
			'preview_toggle_redirect',
			array(
				'action'   => $action,
				'redirect' => $redirect_url,
			)
		);
	}

	nocache_headers();
	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'init', 'smpt_handle_blocked_preview_toggle', 0 );

/**
 * Handle debug trace toggles from the front end.
 *
 * @return void
 */
function smpt_handle_debug_trace_toggle() {
	if ( ! smpt_can_use_blocked_preview() ) {
		return;
	}

	if ( empty( $_GET['smpt_debug_trace_toggle'] ) ) {
		return;
	}

	$action = sanitize_key( wp_unslash( $_GET['smpt_debug_trace_toggle'] ) );
	if ( ! in_array( $action, array( 'on', 'off' ), true ) ) {
		return;
	}

	$current_url   = smpt_get_access_preview_current_url();
	$redirect_to   = isset( $_REQUEST['redirect_to'] ) ? wp_unslash( $_REQUEST['redirect_to'] ) : $current_url;
	$redirect_to   = wp_validate_redirect( $redirect_to, $current_url );
	$cookie_before = isset( $_COOKIE[ SMPT_DEBUG_TRACE_COOKIE ] ) ? (string) wp_unslash( $_COOKIE[ SMPT_DEBUG_TRACE_COOKIE ] ) : '(missing)';

	smpt_access_preview_log(
		sprintf(
			'Debug trace toggle: action=%1$s current=%2$s redirect=%3$s cookie_before=%4$s',
			$action,
			$current_url,
			$redirect_to,
			$cookie_before
		)
	);

	check_admin_referer( 'smpt_toggle_debug_trace' );
	smpt_set_debug_trace_cookie( 'on' === $action );

	smpt_access_preview_log(
		sprintf(
			'Debug trace cookie set: action=%1$s cookie_after=%2$s',
			$action,
			isset( $_COOKIE[ SMPT_DEBUG_TRACE_COOKIE ] ) ? (string) wp_unslash( $_COOKIE[ SMPT_DEBUG_TRACE_COOKIE ] ) : '(missing)'
		)
	);

	nocache_headers();
	wp_safe_redirect(
		remove_query_arg(
			array( 'smpt_debug_trace_toggle', '_wpnonce', 'redirect_to' ),
			$redirect_to
		)
	);
	exit;
}
add_action( 'init', 'smpt_handle_debug_trace_toggle', 0 );

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
	$label   = $enabled ? 'Exit blocked preview' : 'Preview blocked user';
	$title   = '<span class="ab-icon dashicons dashicons-hidden" aria-hidden="true"></span><span class="ab-label">' . esc_html( $label ) . '</span>';
	$url     = smpt_get_access_preview_toggle_url( $action, smpt_get_access_preview_current_url() );

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

	$debug_enabled = smpt_is_debug_trace_enabled();
	$debug_action  = $debug_enabled ? 'off' : 'on';
	$debug_label   = $debug_enabled ? 'Disable debug logging' : 'Enable debug logging';
	$debug_title   = '<span class="ab-icon dashicons dashicons-performance" aria-hidden="true"></span><span class="ab-label">' . esc_html( $debug_label ) . '</span>';
	$debug_url     = smpt_get_debug_trace_toggle_url( $debug_action, smpt_get_access_preview_current_url() );

	$wp_admin_bar->add_node(
		array(
			'id'    => 'smpt-debug-trace',
			'title' => $debug_title,
			'href'  => $debug_url,
			'meta'  => array(
				'class' => $debug_enabled ? 'smpt-debug-trace-enabled' : 'smpt-debug-trace-disabled',
			),
		)
	);
}
add_action( 'admin_bar_menu', 'smpt_add_blocked_preview_admin_bar_toggle', 90 );
