<?php
/**
 * Loads site feature modules and shortcodes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once SMPT_SITE_PLUGIN_PATH . '/php/shortcodes.php';
require_once SMPT_SITE_PLUGIN_PATH . '/php/metadata_episodios.php';
require_once SMPT_SITE_PLUGIN_PATH . '/php/tabelas_episodios.php';
require_once SMPT_SITE_PLUGIN_PATH . '/php/teste.php';
require_once SMPT_SITE_PLUGIN_PATH . '/php/episode-interactions.php';
