<?php
/**
 * Custom branded header hero for the child theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replace the default GeneratePress branding inside the header with the custom hero.
 */
function smpt_register_header_hero() {
	remove_action( 'generate_before_header_content', 'generate_do_site_logo', 5 );
	remove_action( 'generate_before_header_content', 'generate_do_site_branding', 10 );
	remove_action( 'generate_after_header_content', 'generate_do_header_widget', 10 );

	add_action( 'generate_before_header_content', 'smpt_render_header_hero', 8 );
}
add_action( 'after_setup_theme', 'smpt_register_header_hero', 20 );

/**
 * Render the branded header hero markup.
 */
function smpt_render_header_hero() {
	get_template_part( 'template-parts/header/hero' );
}
