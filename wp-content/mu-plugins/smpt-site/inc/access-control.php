<?php
/**
 * Access-control decision helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'SMPT_ACCESS_COOKIE' ) ) {
	define( 'SMPT_ACCESS_COOKIE', 'smpt_visit_gate' );
}

if ( ! defined( 'SMPT_ACCESS_COOKIE_TTL' ) ) {
	define( 'SMPT_ACCESS_COOKIE_TTL', 3 * HOUR_IN_SECONDS );
}

if ( ! defined( 'SMPT_REQUEST_START' ) ) {
	define( 'SMPT_REQUEST_START', microtime( true ) );
}

if ( ! defined( 'SMPT_REQUEST_ID' ) ) {
	define( 'SMPT_REQUEST_ID', wp_generate_uuid4() );
}

/**
 * Get access-control configuration.
 *
 * @return array
 */
function smpt_get_access_control_config() {
	return array(
		'allowed_ips'       => array( '78.151.197.232' ),
		'denied_ips'        => array(),
		'allowed_countries' => array( 'PT', 'FR', 'BR', 'AO', 'MZ', 'TL', 'CH', 'LU', 'GW', 'ST', 'CV' ),
		'protected_tag'     => 'bloqueado',
		'protected_template'=> 'page-protected.php',
	);
}

/**
 * Get the current visitor IP address.
 *
 * @return string
 */
function smpt_get_visitor_ip() {
	$ip = $_SERVER['REMOTE_ADDR'] ?? '';
	$ip = is_string( $ip ) ? trim( $ip ) : '';

	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
}

/**
 * Check whether an IP is private, loopback, or otherwise non-public.
 *
 * @param string $ip IP address.
 * @return bool
 */
function smpt_is_private_or_reserved_ip( $ip ) {
	if ( '' === $ip ) {
		return false;
	}

	return false === filter_var(
		$ip,
		FILTER_VALIDATE_IP,
		FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
	);
}

/**
 * Log access-control details when debugging is enabled.
 *
 * @param string $message Message to log.
 * @return void
 */
function smpt_access_log( $message ) {
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return;
	}

	error_log( '[smpt-access] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
}

/**
 * Determine whether verbose request tracing should be enabled.
 *
 * @return bool
 */
function smpt_access_trace_enabled() {
	static $enabled = null;

	if ( null !== $enabled ) {
		return $enabled;
	}

	$uri          = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	$referer      = isset( $_SERVER['HTTP_REFERER'] ) ? (string) $_SERVER['HTTP_REFERER'] : '';
	$has_preview  = isset( $_GET['smpt_preview_blocked'] ) || isset( $_REQUEST['smpt_preview_blocked'] ) || isset( $_GET['smpt_preview_toggle'] ) || isset( $_REQUEST['smpt_preview_toggle'] ) || ! empty( $_COOKIE['smpt_preview_blocked'] );
	$is_admin_post = false !== strpos( $uri, 'admin-post.php' ) && false !== strpos( $uri, 'smpt_toggle_blocked_preview' );
	$is_member_path = false !== strpos( $uri, '/download-s03/' ) || false !== strpos( $referer, '/download-s03/' );

	$enabled = $has_preview || $is_admin_post || $is_member_path;

	return $enabled;
}

/**
 * Log a timed trace line for the current request.
 *
 * @param string $phase   Trace phase name.
 * @param array  $context Additional context values.
 * @return void
 */
function smpt_access_trace( $phase, array $context = array() ) {
	if ( ! smpt_access_trace_enabled() ) {
		return;
	}

	$elapsed = defined( 'SMPT_REQUEST_START' ) ? microtime( true ) - SMPT_REQUEST_START : 0;
	$parts   = array(
		'id=' . SMPT_REQUEST_ID,
		sprintf( 't=%.4fs', $elapsed ),
		'phase=' . $phase,
	);

	foreach ( $context as $key => $value ) {
		if ( is_bool( $value ) ) {
			$value = $value ? '1' : '0';
		} elseif ( is_scalar( $value ) ) {
			$value = (string) $value;
		} else {
			$value = wp_json_encode( $value );
		}

		$parts[] = $key . '=' . $value;
	}

	smpt_access_log( '[trace] ' . implode( ' ', $parts ) );
}

/**
 * Log the beginning of traced requests.
 *
 * @return void
 */
function smpt_access_trace_request_start() {
	smpt_access_trace(
		'request_start',
		array(
			'method'        => $_SERVER['REQUEST_METHOD'] ?? 'GET',
			'uri'           => isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/',
			'referer'       => isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '',
			'preview_query' => isset( $_REQUEST['smpt_preview_blocked'] ) ? wp_unslash( $_REQUEST['smpt_preview_blocked'] ) : '',
			'preview_toggle'=> isset( $_REQUEST['smpt_preview_toggle'] ) ? wp_unslash( $_REQUEST['smpt_preview_toggle'] ) : '',
			'preview_cookie'=> isset( $_COOKIE['smpt_preview_blocked'] ) ? wp_unslash( $_COOKIE['smpt_preview_blocked'] ) : '',
		)
	);
}
add_action( 'muplugins_loaded', 'smpt_access_trace_request_start', 1 );

add_action(
	'init',
	static function() {
		smpt_access_trace( 'hook_init' );
	},
	9999
);

add_action(
	'parse_request',
	static function() {
		smpt_access_trace( 'hook_parse_request' );
	},
	9999
);

add_action(
	'wp',
	static function() {
		smpt_access_trace( 'hook_wp' );
	},
	9999
);

add_action(
	'template_redirect',
	static function() {
		smpt_access_trace( 'hook_template_redirect' );
	},
	9999
);

add_filter(
	'template_include',
	static function( $template ) {
		smpt_access_trace( 'hook_template_include', array( 'template' => $template ) );
		return $template;
	},
	9999
);

/**
 * Log late-stage traced requests.
 *
 * @return void
 */
function smpt_access_trace_request_summary() {
	global $wpdb;

	$decision = function_exists( 'smpt_get_visitor_access_decision' ) ? smpt_get_visitor_access_decision() : array();

	smpt_access_trace(
		'request_end',
		array(
			'uri'          => isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/',
			'allowed'      => ! empty( $decision['allowed'] ),
			'source'       => isset( $decision['source'] ) ? $decision['source'] : '',
			'preview'      => function_exists( 'smpt_should_apply_blocked_preview' ) && smpt_should_apply_blocked_preview(),
			'queries'      => isset( $wpdb->num_queries ) ? (int) $wpdb->num_queries : 0,
			'memory_mb'    => round( memory_get_peak_usage( true ) / 1048576, 2 ),
			'is_admin'     => is_admin(),
			'doing_ajax'   => wp_doing_ajax(),
			'doing_cron'   => wp_doing_cron(),
			'status_guess' => http_response_code(),
		)
	);
}
add_action( 'shutdown', 'smpt_access_trace_request_summary', 10000 );

/**
 * Get a stable signature for access-control cookies.
 *
 * @param string $payload Encoded cookie payload.
 * @return string
 */
function smpt_sign_access_payload( $payload ) {
	return hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
}

/**
 * Encode access data into a cookie-safe string.
 *
 * @param array $data Access decision data.
 * @return string
 */
function smpt_encode_access_cookie( array $data ) {
	$payload = wp_json_encode( $data );
	if ( false === $payload ) {
		return '';
	}

	$payload   = rtrim( strtr( base64_encode( $payload ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	$signature = smpt_sign_access_payload( $payload );

	return $payload . '.' . $signature;
}

/**
 * Decode and verify access cookie data.
 *
 * @param string $cookie_value Cookie value to decode.
 * @return array|null
 */
function smpt_decode_access_cookie( $cookie_value ) {
	if ( ! is_string( $cookie_value ) || false === strpos( $cookie_value, '.' ) ) {
		return null;
	}

	list( $payload, $signature ) = explode( '.', $cookie_value, 2 );

	if ( ! hash_equals( smpt_sign_access_payload( $payload ), $signature ) ) {
		return null;
	}

	$decoded = base64_decode( strtr( $payload, '-_', '+/' ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	if ( false === $decoded ) {
		return null;
	}

	$data = json_decode( $decoded, true );
	if ( ! is_array( $data ) ) {
		return null;
	}

	if ( empty( $data['exp'] ) || ! is_numeric( $data['exp'] ) || (int) $data['exp'] < time() ) {
		return null;
	}

	if ( ! array_key_exists( 'allowed', $data ) ) {
		return null;
	}

	$data['allowed'] = (bool) $data['allowed'];
	$data['country'] = isset( $data['country'] ) ? strtoupper( (string) $data['country'] ) : 'UNKNOWN';
	$data['source']  = isset( $data['source'] ) ? (string) $data['source'] : 'cookie';
	$data['exp']     = (int) $data['exp'];

	return $data;
}

/**
 * Persist the access decision in a signed cookie.
 *
 * @param array $decision Access decision data.
 * @return void
 */
function smpt_set_access_cookie( array $decision ) {
	$cookie_value = smpt_encode_access_cookie( $decision );
	if ( '' === $cookie_value ) {
		return;
	}

	setcookie(
		SMPT_ACCESS_COOKIE,
		$cookie_value,
		array(
			'expires'  => (int) $decision['exp'],
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);

	$_COOKIE[ SMPT_ACCESS_COOKIE ] = $cookie_value;
}

/**
 * Check whether the visitor is a blocked archive bot.
 *
 * @return bool
 */
function smpt_is_wayback_bot() {
	$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

	foreach ( array( 'archive.org', 'Wayback Machine', 'ia_archiver' ) as $needle ) {
		if ( false !== stripos( $user_agent, $needle ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Check whether the visitor is a known good bot.
 *
 * @return bool
 */
function smpt_is_known_allowed_bot() {
	$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

	foreach ( array( 'Googlebot', 'Bingbot', 'DuckDuckBot', 'Applebot', 'facebookexternalhit', 'Twitterbot', 'Slackbot', 'LinkedInBot' ) as $needle ) {
		if ( false !== stripos( $user_agent, $needle ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Fetch a country code using the configured provider cascade.
 *
 * @param string $ip Visitor IP address.
 * @return array
 */
function smpt_lookup_country_code( $ip ) {
	$cf_country = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? '';
	$cf_country = strtoupper( trim( (string) $cf_country ) );

	if ( preg_match( '/^[A-Z]{2}$/', $cf_country ) ) {
		return array(
			'country' => $cf_country,
			'source'  => 'cloudflare',
		);
	}

	if ( '' === $ip ) {
		return array(
			'country' => 'UNKNOWN',
			'source'  => 'invalid-ip',
		);
	}

	if ( smpt_is_private_or_reserved_ip( $ip ) ) {
		return array(
			'country' => 'UNKNOWN',
			'source'  => 'private-ip',
		);
	}

	$providers = array(
		array(
			'source' => 'ipwhois',
			'url'    => 'https://ipwho.is/' . rawurlencode( $ip ),
			'parse'  => static function( array $data ) {
				if ( isset( $data['success'] ) && false === $data['success'] ) {
					return '';
				}

				return $data['country_code'] ?? '';
			},
		),
		array(
			'source' => 'ipapi',
			'url'    => 'https://ipapi.co/' . rawurlencode( $ip ) . '/json/',
			'parse'  => static function( array $data ) {
				return $data['country_code'] ?? '';
			},
		),
		array(
			'source' => 'ip-api',
			'url'    => 'http://ip-api.com/json/' . rawurlencode( $ip ) . '?fields=status,countryCode',
			'parse'  => static function( array $data ) {
				if ( empty( $data['status'] ) || 'success' !== $data['status'] ) {
					return '';
				}

				return $data['countryCode'] ?? '';
			},
		),
	);

	foreach ( $providers as $provider ) {
		$response = wp_remote_get(
			$provider['url'],
			array(
				'timeout' => 2,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			smpt_access_log( sprintf( 'Geo provider %s failed: %s', $provider['source'], $response->get_error_message() ) );
			continue;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			smpt_access_log( sprintf( 'Geo provider %s returned invalid JSON.', $provider['source'] ) );
			continue;
		}

		$country = strtoupper( trim( (string) $provider['parse']( $data ) ) );
		if ( preg_match( '/^[A-Z]{2}$/', $country ) ) {
			return array(
				'country' => $country,
				'source'  => $provider['source'],
			);
		}
	}

	return array(
		'country' => 'UNKNOWN',
		'source'  => 'lookup-failed',
	);
}

/**
 * Compute the current visitor access decision.
 *
 * @return array
 */
function smpt_get_visitor_access_decision() {
	static $decision = null;

	if ( null !== $decision ) {
		return $decision;
	}

	$config = smpt_get_access_control_config();
	$now    = time();
	$ip     = smpt_get_visitor_ip();

	if ( function_exists( 'smpt_should_apply_blocked_preview' ) && smpt_should_apply_blocked_preview() ) {
		$decision = array(
			'allowed' => false,
			'country' => 'PREVIEW',
			'source'  => 'admin-preview',
			'exp'     => $now + SMPT_ACCESS_COOKIE_TTL,
		);
		smpt_access_log(
			sprintf(
				'Decision: allowed=no country=PREVIEW source=admin-preview request=%s',
				isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'
			)
		);
		return $decision;
	}

	if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
		$decision = array(
			'allowed' => true,
			'country' => 'LOGGED_IN',
			'source'  => 'logged-in',
			'exp'     => $now + SMPT_ACCESS_COOKIE_TTL,
		);
		smpt_access_log(
			sprintf(
				'Decision: allowed=yes country=LOGGED_IN source=logged-in request=%s',
				isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'
			)
		);
		return $decision;
	}

	if ( smpt_is_wayback_bot() ) {
		$decision = array(
			'allowed' => false,
			'country' => 'BOT',
			'source'  => 'wayback',
			'exp'     => $now + SMPT_ACCESS_COOKIE_TTL,
		);
		smpt_set_access_cookie( $decision );
		return $decision;
	}

	if ( smpt_is_known_allowed_bot() ) {
		$decision = array(
			'allowed' => true,
			'country' => 'BOT',
			'source'  => 'known-bot',
			'exp'     => $now + SMPT_ACCESS_COOKIE_TTL,
		);
		return $decision;
	}

	if ( '' !== $ip && in_array( $ip, $config['denied_ips'], true ) ) {
		$decision = array(
			'allowed' => false,
			'country' => 'DENIED_IP',
			'source'  => 'denied-ip',
			'exp'     => $now + SMPT_ACCESS_COOKIE_TTL,
		);
		smpt_set_access_cookie( $decision );
		return $decision;
	}

	if ( '' !== $ip && in_array( $ip, $config['allowed_ips'], true ) ) {
		$decision = array(
			'allowed' => true,
			'country' => 'ALLOWED_IP',
			'source'  => 'allowed-ip',
			'exp'     => $now + SMPT_ACCESS_COOKIE_TTL,
		);
		smpt_set_access_cookie( $decision );
		return $decision;
	}

	$cookie_name = SMPT_ACCESS_COOKIE;
	if ( isset( $_COOKIE[ $cookie_name ] ) ) {
		$cached = smpt_decode_access_cookie( wp_unslash( $_COOKIE[ $cookie_name ] ) );
		if ( null !== $cached ) {
			$decision = $cached;
			return $decision;
		}
	}

	$lookup   = smpt_lookup_country_code( $ip );
	$country  = $lookup['country'];
	$source   = $lookup['source'];
	$allowed  = 'UNKNOWN' === $country ? true : in_array( $country, $config['allowed_countries'], true );
	$decision = array(
		'allowed' => $allowed,
		'country' => $country,
		'source'  => $source,
		'exp'     => $now + SMPT_ACCESS_COOKIE_TTL,
	);

	smpt_set_access_cookie( $decision );
	smpt_access_log( sprintf( 'Decision: allowed=%s country=%s source=%s', $allowed ? 'yes' : 'no', $country, $source ) );

	return $decision;
}

/**
 * Determine whether the current visitor is allowed.
 *
 * @return bool
 */
function smpt_current_visitor_is_allowed() {
	$decision = smpt_get_visitor_access_decision();
	return ! empty( $decision['allowed'] );
}

/**
 * Log slow front-end requests while debugging.
 *
 * @return void
 */
function smpt_log_slow_frontend_requests() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( ! defined( 'SMPT_REQUEST_START' ) ) {
		return;
	}

	$duration = microtime( true ) - SMPT_REQUEST_START;

	if ( $duration < 1 ) {
		return;
	}

	$decision = smpt_get_visitor_access_decision();
	$request  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';

	smpt_access_log(
		sprintf(
			'Slow request: duration=%.3fs request=%s allowed=%s source=%s preview=%s',
			$duration,
			$request,
			! empty( $decision['allowed'] ) ? 'yes' : 'no',
			isset( $decision['source'] ) ? $decision['source'] : 'unknown',
			( function_exists( 'smpt_should_apply_blocked_preview' ) && smpt_should_apply_blocked_preview() ) ? 'yes' : 'no'
		)
	);
}
add_action( 'shutdown', 'smpt_log_slow_frontend_requests', 9999 );
