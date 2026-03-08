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
