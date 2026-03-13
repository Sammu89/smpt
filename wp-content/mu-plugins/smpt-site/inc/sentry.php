<?php
/**
 * Sentry bootstrap for SMPT custom code.
 *
 * Configuration placeholders (set in wp-config.php or env):
 * - SMPT_SENTRY_DSN
 * - SMPT_SENTRY_ENVIRONMENT
 * - SMPT_SENTRY_RELEASE
 * - SMPT_SENTRY_TRACES_SAMPLE_RATE
 * - SMPT_SENTRY_BROWSER_SDK_VERSION
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read a string value from environment variables.
 *
 * @param string $key Environment variable name.
 * @return string
 */
function smpt_sentry_read_env( $key ) {
	$value = getenv( (string) $key );

	if ( ! is_string( $value ) ) {
		return '';
	}

	return trim( $value );
}

/**
 * Get Sentry DSN from constants/environment.
 *
 * @return string
 */
function smpt_sentry_get_dsn() {
	if ( defined( 'SMPT_SENTRY_DSN' ) && is_string( SMPT_SENTRY_DSN ) && '' !== trim( SMPT_SENTRY_DSN ) ) {
		return trim( SMPT_SENTRY_DSN );
	}

	$dsn = smpt_sentry_read_env( 'SMPT_SENTRY_DSN' );

	if ( '' !== $dsn ) {
		return $dsn;
	}

	return smpt_sentry_read_env( 'SENTRY_DSN' );
}

/**
 * Get environment name for Sentry events.
 *
 * @return string
 */
function smpt_sentry_get_environment() {
	if ( defined( 'SMPT_SENTRY_ENVIRONMENT' ) && is_string( SMPT_SENTRY_ENVIRONMENT ) && '' !== trim( SMPT_SENTRY_ENVIRONMENT ) ) {
		return trim( SMPT_SENTRY_ENVIRONMENT );
	}

	$environment = smpt_sentry_read_env( 'SMPT_SENTRY_ENVIRONMENT' );

	if ( '' !== $environment ) {
		return $environment;
	}

	if ( function_exists( 'wp_get_environment_type' ) ) {
		return (string) wp_get_environment_type();
	}

	return 'production';
}

/**
 * Get release identifier for Sentry events.
 *
 * @return string
 */
function smpt_sentry_get_release() {
	if ( defined( 'SMPT_SENTRY_RELEASE' ) && is_string( SMPT_SENTRY_RELEASE ) && '' !== trim( SMPT_SENTRY_RELEASE ) ) {
		return trim( SMPT_SENTRY_RELEASE );
	}

	$release = smpt_sentry_read_env( 'SMPT_SENTRY_RELEASE' );

	if ( '' !== $release ) {
		return $release;
	}

	return smpt_sentry_read_env( 'SENTRY_RELEASE' );
}

/**
 * Get tracing sample rate with conservative production defaults.
 *
 * @return float
 */
function smpt_sentry_get_traces_sample_rate() {
	$value = null;

	if ( defined( 'SMPT_SENTRY_TRACES_SAMPLE_RATE' ) ) {
		$value = SMPT_SENTRY_TRACES_SAMPLE_RATE;
	} else {
		$env_value = smpt_sentry_read_env( 'SMPT_SENTRY_TRACES_SAMPLE_RATE' );
		$value     = '' === $env_value ? null : $env_value;
	}

	if ( null === $value || '' === $value ) {
		$is_production = 'production' === smpt_sentry_get_environment();
		return $is_production ? 0.05 : 0.20;
	}

	$rate = (float) $value;

	if ( $rate < 0 ) {
		$rate = 0.0;
	}

	if ( $rate > 1 ) {
		$rate = 1.0;
	}

	return $rate;
}

/**
 * Whether Sentry has enough configuration to initialize.
 *
 * @return bool
 */
function smpt_sentry_is_enabled() {
	return '' !== smpt_sentry_get_dsn();
}

/**
 * Shared browser configuration for JS initialization.
 *
 * @return array
 */
function smpt_sentry_get_js_config() {
	return array(
		'dsn'              => smpt_sentry_get_dsn(),
		'environment'      => smpt_sentry_get_environment(),
		'release'          => smpt_sentry_get_release(),
		'tracesSampleRate' => smpt_sentry_get_traces_sample_rate(),
	);
}

/**
 * Initialize PHP SDK as early as possible in MU plugin load.
 *
 * @return void
 */
function smpt_sentry_bootstrap_php() {
	if ( ! smpt_sentry_is_enabled() ) {
		return;
	}

	$autoload_path = SMPT_SITE_PLUGIN_PATH . '/vendor/autoload.php';

	if ( ! file_exists( $autoload_path ) ) {
		return;
	}

	require_once $autoload_path;

	if ( ! function_exists( '\Sentry\init' ) ) {
		return;
	}

	$options = array(
		'dsn'                => smpt_sentry_get_dsn(),
		'environment'        => smpt_sentry_get_environment(),
		'traces_sample_rate' => smpt_sentry_get_traces_sample_rate(),
		'attach_stacktrace'  => true,
		'send_default_pii'   => false,
	);

	$release = smpt_sentry_get_release();

	if ( '' !== $release ) {
		$options['release'] = $release;
	}

	\Sentry\init( $options );
}
smpt_sentry_bootstrap_php();
