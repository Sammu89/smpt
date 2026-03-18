<?php
/**
 * Loads site feature modules and shortcodes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine whether the current request is for one of the episode pages.
 *
 * Defined here (mu-plugins) so it is available before the theme loads.
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

require_once SMPT_SITE_PLUGIN_PATH . '/php/shortcodes.php';
require_once SMPT_SITE_PLUGIN_PATH . '/php/metadata_episodios.php';
require_once SMPT_SITE_PLUGIN_PATH . '/php/teste.php';
require_once SMPT_SITE_PLUGIN_PATH . '/php/episode-interactions.php';
require_once SMPT_SITE_PLUGIN_PATH . '/php/comment-protection.php';
