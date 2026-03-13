<?php
/**
 * Head assets: fonts, analytics, and global tracking helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Sentry browser SDK and initialize it early.
 *
 * Loads for both public and admin screens through enqueue hooks.
 *
 * @return void
 */
function smpt_enqueue_sentry_browser_assets() {
	if ( ! function_exists( 'smpt_sentry_get_js_config' ) ) {
		return;
	}

	$config = smpt_sentry_get_js_config();

	if ( empty( $config['dsn'] ) ) {
		return;
	}

	$sdk_version = defined( 'SMPT_SENTRY_BROWSER_SDK_VERSION' ) ? (string) SMPT_SENTRY_BROWSER_SDK_VERSION : '10.20.0';
	$sdk_url     = sprintf(
		'https://browser.sentry-cdn.com/%s/bundle.tracing.min.js',
		rawurlencode( $sdk_version )
	);

	wp_enqueue_script(
		'smpt-sentry-browser',
		$sdk_url,
		array(),
		null,
		false
	);
	wp_script_add_data( 'smpt-sentry-browser', 'crossorigin', 'anonymous' );

	$init_config = array(
		'dsn'              => (string) $config['dsn'],
		'environment'      => (string) $config['environment'],
		'tracesSampleRate' => (float) $config['tracesSampleRate'],
	);

	if ( ! empty( $config['release'] ) ) {
		$init_config['release'] = (string) $config['release'];
	}

	$init_script = 'if(window.Sentry){var cfg=' . wp_json_encode( $init_config ) . ';if(typeof window.Sentry.browserTracingIntegration===\'function\'){cfg.integrations=[window.Sentry.browserTracingIntegration()];}window.Sentry.init(cfg);}';

	wp_add_inline_script(
		'smpt-sentry-browser',
		$init_script,
		'after'
	);
}
add_action( 'wp_enqueue_scripts', 'smpt_enqueue_sentry_browser_assets', 1 );
add_action( 'admin_enqueue_scripts', 'smpt_enqueue_sentry_browser_assets', 1 );

/**
 * Enqueue third-party assets loaded in <head>.
 */
function smpt_enqueue_external_head_assets() {
	wp_enqueue_style(
		'smpt-font-atma',
		'https://fonts.googleapis.com/css2?family=Atma:wght@300;400;500;600;700&display=swap',
		array(),
		null
	);
	wp_enqueue_style(
		'smpt-font-yanone',
		'https://fonts.googleapis.com/css?family=Yanone+Kaffeesatz',
		array(),
		null
	);

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
}
add_action( 'wp_enqueue_scripts', 'smpt_enqueue_external_head_assets', 20 );
