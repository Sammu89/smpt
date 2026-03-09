<?php
/**
 * Navigation customizations for the child theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
		?>
		<div class="menu-bar-item smpt-account-link">
			<a href="<?php echo esc_url( function_exists( 'smpt_member_get_login_url' ) ? smpt_member_get_login_url( $current_url ) : wp_login_url( $current_url ) ); ?>">
				<span class="dashicons dashicons-lock" aria-hidden="true"></span>
				<span class="smpt-account-label"><?php esc_html_e( 'Entrar', 'generatepress' ); ?></span>
			</a>
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
		$account_item = sprintf(
			'<li class="menu-item smpt-mobile-account-item"><a href="%1$s"><span class="dashicons dashicons-lock" aria-hidden="true"></span><span class="smpt-account-label">%2$s</span></a></li>',
			esc_url( function_exists( 'smpt_member_get_login_url' ) ? smpt_member_get_login_url( $current_url ) : wp_login_url( $current_url ) ),
			esc_html__( 'Entrar', 'generatepress' )
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
