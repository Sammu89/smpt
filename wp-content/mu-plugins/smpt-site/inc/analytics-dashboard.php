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
		<!-- Period selector -->
		<div class="smpt-period-bar">
			<button class="smpt-period-btn" data-period="today">Today</button>
			<button class="smpt-period-btn" data-period="week">This Week</button>
			<button class="smpt-period-btn smpt-period-active" data-period="month">This Month</button>
			<button class="smpt-period-btn" data-period="year">This Year</button>
			<button class="smpt-period-btn" data-period="all">All Time</button>
		</div>

		<!-- Loading indicator -->
		<div id="smpt-loading" class="smpt-loading">Loading analytics&hellip;</div>

		<!-- KPI cards -->
		<div class="smpt-kpi-grid" id="smpt-kpis"></div>

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
	$co     = $wpdb->prefix . 'smpt_counters';

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

	// --- Events over time ---
	$timeline_raw = $wpdb->get_results( $wpdb->prepare(
		"SELECT DATE_FORMAT(created_at, %s) as period_label, event_type, COUNT(*) as cnt
		 FROM {$ev} WHERE created_at BETWEEN %s AND %s
		 GROUP BY period_label, event_type ORDER BY period_label",
		$gf, $start, $end
	) );
	$timeline = array();
	foreach ( $timeline_raw as $row ) {
		$timeline[] = array(
			'label' => $row->period_label,
			'type'  => $row->event_type,
			'count' => (int) $row->cnt,
		);
	}

	// --- Top episodes (streams) ---
	$top_streams = $wpdb->get_results( $wpdb->prepare(
		"SELECT item_id, COUNT(*) as cnt FROM {$ev}
		 WHERE event_type = 'stream' AND created_at BETWEEN %s AND %s
		 GROUP BY item_id ORDER BY cnt DESC LIMIT 10",
		$start, $end
	) );

	// --- Top episodes (downloads) ---
	$top_downloads = $wpdb->get_results( $wpdb->prepare(
		"SELECT item_id, COUNT(*) as cnt FROM {$ev}
		 WHERE event_type = 'download' AND created_at BETWEEN %s AND %s
		 GROUP BY item_id ORDER BY cnt DESC LIMIT 10",
		$start, $end
	) );

	// --- Top music ---
	$top_music = $wpdb->get_results( $wpdb->prepare(
		"SELECT item_id, COUNT(*) as cnt FROM {$ev}
		 WHERE event_type = 'music_stream' AND created_at BETWEEN %s AND %s
		 GROUP BY item_id ORDER BY cnt DESC LIMIT 10",
		$start, $end
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

	// --- Format response ---
	$format_list = static function ( $rows ) {
		$out = array();
		foreach ( $rows as $row ) {
			$out[] = array( 'label' => $row->label ?? $row->item_id, 'count' => (int) $row->cnt );
		}
		return $out;
	};

	return new WP_REST_Response( array(
		'period'      => $period,
		'kpis'        => array(
			'visitors'       => $visitors,
			'streams'        => $event_totals['stream'] ?? 0,
			'downloads'      => $event_totals['download'] ?? 0,
			'music_streams'  => $event_totals['music_stream'] ?? 0,
			'manga_views'    => $event_totals['manga_view'] ?? 0,
			'manga_downloads'=> $event_totals['manga_download'] ?? 0,
			'nostalgia'      => $event_totals['nostalgia_play'] ?? 0,
		),
		'timeline'     => $timeline,
		'top_streams'  => $format_list( $top_streams ),
		'top_downloads'=> $format_list( $top_downloads ),
		'top_music'    => $format_list( $top_music ),
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
