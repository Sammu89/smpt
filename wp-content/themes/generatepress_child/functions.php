<?php
/**
 * GeneratePress Child Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$smpt_theme_includes = array(
	'/inc/assets.php',
	'/inc/header-hero.php',
	'/inc/navigation.php',
);

foreach ( $smpt_theme_includes as $smpt_theme_include ) {
	require_once get_stylesheet_directory() . $smpt_theme_include;
}
