(function () {
	'use strict';

	var cfg = window.smptDashboard || {};
	var charts = {};
	var currentPeriod = 'month';

	// Sailor Moon color palette.
	var COLORS = {
		stream:         '#FF69B4',
		download:       '#9B59B6',
		music_stream:   '#3498DB',
		manga_view:     '#F39C12',
		manga_download: '#E74C3C',
		nostalgia_play: '#1ABC9C'
	};
	var PALETTE = ['#FF69B4', '#9B59B6', '#3498DB', '#F39C12', '#E74C3C', '#1ABC9C', '#E91E63', '#8E44AD', '#2980B9', '#D35400'];

	function num(n) {
		return (n || 0).toLocaleString();
	}

	function destroyCharts() {
		Object.keys(charts).forEach(function (k) {
			if (charts[k]) { charts[k].destroy(); charts[k] = null; }
		});
	}

	function showLoading(show) {
		var el = document.getElementById('smpt-loading');
		if (el) el.classList.toggle('smpt-hidden', !show);
	}

	// --- Fetch data ---
	function fetchStats(period) {
		showLoading(true);
		return fetch(cfg.rest_url + '?period=' + encodeURIComponent(period), {
			headers: { 'X-WP-Nonce': cfg.nonce }
		})
		.then(function (r) { return r.json(); })
		.then(function (data) {
			showLoading(false);
			return data;
		})
		.catch(function () {
			showLoading(false);
		});
	}

	// --- Render KPIs ---
	function renderKPIs(kpis) {
		var container = document.getElementById('smpt-kpis');
		if (!container) return;

		var cards = [
			{ label: 'Visitors',   value: kpis.visitors,        color: '#50575e' },
			{ label: 'Streams',    value: kpis.streams,         color: COLORS.stream },
			{ label: 'Downloads',  value: kpis.downloads,       color: COLORS.download },
			{ label: 'Music',      value: kpis.music_streams,   color: COLORS.music_stream },
			{ label: 'Manga',      value: kpis.manga_views,     color: COLORS.manga_view },
			{ label: 'Nostalgia',  value: kpis.nostalgia,       color: COLORS.nostalgia_play }
		];

		container.innerHTML = cards.map(function (c) {
			return '<div class="smpt-kpi-card"><div class="smpt-kpi-number" style="color:' + c.color + '">' + num(c.value) + '</div><div class="smpt-kpi-label">' + c.label + '</div></div>';
		}).join('');
	}

	// --- Timeline chart ---
	function renderTimeline(timeline) {
		var ctx = document.getElementById('smpt-chart-timeline');
		if (!ctx) return;

		// Group by label, then by type.
		var labels = [];
		var byType = {};
		timeline.forEach(function (row) {
			if (labels.indexOf(row.label) === -1) labels.push(row.label);
			if (!byType[row.type]) byType[row.type] = {};
			byType[row.type][row.label] = row.count;
		});

		var datasets = Object.keys(byType).map(function (type) {
			return {
				label: type,
				data: labels.map(function (l) { return byType[type][l] || 0; }),
				borderColor: COLORS[type] || '#999',
				backgroundColor: (COLORS[type] || '#999') + '33',
				fill: true,
				tension: 0.3,
				pointRadius: 2
			};
		});

		charts.timeline = new Chart(ctx, {
			type: 'line',
			data: { labels: labels, datasets: datasets },
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: { mode: 'index', intersect: false },
				plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
				scales: {
					x: { ticks: { font: { size: 10 }, maxRotation: 45 } },
					y: { beginAtZero: true, ticks: { font: { size: 10 } } }
				}
			}
		});
	}

	// --- Horizontal bar chart helper ---
	function renderHBar(canvasId, items, color) {
		var ctx = document.getElementById(canvasId);
		if (!ctx) return;

		if (!items || items.length === 0) {
			ctx.parentElement.innerHTML = '<p class="smpt-empty">No data</p>';
			return;
		}

		var labels = items.map(function (i) { return i.label; });
		var data = items.map(function (i) { return i.count; });

		charts[canvasId] = new Chart(ctx, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: [{
					data: data,
					backgroundColor: color || COLORS.stream,
					borderRadius: 3
				}]
			},
			options: {
				indexAxis: 'y',
				responsive: true,
				maintainAspectRatio: false,
				plugins: { legend: { display: false } },
				scales: {
					x: { beginAtZero: true, ticks: { font: { size: 10 } } },
					y: { ticks: { font: { size: 10 } } }
				}
			}
		});
	}

	// --- Doughnut chart helper ---
	function renderDoughnut(canvasId, items) {
		var ctx = document.getElementById(canvasId);
		if (!ctx) return;

		if (!items || items.length === 0) {
			ctx.parentElement.innerHTML = '<p class="smpt-empty">No data</p>';
			return;
		}

		var labels = items.map(function (i) { return i.label; });
		var data = items.map(function (i) { return i.count; });

		charts[canvasId] = new Chart(ctx, {
			type: 'doughnut',
			data: {
				labels: labels,
				datasets: [{
					data: data,
					backgroundColor: PALETTE.slice(0, data.length),
					borderWidth: 1
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 }, padding: 6 } } }
			}
		});
	}

	// --- Watch completion funnel ---
	function renderFunnel(funnel) {
		var ctx = document.getElementById('smpt-chart-funnel');
		if (!ctx) return;

		var labels = ['0%', '25%', '50%', '75%', '100%'];
		var data = [funnel[0] || 0, funnel[25] || 0, funnel[50] || 0, funnel[75] || 0, funnel[100] || 0];

		charts.funnel = new Chart(ctx, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: [{
					label: 'Events at milestone',
					data: data,
					backgroundColor: ['#e0e0e0', '#F39C12', '#FF69B4', '#9B59B6', '#1ABC9C'],
					borderRadius: 3
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: { legend: { display: false } },
				scales: {
					y: { beginAtZero: true, ticks: { font: { size: 10 } } },
					x: { ticks: { font: { size: 10 } } }
				}
			}
		});
	}

	// --- Peak hours ---
	function renderHours(hours) {
		var ctx = document.getElementById('smpt-chart-hours');
		if (!ctx) return;

		var labels = [];
		for (var i = 0; i < 24; i++) labels.push(i + 'h');

		charts.hours = new Chart(ctx, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: [{
					label: 'Events',
					data: hours,
					backgroundColor: '#FF69B480',
					borderColor: '#FF69B4',
					borderWidth: 1,
					borderRadius: 2
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: { legend: { display: false } },
				scales: {
					y: { beginAtZero: true, ticks: { font: { size: 10 } } },
					x: { ticks: { font: { size: 9 } } }
				}
			}
		});
	}

	// --- New vs Returning ---
	function renderNewRet(data) {
		var ctx = document.getElementById('smpt-chart-newret');
		if (!ctx) return;

		charts.newret = new Chart(ctx, {
			type: 'doughnut',
			data: {
				labels: ['New', 'Returning'],
				datasets: [{
					data: [data.new, data.returning],
					backgroundColor: ['#1ABC9C', '#9B59B6'],
					borderWidth: 1
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } }
			}
		});
	}

	// --- Table helper ---
	function renderTable(tableId, items) {
		var table = document.getElementById(tableId);
		if (!table) return;
		var tbody = table.querySelector('tbody');
		if (!tbody) return;

		if (!items || items.length === 0) {
			tbody.innerHTML = '<tr><td colspan="2" class="smpt-empty">No data</td></tr>';
			return;
		}

		tbody.innerHTML = items.map(function (item) {
			return '<tr><td class="smpt-table-label">' + escHtml(item.label) + '</td><td class="smpt-table-count">' + num(item.count) + '</td></tr>';
		}).join('');
	}

	function escHtml(str) {
		var div = document.createElement('div');
		div.textContent = str || '';
		return div.innerHTML;
	}

	// --- Main render ---
	function render(data) {
		if (!data) return;
		destroyCharts();
		renderKPIs(data.kpis);
		renderTimeline(data.timeline);
		renderHBar('smpt-chart-top-streams', data.top_streams, COLORS.stream);
		renderHBar('smpt-chart-top-downloads', data.top_downloads, COLORS.download);
		renderHBar('smpt-chart-top-music', data.top_music, COLORS.music_stream);
		renderFunnel(data.funnel);
		renderDoughnut('smpt-chart-country', data.countries);
		renderDoughnut('smpt-chart-device', data.devices);
		renderDoughnut('smpt-chart-os', data.oses);
		renderDoughnut('smpt-chart-browser', data.browsers);
		renderHours(data.hours);
		renderNewRet(data.new_vs_returning);
		renderDoughnut('smpt-chart-connection', data.connections);
		renderTable('smpt-table-referrers', data.referrers);
		renderTable('smpt-table-resolutions', data.resolutions);
		renderTable('smpt-table-languages', data.languages);
	}

	// --- Period selector ---
	function initPeriodButtons() {
		var buttons = document.querySelectorAll('.smpt-period-btn');
		buttons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				buttons.forEach(function (b) { b.classList.remove('smpt-period-active'); });
				btn.classList.add('smpt-period-active');
				currentPeriod = btn.getAttribute('data-period');
				fetchStats(currentPeriod).then(render);
			});
		});
	}

	// --- Init ---
	function init() {
		if (!document.getElementById('smpt-analytics-root')) return;
		initPeriodButtons();
		fetchStats(currentPeriod).then(render);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
