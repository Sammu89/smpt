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
			'login'         => array( 'entrar', 'login' ),
			'register'      => array( 'registar', 'register' ),
			'lost_password' => array( 'recuperar-password', 'password-reset', 'lost-password' ),
			'dashboard'     => array( 'painel' ),
			'logout'        => array( 'desconectar', 'logout' ),
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
 * Get the hidden backend entry slug.
 *
 * @return string
 */
function smpt_member_get_backend_entry_slug() {
	return 'entrada';
}

/**
 * Get the hidden backend login entry URL.
 *
 * @return string
 */
function smpt_member_get_backend_entry_url() {
	$slug = smpt_member_get_backend_entry_slug();

	return home_url( '/' . trim( $slug, '/' ) . '/' );
}

/**
 * Check whether the current request is for the hidden backend entry route.
 *
 * @return bool
 */
function smpt_member_is_backend_entry_request() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	$request_path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( home_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	$request_path = is_string( $request_path ) ? trim( $request_path, '/' ) : '';

	return smpt_member_get_backend_entry_slug() === $request_path;
}

/**
 * Check whether the request is an allowed backend-entry login hit.
 *
 * @return bool
 */
function smpt_member_is_backend_entry_login_request() {
	return isset( $_REQUEST['smpt_entry'] ) && '1' === (string) wp_unslash( $_REQUEST['smpt_entry'] );
}

/**
 * Check whether the given user can access the WordPress backend.
 *
 * @param WP_User|int|null $user User object or ID.
 * @return bool
 */
function smpt_member_user_can_access_backend( $user = null ) {
	if ( $user instanceof WP_User ) {
		$user = $user;
	} elseif ( null !== $user && '' !== $user ) {
		$user = get_userdata( $user );
	} else {
		$user = wp_get_current_user();
	}

	return $user instanceof WP_User && $user->exists() && user_can( $user, 'manage_options' );
}

/**
 * Render the active theme 404 template.
 *
 * @return void
 */
function smpt_member_send_not_found() {
	global $wp_query;

	status_header( 404 );
	nocache_headers();

	if ( ! ( $wp_query instanceof WP_Query ) ) {
		$wp_query = new WP_Query();
	}

	$wp_query->set_404();

	if ( isset( $wp_query->is_404 ) ) {
		$wp_query->is_404 = true;
	}

	if ( isset( $wp_query->is_home ) ) {
		$wp_query->is_home = false;
	}

	if ( isset( $wp_query->is_singular ) ) {
		$wp_query->is_singular = false;
	}

	if ( isset( $wp_query->posts ) ) {
		$wp_query->posts = array();
	}

	$template = get_query_template( '404' );

	if ( ! $template ) {
		$template = get_index_template();
	}

	if ( $template && file_exists( $template ) ) {
		include $template;
		exit;
	}

	wp_die(
		esc_html__( 'Pagina nao encontrada.', 'generatepress' ),
		esc_html__( '404', 'generatepress' ),
		array(
			'response' => 404,
		)
	);
}

/**
 * Check whether the given user is a moonie.
 *
 * @param WP_User|int|null $user User object or ID.
 * @return bool
 */
function smpt_member_is_moonie( $user = null ) {
	if ( $user instanceof WP_User ) {
		$user = $user;
	} elseif ( null !== $user && '' !== $user ) {
		$user = get_userdata( $user );
	} else {
		$user = wp_get_current_user();
	}

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
	if ( 'lost_password' === $screen ) {
		$slug = smpt_member_get_primary_slug( $screen );
		return $slug ? home_url( '/' . trim( $slug, '/' ) . '/' ) : home_url( '/' );
	}

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
	if ( $user instanceof WP_User ) {
		$user = $user;
	} elseif ( null !== $user && '' !== $user ) {
		$user = get_userdata( $user );
	} else {
		$user = wp_get_current_user();
	}

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
	return smpt_member_get_url( 'dashboard' );
}

/**
 * Get the password reset URL.
 *
 * @param string $redirect_to Optional redirect target after reset flow.
 * @return string
 */
function smpt_member_get_lost_password_url( $redirect_to = '' ) {
	$url = smpt_member_get_url( 'lost_password' );

	if ( $redirect_to ) {
		$url = add_query_arg( 'redirect_to', rawurlencode( $redirect_to ), $url );
	}

	return $url;
}

/**
 * Get the front-end reset password URL.
 *
 * @param string $login       User login slug.
 * @param string $key         Reset key.
 * @param string $redirect_to Optional redirect target.
 * @return string
 */
function smpt_member_get_reset_password_url( $login = '', $key = '', $redirect_to = '' ) {
	$args = array(
		'action' => 'reset_password',
		'login'  => rawurlencode( $login ),
		'key'    => rawurlencode( $key ),
	);

	if ( $redirect_to ) {
		$args['redirect_to'] = rawurlencode( $redirect_to );
	}

	return add_query_arg( $args, smpt_member_get_lost_password_url() );
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

	if ( ! $slugs ) {
		return false;
	}

	if ( is_page( $slugs ) ) {
		return true;
	}

	$request_path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( home_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	$request_path = is_string( $request_path ) ? trim( $request_path, '/' ) : '';

	return '' !== $request_path && in_array( $request_path, $slugs, true );
}

/**
 * Check whether the current request is for the auth pages.
 *
 * @return bool
 */
function smpt_member_is_auth_page_request() {
	return smpt_member_is_page_request( 'login' ) || smpt_member_is_page_request( 'register' ) || smpt_member_is_page_request( 'lost_password' );
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

	if ( smpt_member_is_page_request( 'lost_password' ) ) {
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';

		return in_array( $action, array( 'reset_password', 'rp', 'resetpass' ), true ) ? 'reset_password' : 'lost_password';
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
 * Prevent virtual member-area routes from staying in 404 state.
 *
 * @return void
 */
function smpt_member_normalize_virtual_routes() {
	if ( ! smpt_member_is_auth_page_request() && ! smpt_member_is_dashboard_page_request() && ! smpt_member_is_logout_page_request() ) {
		return;
	}

	global $wp_query;

	if ( isset( $wp_query ) && $wp_query instanceof WP_Query && $wp_query->is_404() ) {
		$wp_query->is_404 = false;
		status_header( 200 );
	}
}
add_action( 'template_redirect', 'smpt_member_normalize_virtual_routes', 1 );

/**
 * Route the hidden backend entry URL into the real WordPress login screen.
 *
 * @return void
 */
function smpt_member_handle_backend_entry_route() {
	if ( ! smpt_member_is_backend_entry_request() ) {
		return;
	}

	if ( is_user_logged_in() ) {
		if ( smpt_member_user_can_access_backend() ) {
			wp_safe_redirect( admin_url() );
			exit;
		}

		wp_safe_redirect( smpt_member_get_url( 'dashboard' ) );
		exit;
	}

	wp_safe_redirect( add_query_arg( 'smpt_entry', '1', wp_login_url() ) );
	exit;
}
add_action( 'template_redirect', 'smpt_member_handle_backend_entry_route', 2 );

/**
 * Block unauthenticated direct backend hits before WordPress auth_redirect runs.
 *
 * @return void
 */
function smpt_member_block_public_backend_bootstrap() {
	if ( ! is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( is_user_logged_in() || ! smpt_member_current_visitor_is_allowed() ) {
		return;
	}

	smpt_member_send_not_found();
}
add_action( 'init', 'smpt_member_block_public_backend_bootstrap', 1 );

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

	if ( smpt_member_is_auth_page_request() && is_user_logged_in() ) {
		wp_safe_redirect( smpt_member_get_url( 'dashboard' ) );
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
		smpt_member_send_not_found();
	}

	if ( smpt_member_user_can_access_backend() ) {
		return;
	}

	if ( ! smpt_member_current_visitor_is_allowed() ) {
		wp_safe_redirect( smpt_member_get_blocked_redirect_url() );
		exit;
	}

	wp_safe_redirect( smpt_member_get_url( 'dashboard' ) );
	exit;
}
add_action( 'admin_init', 'smpt_member_handle_admin_redirects', 1 );

/**
 * Redirect direct wp-login access into the front-end flow.
 *
 * @return void
 */
function smpt_member_handle_wp_login_redirects() {
	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';

	if ( 'logout' === $action ) {
		wp_safe_redirect( smpt_member_get_url( 'logout' ) );
		exit;
	}

	if ( in_array( $action, array( 'lostpassword', 'retrievepassword' ), true ) ) {
		$redirect_to = isset( $_REQUEST['redirect_to'] ) ? wp_unslash( $_REQUEST['redirect_to'] ) : '';
		wp_safe_redirect( smpt_member_get_lost_password_url( $redirect_to ) );
		exit;
	}

	if ( in_array( $action, array( 'rp', 'resetpass' ), true ) ) {
		$key         = isset( $_REQUEST['key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['key'] ) ) : '';
		$login       = isset( $_REQUEST['login'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['login'] ) ) : '';
		$redirect_to = isset( $_REQUEST['redirect_to'] ) ? wp_unslash( $_REQUEST['redirect_to'] ) : '';
		wp_safe_redirect( smpt_member_get_reset_password_url( $login, $key, $redirect_to ) );
		exit;
	}

	if ( ! is_user_logged_in() && ! smpt_member_current_visitor_is_allowed() ) {
		wp_safe_redirect( smpt_member_get_blocked_redirect_url() );
		exit;
	}

	if ( is_user_logged_in() ) {
		if ( smpt_member_user_can_access_backend() ) {
			wp_safe_redirect( admin_url() );
			exit;
		}

		wp_safe_redirect( smpt_member_get_url( 'dashboard' ) );
		exit;
	}

	if ( ! smpt_member_is_backend_entry_login_request() ) {
		smpt_member_send_not_found();
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
	if ( smpt_member_user_can_access_backend() ) {
		return $show;
	}

	return false;
}
add_filter( 'show_admin_bar', 'smpt_member_hide_admin_bar' );

/**
 * Preserve backend-entry access across the core WordPress login form POST.
 *
 * @return void
 */
function smpt_member_render_backend_entry_login_field() {
	if ( ! smpt_member_is_backend_entry_login_request() ) {
		return;
	}
	?>
	<input type="hidden" name="smpt_entry" value="1">
	<?php
}
add_action( 'login_form', 'smpt_member_render_backend_entry_login_field' );

/**
 * Replace core reset links with the front-end password reset URL.
 *
 * @param string  $message    Default email message.
 * @param string  $key        Reset key.
 * @param string  $user_login User login.
 * @param WP_User $user_data  User object.
 * @return string
 */
function smpt_member_filter_retrieve_password_message( $message, $key, $user_login, $user_data ) {
	$locale = get_user_locale( $user_data );
	$core   = network_site_url( 'wp-login.php?login=' . rawurlencode( $user_login ) . "&key=$key&action=rp", 'login' ) . '&wp_lang=' . $locale;
	$front  = smpt_member_get_reset_password_url( $user_login, $key );

	return str_replace( $core, $front, $message );
}
add_filter( 'retrieve_password_message', 'smpt_member_filter_retrieve_password_message', 10, 4 );

/**
 * Get the reset-password user for the current request.
 *
 * @return WP_User|WP_Error|null
 */
function smpt_member_get_requested_reset_user() {
	$login = isset( $_REQUEST['login'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['login'] ) ) : '';
	$key   = isset( $_REQUEST['key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['key'] ) ) : '';

	if ( '' === $login || '' === $key ) {
		return null;
	}

	return check_password_reset_key( $key, $login );
}

/**
 * Get the generic front-end dashboard context.
 *
 * @return array
 */
function smpt_member_get_dashboard_context() {
	$user  = wp_get_current_user();
	$roles = array_filter(
		array_map(
			static function ( $role ) {
				$role_object = get_role( $role );
				return $role_object && ! empty( $role_object->name ) ? translate_user_role( $role_object->name ) : $role;
			},
			(array) $user->roles
		)
	);

	return array(
		'user'        => $user,
		'roles'       => $roles,
		'is_moonie'   => smpt_member_is_moonie( $user ),
		'logout_url'  => smpt_member_get_url( 'logout' ),
		'home_url'    => home_url( '/' ),
		'profile_url' => smpt_member_get_url( 'dashboard' ),
	);
}

/**
 * Build the default auth context.
 *
 * @return array
 */
function smpt_member_get_default_auth_context() {
	$view       = smpt_member_get_auth_view();
	$reset_user = 'reset_password' === $view ? smpt_member_get_requested_reset_user() : null;

	return array(
		'view'            => $view,
		'redirect_to'     => isset( $_REQUEST['redirect_to'] ) ? wp_unslash( $_REQUEST['redirect_to'] ) : '',
		'login_error'     => '',
		'login_notice'    => isset( $_GET['password_reset'] ) && 'complete' === sanitize_key( wp_unslash( $_GET['password_reset'] ) ) ? __( 'A tua palavra-passe foi atualizada. Ja podes entrar.', 'generatepress' ) : '',
		'register_error'  => '',
		'password_error'  => is_wp_error( $reset_user ) ? $reset_user->get_error_message() : '',
		'password_notice' => isset( $_GET['checkemail'] ) && 'confirm' === sanitize_key( wp_unslash( $_GET['checkemail'] ) ) ? __( 'Enviamos um email com um link para redefinir a tua palavra-passe.', 'generatepress' ) : '',
		'reset_user'      => $reset_user instanceof WP_User ? $reset_user : null,
		'login_values'    => array(
			'identifier' => '',
			'remember'   => false,
		),
		'password_values' => array(
			'identifier' => '',
		),
		'reset_values'    => array(
			'password'         => '',
			'password_confirm' => '',
		),
		'register_values' => array(
			'display_name' => '',
			'user_login'   => '',
			'user_email'   => '',
		),
	);
}

/**
 * Process auth submissions and return the updated context.
 *
 * @param array $context Existing auth context.
 * @return array
 */
function smpt_member_process_auth_context( array $context ) {
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

	if ( 'lost_password' === $action ) {
		$context['view']                          = 'lost_password';
		$context['password_values']['identifier'] = sanitize_text_field( wp_unslash( $_POST['user_login'] ?? '' ) );

		if ( ! isset( $_POST['smpt_member_lost_password_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['smpt_member_lost_password_nonce'] ) ), 'smpt_member_lost_password' ) ) {
			$context['password_error'] = __( 'Nao foi possivel validar o pedido de recuperacao. Tente novamente.', 'generatepress' );
			return $context;
		}

		$result = retrieve_password( $context['password_values']['identifier'] );

		if ( is_wp_error( $result ) ) {
			$context['password_error'] = $result->get_error_message();
			return $context;
		}

		wp_safe_redirect( add_query_arg( 'checkemail', 'confirm', smpt_member_get_lost_password_url() ) );
		exit;
	}

	if ( 'reset_password' === $action ) {
		$context['view']                             = 'reset_password';
		$context['reset_values']['password']         = (string) ( $_POST['pass1'] ?? '' );
		$context['reset_values']['password_confirm'] = (string) ( $_POST['pass2'] ?? '' );

		if ( ! isset( $_POST['smpt_member_reset_password_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['smpt_member_reset_password_nonce'] ) ), 'smpt_member_reset_password' ) ) {
			$context['password_error'] = __( 'Nao foi possivel validar o pedido de redefinicao. Tente novamente.', 'generatepress' );
			return $context;
		}

		$login            = isset( $_POST['login'] ) ? sanitize_text_field( wp_unslash( $_POST['login'] ) ) : '';
		$key              = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
		$context['reset_user'] = check_password_reset_key( $key, $login );

		if ( is_wp_error( $context['reset_user'] ) ) {
			$context['password_error'] = $context['reset_user']->get_error_message();
			$context['reset_user']     = null;
			return $context;
		}

		if ( '' === $context['reset_values']['password'] ) {
			$context['password_error'] = __( 'Introduz uma nova palavra-passe.', 'generatepress' );
			return $context;
		}

		if ( $context['reset_values']['password'] !== $context['reset_values']['password_confirm'] ) {
			$context['password_error'] = __( 'As palavras-passe nao coincidem.', 'generatepress' );
			return $context;
		}

		reset_password( $context['reset_user'], $context['reset_values']['password'] );

		wp_safe_redirect( add_query_arg( 'password_reset', 'complete', smpt_member_get_login_url() ) );
		exit;
	}

	return $context;
}

/**
 * Bootstrap the auth context early so forms can live outside the auth template.
 *
 * @return void
 */
function smpt_member_bootstrap_auth_context() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	$GLOBALS['smpt_member_auth_context'] = smpt_member_process_auth_context( smpt_member_get_default_auth_context() );
}
add_action( 'template_redirect', 'smpt_member_bootstrap_auth_context', 3 );

/**
 * Build the auth-page context.
 *
 * @return array
 */
function smpt_member_get_auth_context() {
	if ( isset( $GLOBALS['smpt_member_auth_context'] ) && is_array( $GLOBALS['smpt_member_auth_context'] ) ) {
		return $GLOBALS['smpt_member_auth_context'];
	}

	$GLOBALS['smpt_member_auth_context'] = smpt_member_get_default_auth_context();

	return $GLOBALS['smpt_member_auth_context'];
}
