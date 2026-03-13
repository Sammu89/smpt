<?php
/**
 * Local analytics tracking — replaces Google Analytics with MySQL-based events.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SMPT_ANALYTICS_DB_VERSION', '1.0' );

/**
 * Create or update analytics database tables.
 */
function smpt_analytics_maybe_create_tables() {
	$installed_version = get_option( 'smpt_analytics_db_version', '' );

	if ( SMPT_ANALYTICS_DB_VERSION === $installed_version ) {
		return;
	}

	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$wpdb->prefix}smpt_visitors (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		visitor_hash VARCHAR(64) NOT NULL,
		user_id BIGINT UNSIGNED DEFAULT NULL,
		country VARCHAR(2) NOT NULL DEFAULT '',
		device_type VARCHAR(20) NOT NULL DEFAULT '',
		os VARCHAR(50) NOT NULL DEFAULT '',
		browser VARCHAR(50) NOT NULL DEFAULT '',
		screen_width SMALLINT UNSIGNED NOT NULL DEFAULT 0,
		screen_height SMALLINT UNSIGNED NOT NULL DEFAULT 0,
		language VARCHAR(10) NOT NULL DEFAULT '',
		connection VARCHAR(10) NOT NULL DEFAULT '',
		first_seen DATETIME NOT NULL,
		last_seen DATETIME NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY visitor_hash (visitor_hash)
	) $charset_collate;

	CREATE TABLE {$wpdb->prefix}smpt_events (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		visitor_id BIGINT UNSIGNED NOT NULL,
		event_type VARCHAR(20) NOT NULL,
		item_id VARCHAR(100) NOT NULL DEFAULT '',
		page_url VARCHAR(255) NOT NULL DEFAULT '',
		referrer VARCHAR(255) NOT NULL DEFAULT '',
		watch_seconds INT UNSIGNED NOT NULL DEFAULT 0,
		milestone TINYINT UNSIGNED NOT NULL DEFAULT 0,
		created_at DATETIME NOT NULL,
		PRIMARY KEY (id),
		KEY visitor_id (visitor_id),
		KEY event_type (event_type),
		KEY created_at (created_at)
	) $charset_collate;

	CREATE TABLE {$wpdb->prefix}smpt_counters (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		event_type VARCHAR(20) NOT NULL,
		item_id VARCHAR(100) NOT NULL DEFAULT '',
		total_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		UNIQUE KEY event_item (event_type, item_id)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	update_option( 'smpt_analytics_db_version', SMPT_ANALYTICS_DB_VERSION );
}
add_action( 'init', 'smpt_analytics_maybe_create_tables' );

/**
 * Detect country from Cloudflare header or fallback.
 *
 * @return string Two-letter country code.
 */
function smpt_analytics_get_country() {
	$cf_country = isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ? strtoupper( trim( (string) $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) : '';

	if ( preg_match( '/^[A-Z]{2}$/', $cf_country ) ) {
		return $cf_country;
	}

	if ( function_exists( 'smpt_lookup_country_code' ) ) {
		$ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? trim( (string) $_SERVER['REMOTE_ADDR'] ) : '';
		$lookup = smpt_lookup_country_code( $ip );
		return $lookup['country'];
	}

	return '';
}

/**
 * Register REST API routes for analytics tracking.
 */
function smpt_analytics_register_routes() {
	register_rest_route( 'smpt/v1', '/track', array(
		'methods'             => 'POST',
		'callback'            => 'smpt_rest_handle_track',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'smpt/v1', '/watch', array(
		'methods'             => 'POST',
		'callback'            => 'smpt_rest_handle_watch',
		'permission_callback' => '__return_true',
	) );
}
add_action( 'rest_api_init', 'smpt_analytics_register_routes' );

/**
 * Handle POST /smpt/v1/track — log an analytics event.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response
 */
function smpt_rest_handle_track( WP_REST_Request $request ) {
	global $wpdb;

	$body = json_decode( $request->get_body(), true );
	if ( ! is_array( $body ) ) {
		return new WP_REST_Response( array( 'ok' => false ), 400 );
	}

	$visitor_hash = isset( $body['visitor_hash'] ) ? sanitize_text_field( $body['visitor_hash'] ) : '';
	$event_type   = isset( $body['event_type'] ) ? sanitize_text_field( $body['event_type'] ) : '';
	$item_id      = isset( $body['item_id'] ) ? sanitize_text_field( $body['item_id'] ) : '';

	if ( '' === $visitor_hash || '' === $event_type ) {
		return new WP_REST_Response( array( 'ok' => false ), 400 );
	}

	$allowed_events = array( 'stream', 'download', 'music_stream', 'nostalgia_play', 'manga_view', 'manga_download' );
	if ( ! in_array( $event_type, $allowed_events, true ) ) {
		return new WP_REST_Response( array( 'ok' => false ), 400 );
	}

	// Rate limiting: skip if same visitor+event+item within last 5 seconds.
	$recent = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->prefix}smpt_events e
		 INNER JOIN {$wpdb->prefix}smpt_visitors v ON v.id = e.visitor_id
		 WHERE v.visitor_hash = %s AND e.event_type = %s AND e.item_id = %s
		 AND e.created_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)",
		$visitor_hash,
		$event_type,
		$item_id
	) );

	if ( $recent > 0 ) {
		return new WP_REST_Response( array( 'ok' => true, 'skipped' => true ) );
	}

	$meta        = isset( $body['meta'] ) && is_array( $body['meta'] ) ? $body['meta'] : array();
	$device_type = isset( $meta['device_type'] ) ? sanitize_text_field( $meta['device_type'] ) : '';
	$os          = isset( $meta['os'] ) ? sanitize_text_field( substr( $meta['os'], 0, 50 ) ) : '';
	$browser     = isset( $meta['browser'] ) ? sanitize_text_field( substr( $meta['browser'], 0, 50 ) ) : '';
	$screen_w    = isset( $meta['screen_width'] ) ? absint( $meta['screen_width'] ) : 0;
	$screen_h    = isset( $meta['screen_height'] ) ? absint( $meta['screen_height'] ) : 0;
	$language    = isset( $meta['language'] ) ? sanitize_text_field( substr( $meta['language'], 0, 10 ) ) : '';
	$connection  = isset( $meta['connection'] ) ? sanitize_text_field( substr( $meta['connection'], 0, 10 ) ) : '';
	$page_url    = isset( $body['page_url'] ) ? sanitize_text_field( substr( $body['page_url'], 0, 255 ) ) : '';
	$referrer    = isset( $body['referrer'] ) ? sanitize_text_field( substr( $body['referrer'], 0, 255 ) ) : '';

	$country = smpt_analytics_get_country();
	$user_id = get_current_user_id();
	$now     = current_time( 'mysql' );

	// Visitor upsert.
	$wpdb->query( $wpdb->prepare(
		"INSERT INTO {$wpdb->prefix}smpt_visitors
			(visitor_hash, user_id, country, device_type, os, browser, screen_width, screen_height, language, `connection`, first_seen, last_seen)
		 VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %s, %s, %s, %s)
		 ON DUPLICATE KEY UPDATE
			last_seen = %s,
			user_id = COALESCE(VALUES(user_id), user_id),
			country = IF(VALUES(country) != '', VALUES(country), country),
			device_type = IF(VALUES(device_type) != '', VALUES(device_type), device_type),
			os = IF(VALUES(os) != '', VALUES(os), os),
			browser = IF(VALUES(browser) != '', VALUES(browser), browser),
			screen_width = IF(VALUES(screen_width) > 0, VALUES(screen_width), screen_width),
			screen_height = IF(VALUES(screen_height) > 0, VALUES(screen_height), screen_height),
			language = IF(VALUES(language) != '', VALUES(language), language),
			`connection` = IF(VALUES(`connection`) != '', VALUES(`connection`), `connection`)",
		$visitor_hash,
		$user_id > 0 ? $user_id : null,
		$country,
		$device_type,
		$os,
		$browser,
		$screen_w,
		$screen_h,
		$language,
		$connection,
		$now,
		$now,
		$now
	) );

	// Get visitor ID.
	$visitor_id = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM {$wpdb->prefix}smpt_visitors WHERE visitor_hash = %s",
		$visitor_hash
	) );

	if ( ! $visitor_id ) {
		return new WP_REST_Response( array( 'ok' => false ), 500 );
	}

	// Insert event.
	$wpdb->insert(
		$wpdb->prefix . 'smpt_events',
		array(
			'visitor_id'    => $visitor_id,
			'event_type'    => $event_type,
			'item_id'       => $item_id,
			'page_url'      => $page_url,
			'referrer'      => $referrer,
			'watch_seconds' => 0,
			'milestone'     => 0,
			'created_at'    => $now,
		),
		array( '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
	);

	$event_id = $wpdb->insert_id;

	// Counter increment.
	$wpdb->query( $wpdb->prepare(
		"INSERT INTO {$wpdb->prefix}smpt_counters (event_type, item_id, total_count)
		 VALUES (%s, %s, 1)
		 ON DUPLICATE KEY UPDATE total_count = total_count + 1",
		$event_type,
		$item_id
	) );

	return new WP_REST_Response( array( 'ok' => true, 'event_id' => $event_id ) );
}

/**
 * Handle POST /smpt/v1/watch — update watch time and milestone on an existing event.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response
 */
function smpt_rest_handle_watch( WP_REST_Request $request ) {
	global $wpdb;

	$body = json_decode( $request->get_body(), true );
	if ( ! is_array( $body ) ) {
		return new WP_REST_Response( array( 'ok' => false ), 400 );
	}

	$event_id      = isset( $body['event_id'] ) ? absint( $body['event_id'] ) : 0;
	$visitor_hash  = isset( $body['visitor_hash'] ) ? sanitize_text_field( $body['visitor_hash'] ) : '';
	$watch_seconds = isset( $body['watch_seconds'] ) ? absint( $body['watch_seconds'] ) : 0;
	$milestone     = isset( $body['milestone'] ) ? absint( $body['milestone'] ) : 0;

	if ( 0 === $event_id || '' === $visitor_hash ) {
		return new WP_REST_Response( array( 'ok' => false ), 400 );
	}

	// Clamp milestone to valid values.
	$valid_milestones = array( 0, 25, 50, 75, 100 );
	if ( ! in_array( $milestone, $valid_milestones, true ) ) {
		$milestone = 0;
	}

	// Update using GREATEST to never lose progress. Verify visitor ownership.
	$updated = $wpdb->query( $wpdb->prepare(
		"UPDATE {$wpdb->prefix}smpt_events e
		 INNER JOIN {$wpdb->prefix}smpt_visitors v ON v.id = e.visitor_id
		 SET e.watch_seconds = GREATEST(e.watch_seconds, %d),
		     e.milestone = GREATEST(e.milestone, %d)
		 WHERE e.id = %d AND v.visitor_hash = %s",
		$watch_seconds,
		$milestone,
		$event_id,
		$visitor_hash
	) );

	return new WP_REST_Response( array( 'ok' => true, 'updated' => $updated !== false ) );
}
