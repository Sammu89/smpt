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

if ( ! defined( 'SMPT_SLOW_REQUEST_THRESHOLD' ) ) {
	define( 'SMPT_SLOW_REQUEST_THRESHOLD', 1.0 );
}

if ( ! defined( 'SAVEQUERIES' ) ) {
	$smpt_trace_cookie = isset( $_COOKIE['smpt_debug_trace'] ) ? (string) wp_unslash( $_COOKIE['smpt_debug_trace'] ) : '';
	$smpt_trace_toggle = isset( $_GET['smpt_debug_trace_toggle'] ) ? (string) wp_unslash( $_GET['smpt_debug_trace_toggle'] ) : '';

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ( '1' === $smpt_trace_cookie || 'on' === $smpt_trace_toggle ) ) {
		define( 'SAVEQUERIES', true );
	}
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
 * Get the in-memory request profiler state.
 *
 * @return array
 */
function &smpt_access_profiler_state() {
	static $state = array(
		'checkpoints' => array(),
		'meta'        => array(),
	);

	return $state;
}

/**
 * Store request profiler metadata.
 *
 * @param string $key   Metadata key.
 * @param mixed  $value Metadata value.
 * @return void
 */
function smpt_access_profile_set_meta( $key, $value ) {
	$state = &smpt_access_profiler_state();
	$state['meta'][ $key ] = $value;
}

/**
 * Append a value to request profiler metadata.
 *
 * @param string $key   Metadata key.
 * @param mixed  $value Metadata value.
 * @return void
 */
function smpt_access_profile_append_meta( $key, $value ) {
	$state = &smpt_access_profiler_state();

	if ( ! isset( $state['meta'][ $key ] ) || ! is_array( $state['meta'][ $key ] ) ) {
		$state['meta'][ $key ] = array();
	}

	$state['meta'][ $key ][] = $value;
}

/**
 * Record a request checkpoint with timing, query count, and memory usage.
 *
 * @param string $label   Checkpoint label.
 * @param array  $context Additional context values.
 * @return void
 */
function smpt_access_profile_mark_checkpoint( $label, array $context = array() ) {
	global $wpdb;

	$state = &smpt_access_profiler_state();
	$last  = end( $state['checkpoints'] );

	if ( $last && isset( $last['label'] ) && $last['label'] === $label ) {
		return;
	}

	$state['checkpoints'][] = array(
		'label'    => (string) $label,
		't'        => defined( 'SMPT_REQUEST_START' ) ? microtime( true ) - SMPT_REQUEST_START : 0,
		'queries'  => isset( $wpdb->num_queries ) ? (int) $wpdb->num_queries : 0,
		'memory'   => round( memory_get_usage( true ) / 1048576, 2 ),
		'peak'     => round( memory_get_peak_usage( true ) / 1048576, 2 ),
		'context'  => $context,
	);
}

/**
 * Get request profiler checkpoints.
 *
 * @return array
 */
function smpt_access_profile_get_checkpoints() {
	$state = &smpt_access_profiler_state();
	return isset( $state['checkpoints'] ) ? $state['checkpoints'] : array();
}

/**
 * Get request profiler metadata.
 *
 * @return array
 */
function smpt_access_profile_get_meta() {
	$state = &smpt_access_profiler_state();
	return isset( $state['meta'] ) ? $state['meta'] : array();
}

/**
 * Append a timed callback sample to the profiler metadata.
 *
 * @param string $bucket Timing bucket name.
 * @param array  $sample Timed callback sample.
 * @return void
 */
function smpt_access_profile_add_timed_sample( $bucket, array $sample ) {
	$state = &smpt_access_profiler_state();

	if ( ! isset( $state['meta'][ $bucket ] ) || ! is_array( $state['meta'][ $bucket ] ) ) {
		$state['meta'][ $bucket ] = array();
	}

	$state['meta'][ $bucket ][] = $sample;
}

/**
 * Determine whether the current request is the wp-admin dashboard screen.
 *
 * @return bool
 */
function smpt_access_is_admin_dashboard_request() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';

	if ( false === strpos( $uri, '/wp-admin/' ) ) {
		return false;
	}

	if ( false !== strpos( $uri, 'admin-ajax.php' ) || false !== strpos( $uri, 'admin-post.php' ) ) {
		return false;
	}

	if ( false === strpos( $uri, 'index.php' ) && ! preg_match( '#/wp-admin/?(?:\?|$)#', $uri ) ) {
		return false;
	}

	return true;
}

/**
 * Describe a callable for debug output.
 *
 * @param mixed $callable Callable to describe.
 * @return string
 */
function smpt_access_describe_callable( $callable ) {
	if ( is_string( $callable ) ) {
		return $callable;
	}

	if ( is_array( $callable ) && isset( $callable[0], $callable[1] ) ) {
		$class = is_object( $callable[0] ) ? get_class( $callable[0] ) : (string) $callable[0];
		return $class . '::' . $callable[1];
	}

	if ( $callable instanceof Closure ) {
		return 'Closure';
	}

	return 'callable';
}

/**
 * Wrap callbacks on selected hooks to record per-callback timing.
 *
 * @param string $hook_name Hook name.
 * @return void
 */
function smpt_access_wrap_hook_callbacks( $hook_name ) {
	global $wp_filter;

	static $wrapped_hooks = array();

	if ( isset( $wrapped_hooks[ $hook_name ] ) ) {
		return;
	}

	if ( empty( $wp_filter[ $hook_name ] ) || ! $wp_filter[ $hook_name ] instanceof WP_Hook ) {
		$wrapped_hooks[ $hook_name ] = true;
		return;
	}

	foreach ( $wp_filter[ $hook_name ]->callbacks as $priority => &$callbacks ) {
		foreach ( $callbacks as $callback_id => &$callback ) {
			if ( empty( $callback['function'] ) || ! is_callable( $callback['function'] ) ) {
				continue;
			}

			if ( ! empty( $callback['smpt_profile_wrapped'] ) ) {
				continue;
			}

			$original_callable = $callback['function'];
			$description       = smpt_access_describe_callable( $original_callable );
			$accepted_args     = isset( $callback['accepted_args'] ) ? (int) $callback['accepted_args'] : 1;

			$callback['function'] = static function( ...$args ) use ( $original_callable, $hook_name, $priority, $description, $accepted_args ) {
				global $wpdb;

				$start          = microtime( true );
				$queries_before = isset( $wpdb->num_queries ) ? (int) $wpdb->num_queries : 0;
				$result         = call_user_func_array( $original_callable, array_slice( $args, 0, $accepted_args ) );
				$elapsed        = microtime( true ) - $start;
				$queries_after  = isset( $wpdb->num_queries ) ? (int) $wpdb->num_queries : $queries_before;

				smpt_access_profile_add_timed_sample(
					'hook_callback_timings',
					array(
						'hook'      => $hook_name,
						'priority'  => $priority,
						'callback'  => $description,
						'elapsed'   => $elapsed,
						'queries'   => max( 0, $queries_after - $queries_before ),
						'memory_mb' => round( memory_get_usage( true ) / 1048576, 2 ),
					)
				);

				return $result;
			};
			$callback['smpt_profile_wrapped'] = true;
		}
		unset( $callback );
	}
	unset( $callbacks );

	$wrapped_hooks[ $hook_name ] = true;
}

/**
 * Wrap dashboard widget render callbacks for timing analysis.
 *
 * @return void
 */
function smpt_access_wrap_dashboard_widgets() {
	global $wp_meta_boxes, $current_screen;

	static $wrapped = false;

	if ( $wrapped || empty( $wp_meta_boxes['dashboard'] ) ) {
		return;
	}

	$screen_id = '';
	if ( isset( $current_screen ) && is_object( $current_screen ) && ! empty( $current_screen->id ) ) {
		$screen_id = (string) $current_screen->id;
	}

	foreach ( $wp_meta_boxes['dashboard'] as $context => &$priorities ) {
		foreach ( $priorities as $priority => &$boxes ) {
			foreach ( $boxes as $box_id => &$box ) {
				if ( empty( $box['callback'] ) || ! is_callable( $box['callback'] ) ) {
					continue;
				}

				if ( ! empty( $box['smpt_profile_wrapped'] ) ) {
					continue;
				}

				$original_callback = $box['callback'];
				$description       = smpt_access_describe_callable( $original_callback );
				$title             = isset( $box['title'] ) ? wp_strip_all_tags( (string) $box['title'] ) : $box_id;

				$box['callback'] = static function( ...$args ) use ( $original_callback, $box_id, $title, $context, $priority, $screen_id, $description ) {
					global $wpdb;

					$start          = microtime( true );
					$queries_before = isset( $wpdb->num_queries ) ? (int) $wpdb->num_queries : 0;
					$result         = call_user_func_array( $original_callback, $args );
					$elapsed        = microtime( true ) - $start;
					$queries_after  = isset( $wpdb->num_queries ) ? (int) $wpdb->num_queries : $queries_before;

					smpt_access_profile_add_timed_sample(
						'dashboard_widget_timings',
						array(
							'box_id'    => $box_id,
							'title'     => $title,
							'screen'    => $screen_id,
							'context'   => $context,
							'priority'  => $priority,
							'callback'  => $description,
							'elapsed'   => $elapsed,
							'queries'   => max( 0, $queries_after - $queries_before ),
							'memory_mb' => round( memory_get_usage( true ) / 1048576, 2 ),
						)
					);

					return $result;
				};
				$box['smpt_profile_wrapped'] = true;
			}
			unset( $box );
		}
		unset( $boxes );
	}
	unset( $priorities );

	$wrapped = true;
}

/**
 * Format timed callback samples into a compact log string.
 *
 * @param array  $samples Timed callback samples.
 * @param string $label   Sample label key.
 * @return string
 */
function smpt_access_format_timed_samples( array $samples, $label = 'callback' ) {
	if ( empty( $samples ) ) {
		return '';
	}

	usort(
		$samples,
		static function( $left, $right ) {
			return $left['elapsed'] < $right['elapsed'] ? 1 : -1;
		}
	);

	$parts = array();

	foreach ( array_slice( $samples, 0, 8 ) as $sample ) {
		$name = isset( $sample[ $label ] ) ? $sample[ $label ] : 'unknown';
		$parts[] = sprintf(
			'%1$s:%2$.3fs:%3$dq',
			$name,
			isset( $sample['elapsed'] ) ? (float) $sample['elapsed'] : 0,
			isset( $sample['queries'] ) ? (int) $sample['queries'] : 0
		);
	}

	return implode( ' | ', $parts );
}

/**
 * Format profiler checkpoints into a compact log string.
 *
 * @return string
 */
function smpt_access_profile_format_checkpoints() {
	$checkpoints = smpt_access_profile_get_checkpoints();
	$parts       = array();
	$previous_t  = 0;
	$previous_q  = 0;

	foreach ( $checkpoints as $checkpoint ) {
		$delta_t = $checkpoint['t'] - $previous_t;
		$delta_q = $checkpoint['queries'] - $previous_q;

		$parts[] = sprintf(
			'%1$s@%2$.3fs(+%3$.3fs,%4$dq,%5$.1fmb)',
			$checkpoint['label'],
			$checkpoint['t'],
			max( 0, $delta_t ),
			$delta_q,
			$checkpoint['memory']
		);

		$previous_t = $checkpoint['t'];
		$previous_q = $checkpoint['queries'];
	}

	return implode( ' | ', $parts );
}

/**
 * Build a compact main-query summary for debug logs.
 *
 * @return array
 */
function smpt_access_get_main_query_summary() {
	global $wp_query;

	if ( ! isset( $wp_query ) || ! $wp_query instanceof WP_Query ) {
		return array();
	}

	$type = 'other';

	if ( is_front_page() ) {
		$type = 'front-page';
	} elseif ( is_home() ) {
		$type = 'home';
	} elseif ( is_page() ) {
		$type = 'page';
	} elseif ( is_single() ) {
		$type = 'single';
	} elseif ( is_category() ) {
		$type = 'category';
	} elseif ( is_tag() ) {
		$type = 'tag';
	} elseif ( is_tax() ) {
		$type = 'taxonomy';
	} elseif ( is_search() ) {
		$type = 'search';
	} elseif ( is_archive() ) {
		$type = 'archive';
	} elseif ( is_404() ) {
		$type = '404';
	}

	$queried_object = get_queried_object();
	$object_bits    = array();

	if ( is_object( $queried_object ) ) {
		foreach ( array( 'post_type', 'taxonomy', 'slug', 'name' ) as $property ) {
			if ( ! empty( $queried_object->{$property} ) ) {
				$object_bits[] = $property . ':' . $queried_object->{$property};
			}
		}
	}

	return array(
		'type'          => $type,
		'object_id'     => get_queried_object_id(),
		'object'        => implode( ',', $object_bits ),
		'post_count'    => (int) $wp_query->post_count,
		'found_posts'   => isset( $wp_query->found_posts ) ? (int) $wp_query->found_posts : 0,
		'max_pages'     => isset( $wp_query->max_num_pages ) ? (int) $wp_query->max_num_pages : 0,
		'is_main_query' => $wp_query->is_main_query(),
	);
}

/**
 * Get the slowest recorded SQL queries for the current request.
 *
 * @param int   $limit          Maximum number of queries to return.
 * @param float $min_duration_s Minimum duration in seconds.
 * @return array
 */
function smpt_access_get_slowest_queries( $limit = 5, $min_duration_s = 0.01 ) {
	global $wpdb;

	if ( ! defined( 'SAVEQUERIES' ) || ! SAVEQUERIES || empty( $wpdb->queries ) || ! is_array( $wpdb->queries ) ) {
		return array();
	}

	$queries = array();

	foreach ( $wpdb->queries as $entry ) {
		if ( ! is_array( $entry ) || ! isset( $entry[0], $entry[1] ) ) {
			continue;
		}

		$duration = (float) $entry[1];
		if ( $duration < $min_duration_s ) {
			continue;
		}

		$sql    = preg_replace( '/\s+/', ' ', trim( (string) $entry[0] ) );
		$caller = isset( $entry[2] ) ? preg_replace( '/\s+/', ' ', trim( (string) $entry[2] ) ) : '';

		if ( strlen( $sql ) > 220 ) {
			$sql = substr( $sql, 0, 217 ) . '...';
		}

		if ( strlen( $caller ) > 140 ) {
			$caller = substr( $caller, 0, 137 ) . '...';
		}

		$queries[] = array(
			'duration' => $duration,
			'sql'      => $sql,
			'caller'   => $caller,
		);
	}

	usort(
		$queries,
		static function( $left, $right ) {
			return $left['duration'] < $right['duration'] ? 1 : -1;
		}
	);

	return array_slice( $queries, 0, $limit );
}

/**
 * Format the slowest SQL queries into a log-friendly string.
 *
 * @param array $queries Query list.
 * @return string
 */
function smpt_access_format_slowest_queries( array $queries ) {
	$parts = array();

	foreach ( $queries as $index => $query ) {
		$part = sprintf(
			'#%1$d %2$.4fs %3$s',
			$index + 1,
			$query['duration'],
			$query['sql']
		);

		if ( ! empty( $query['caller'] ) ) {
			$part .= ' [' . $query['caller'] . ']';
		}

		$parts[] = $part;
	}

	return implode( ' || ', $parts );
}

/**
 * Format geo lookup attempts into a log-friendly string.
 *
 * @param array $attempts Geo attempts.
 * @return string
 */
function smpt_access_format_geo_attempts( array $attempts ) {
	$parts = array();

	foreach ( $attempts as $attempt ) {
		if ( empty( $attempt['provider'] ) ) {
			continue;
		}

		$parts[] = sprintf(
			'%1$s:%2$.3fs:%3$s',
			$attempt['provider'],
			isset( $attempt['elapsed'] ) ? (float) $attempt['elapsed'] : 0,
			isset( $attempt['result'] ) ? $attempt['result'] : 'unknown'
		);
	}

	return implode( ', ', $parts );
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
	$has_debug_toggle = isset( $_GET['smpt_debug_trace_toggle'] ) || isset( $_REQUEST['smpt_debug_trace_toggle'] );
	$debug_enabled = function_exists( 'smpt_is_debug_trace_enabled' ) && smpt_is_debug_trace_enabled();

	$enabled = $debug_enabled || $has_debug_toggle || $has_preview || $is_admin_post || $is_member_path;

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
	smpt_access_profile_set_meta( 'request_uri', isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' );
	smpt_access_profile_mark_checkpoint( 'request_start' );
	smpt_access_trace(
		'request_start',
		array(
			'method'        => $_SERVER['REQUEST_METHOD'] ?? 'GET',
			'uri'           => isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/',
			'referer'       => isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '',
			'preview_query' => isset( $_REQUEST['smpt_preview_blocked'] ) ? wp_unslash( $_REQUEST['smpt_preview_blocked'] ) : '',
			'preview_toggle'=> isset( $_REQUEST['smpt_preview_toggle'] ) ? wp_unslash( $_REQUEST['smpt_preview_toggle'] ) : '',
			'debug_toggle'  => isset( $_REQUEST['smpt_debug_trace_toggle'] ) ? wp_unslash( $_REQUEST['smpt_debug_trace_toggle'] ) : '',
			'debug_cookie'  => isset( $_COOKIE['smpt_debug_trace'] ) ? wp_unslash( $_COOKIE['smpt_debug_trace'] ) : '',
			'preview_cookie'=> isset( $_COOKIE['smpt_preview_blocked'] ) ? wp_unslash( $_COOKIE['smpt_preview_blocked'] ) : '',
		)
	);
}
add_action( 'muplugins_loaded', 'smpt_access_trace_request_start', 1 );

add_action(
	'init',
	static function() {
		smpt_access_profile_mark_checkpoint( 'init' );
		smpt_access_trace( 'hook_init' );
	},
	9999
);

add_action(
	'parse_request',
	static function() {
		smpt_access_profile_mark_checkpoint( 'parse_request' );
		smpt_access_trace( 'hook_parse_request' );
	},
	9999
);

add_action(
	'wp',
	static function() {
		$summary = smpt_access_get_main_query_summary();

		if ( ! empty( $summary ) ) {
			smpt_access_profile_set_meta( 'main_query', $summary );
		}

		smpt_access_profile_mark_checkpoint( 'wp', $summary );
		smpt_access_trace( 'hook_wp' );
	},
	9999
);

add_action(
	'admin_init',
	static function() {
		smpt_access_profile_mark_checkpoint( 'admin_init' );
	},
	9999
);

add_action(
	'current_screen',
	static function( $screen ) {
		$screen_id = is_object( $screen ) && ! empty( $screen->id ) ? (string) $screen->id : '';
		smpt_access_profile_set_meta( 'admin_screen', $screen_id );
		smpt_access_profile_mark_checkpoint( 'current_screen', array( 'screen' => $screen_id ) );
	},
	9999
);

add_action(
	'load-index.php',
	static function() {
		smpt_access_profile_mark_checkpoint( 'load_index' );
	},
	9999
);

add_action(
	'wp_dashboard_setup',
	static function() {
		smpt_access_profile_mark_checkpoint( 'wp_dashboard_setup' );
	},
	9999
);

add_action(
	'admin_enqueue_scripts',
	static function() {
		smpt_access_profile_mark_checkpoint( 'admin_enqueue_scripts' );
	},
	9999
);

add_action(
	'admin_head',
	static function() {
		smpt_access_profile_mark_checkpoint( 'admin_head' );
	},
	9999
);

add_action(
	'in_admin_header',
	static function() {
		smpt_access_profile_mark_checkpoint( 'in_admin_header' );
	},
	9999
);

add_action(
	'admin_notices',
	static function() {
		smpt_access_profile_mark_checkpoint( 'admin_notices' );
	},
	9999
);

add_action(
	'all_admin_notices',
	static function() {
		smpt_access_profile_mark_checkpoint( 'all_admin_notices' );
	},
	9999
);

add_action(
	'admin_footer',
	static function() {
		smpt_access_profile_mark_checkpoint( 'admin_footer' );
	},
	9999
);

add_action(
	'template_redirect',
	static function() {
		smpt_access_profile_mark_checkpoint( 'template_redirect' );
		smpt_access_trace( 'hook_template_redirect' );
	},
	9999
);

add_action(
	'wp_enqueue_scripts',
	static function() {
		smpt_access_profile_mark_checkpoint( 'wp_enqueue_scripts' );
	},
	9999
);

add_action(
	'wp_head',
	static function() {
		smpt_access_profile_mark_checkpoint( 'wp_head' );
	},
	9999
);

add_action(
	'loop_start',
	static function( $query ) {
		if ( $query instanceof WP_Query && $query->is_main_query() ) {
			smpt_access_profile_mark_checkpoint(
				'loop_start',
				array(
					'post_count' => (int) $query->post_count,
				)
			);
		}
	},
	1
);

add_action(
	'loop_end',
	static function( $query ) {
		if ( $query instanceof WP_Query && $query->is_main_query() ) {
			smpt_access_profile_mark_checkpoint(
				'loop_end',
				array(
					'post_count' => (int) $query->post_count,
				)
			);
		}
	},
	9999
);

add_action(
	'wp_footer',
	static function() {
		smpt_access_profile_mark_checkpoint( 'wp_footer' );
	},
	1
);

add_filter(
	'template_include',
	static function( $template ) {
		smpt_access_profile_set_meta( 'template', $template );
		smpt_access_profile_mark_checkpoint( 'template_include', array( 'template' => basename( $template ) ) );
		smpt_access_trace( 'hook_template_include', array( 'template' => $template ) );
		return $template;
	},
	9999
);

add_action(
	'plugins_loaded',
	static function() {
		if ( ! is_admin() || ! smpt_access_is_admin_dashboard_request() ) {
			return;
		}

		foreach ( array( 'admin_init', 'current_screen', 'load-index.php', 'wp_dashboard_setup', 'admin_enqueue_scripts', 'admin_head', 'in_admin_header', 'admin_notices', 'all_admin_notices', 'admin_footer' ) as $hook_name ) {
			smpt_access_wrap_hook_callbacks( $hook_name );
		}
	},
	9999
);

add_action(
	'wp_dashboard_setup',
	static function() {
		if ( ! is_admin() || ! smpt_access_is_admin_dashboard_request() ) {
			return;
		}

		smpt_access_wrap_dashboard_widgets();
	},
	99999
);

/**
 * Log late-stage traced requests.
 *
 * @return void
 */
function smpt_access_trace_request_summary() {
	global $wpdb;

	$decision = function_exists( 'smpt_get_visitor_access_decision' ) ? smpt_get_visitor_access_decision() : array();
	$meta     = smpt_access_profile_get_meta();

	smpt_access_profile_mark_checkpoint( 'shutdown' );

	smpt_access_trace(
		'request_end',
		array(
			'uri'          => isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/',
			'allowed'      => ! empty( $decision['allowed'] ),
			'source'       => isset( $decision['source'] ) ? $decision['source'] : '',
			'preview'      => function_exists( 'smpt_should_apply_blocked_preview' ) && smpt_should_apply_blocked_preview(),
			'debug'        => function_exists( 'smpt_is_debug_trace_enabled' ) && smpt_is_debug_trace_enabled(),
			'queries'      => isset( $wpdb->num_queries ) ? (int) $wpdb->num_queries : 0,
			'memory_mb'    => round( memory_get_peak_usage( true ) / 1048576, 2 ),
			'is_admin'     => is_admin(),
			'doing_ajax'   => wp_doing_ajax(),
			'doing_cron'   => wp_doing_cron(),
			'status_guess' => http_response_code(),
			'template'     => isset( $meta['template'] ) ? basename( (string) $meta['template'] ) : '',
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
		smpt_access_profile_set_meta( 'geo_lookup', array( 'country' => $cf_country, 'source' => 'cloudflare' ) );
		return array(
			'country' => $cf_country,
			'source'  => 'cloudflare',
		);
	}

	if ( '' === $ip ) {
		smpt_access_profile_set_meta( 'geo_lookup', array( 'country' => 'UNKNOWN', 'source' => 'invalid-ip' ) );
		return array(
			'country' => 'UNKNOWN',
			'source'  => 'invalid-ip',
		);
	}

	if ( smpt_is_private_or_reserved_ip( $ip ) ) {
		smpt_access_profile_set_meta( 'geo_lookup', array( 'country' => 'UNKNOWN', 'source' => 'private-ip' ) );
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
		$lookup_start = microtime( true );
		$response = wp_remote_get(
			$provider['url'],
			array(
				'timeout' => 2,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		$elapsed = microtime( true ) - $lookup_start;

		if ( is_wp_error( $response ) ) {
			smpt_access_profile_append_meta(
				'geo_attempts',
				array(
					'provider' => $provider['source'],
					'elapsed'  => $elapsed,
					'result'   => 'error',
				)
			);
			smpt_access_log( sprintf( 'Geo provider %s failed: %s', $provider['source'], $response->get_error_message() ) );
			continue;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			smpt_access_profile_append_meta(
				'geo_attempts',
				array(
					'provider' => $provider['source'],
					'elapsed'  => $elapsed,
					'result'   => 'invalid-json',
				)
			);
			smpt_access_log( sprintf( 'Geo provider %s returned invalid JSON.', $provider['source'] ) );
			continue;
		}

		$country = strtoupper( trim( (string) $provider['parse']( $data ) ) );
		if ( preg_match( '/^[A-Z]{2}$/', $country ) ) {
			smpt_access_profile_append_meta(
				'geo_attempts',
				array(
					'provider' => $provider['source'],
					'elapsed'  => $elapsed,
					'result'   => 'success:' . $country,
				)
			);
			smpt_access_profile_set_meta( 'geo_lookup', array( 'country' => $country, 'source' => $provider['source'] ) );
			return array(
				'country' => $country,
				'source'  => $provider['source'],
			);
		}

		smpt_access_profile_append_meta(
			'geo_attempts',
			array(
				'provider' => $provider['source'],
				'elapsed'  => $elapsed,
				'result'   => 'no-match',
			)
		);
	}

	smpt_access_profile_set_meta( 'geo_lookup', array( 'country' => 'UNKNOWN', 'source' => 'lookup-failed' ) );

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
		smpt_access_profile_set_meta( 'decision', $decision );
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
		smpt_access_profile_set_meta( 'decision', $decision );
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
		smpt_access_profile_set_meta( 'decision', $decision );
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
		smpt_access_profile_set_meta( 'decision', $decision );
		return $decision;
	}

	if ( '' !== $ip && in_array( $ip, $config['denied_ips'], true ) ) {
		$decision = array(
			'allowed' => false,
			'country' => 'DENIED_IP',
			'source'  => 'denied-ip',
			'exp'     => $now + SMPT_ACCESS_COOKIE_TTL,
		);
		smpt_access_profile_set_meta( 'decision', $decision );
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
		smpt_access_profile_set_meta( 'decision', $decision );
		smpt_set_access_cookie( $decision );
		return $decision;
	}

	$cookie_name = SMPT_ACCESS_COOKIE;
	if ( isset( $_COOKIE[ $cookie_name ] ) ) {
		$cached = smpt_decode_access_cookie( wp_unslash( $_COOKIE[ $cookie_name ] ) );
		if ( null !== $cached ) {
			$decision = $cached;
			smpt_access_profile_set_meta( 'decision', $decision );
			smpt_access_profile_set_meta( 'decision_cache', 'hit' );
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

	smpt_access_profile_set_meta( 'decision', $decision );
	smpt_access_profile_set_meta( 'decision_cache', 'miss' );
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
	if ( wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( ! defined( 'SMPT_REQUEST_START' ) ) {
		return;
	}

	$duration = microtime( true ) - SMPT_REQUEST_START;

	if ( $duration < SMPT_SLOW_REQUEST_THRESHOLD ) {
		return;
	}

	smpt_access_profile_mark_checkpoint( 'shutdown' );

	$decision = smpt_get_visitor_access_decision();
	$request  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$meta     = smpt_access_profile_get_meta();
	$profile  = smpt_access_profile_format_checkpoints();
	$template = isset( $meta['template'] ) ? basename( (string) $meta['template'] ) : '';
	$main     = isset( $meta['main_query'] ) && is_array( $meta['main_query'] ) ? $meta['main_query'] : array();
	$geo      = isset( $meta['geo_lookup'] ) && is_array( $meta['geo_lookup'] ) ? $meta['geo_lookup'] : array();
	$geo_log  = isset( $meta['geo_attempts'] ) && is_array( $meta['geo_attempts'] ) ? smpt_access_format_geo_attempts( $meta['geo_attempts'] ) : '';
	$screen   = isset( $meta['admin_screen'] ) ? (string) $meta['admin_screen'] : '';

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

	smpt_access_log(
		sprintf(
			'Slow request profile: request=%1$s template=%2$s screen=%3$s main=%4$s object_id=%5$s posts=%6$d found=%7$d decision=%8$s geo=%9$s checkpoints=%10$s',
			$request,
			$template ? $template : '(none)',
			$screen ? $screen : '(none)',
			isset( $main['type'] ) ? $main['type'] : 'unknown',
			isset( $main['object_id'] ) ? (int) $main['object_id'] : 0,
			isset( $main['post_count'] ) ? (int) $main['post_count'] : 0,
			isset( $main['found_posts'] ) ? (int) $main['found_posts'] : 0,
			isset( $decision['source'] ) ? $decision['source'] : 'unknown',
			! empty( $geo_log ) ? $geo_log : ( ! empty( $geo['source'] ) ? $geo['source'] : 'n/a' ),
			$profile ? $profile : '(none)'
		)
	);

	if ( is_admin() && ! empty( $meta['hook_callback_timings'] ) && is_array( $meta['hook_callback_timings'] ) ) {
		smpt_access_log( 'Slow request admin hooks: ' . smpt_access_format_timed_samples( $meta['hook_callback_timings'], 'callback' ) );
	}

	if ( is_admin() && ! empty( $meta['dashboard_widget_timings'] ) && is_array( $meta['dashboard_widget_timings'] ) ) {
		smpt_access_log( 'Slow request dashboard widgets: ' . smpt_access_format_timed_samples( $meta['dashboard_widget_timings'], 'title' ) );
	}

	if ( smpt_access_trace_enabled() ) {
		$queries = smpt_access_get_slowest_queries();

		if ( ! empty( $queries ) ) {
			smpt_access_log( 'Slow request SQL: ' . smpt_access_format_slowest_queries( $queries ) );
		}
	}
}
add_action( 'shutdown', 'smpt_log_slow_frontend_requests', 9999 );
