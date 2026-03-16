<?php
/**
 * Analytics dashboard widget and REST stats endpoint.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the analytics dashboard widget.
 */
function smpt_analytics_register_dashboard_widget() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	wp_add_dashboard_widget(
		'smpt_analytics_widget',
		'SMPT Analytics',
		'smpt_analytics_dashboard_widget_render'
	);

	// Move widget to top of the dashboard.
	global $wp_meta_boxes;
	$widget = $wp_meta_boxes['dashboard']['normal']['core']['smpt_analytics_widget'] ?? null;
	if ( $widget ) {
		unset( $wp_meta_boxes['dashboard']['normal']['core']['smpt_analytics_widget'] );
		$wp_meta_boxes['dashboard']['normal']['high']['smpt_analytics_widget'] = $widget;
	}
}
add_action( 'wp_dashboard_setup', 'smpt_analytics_register_dashboard_widget' );

/**
 * Render the dashboard widget HTML skeleton.
 */
function smpt_analytics_dashboard_widget_render() {
	?>
	<div id="smpt-analytics-root">
		<div class="smpt-toolbar">
			<button type="button" class="smpt-toggle-btn" id="smpt-toggle-stats">Show stats</button>
			<p class="smpt-toolbar-note">Analytics stays unloaded until you open it.</p>
		</div>

		<div id="smpt-analytics-panel" class="smpt-hidden">
			<!-- Period selector -->
			<div class="smpt-period-bar">
				<button class="smpt-period-btn" data-period="today">Today</button>
				<button class="smpt-period-btn" data-period="week">This Week</button>
				<button class="smpt-period-btn smpt-period-active" data-period="month">This Month</button>
				<button class="smpt-period-btn" data-period="year">This Year</button>
				<button class="smpt-period-btn" data-period="all">All Time</button>
			</div>

			<!-- Loading indicator -->
			<div id="smpt-loading" class="smpt-loading smpt-hidden">Loading analytics&hellip;</div>

			<!-- KPI cards -->
			<div class="smpt-kpi-grid" id="smpt-kpis"></div>

			<div class="smpt-chart-section smpt-detail-section" id="smpt-detail-section">
				<div class="smpt-detail-head">
					<h3 id="smpt-detail-title">Streams</h3>
					<p class="smpt-detail-summary" id="smpt-detail-summary"></p>
				</div>
				<div class="smpt-detail-grid" id="smpt-detail-grid"></div>
			</div>

			<!-- Events over time -->
			<div class="smpt-chart-section">
				<h3>Events Over Time</h3>
				<div class="smpt-chart-wrap"><canvas id="smpt-chart-timeline"></canvas></div>
			</div>

			<!-- Top content: episodes + music side by side -->
			<div class="smpt-two-col">
				<div class="smpt-chart-section">
					<h3>Top Streamed Episodes</h3>
					<div class="smpt-chart-wrap smpt-chart-bar"><canvas id="smpt-chart-top-streams"></canvas></div>
				</div>
				<div class="smpt-chart-section">
					<h3>Top Downloaded Episodes</h3>
					<div class="smpt-chart-wrap smpt-chart-bar"><canvas id="smpt-chart-top-downloads"></canvas></div>
				</div>
			</div>

			<div class="smpt-two-col">
				<div class="smpt-chart-section">
					<h3>Top Music</h3>
					<div class="smpt-chart-wrap smpt-chart-bar"><canvas id="smpt-chart-top-music"></canvas></div>
				</div>
				<div class="smpt-chart-section">
					<h3>Watch Completion</h3>
					<div class="smpt-chart-wrap"><canvas id="smpt-chart-funnel"></canvas></div>
				</div>
			</div>

			<!-- Demographics: 2x2 grid -->
			<h3>Visitor Demographics</h3>
			<div class="smpt-demo-grid">
				<div class="smpt-chart-section">
					<h4>Country</h4>
					<div class="smpt-chart-wrap smpt-chart-sm"><canvas id="smpt-chart-country"></canvas></div>
				</div>
				<div class="smpt-chart-section">
					<h4>Device</h4>
					<div class="smpt-chart-wrap smpt-chart-sm"><canvas id="smpt-chart-device"></canvas></div>
				</div>
				<div class="smpt-chart-section">
					<h4>OS</h4>
					<div class="smpt-chart-wrap smpt-chart-sm"><canvas id="smpt-chart-os"></canvas></div>
				</div>
				<div class="smpt-chart-section">
					<h4>Browser</h4>
					<div class="smpt-chart-wrap smpt-chart-sm"><canvas id="smpt-chart-browser"></canvas></div>
				</div>
			</div>

			<!-- Additional insights -->
			<div class="smpt-two-col">
				<div class="smpt-chart-section">
					<h3>Peak Hours</h3>
					<div class="smpt-chart-wrap"><canvas id="smpt-chart-hours"></canvas></div>
				</div>
				<div class="smpt-chart-section">
					<h3>New vs Returning</h3>
					<div class="smpt-chart-wrap smpt-chart-sm"><canvas id="smpt-chart-newret"></canvas></div>
				</div>
			</div>

			<div class="smpt-two-col">
				<div class="smpt-chart-section">
					<h3>Top Referrers</h3>
					<table class="smpt-table" id="smpt-table-referrers"><tbody></tbody></table>
				</div>
				<div class="smpt-chart-section">
					<h3>Screen Resolutions</h3>
					<table class="smpt-table" id="smpt-table-resolutions"><tbody></tbody></table>
				</div>
			</div>

			<div class="smpt-two-col">
				<div class="smpt-chart-section">
					<h3>Languages</h3>
					<table class="smpt-table" id="smpt-table-languages"><tbody></tbody></table>
				</div>
				<div class="smpt-chart-section">
					<h3>Connection Types</h3>
					<div class="smpt-chart-wrap smpt-chart-sm"><canvas id="smpt-chart-connection"></canvas></div>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Enqueue admin assets for the dashboard widget.
 *
 * @param string $hook_suffix Admin page hook.
 */
function smpt_analytics_enqueue_admin_assets( $hook_suffix ) {
	if ( 'index.php' !== $hook_suffix || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	wp_enqueue_script(
		'chartjs',
		'https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js',
		array(),
		'4.4.7',
		true
	);

	wp_enqueue_script(
		'smpt-analytics-dashboard',
		smpt_site_plugin_url( 'javascript/analytics-dashboard.js' ),
		array( 'chartjs' ),
		'1.0',
		true
	);
	wp_localize_script( 'smpt-analytics-dashboard', 'smptDashboard', array(
		'rest_url' => rest_url( 'smpt/v1/stats' ),
		'nonce'    => wp_create_nonce( 'wp_rest' ),
	) );

	wp_enqueue_style(
		'smpt-analytics-dashboard',
		smpt_site_plugin_url( 'css/analytics-dashboard.css' ),
		array(),
		'1.0'
	);
}
add_action( 'admin_enqueue_scripts', 'smpt_analytics_enqueue_admin_assets' );

/**
 * Register the stats REST endpoint.
 */
function smpt_analytics_register_stats_route() {
	register_rest_route( 'smpt/v1', '/stats', array(
		'methods'             => 'GET',
		'callback'            => 'smpt_rest_handle_stats',
		'permission_callback' => static function () {
			return current_user_can( 'manage_options' );
		},
		'args'                => array(
			'period' => array(
				'default'           => 'month',
				'sanitize_callback' => 'sanitize_text_field',
			),
		),
	) );
}
add_action( 'rest_api_init', 'smpt_analytics_register_stats_route' );

/**
 * Compute the date range for a given period.
 *
 * @param string $period Period identifier.
 * @return array{start: string, end: string, group_format: string}
 */
function smpt_analytics_period_range( $period ) {
	$now = current_time( 'mysql' );
	$end = $now;

	switch ( $period ) {
		case 'today':
			$start        = gmdate( 'Y-m-d 00:00:00', strtotime( $now ) );
			$group_format = '%H:00';
			break;
		case 'week':
			$start        = gmdate( 'Y-m-d 00:00:00', strtotime( '-6 days', strtotime( $now ) ) );
			$group_format = '%Y-%m-%d';
			break;
		case 'year':
			$start        = gmdate( 'Y-01-01 00:00:00', strtotime( $now ) );
			$group_format = '%Y-%m';
			break;
		case 'all':
			$start        = '2000-01-01 00:00:00';
			$group_format = '%Y-%m';
			break;
		case 'month':
		default:
			$start        = gmdate( 'Y-m-01 00:00:00', strtotime( $now ) );
			$group_format = '%Y-%m-%d';
			break;
	}

	return array(
		'start'        => $start,
		'end'          => $end,
		'group_format' => $group_format,
	);
}

/**
 * Merge grouped totals into an event_type => count map.
 *
 * @param array $totals Existing totals.
 * @param array $rows   Query rows containing event_type and cnt properties.
 * @return array
 */
function smpt_analytics_merge_event_totals( array $totals, array $rows ) {
	foreach ( $rows as $row ) {
		$type = isset( $row->event_type ) ? (string) $row->event_type : '';

		if ( '' === $type ) {
			continue;
		}

		$totals[ $type ] = ( $totals[ $type ] ?? 0 ) + (int) $row->cnt;
	}

	return $totals;
}

/**
 * Merge timeline rows into a normalized chart payload.
 *
 * @param array $local_rows Local analytics rows.
 * @param array $ga_rows    Imported GA rows.
 * @return array
 */
function smpt_analytics_merge_timeline_rows( array $local_rows, array $ga_rows ) {
	$merged = array();

	foreach ( array_merge( $local_rows, $ga_rows ) as $row ) {
		$label = isset( $row->period_label ) ? (string) $row->period_label : '';
		$type  = isset( $row->event_type ) ? (string) $row->event_type : '';

		if ( '' === $label || '' === $type ) {
			continue;
		}

		$key = $label . '|' . $type;
		if ( ! isset( $merged[ $key ] ) ) {
			$merged[ $key ] = array(
				'label' => $label,
				'type'  => $type,
				'count' => 0,
			);
		}

		$merged[ $key ]['count'] += (int) $row->cnt;
	}

	usort(
		$merged,
		static function ( $left, $right ) {
			if ( $left['label'] === $right['label'] ) {
				return strcmp( $left['type'], $right['type'] );
			}

			return strcmp( $left['label'], $right['label'] );
		}
	);

	return array_values( $merged );
}

/**
 * Merge item-count leaderboards from multiple sources.
 *
 * @param int   $limit Number of rows to return.
 * @param array ...$row_sets Query result sets with item_id and cnt properties.
 * @return array
 */
function smpt_analytics_merge_item_rankings( $limit, ...$row_sets ) {
	$counts = array();

	foreach ( $row_sets as $rows ) {
		foreach ( $rows as $row ) {
			$item_id = isset( $row->item_id ) ? trim( (string) $row->item_id ) : '';

			if ( '' === $item_id ) {
				continue;
			}

			$counts[ $item_id ] = ( $counts[ $item_id ] ?? 0 ) + (int) $row->cnt;
		}
	}

	arsort( $counts, SORT_NUMERIC );
	$counts = array_slice( $counts, 0, (int) $limit, true );

	$results = array();
	foreach ( $counts as $label => $count ) {
		$results[] = array(
			'label' => $label,
			'count' => (int) $count,
		);
	}

	return $results;
}

/**
 * Merge item-count leaderboards and sort either descending or ascending.
 *
 * @param int   $limit     Number of rows to return.
 * @param bool  $ascending Whether to sort ascending.
 * @param array ...$row_sets Query result sets with item_id and cnt properties.
 * @return array
 */
function smpt_analytics_merge_item_rankings_sorted( $limit, $ascending, ...$row_sets ) {
	$counts = array();

	foreach ( $row_sets as $rows ) {
		foreach ( $rows as $row ) {
			$item_id = isset( $row->item_id ) ? trim( (string) $row->item_id ) : '';

			if ( '' === $item_id ) {
				continue;
			}

			$counts[ $item_id ] = ( $counts[ $item_id ] ?? 0 ) + (int) $row->cnt;
		}
	}

	if ( $ascending ) {
		asort( $counts, SORT_NUMERIC );
	} else {
		arsort( $counts, SORT_NUMERIC );
	}

	$counts  = array_slice( $counts, 0, (int) $limit, true );
	$results = array();

	foreach ( $counts as $label => $count ) {
		$results[] = array(
			'label' => $label,
			'count' => (int) $count,
		);
	}

	return $results;
}

/**
 * Merge grouped date counts and return ranked periods.
 *
 * @param int   $limit     Number of rows to return.
 * @param bool  $ascending Whether to sort ascending.
 * @param array ...$row_sets Query rows containing period_label and cnt properties.
 * @return array
 */
function smpt_analytics_merge_period_rankings( $limit, $ascending, ...$row_sets ) {
	$counts = array();

	foreach ( $row_sets as $rows ) {
		foreach ( $rows as $row ) {
			$label = isset( $row->period_label ) ? trim( (string) $row->period_label ) : '';

			if ( '' === $label ) {
				continue;
			}

			$counts[ $label ] = ( $counts[ $label ] ?? 0 ) + (int) $row->cnt;
		}
	}

	if ( $ascending ) {
		asort( $counts, SORT_NUMERIC );
	} else {
		arsort( $counts, SORT_NUMERIC );
	}

	$counts  = array_slice( $counts, 0, (int) $limit, true );
	$results = array();

	foreach ( $counts as $label => $count ) {
		$results[] = array(
			'label' => $label,
			'count' => (int) $count,
		);
	}

	return $results;
}

/**
 * Prepare a SQL statement using an argument array.
 *
 * @param wpdb  $wpdb   WordPress DB handle.
 * @param string $sql   SQL with placeholders.
 * @param array  $args  Placeholder arguments.
 * @return string
 */
function smpt_analytics_prepare_sql( $wpdb, $sql, array $args ) {
	return call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $args ) );
}

/**
 * Handle GET /smpt/v1/stats — return all dashboard data for a period.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response
 */
function smpt_rest_handle_stats( WP_REST_Request $request ) {
	global $wpdb;

	$period = $request->get_param( 'period' );
	$range  = smpt_analytics_period_range( $period );
	$start  = $range['start'];
	$end    = $range['end'];
	$gf     = $range['group_format'];
	$ev     = $wpdb->prefix . 'smpt_events';
	$vi     = $wpdb->prefix . 'smpt_visitors';
	$ga     = $wpdb->prefix . 'smpt_ga4_history';
	$ga_start = gmdate( 'Y-m-d', strtotime( $start ) );
	$ga_end   = gmdate( 'Y-m-d', strtotime( $end ) );

	// --- KPIs ---
	$visitors = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(DISTINCT visitor_id) FROM {$ev} WHERE created_at BETWEEN %s AND %s",
		$start, $end
	) );

	$event_totals_raw = $wpdb->get_results( $wpdb->prepare(
		"SELECT event_type, COUNT(*) as cnt FROM {$ev} WHERE created_at BETWEEN %s AND %s GROUP BY event_type",
		$start, $end
	) );
	$event_totals = array();
	foreach ( $event_totals_raw as $row ) {
		$event_totals[ $row->event_type ] = (int) $row->cnt;
	}

	$ga_event_totals_raw = $wpdb->get_results( $wpdb->prepare(
		"SELECT event_type, SUM(event_count) as cnt FROM {$ga}
		 WHERE event_date BETWEEN %s AND %s GROUP BY event_type",
		$ga_start, $ga_end
	) );
	$event_totals = smpt_analytics_merge_event_totals( $event_totals, $ga_event_totals_raw );
	$manga_total  = ( $event_totals['manga_view'] ?? 0 ) + ( $event_totals['manga_download'] ?? 0 );

	$build_event_type_placeholders = static function ( array $event_types ) {
		return implode( ', ', array_fill( 0, count( $event_types ), '%s' ) );
	};

	$get_local_item_rows = static function ( array $event_types ) use ( $wpdb, $ev, $start, $end, $build_event_type_placeholders ) {
		$placeholders = $build_event_type_placeholders( $event_types );
		$sql          = "SELECT item_id, COUNT(*) as cnt FROM {$ev}
			WHERE created_at BETWEEN %s AND %s
			AND event_type IN ({$placeholders})
			AND item_id != ''
			GROUP BY item_id";
		$args         = array_merge( array( $start, $end ), $event_types );
		return $wpdb->get_results( smpt_analytics_prepare_sql( $wpdb, $sql, $args ) );
	};

	$get_ga_item_rows = static function ( array $event_types ) use ( $wpdb, $ga, $ga_start, $ga_end, $build_event_type_placeholders ) {
		$placeholders = $build_event_type_placeholders( $event_types );
		$sql          = "SELECT item_id, SUM(event_count) as cnt FROM {$ga}
			WHERE event_date BETWEEN %s AND %s
			AND event_type IN ({$placeholders})
			AND item_id != ''
			GROUP BY item_id";
		$args         = array_merge( array( $ga_start, $ga_end ), $event_types );
		return $wpdb->get_results( smpt_analytics_prepare_sql( $wpdb, $sql, $args ) );
	};

	$get_local_period_rows = static function ( array $event_types ) use ( $wpdb, $ev, $start, $end, $build_event_type_placeholders ) {
		$placeholders = $build_event_type_placeholders( $event_types );
		$sql          = "SELECT DATE(created_at) as period_label, COUNT(*) as cnt FROM {$ev}
			WHERE created_at BETWEEN %s AND %s
			AND event_type IN ({$placeholders})
			GROUP BY DATE(created_at)";
		$args         = array_merge( array( $start, $end ), $event_types );
		return $wpdb->get_results( smpt_analytics_prepare_sql( $wpdb, $sql, $args ) );
	};

	$get_ga_period_rows = static function ( array $event_types ) use ( $wpdb, $ga, $ga_start, $ga_end, $build_event_type_placeholders ) {
		$placeholders = $build_event_type_placeholders( $event_types );
		$sql          = "SELECT event_date as period_label, SUM(event_count) as cnt FROM {$ga}
			WHERE event_date BETWEEN %s AND %s
			AND event_type IN ({$placeholders})
			GROUP BY event_date";
		$args         = array_merge( array( $ga_start, $ga_end ), $event_types );
		return $wpdb->get_results( smpt_analytics_prepare_sql( $wpdb, $sql, $args ) );
	};

	// --- Events over time ---
	$timeline_raw = $wpdb->get_results( $wpdb->prepare(
		"SELECT DATE_FORMAT(created_at, %s) as period_label, event_type, COUNT(*) as cnt
		 FROM {$ev} WHERE created_at BETWEEN %s AND %s
		 GROUP BY period_label, event_type ORDER BY period_label",
		$gf, $start, $end
	) );
	$ga_timeline_raw = array();
	if ( 'today' !== $period ) {
		$ga_timeline_raw = $wpdb->get_results( $wpdb->prepare(
			"SELECT DATE_FORMAT(event_date, %s) as period_label, event_type, SUM(event_count) as cnt
			 FROM {$ga} WHERE event_date BETWEEN %s AND %s
			 GROUP BY period_label, event_type ORDER BY period_label",
			$gf, $ga_start, $ga_end
		) );
	}
	$timeline = smpt_analytics_merge_timeline_rows( $timeline_raw, $ga_timeline_raw );

	// --- Top episodes (streams) ---
	$top_streams = $wpdb->get_results( $wpdb->prepare(
		"SELECT item_id, COUNT(*) as cnt FROM {$ev}
		 WHERE event_type = 'stream' AND created_at BETWEEN %s AND %s
		 GROUP BY item_id ORDER BY cnt DESC LIMIT 10",
		$start, $end
	) );
	$ga_top_streams = $wpdb->get_results( $wpdb->prepare(
		"SELECT item_id, SUM(event_count) as cnt FROM {$ga}
		 WHERE event_type = 'stream' AND event_date BETWEEN %s AND %s
		 GROUP BY item_id ORDER BY cnt DESC LIMIT 25",
		$ga_start, $ga_end
	) );

	// --- Top episodes (downloads) ---
	$top_downloads = $wpdb->get_results( $wpdb->prepare(
		"SELECT item_id, COUNT(*) as cnt FROM {$ev}
		 WHERE event_type = 'download' AND created_at BETWEEN %s AND %s
		 GROUP BY item_id ORDER BY cnt DESC LIMIT 10",
		$start, $end
	) );
	$ga_top_downloads = $wpdb->get_results( $wpdb->prepare(
		"SELECT item_id, SUM(event_count) as cnt FROM {$ga}
		 WHERE event_type = 'download' AND event_date BETWEEN %s AND %s
		 GROUP BY item_id ORDER BY cnt DESC LIMIT 25",
		$ga_start, $ga_end
	) );

	// --- Top music ---
	$top_music = $wpdb->get_results( $wpdb->prepare(
		"SELECT item_id, COUNT(*) as cnt FROM {$ev}
		 WHERE event_type = 'music_stream' AND created_at BETWEEN %s AND %s
		 GROUP BY item_id ORDER BY cnt DESC LIMIT 10",
		$start, $end
	) );
	$ga_top_music = $wpdb->get_results( $wpdb->prepare(
		"SELECT item_id, SUM(event_count) as cnt FROM {$ga}
		 WHERE event_type = 'music_stream' AND event_date BETWEEN %s AND %s
		 GROUP BY item_id ORDER BY cnt DESC LIMIT 25",
		$ga_start, $ga_end
	) );

	// --- Watch completion funnel ---
	$funnel_raw = $wpdb->get_results( $wpdb->prepare(
		"SELECT milestone, COUNT(*) as cnt FROM {$ev}
		 WHERE event_type IN ('stream', 'music_stream') AND created_at BETWEEN %s AND %s
		 GROUP BY milestone ORDER BY milestone",
		$start, $end
	) );
	$funnel = array();
	foreach ( array( 0, 25, 50, 75, 100 ) as $m ) {
		$funnel[ $m ] = 0;
	}
	foreach ( $funnel_raw as $row ) {
		$funnel[ (int) $row->milestone ] = (int) $row->cnt;
	}

	// --- Demographics (joined with visitors table via visitor_id) ---
	$countries = $wpdb->get_results( $wpdb->prepare(
		"SELECT v.country as label, COUNT(DISTINCT e.visitor_id) as cnt
		 FROM {$ev} e INNER JOIN {$vi} v ON v.id = e.visitor_id
		 WHERE e.created_at BETWEEN %s AND %s AND v.country != ''
		 GROUP BY v.country ORDER BY cnt DESC LIMIT 10",
		$start, $end
	) );

	$devices = $wpdb->get_results( $wpdb->prepare(
		"SELECT v.device_type as label, COUNT(DISTINCT e.visitor_id) as cnt
		 FROM {$ev} e INNER JOIN {$vi} v ON v.id = e.visitor_id
		 WHERE e.created_at BETWEEN %s AND %s AND v.device_type != ''
		 GROUP BY v.device_type ORDER BY cnt DESC",
		$start, $end
	) );

	$oses = $wpdb->get_results( $wpdb->prepare(
		"SELECT v.os as label, COUNT(DISTINCT e.visitor_id) as cnt
		 FROM {$ev} e INNER JOIN {$vi} v ON v.id = e.visitor_id
		 WHERE e.created_at BETWEEN %s AND %s AND v.os != ''
		 GROUP BY v.os ORDER BY cnt DESC LIMIT 8",
		$start, $end
	) );

	$browsers = $wpdb->get_results( $wpdb->prepare(
		"SELECT v.browser as label, COUNT(DISTINCT e.visitor_id) as cnt
		 FROM {$ev} e INNER JOIN {$vi} v ON v.id = e.visitor_id
		 WHERE e.created_at BETWEEN %s AND %s AND v.browser != ''
		 GROUP BY v.browser ORDER BY cnt DESC LIMIT 8",
		$start, $end
	) );

	// --- Peak hours ---
	$hours_raw = $wpdb->get_results( $wpdb->prepare(
		"SELECT HOUR(created_at) as h, COUNT(*) as cnt FROM {$ev}
		 WHERE created_at BETWEEN %s AND %s GROUP BY h ORDER BY h",
		$start, $end
	) );
	$hours = array_fill( 0, 24, 0 );
	foreach ( $hours_raw as $row ) {
		$hours[ (int) $row->h ] = (int) $row->cnt;
	}

	// --- Referrers ---
	$referrers = $wpdb->get_results( $wpdb->prepare(
		"SELECT referrer as label, COUNT(*) as cnt FROM {$ev}
		 WHERE created_at BETWEEN %s AND %s AND referrer != ''
		 GROUP BY referrer ORDER BY cnt DESC LIMIT 10",
		$start, $end
	) );

	// --- Screen resolutions ---
	$resolutions = $wpdb->get_results( $wpdb->prepare(
		"SELECT CONCAT(v.screen_width, 'x', v.screen_height) as label, COUNT(DISTINCT e.visitor_id) as cnt
		 FROM {$ev} e INNER JOIN {$vi} v ON v.id = e.visitor_id
		 WHERE e.created_at BETWEEN %s AND %s AND v.screen_width > 0
		 GROUP BY label ORDER BY cnt DESC LIMIT 10",
		$start, $end
	) );

	// --- Languages ---
	$languages = $wpdb->get_results( $wpdb->prepare(
		"SELECT v.language as label, COUNT(DISTINCT e.visitor_id) as cnt
		 FROM {$ev} e INNER JOIN {$vi} v ON v.id = e.visitor_id
		 WHERE e.created_at BETWEEN %s AND %s AND v.language != ''
		 GROUP BY v.language ORDER BY cnt DESC LIMIT 10",
		$start, $end
	) );

	// --- Connection types ---
	$connections = $wpdb->get_results( $wpdb->prepare(
		"SELECT v.`connection` as label, COUNT(DISTINCT e.visitor_id) as cnt
		 FROM {$ev} e INNER JOIN {$vi} v ON v.id = e.visitor_id
		 WHERE e.created_at BETWEEN %s AND %s AND v.`connection` != ''
		 GROUP BY v.`connection` ORDER BY cnt DESC",
		$start, $end
	) );

	// --- New vs Returning ---
	$new_visitors = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(DISTINCT e.visitor_id) FROM {$ev} e
		 INNER JOIN {$vi} v ON v.id = e.visitor_id
		 WHERE e.created_at BETWEEN %s AND %s AND v.first_seen >= %s",
		$start, $end, $start
	) );
	$returning_visitors = max( 0, $visitors - $new_visitors );

	$format_list = static function ( $rows ) {
		$out = array();
		foreach ( $rows as $row ) {
			$out[] = array( 'label' => $row->label ?? $row->item_id, 'count' => (int) $row->cnt );
		}
		return $out;
	};

	$visitor_days = $wpdb->get_results( $wpdb->prepare(
		"SELECT DATE(created_at) as period_label, COUNT(DISTINCT visitor_id) as cnt
		 FROM {$ev} WHERE created_at BETWEEN %s AND %s
		 GROUP BY DATE(created_at)",
		$start, $end
	) );

	$metric_details = array(
		'visitors'  => array(
			'title'   => 'Visitors',
			'summary' => sprintf( '%s unique visitors in this period.', number_format_i18n( $visitors ) ),
			'cards'   => array(
				array(
					'title' => 'Top Countries',
					'items' => $format_list( array_slice( $countries, 0, 5 ) ),
				),
				array(
					'title' => 'Top Devices',
					'items' => $format_list( array_slice( $devices, 0, 5 ) ),
				),
				array(
					'title' => 'Peak Visitor Days',
					'items' => smpt_analytics_merge_period_rankings( 5, false, $visitor_days ),
				),
				array(
					'title' => 'Quiet Visitor Days',
					'items' => smpt_analytics_merge_period_rankings( 5, true, $visitor_days ),
				),
			),
		),
	);

	$detail_metric_map = array(
		'streams'   => array(
			'title'       => 'Streams',
			'summary'     => sprintf( '%s total streams in this period.', number_format_i18n( $event_totals['stream'] ?? 0 ) ),
			'event_types' => array( 'stream' ),
			'top_label'   => 'Top Streamed Items',
			'low_label'   => 'Least Streamed Items',
			'peak_label'  => 'Highest Stream Days',
			'quiet_label' => 'Lowest Stream Days',
		),
		'downloads' => array(
			'title'       => 'Downloads',
			'summary'     => sprintf( '%s total downloads in this period.', number_format_i18n( $event_totals['download'] ?? 0 ) ),
			'event_types' => array( 'download' ),
			'top_label'   => 'Top Downloaded Items',
			'low_label'   => 'Least Downloaded Items',
			'peak_label'  => 'Highest Download Days',
			'quiet_label' => 'Lowest Download Days',
		),
		'music'     => array(
			'title'       => 'Music',
			'summary'     => sprintf( '%s music streams in this period.', number_format_i18n( $event_totals['music_stream'] ?? 0 ) ),
			'event_types' => array( 'music_stream' ),
			'top_label'   => 'Top Music Tracks',
			'low_label'   => 'Least Streamed Tracks',
			'peak_label'  => 'Highest Music Days',
			'quiet_label' => 'Lowest Music Days',
		),
		'manga'     => array(
			'title'       => 'Manga',
			'summary'     => sprintf( '%s total manga interactions in this period.', number_format_i18n( $manga_total ) ),
			'event_types' => array( 'manga_view', 'manga_download' ),
			'top_label'   => 'Top Manga Items',
			'low_label'   => 'Least Active Manga Items',
			'peak_label'  => 'Highest Manga Days',
			'quiet_label' => 'Lowest Manga Days',
		),
		'nostalgia' => array(
			'title'       => 'Nostalgia',
			'summary'     => sprintf( '%s nostalgia plays in this period.', number_format_i18n( $event_totals['nostalgia_play'] ?? 0 ) ),
			'event_types' => array( 'nostalgia_play' ),
			'top_label'   => 'Top Nostalgia Items',
			'low_label'   => 'Least Played Nostalgia Items',
			'peak_label'  => 'Highest Nostalgia Days',
			'quiet_label' => 'Lowest Nostalgia Days',
		),
	);

	foreach ( $detail_metric_map as $metric_key => $detail_config ) {
		$local_items   = $get_local_item_rows( $detail_config['event_types'] );
		$ga_items      = $get_ga_item_rows( $detail_config['event_types'] );
		$local_periods = $get_local_period_rows( $detail_config['event_types'] );
		$ga_periods    = $get_ga_period_rows( $detail_config['event_types'] );

		$metric_details[ $metric_key ] = array(
			'title'   => $detail_config['title'],
			'summary' => $detail_config['summary'],
			'cards'   => array(
				array(
					'title' => $detail_config['top_label'],
					'items' => smpt_analytics_merge_item_rankings_sorted( 5, false, $local_items, $ga_items ),
				),
				array(
					'title' => $detail_config['low_label'],
					'items' => smpt_analytics_merge_item_rankings_sorted( 5, true, $local_items, $ga_items ),
				),
				array(
					'title' => $detail_config['peak_label'],
					'items' => smpt_analytics_merge_period_rankings( 5, false, $local_periods, $ga_periods ),
				),
				array(
					'title' => $detail_config['quiet_label'],
					'items' => smpt_analytics_merge_period_rankings( 5, true, $local_periods, $ga_periods ),
				),
			),
		);
	}

	return new WP_REST_Response( array(
		'period'      => $period,
		'kpis'        => array(
			'visitors'       => $visitors,
			'streams'        => $event_totals['stream'] ?? 0,
			'downloads'      => $event_totals['download'] ?? 0,
			'music_streams'  => $event_totals['music_stream'] ?? 0,
			'manga'          => $manga_total,
			'manga_views'    => $event_totals['manga_view'] ?? 0,
			'manga_downloads'=> $event_totals['manga_download'] ?? 0,
			'nostalgia'      => $event_totals['nostalgia_play'] ?? 0,
		),
		'details'      => array(
			'default_metric' => 'streams',
			'metrics'        => $metric_details,
		),
		'timeline'     => $timeline,
		'top_streams'  => smpt_analytics_merge_item_rankings( 10, $top_streams, $ga_top_streams ),
		'top_downloads'=> smpt_analytics_merge_item_rankings( 10, $top_downloads, $ga_top_downloads ),
		'top_music'    => smpt_analytics_merge_item_rankings( 10, $top_music, $ga_top_music ),
		'funnel'       => $funnel,
		'countries'    => $format_list( $countries ),
		'devices'      => $format_list( $devices ),
		'oses'         => $format_list( $oses ),
		'browsers'     => $format_list( $browsers ),
		'hours'        => $hours,
		'referrers'    => $format_list( $referrers ),
		'resolutions'  => $format_list( $resolutions ),
		'languages'    => $format_list( $languages ),
		'connections'  => $format_list( $connections ),
		'new_vs_returning' => array(
			'new'       => $new_visitors,
			'returning' => $returning_visitors,
		),
	) );
}
