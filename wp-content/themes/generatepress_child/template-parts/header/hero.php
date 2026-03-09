<?php
/**
 * Template part for the branded site hero inside the GeneratePress header.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$smpt_logo_path   = get_stylesheet_directory() . '/imagens/logo.png';
$smpt_senshi_path = get_stylesheet_directory() . '/imagens/senshi.png';

$smpt_logo_uri   = get_stylesheet_directory_uri() . '/imagens/logo.png';
$smpt_senshi_uri = get_stylesheet_directory_uri() . '/imagens/senshi.png';

$smpt_logo_size   = file_exists( $smpt_logo_path ) ? getimagesize( $smpt_logo_path ) : false;
$smpt_senshi_size = file_exists( $smpt_senshi_path ) ? getimagesize( $smpt_senshi_path ) : false;
?>
<div class="smpt-header-hero">
	<div class="smpt-hero-header">
		<div class="smpt-header-hero__logo">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<img
					src="<?php echo esc_url( $smpt_logo_uri ); ?>"
					alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
					<?php if ( $smpt_logo_size ) : ?>
						width="<?php echo esc_attr( $smpt_logo_size[0] ); ?>"
						height="<?php echo esc_attr( $smpt_logo_size[1] ); ?>"
					<?php endif; ?>
					loading="eager"
					fetchpriority="high"
				>
			</a>
		</div>

		<div class="smpt-hero-header__image" aria-hidden="true">
			<img
				src="<?php echo esc_url( $smpt_senshi_uri ); ?>"
				alt=""
				<?php if ( $smpt_senshi_size ) : ?>
					width="<?php echo esc_attr( $smpt_senshi_size[0] ); ?>"
					height="<?php echo esc_attr( $smpt_senshi_size[1] ); ?>"
				<?php endif; ?>
				loading="eager"
				fetchpriority="high"
			>
		</div>
	</div>
</div>
