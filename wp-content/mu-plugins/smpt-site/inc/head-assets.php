<?php
/**
 * Head assets: fonts, analytics, and global tracking helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue third-party assets loaded in <head>.
 */
function smpt_enqueue_external_head_assets() {
	wp_enqueue_script(
		'smpt-fontawesome-kit',
		'https://kit.fontawesome.com/ddf2ba72f8.js',
		array(),
		null,
		false
	);
	wp_script_add_data( 'smpt-fontawesome-kit', 'crossorigin', 'anonymous' );

	// Google Analytics (pageview tracking only — no events).
	wp_enqueue_script(
		'smpt-gtag',
		'https://www.googletagmanager.com/gtag/js?id=G-GG2KC4SYW9',
		array(),
		null,
		false
	);
	wp_add_inline_script( 'smpt-gtag', "window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','G-GG2KC4SYW9');" );

	// Local analytics (event tracking).
	wp_enqueue_script(
		'smpt-analytics',
		smpt_site_plugin_url( 'javascript/analytics.js' ),
		array(),
		'1.0',
		false
	);
	wp_localize_script( 'smpt-analytics', 'smptAnalytics', array(
		'rest_url'  => rest_url( 'smpt/v1/track' ),
		'watch_url' => rest_url( 'smpt/v1/watch' ),
	) );

	wp_enqueue_script(
		'smpt-watch-tracker',
		smpt_site_plugin_url( 'javascript/watch-tracker.js' ),
		array( 'smpt-analytics' ),
		'1.0',
		true
	);

	// Thin wrappers for onclick handlers stored in post content (DB).
	$inline_tracking_js = <<<'JS'
function trackDownload(id){smptTrack('download',id);}
function mangaview(id){smptTrack('manga_view',id);}
function manga(id){smptTrack('manga_download',id);}
JS;
	wp_add_inline_script( 'smpt-analytics', $inline_tracking_js );

	// Nostalgia TV tracking (only on that page).
	if ( is_page( 'nostalgia-tv' ) ) {
		wp_enqueue_script(
			'smpt-nostalgia-tracker',
			smpt_site_plugin_url( 'javascript/nostalgia-tracker.js' ),
			array( 'smpt-analytics' ),
			'1.0',
			true
		);
	}

	// Episode interactions (likes, ratings, comments, watched).
	if ( smpt_is_episode_page() ) {
		wp_enqueue_script(
			'smpt-episode-interactions',
			smpt_site_plugin_url( 'javascript/episode-interactions.js' ),
			array( 'jquery', 'smpt-analytics' ),
			filemtime( SMPT_SITE_PLUGIN_PATH . '/javascript/episode-interactions.js' ),
			true
		);
		wp_localize_script( 'smpt-episode-interactions', 'smptEpInteractions', array(
			'restBase'  => rest_url( 'smpt/v1/' ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'userId'    => get_current_user_id(),
			'needsSync' => (bool) get_transient( 'smpt_ep_needs_sync_' . get_current_user_id() ),
			'userName'  => wp_get_current_user()->display_name ?: '',
		) );
	}
}
add_action( 'wp_enqueue_scripts', 'smpt_enqueue_external_head_assets', 20 );
