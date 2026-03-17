<?php
/**
 * Front-end theme styles.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return a cache-busting version string for a child-theme asset.
 *
 * @param string $relative_path Asset path relative to the child theme root.
 * @param string $fallback      Fallback version when the file is missing.
 * @return string
 */
function smpt_child_asset_version( $relative_path, $fallback ) {
	$asset_path = get_stylesheet_directory() . '/' . ltrim( $relative_path, '/' );

	if ( file_exists( $asset_path ) ) {
		return (string) filemtime( $asset_path );
	}

	return $fallback;
}

/**
 * Determine whether the current request is for one of the episode pages.
 *
 * @return bool
 */
function smpt_is_episode_page() {
	if ( ! is_page() ) {
		return false;
	}

	return is_page(
		array(
			'episodios-s01',
			'episodios-s02',
			'episodios-s03',
			'episodios-s04',
			'episodios-s05',
		)
	);
}

/**
 * Enqueue migrated custom styles.
 */
function smpt_generatepress_enqueue_styles() {
	$theme           = wp_get_theme();
	$version         = $theme->get( 'Version' );
	$noticias_ver    = smpt_child_asset_version( 'css/noticias.css', $version );
	$infobox_ver     = smpt_child_asset_version( 'css/infobox.css', $version );
	$headers_ver     = smpt_child_asset_version( 'css/headers.css', $version );
	$header_ver      = smpt_child_asset_version( 'css/header.css', $version );
	$member_area_ver = smpt_child_asset_version( 'css/member-area.css', $version );
	$botoes_ver      = smpt_child_asset_version( 'css/botoes_e_links.css', $version );
	$page_nav_ver    = smpt_child_asset_version( 'css/page-nav.css', $version );
	$episodios_ver   = smpt_child_asset_version( 'css/episodios.css', $version );

	wp_enqueue_style( 'dashicons' );

	wp_enqueue_style(
		'noticias-style',
		get_stylesheet_directory_uri() . '/css/noticias.css',
		array( 'generate-style' ),
		$noticias_ver
	);
	wp_enqueue_style(
		'infobox-style',
		get_stylesheet_directory_uri() . '/css/infobox.css',
		array( 'generate-style' ),
		$infobox_ver
	);
	wp_enqueue_style(
		'headers-style',
		get_stylesheet_directory_uri() . '/css/headers.css',
		array( 'generate-style' ),
		$headers_ver
	);
	wp_enqueue_style(
		'smpt-header-style',
		get_stylesheet_directory_uri() . '/css/header.css',
		array( 'generate-style' ),
		$header_ver
	);
	wp_enqueue_style(
		'smpt-member-area-style',
		get_stylesheet_directory_uri() . '/css/member-area.css',
		array( 'generate-style' ),
		$member_area_ver
	);
	wp_enqueue_style(
		'botoes-style',
		get_stylesheet_directory_uri() . '/css/botoes_e_links.css',
		array( 'generate-style' ),
		$botoes_ver
	);
	wp_enqueue_style(
		'smpt-page-nav-style',
		get_stylesheet_directory_uri() . '/css/page-nav.css',
		array( 'generate-style' ),
		$page_nav_ver
	);

	if ( smpt_is_episode_page() ) {
		wp_enqueue_style(
			'smpt-episodios-style',
			get_stylesheet_directory_uri() . '/css/episodios.css',
			array( 'generate-style' ),
			$episodios_ver
		);
		wp_enqueue_style(
			'smpt-episode-interactions-style',
			get_stylesheet_directory_uri() . '/css/episode-interactions.css',
			array( 'smpt-episodios-style' ),
			smpt_child_asset_version( 'css/episode-interactions.css', $version )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'smpt_generatepress_enqueue_styles' );

/**
 * Enqueue child-theme front-end scripts.
 */
function smpt_generatepress_enqueue_scripts() {
	$theme          = wp_get_theme();
	$version        = $theme->get( 'Version' );
	$day_key        = wp_date( 'Y-m-d' );
	$sticky_nav_ver = smpt_child_asset_version( 'javascript/sticky-nav.js', $version );
	$hero_sky_ver   = smpt_child_asset_version( 'javascript/hero-header-animation.js', $version );
	$featured_ver   = smpt_child_asset_version( 'javascript/featured-video.js', $version );
	$infobox_ver    = smpt_child_asset_version( 'javascript/infobox.js', $version );
	$episodios_ver  = smpt_child_asset_version( 'javascript/video.js', $version );
	$columns_ver    = smpt_child_asset_version( 'javascript/content-columns.js', $version );

	wp_enqueue_script(
		'smpt-sticky-nav',
		get_stylesheet_directory_uri() . '/javascript/sticky-nav.js',
		array(),
		$sticky_nav_ver,
		true
	);

	wp_enqueue_script(
		'smpt-header-sky',
		get_stylesheet_directory_uri() . '/javascript/hero-header-animation.js',
		array(),
		$hero_sky_ver,
		true
	);

	wp_add_inline_script(
		'smpt-header-sky',
		'window.smptHeaderSeed = ' . wp_json_encode( $day_key ) . ';',
		'before'
	);

	wp_enqueue_script(
		'smpt-infobox',
		get_stylesheet_directory_uri() . '/javascript/infobox.js',
		array(),
		$infobox_ver,
		true
	);

	wp_enqueue_script(
		'smpt-content-columns',
		get_stylesheet_directory_uri() . '/javascript/content-columns.js',
		array(),
		$columns_ver,
		true
	);

	if ( function_exists( 'smpt_page_has_featured_video' ) && is_page() && smpt_page_has_featured_video( get_queried_object_id() ) ) {
		wp_enqueue_script(
			'smpt-featured-video',
			get_stylesheet_directory_uri() . '/javascript/featured-video.js',
			array(),
			$featured_ver,
			true
		);
	}

	if ( smpt_is_episode_page() ) {
		wp_enqueue_script(
			'smpt-episodios',
			get_stylesheet_directory_uri() . '/javascript/video.js',
			array( 'jquery' ),
			$episodios_ver,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'smpt_generatepress_enqueue_scripts' );
