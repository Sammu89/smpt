<?php
/**
 * Front-end theme styles.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue migrated custom styles.
 */
function smpt_generatepress_enqueue_styles() {
	$theme   = wp_get_theme();
	$version = $theme->get( 'Version' );

	wp_enqueue_style(
		'noticias-style',
		get_stylesheet_directory_uri() . '/css/noticias.css',
		array( 'generate-style' ),
		$version
	);
	wp_enqueue_style(
		'infobox-style',
		get_stylesheet_directory_uri() . '/css/infobox.css',
		array( 'generate-style' ),
		$version
	);
	wp_enqueue_style(
		'headers-style',
		get_stylesheet_directory_uri() . '/css/headers.css',
		array( 'generate-style' ),
		$version
	);
	wp_enqueue_style(
		'smpt-header-style',
		get_stylesheet_directory_uri() . '/css/header.css',
		array( 'generate-style' ),
		$version
	);
	wp_enqueue_style(
		'botoes-style',
		get_stylesheet_directory_uri() . '/css/botoes_e_links.css',
		array( 'generate-style' ),
		$version
	);
}
add_action( 'wp_enqueue_scripts', 'smpt_generatepress_enqueue_styles' );

/**
 * Enqueue child-theme front-end scripts.
 */
function smpt_generatepress_enqueue_scripts() {
	$theme   = wp_get_theme();
	$version = $theme->get( 'Version' );

	wp_enqueue_script(
		'smpt-sticky-nav',
		get_stylesheet_directory_uri() . '/javascript/sticky-nav.js',
		array(),
		$version,
		true
	);

	wp_enqueue_script(
		'smpt-header-sky',
		get_stylesheet_directory_uri() . '/javascript/header-sky.js',
		array(),
		$version,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'smpt_generatepress_enqueue_scripts' );
