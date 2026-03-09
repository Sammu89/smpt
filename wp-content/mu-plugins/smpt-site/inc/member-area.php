<?php
/**
 * Front-end member area and auth flow.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get member-area configuration.
 *
 * @return array
 */
function smpt_member_get_config() {
	return array(
		'role'  => 'moonies',
		'pages' => array(
			'login'     => array( 'entrar', 'login' ),
			'register'  => array( 'registar', 'register' ),
			'dashboard' => array( 'painel', 'dashboard', 'members' ),
			'logout'    => array( 'desconectar', 'logout' ),
		),
	);
}

/**
 * Get the first configured slug for a member-area screen.
 *
 * @param string $screen Screen key.
 * @return string
 */
function smpt_member_get_primary_slug( $screen ) {
	$config = smpt_member_get_config();
	$slugs  = $config['pages'][ $screen ] ?? array();

	return $slugs ? (string) $slugs[0] : '';
}

/**
 * Get the current front-end URL.
 *
 * @return string
 */
function smpt_member_get_current_frontend_url() {
	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		return home_url( '/' );
	}

	return home_url( wp_unslash( $_SERVER['REQUEST_URI'] ) );
}

/**
 * Check whether the current visitor can access the member flow.
 *
 * @return bool
 */
function smpt_member_current_visitor_is_allowed() {
	if ( function_exists( 'smpt_current_visitor_is_allowed' ) ) {
		return smpt_current_visitor_is_allowed();
	}

	return true;
}

/**
 * Get the redirect used for blocked visitors.
 *
 * @return string
 */
function smpt_member_get_blocked_redirect_url() {
	return home_url( '/' );
}

/**
 * Ensure the moonies role exists.
 *
 * @return void
 */
function smpt_member_register_role() {
	$config = smpt_member_get_config();
	$role   = get_role( $config['role'] );

	if ( $role ) {
		return;
	}

	add_role(
		$config['role'],
		'Moonies',
		array(
			'read' => true,
		)
	);
}
add_action( 'init', 'smpt_member_register_role' );

/**
 * Check whether the given user is a moonie.
 *
 * @param WP_User|int|null $user User object or ID.
 * @return bool
 */
function smpt_member_is_moonie( $user = null ) {
	$user = $user ? get_userdata( $user ) : wp_get_current_user();

	if ( ! $user instanceof WP_User || ! $user->exists() ) {
		return false;
	}

	return in_array( smpt_member_get_config()['role'], (array) $user->roles, true );
}

/**
 * Get the first available page object for a member-area screen.
 *
 * @param string $screen Screen key.
 * @return WP_Post|null
 */
function smpt_member_get_page( $screen ) {
	$config = smpt_member_get_config();
	$slugs  = $config['pages'][ $screen ] ?? array();

	foreach ( $slugs as $slug ) {
		$page = get_page_by_path( $slug );

		if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
			return $page;
		}
	}

	return null;
}

/**
 * Get the front-end URL for a member-area screen.
 *
 * @param string $screen Screen key.
 * @return string
 */
function smpt_member_get_url( $screen ) {
	$page = smpt_member_get_page( $screen );

	if ( $page instanceof WP_Post ) {
		return get_permalink( $page );
	}

	$slug = smpt_member_get_primary_slug( $screen );

	return $slug ? home_url( '/' . trim( $slug, '/' ) . '/' ) : home_url( '/' );
}

/**
 * Redirect legacy member-area aliases to the primary localized slugs.
 *
 * @return void
 */
function smpt_member_redirect_legacy_aliases() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	$request_path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( home_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	$request_path = is_string( $request_path ) ? trim( $request_path, '/' ) : '';

	if ( '' === $request_path ) {
		return;
	}

	foreach ( smpt_member_get_config()['pages'] as $screen => $slugs ) {
		$primary_slug = smpt_member_get_primary_slug( $screen );

		if ( '' === $primary_slug || $primary_slug === $request_path || ! in_array( $request_path, $slugs, true ) ) {
			continue;
		}

		wp_safe_redirect( smpt_member_get_url( $screen ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'smpt_member_redirect_legacy_aliases', 1 );

/**
 * Get the custom login URL.
 *
 * @param string $redirect_to Optional redirect target.
 * @return string
 */
function smpt_member_get_login_url( $redirect_to = '' ) {
	$url = smpt_member_get_url( 'login' );

	if ( $redirect_to ) {
		$url = add_query_arg( 'redirect_to', rawurlencode( $redirect_to ), $url );
	}

	return $url;
}

/**
 * Get the logout URL for the current user.
 *
 * @param string $redirect_to Optional redirect target.
 * @param mixed  $user        User object or ID.
 * @return string
 */
function smpt_member_get_logout_url( $redirect_to = '', $user = null ) {
	$user = $user ? get_userdata( $user ) : wp_get_current_user();

	if ( $user instanceof WP_User && smpt_member_is_moonie( $user ) ) {
		return smpt_member_get_url( 'logout' );
	}

	return wp_logout_url( $redirect_to ? $redirect_to : smpt_member_get_current_frontend_url() );
}

/**
 * Get the post-login destination for a user.
 *
 * @param WP_User $user User object.
 * @param string  $redirect_to Requested redirect target.
 * @return string
 */
function smpt_member_get_post_login_redirect( WP_User $user, $redirect_to = '' ) {
	if ( smpt_member_is_moonie( $user ) ) {
		return smpt_member_get_url( 'dashboard' );
	}

	$redirect_to = wp_validate_redirect( $redirect_to, '' );

	if ( $redirect_to ) {
		return $redirect_to;
	}

	return admin_url();
}

/**
 * Check whether the current request is for a member-area page.
 *
 * @param string $screen Screen key.
 * @return bool
 */
function smpt_member_is_page_request( $screen ) {
	$config = smpt_member_get_config();
	$slugs  = $config['pages'][ $screen ] ?? array();

	return $slugs ? is_page( $slugs ) : false;
}

/**
 * Check whether the current request is for the auth pages.
 *
 * @return bool
 */
function smpt_member_is_auth_page_request() {
	return smpt_member_is_page_request( 'login' ) || smpt_member_is_page_request( 'register' );
}

/**
 * Check whether the current request is for the dashboard page.
 *
 * @return bool
 */
function smpt_member_is_dashboard_page_request() {
	return smpt_member_is_page_request( 'dashboard' );
}

/**
 * Check whether the current request is for the logout page.
 *
 * @return bool
 */
function smpt_member_is_logout_page_request() {
	return smpt_member_is_page_request( 'logout' );
}

/**
 * Get the current auth page view.
 *
 * @return string
 */
function smpt_member_get_auth_view() {
	if ( smpt_member_is_page_request( 'register' ) ) {
		return 'register';
	}

	$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';

	return 'register' === $view ? 'register' : 'login';
}

/**
 * Get the member-area template for the current request.
 *
 * @param string $template Current template path.
 * @return string
 */
function smpt_member_template_include( $template ) {
	$theme_template_dir = trailingslashit( get_stylesheet_directory() ) . 'templates/';

	if ( smpt_member_is_auth_page_request() ) {
		$auth_template = $theme_template_dir . 'member-auth.php';

		if ( file_exists( $auth_template ) ) {
			return $auth_template;
		}
	}

	if ( smpt_member_is_dashboard_page_request() ) {
		$dashboard_template = $theme_template_dir . 'member-dashboard.php';

		if ( file_exists( $dashboard_template ) ) {
			return $dashboard_template;
		}
	}

	return $template;
}
add_filter( 'template_include', 'smpt_member_template_include', 99 );

/**
 * Redirect member-area requests as needed.
 *
 * @return void
 */
function smpt_member_handle_page_redirects() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( ! smpt_member_current_visitor_is_allowed() && ( smpt_member_is_auth_page_request() || smpt_member_is_dashboard_page_request() || smpt_member_is_logout_page_request() ) ) {
		if ( function_exists( 'smpt_access_log' ) ) {
			smpt_access_log(
				sprintf(
					'Member redirect: request=%s target=%s',
					isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/',
					smpt_member_get_blocked_redirect_url()
				)
			);
		}
		wp_safe_redirect( smpt_member_get_blocked_redirect_url() );
		exit;
	}

	if ( smpt_member_is_logout_page_request() ) {
		wp_logout();
		wp_safe_redirect( smpt_member_get_login_url() );
		exit;
	}

	if ( smpt_member_is_dashboard_page_request() && ! is_user_logged_in() ) {
		wp_safe_redirect( smpt_member_get_login_url( smpt_member_get_url( 'dashboard' ) ) );
		exit;
	}

	if ( smpt_member_is_dashboard_page_request() && is_user_logged_in() && ! smpt_member_is_moonie() ) {
		wp_safe_redirect( admin_url() );
		exit;
	}

	if ( smpt_member_is_auth_page_request() && is_user_logged_in() ) {
		$redirect_url = smpt_member_is_moonie() ? smpt_member_get_url( 'dashboard' ) : admin_url();
		wp_safe_redirect( $redirect_url );
		exit;
	}
}
add_action( 'template_redirect', 'smpt_member_handle_page_redirects', 2 );

/**
 * Redirect admin access into the front-end member flow when appropriate.
 *
 * @return void
 */
function smpt_member_handle_admin_redirects() {
	if ( wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	if ( ! is_user_logged_in() && ! smpt_member_current_visitor_is_allowed() ) {
		wp_safe_redirect( smpt_member_get_blocked_redirect_url() );
		exit;
	}

	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( smpt_member_get_login_url( admin_url() ) );
		exit;
	}

	if ( smpt_member_is_moonie() ) {
		wp_safe_redirect( smpt_member_get_url( 'dashboard' ) );
		exit;
	}
}
add_action( 'admin_init', 'smpt_member_handle_admin_redirects', 1 );

/**
 * Redirect direct wp-login access into the front-end flow.
 *
 * @return void
 */
function smpt_member_handle_wp_login_redirects() {
	if ( isset( $_GET['smpt_admin'] ) ) {
		return;
	}

	if ( ! is_user_logged_in() && ! smpt_member_current_visitor_is_allowed() ) {
		wp_safe_redirect( smpt_member_get_blocked_redirect_url() );
		exit;
	}

	if ( is_user_logged_in() && smpt_member_is_moonie() ) {
		wp_safe_redirect( smpt_member_get_url( 'dashboard' ) );
		exit;
	}

	if ( ! is_user_logged_in() && 'GET' === strtoupper( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
		$redirect_to = isset( $_REQUEST['redirect_to'] ) ? wp_unslash( $_REQUEST['redirect_to'] ) : '';
		wp_safe_redirect( smpt_member_get_login_url( $redirect_to ) );
		exit;
	}
}
add_action( 'login_init', 'smpt_member_handle_wp_login_redirects', 1 );

/**
 * Hide the admin bar for moonies.
 *
 * @param bool $show Whether to show the admin bar.
 * @return bool
 */
function smpt_member_hide_admin_bar( $show ) {
	return smpt_member_is_moonie() ? false : $show;
}
add_filter( 'show_admin_bar', 'smpt_member_hide_admin_bar' );

/**
 * Build the auth-page context and process submissions.
 *
 * @return array
 */
function smpt_member_get_auth_context() {
	$context = array(
		'view'            => smpt_member_get_auth_view(),
		'redirect_to'     => isset( $_REQUEST['redirect_to'] ) ? wp_unslash( $_REQUEST['redirect_to'] ) : '',
		'login_error'     => '',
		'register_error'  => '',
		'login_values'    => array(
			'identifier' => '',
			'remember'   => false,
		),
		'register_values' => array(
			'display_name' => '',
			'user_login'   => '',
			'user_email'   => '',
		),
	);

	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) || empty( $_POST['smpt_member_action'] ) ) {
		return $context;
	}

	$action = sanitize_key( wp_unslash( $_POST['smpt_member_action'] ) );

	if ( 'login' === $action ) {
		$context['view']                        = 'login';
		$context['login_values']['identifier'] = sanitize_text_field( wp_unslash( $_POST['log'] ?? '' ) );
		$context['login_values']['remember']   = ! empty( $_POST['rememberme'] );
		$context['redirect_to']                = isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : $context['redirect_to'];

		if ( ! isset( $_POST['smpt_member_login_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['smpt_member_login_nonce'] ) ), 'smpt_member_login' ) ) {
			$context['login_error'] = __( 'Nao foi possivel validar o pedido de entrada. Tente novamente.', 'generatepress' );
			return $context;
		}

		$identifier = $context['login_values']['identifier'];
		$password   = (string) ( $_POST['pwd'] ?? '' );
		$user_login = $identifier;

		if ( is_email( $identifier ) ) {
			$user_by_email = get_user_by( 'email', $identifier );

			if ( $user_by_email instanceof WP_User ) {
				$user_login = $user_by_email->user_login;
			}
		}

		$user = wp_signon(
			array(
				'user_login'    => $user_login,
				'user_password' => $password,
				'remember'      => $context['login_values']['remember'],
			),
			is_ssl()
		);

		if ( is_wp_error( $user ) ) {
			$context['login_error'] = $user->get_error_message();
			return $context;
		}

		wp_safe_redirect( smpt_member_get_post_login_redirect( $user, $context['redirect_to'] ) );
		exit;
	}

	if ( 'register' === $action ) {
		$context['view']                             = 'register';
		$context['register_values']['display_name'] = sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) );
		$context['register_values']['user_login']   = sanitize_user( wp_unslash( $_POST['user_login'] ?? '' ), true );
		$context['register_values']['user_email']   = sanitize_email( wp_unslash( $_POST['user_email'] ?? '' ) );

		if ( ! isset( $_POST['smpt_member_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['smpt_member_register_nonce'] ) ), 'smpt_member_register' ) ) {
			$context['register_error'] = __( 'Nao foi possivel validar o pedido de registo. Tente novamente.', 'generatepress' );
			return $context;
		}

		$password         = (string) ( $_POST['user_password'] ?? '' );
		$password_confirm = (string) ( $_POST['user_password_confirm'] ?? '' );

		if ( '' === $context['register_values']['user_login'] || '' === $context['register_values']['user_email'] || '' === $password ) {
			$context['register_error'] = __( 'Nome de utilizador, email e palavra-passe sao obrigatorios.', 'generatepress' );
			return $context;
		}

		if ( ! is_email( $context['register_values']['user_email'] ) ) {
			$context['register_error'] = __( 'Introduz um endereco de email valido.', 'generatepress' );
			return $context;
		}

		if ( $password !== $password_confirm ) {
			$context['register_error'] = __( 'As palavras-passe nao coincidem.', 'generatepress' );
			return $context;
		}

		if ( username_exists( $context['register_values']['user_login'] ) ) {
			$context['register_error'] = __( 'Esse nome de utilizador ja esta a ser usado.', 'generatepress' );
			return $context;
		}

		if ( email_exists( $context['register_values']['user_email'] ) ) {
			$context['register_error'] = __( 'Esse endereco de email ja esta a ser usado.', 'generatepress' );
			return $context;
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => $context['register_values']['user_login'],
				'user_email'   => $context['register_values']['user_email'],
				'user_pass'    => $password,
				'display_name' => $context['register_values']['display_name'] ? $context['register_values']['display_name'] : $context['register_values']['user_login'],
				'role'         => smpt_member_get_config()['role'],
			)
		);

		if ( is_wp_error( $user_id ) ) {
			$context['register_error'] = $user_id->get_error_message();
			return $context;
		}

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true, is_ssl() );

		wp_safe_redirect( smpt_member_get_url( 'dashboard' ) );
		exit;
	}

	return $context;
}
