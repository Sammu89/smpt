<?php
/**
 * Navigation customizations for the child theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build the inline login panel markup used in navigation submenus.
 *
 * @param string $context Display context.
 * @return string
 */
function smpt_get_navigation_login_panel_markup( $context = 'desktop' ) {
	$auth_context       = function_exists( 'smpt_member_get_auth_context' ) ? smpt_member_get_auth_context() : array();
	$current_url        = function_exists( 'smpt_member_get_current_frontend_url' ) ? smpt_member_get_current_frontend_url() : home_url( '/' );
	$register_url       = function_exists( 'smpt_member_get_url' ) ? smpt_member_get_url( 'register' ) : wp_registration_url();
	$lost_password_url  = function_exists( 'smpt_member_get_lost_password_url' ) ? smpt_member_get_lost_password_url( $current_url ) : wp_lostpassword_url( $current_url );
	$identifier         = isset( $auth_context['login_values']['identifier'] ) ? $auth_context['login_values']['identifier'] : '';
	$error_message      = isset( $auth_context['login_error'] ) ? $auth_context['login_error'] : '';
	$panel_classes      = 'smpt-nav-login-panel smpt-nav-login-panel--' . sanitize_html_class( $context );
	$link_classes       = 'smpt-nav-login-panel__links smpt-nav-login-panel__links--' . sanitize_html_class( $context );
	$password_name_attr = 'pwd';

	ob_start();
	?>
	<div class="<?php echo esc_attr( $panel_classes ); ?>">
		<?php if ( $error_message ) : ?>
			<div class="smpt-nav-login-panel__notice"><?php echo wp_kses_post( $error_message ); ?></div>
		<?php endif; ?>

		<form method="post" class="smpt-nav-login-panel__form" action="<?php echo esc_url( $current_url ); ?>">
			<input
				type="text"
				name="log"
				value="<?php echo esc_attr( $identifier ); ?>"
				placeholder="<?php echo esc_attr__( 'Email ou utilizador', 'generatepress' ); ?>"
				autocomplete="username"
				required
			>
			<input
				type="password"
				name="<?php echo esc_attr( $password_name_attr ); ?>"
				placeholder="<?php echo esc_attr__( 'Palavra-passe', 'generatepress' ); ?>"
				autocomplete="current-password"
				required
			>

			<input type="hidden" name="smpt_member_login_nonce" value="<?php echo esc_attr( wp_create_nonce( 'smpt_member_login' ) ); ?>">
			<input type="hidden" name="smpt_member_action" value="login">
			<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $current_url ); ?>">

			<button type="submit"><?php esc_html_e( 'Entrar', 'generatepress' ); ?></button>
		</form>

		<div class="<?php echo esc_attr( $link_classes ); ?>">
			<a href="<?php echo esc_url( $lost_password_url ); ?>"><?php esc_html_e( 'Esqueci-me da password', 'generatepress' ); ?></a>
			<a href="<?php echo esc_url( $register_url ); ?>"><?php esc_html_e( 'Registar', 'generatepress' ); ?></a>
		</div>
	</div>
	<?php

	return trim( ob_get_clean() );
}

/**
 * Check whether the inline menu login panel currently contains an error.
 *
 * @return bool
 */
function smpt_navigation_login_panel_has_error() {
	$auth_context = function_exists( 'smpt_member_get_auth_context' ) ? smpt_member_get_auth_context() : array();

	return ! empty( $auth_context['login_error'] );
}

/**
 * Render the small sticky navigation logo inside the menu.
 *
 * @return void
 */
function smpt_render_sticky_nav_logo() {
	$logo_path = get_stylesheet_directory() . '/imagens/logopequeno.png';
	$logo_uri  = get_stylesheet_directory_uri() . '/imagens/logopequeno.png';
	$logo_size = file_exists( $logo_path ) ? getimagesize( $logo_path ) : false;
	?>
	<a class="smpt-sticky-nav-branding" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
		<img
			src="<?php echo esc_url( $logo_uri ); ?>"
			alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
			<?php if ( $logo_size ) : ?>
				width="<?php echo esc_attr( $logo_size[0] ); ?>"
				height="<?php echo esc_attr( $logo_size[1] ); ?>"
			<?php endif; ?>
			loading="lazy"
			decoding="async"
		>
	</a>
	<?php
}
add_action( 'generate_inside_navigation', 'smpt_render_sticky_nav_logo', 5 );

/**
 * Render the account utility item in the GeneratePress menu-bar area.
 *
 * @return void
 */
function smpt_render_navigation_account_item() {
	$current_url = function_exists( 'smpt_member_get_current_frontend_url' ) ? smpt_member_get_current_frontend_url() : home_url( '/' );

	if ( function_exists( 'smpt_member_current_visitor_is_allowed' ) && ! smpt_member_current_visitor_is_allowed() ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		$has_error = smpt_navigation_login_panel_has_error();
		?>
		<div class="menu-bar-item smpt-account-link smpt-account-link--guest menu-item-has-children<?php echo $has_error ? ' smpt-account-link--has-error' : ''; ?>">
			<button type="button" class="smpt-account-link__toggle" aria-expanded="<?php echo $has_error ? 'true' : 'false'; ?>">
				<span class="dashicons dashicons-lock" aria-hidden="true"></span>
				<span class="smpt-account-label"><?php esc_html_e( 'Entrar', 'generatepress' ); ?></span>
				<span role="presentation" class="dropdown-menu-toggle"><?php echo generate_get_svg_icon( 'arrow' ); ?></span>
			</button>
			<ul class="sub-menu">
				<li class="menu-item smpt-account-form-item">
					<?php echo smpt_get_navigation_login_panel_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</li>
			</ul>
		</div>
		<?php
		return;
	}

	$user         = wp_get_current_user();
	$display_name = $user->display_name ? $user->display_name : __( 'Conta', 'generatepress' );
	$is_moonie    = function_exists( 'smpt_member_is_moonie' ) && smpt_member_is_moonie( $user );
	$account_url  = $is_moonie && function_exists( 'smpt_member_get_url' ) ? smpt_member_get_url( 'dashboard' ) : admin_url();
	$logout_url   = function_exists( 'smpt_member_get_logout_url' ) ? smpt_member_get_logout_url( $current_url, $user ) : wp_logout_url( $current_url );
	?>
	<div class="menu-bar-item smpt-account-link smpt-account-link--logged-in menu-item-has-children">
		<a href="<?php echo esc_url( $account_url ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Abrir menu da conta de %s', 'generatepress' ), $display_name ) ); ?>">
			<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
			<span class="smpt-account-menu__name"><?php echo esc_html( $display_name ); ?></span>
			<span role="presentation" class="dropdown-menu-toggle"><?php echo generate_get_svg_icon( 'arrow' ); ?></span>
		</a>
		<ul class="sub-menu">
			<li class="menu-item">
				<a href="<?php echo esc_url( $account_url ); ?>">
					<span class="dashicons dashicons-dashboard" aria-hidden="true"></span>
					<span><?php esc_html_e( 'Painel', 'generatepress' ); ?></span>
				</a>
			</li>
			<?php if ( ! $is_moonie ) : ?>
				<li class="menu-item">
					<a href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>">
						<span class="dashicons dashicons-id-alt" aria-hidden="true"></span>
						<span><?php esc_html_e( 'Perfil', 'generatepress' ); ?></span>
					</a>
				</li>
			<?php endif; ?>
			<li class="menu-item">
				<a href="<?php echo esc_url( $logout_url ); ?>">
					<span class="dashicons dashicons-exit" aria-hidden="true"></span>
					<span><?php esc_html_e( 'Desconectar', 'generatepress' ); ?></span>
				</a>
			</li>
		</ul>
	</div>
	<?php
}
add_action( 'generate_menu_bar_items', 'smpt_render_navigation_account_item', 20 );

/**
 * Prepend the account entry to the primary menu for collapsed navigation.
 *
 * @param string   $items Existing menu items.
 * @param stdClass $args  Menu arguments.
 * @return string
 */
function smpt_prepend_collapsed_navigation_account_item( $items, $args ) {
	if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location ) {
		return $items;
	}

	if ( ! isset( $args->container_class ) || 'main-nav' !== $args->container_class ) {
		return $items;
	}

	if ( function_exists( 'smpt_member_current_visitor_is_allowed' ) && ! smpt_member_current_visitor_is_allowed() ) {
		return $items;
	}

	$current_url = function_exists( 'smpt_member_get_current_frontend_url' ) ? smpt_member_get_current_frontend_url() : home_url( '/' );

	if ( ! is_user_logged_in() ) {
		$account_classes = array( 'menu-item', 'menu-item-has-children', 'smpt-mobile-account-item' );

		if ( smpt_navigation_login_panel_has_error() ) {
			$account_classes[] = 'smpt-force-open';
		}

		$account_item = sprintf(
			'<li class="%1$s">' .
				'<a href="#"><span class="dashicons dashicons-lock" aria-hidden="true"></span><span class="smpt-account-label">%2$s</span></a>' .
				'<ul class="sub-menu"><li class="menu-item smpt-mobile-login-panel-item">%3$s</li></ul>' .
			'</li>',
			esc_attr( implode( ' ', $account_classes ) ),
			esc_html__( 'Entrar', 'generatepress' ),
			smpt_get_navigation_login_panel_markup( 'mobile' )
		);

		return $account_item . $items;
	}

	$user         = wp_get_current_user();
	$display_name = $user->display_name ? $user->display_name : __( 'Conta', 'generatepress' );
	$is_moonie    = function_exists( 'smpt_member_is_moonie' ) && smpt_member_is_moonie( $user );
	$account_url  = $is_moonie && function_exists( 'smpt_member_get_url' ) ? smpt_member_get_url( 'dashboard' ) : admin_url();
	$logout_url   = function_exists( 'smpt_member_get_logout_url' ) ? smpt_member_get_logout_url( $current_url, $user ) : wp_logout_url( $current_url );
	$submenu      = sprintf(
		'<li class="menu-item"><a href="%1$s"><span class="dashicons dashicons-dashboard" aria-hidden="true"></span><span>%2$s</span></a></li>',
		esc_url( $account_url ),
		esc_html__( 'Painel', 'generatepress' )
	);

	if ( ! $is_moonie ) {
		$submenu .= sprintf(
			'<li class="menu-item"><a href="%1$s"><span class="dashicons dashicons-id-alt" aria-hidden="true"></span><span>%2$s</span></a></li>',
			esc_url( admin_url( 'profile.php' ) ),
			esc_html__( 'Perfil', 'generatepress' )
		);
	}

	$submenu .= sprintf(
		'<li class="menu-item"><a href="%1$s"><span class="dashicons dashicons-exit" aria-hidden="true"></span><span>%2$s</span></a></li>',
		esc_url( $logout_url ),
		esc_html__( 'Desconectar', 'generatepress' )
	);

	$account_item = sprintf(
		'<li class="menu-item menu-item-has-children smpt-mobile-account-item">' .
			'<a href="%1$s">' .
				'<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>' .
				'<span class="smpt-account-menu__name">%2$s</span>' .
				'<span role="button" class="dropdown-menu-toggle" tabindex="0" aria-label="%3$s">%4$s</span>' .
			'</a>' .
			'<ul class="sub-menu">%5$s</ul>' .
		'</li>',
		esc_url( $account_url ),
		esc_html( $display_name ),
		esc_attr__( 'Abrir submenu', 'generatepress' ),
		generate_get_svg_icon( 'arrow' ),
		$submenu
	);

	return $account_item . $items;
}
add_filter( 'wp_nav_menu_items', 'smpt_prepend_collapsed_navigation_account_item', 20, 2 );

/**
 * Render the off-canvas mobile navigation shell.
 *
 * The menu tree is cloned from the live primary menu in JavaScript so the
 * drawer stays aligned with the rendered GeneratePress navigation.
 *
 * @return void
 */
function smpt_render_offcanvas_mobile_navigation() {
	?>
	<div class="smpt-mobile-menu" id="smpt-mobile-menu" hidden aria-hidden="true">
		<div class="smpt-mobile-menu__overlay" data-smpt-mobile-close tabindex="-1"></div>
		<aside class="smpt-mobile-menu__panel" aria-label="<?php echo esc_attr__( 'Menu lateral', 'generatepress' ); ?>">
			<div class="smpt-mobile-menu__header">
				<p class="smpt-mobile-menu__eyebrow"><?php esc_html_e( 'Menu', 'generatepress' ); ?></p>
				<button type="button" class="smpt-mobile-menu__close" data-smpt-mobile-close aria-label="<?php echo esc_attr__( 'Fechar menu', 'generatepress' ); ?>">
					<span aria-hidden="true">Fechar</span>
				</button>
			</div>

			<div class="smpt-mobile-menu__search">
				<form method="get" class="smpt-mobile-menu__search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label class="screen-reader-text" for="smpt-mobile-menu-search"><?php esc_html_e( 'Pesquisar por:', 'generatepress' ); ?></label>
					<input
						type="search"
						id="smpt-mobile-menu-search"
						name="s"
						value="<?php echo esc_attr( get_search_query() ); ?>"
						placeholder="<?php echo esc_attr_x( 'Pesquisar...', 'placeholder', 'generatepress' ); ?>"
					>
					<button type="submit"><?php esc_html_e( 'Pesquisar', 'generatepress' ); ?></button>
				</form>
			</div>

			<section class="smpt-mobile-menu__section" aria-labelledby="smpt-mobile-menu-title">
				<h2 class="smpt-mobile-menu__title" id="smpt-mobile-menu-title"><?php esc_html_e( 'Navegação principal', 'generatepress' ); ?></h2>
				<div class="smpt-mobile-menu__nav" data-smpt-mobile-menu-root></div>
			</section>
		</aside>
	</div>
	<?php
}
add_action( 'wp_footer', 'smpt_render_offcanvas_mobile_navigation', 20 );
