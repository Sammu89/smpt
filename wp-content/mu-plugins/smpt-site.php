<?php
/**
 * Plugin Name: SMPT Site Core
 * Description: Must-use site functionality for Sailor Moon Portugal.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SMPT_SITE_PLUGIN_PATH', WPMU_PLUGIN_DIR . '/smpt-site' );
define( 'SMPT_SITE_PLUGIN_URL', WPMU_PLUGIN_URL . '/smpt-site' );

/**
 * Build a URL for a file inside the SMPT mu-plugin.
 *
 * @param string $relative_path Relative path inside the plugin directory.
 * @return string
 */
function smpt_site_plugin_url( $relative_path = '' ) {
	return SMPT_SITE_PLUGIN_URL . '/' . ltrim( $relative_path, '/' );
}

$smpt_site_includes = array(
	'/inc/core.php',
	'/inc/cli.php',
	'/inc/assets.php',
	'/inc/head-assets.php',
	'/inc/access-control.php',
	'/inc/access-preview.php',
	'/inc/content-protection.php',
	'/inc/member-area.php',
	'/inc/modules.php',
	'/inc/analytics.php',
	'/inc/analytics-dashboard.php',
);

foreach ( $smpt_site_includes as $smpt_site_include ) {
	require_once SMPT_SITE_PLUGIN_PATH . $smpt_site_include;
}
