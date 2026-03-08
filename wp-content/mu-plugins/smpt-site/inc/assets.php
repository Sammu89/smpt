<?php
/**
 * Front-end scripts for site functionality.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy news DOM script kept for reference only.
 *
 * This is intentionally not enqueued on the live site.
 */
function smpt_enqueue_news_script() {
	if ( is_home() || ( is_single() && 'post' === get_post_type() ) || is_category() || is_tag() ) {
		wp_enqueue_script(
			'pagina-noticias-script',
			smpt_site_plugin_url( 'javascript/noticias.js' ),
			array( 'jquery' ),
			null,
			true
		);
	}
}
// Legacy only: do not load on the live site.
// add_action( 'wp_enqueue_scripts', 'smpt_enqueue_news_script' );
