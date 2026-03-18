<?php
/**
 * GeneratePress Child Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$smpt_theme_includes = array(
	'/templates/tabelas_episodios.php',
	'/inc/assets.php',
	'/inc/featured-images.php',
	'/inc/featured-video.php',
	'/inc/header-hero.php',
	'/inc/infoboxes.php',
	'/inc/navigation.php',
	'/inc/page-nav.php',
	'/inc/tables.php',
);

foreach ( $smpt_theme_includes as $smpt_theme_include ) {
	require_once get_stylesheet_directory() . $smpt_theme_include;
}
