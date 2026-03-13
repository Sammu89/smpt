(function () {
	'use strict';

	// --- Fingerprint: localStorage with cookie fallback ---
	var vid;
	try {
		vid = localStorage.getItem('smpt_vid');
	} catch (e) {
		vid = null;
	}
	if (!vid) {
		// Check cookie fallback.
		var match = document.cookie.match(/(?:^|; )smpt_vid=([^;]+)/);
		vid = match ? decodeURIComponent(match[1]) : null;
	}
	if (!vid) {
		vid = crypto.randomUUID ? crypto.randomUUID() : ('xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx').replace(/[xy]/g, function (c) {
			var r = Math.random() * 16 | 0;
			return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
		});
		try {
			localStorage.setItem('smpt_vid', vid);
		} catch (e) {
			// localStorage unavailable — use cookie.
		}
		// Always set cookie as fallback.
		var expires = new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toUTCString();
		document.cookie = 'smpt_vid=' + encodeURIComponent(vid) + '; expires=' + expires + '; path=/; SameSite=Lax';
	}

	// --- Collect metadata once ---
	var ua = navigator.userAgent || '';

	function parseOS() {
		if (/Windows NT 10/.test(ua)) return 'Windows 10/11';
		if (/Windows NT/.test(ua)) return 'Windows';
		if (/Mac OS X ([\d_]+)/.test(ua)) return 'macOS ' + RegExp.$1.replace(/_/g, '.');
		if (/CrOS/.test(ua)) return 'Chrome OS';
		if (/Android ([\d.]+)/.test(ua)) return 'Android ' + RegExp.$1;
		if (/iPhone OS ([\d_]+)/.test(ua)) return 'iOS ' + RegExp.$1.replace(/_/g, '.');
		if (/iPad.*OS ([\d_]+)/.test(ua)) return 'iPadOS ' + RegExp.$1.replace(/_/g, '.');
		if (/Linux/.test(ua)) return 'Linux';
		return '';
	}

	function parseBrowser() {
		if (/EdgA?\/([\d.]+)/.test(ua)) return 'Edge ' + RegExp.$1;
		if (/OPR\/([\d.]+)/.test(ua)) return 'Opera ' + RegExp.$1;
		if (/Chrome\/([\d.]+)/.test(ua) && !/Edg/.test(ua)) return 'Chrome ' + RegExp.$1;
		if (/Firefox\/([\d.]+)/.test(ua)) return 'Firefox ' + RegExp.$1;
		if (/Version\/([\d.]+).*Safari/.test(ua)) return 'Safari ' + RegExp.$1;
		return '';
	}

	var sw = screen.width || 0;
	var sh = screen.height || 0;
	var deviceType = sw < 768 ? 'mobile' : (sw < 1024 ? 'tablet' : 'desktop');
	var conn = '';
	if (navigator.connection && navigator.connection.effectiveType) {
		conn = navigator.connection.effectiveType;
	}

	var meta = {
		device_type: deviceType,
		os: parseOS(),
		browser: parseBrowser(),
		screen_width: sw,
		screen_height: sh,
		language: (navigator.language || '').substring(0, 10),
		connection: conn
	};

	// --- Deduplication per page session ---
	var tracked = new Set();

	// --- REST URL ---
	var restUrl = (typeof smptAnalytics !== 'undefined' && smptAnalytics.rest_url)
		? smptAnalytics.rest_url
		: '/wp-json/smpt/v1/track';

	var watchUrl = (typeof smptAnalytics !== 'undefined' && smptAnalytics.watch_url)
		? smptAnalytics.watch_url
		: '/wp-json/smpt/v1/watch';

	/**
	 * Track an event via sendBeacon.
	 *
	 * @param {string} eventType  Event type identifier.
	 * @param {string} itemId     Item identifier.
	 * @return {void}
	 */
	function smptTrack(eventType, itemId) {
		var key = eventType + ':' + itemId;
		if (tracked.has(key)) {
			console.log('[smpt] Duplicate skipped: ' + key);
			return;
		}
		tracked.add(key);

		var payload = JSON.stringify({
			visitor_hash: vid,
			event_type: eventType,
			item_id: itemId || '',
			page_url: location.pathname,
			referrer: document.referrer || '',
			meta: meta
		});

		if (navigator.sendBeacon) {
			navigator.sendBeacon(restUrl, new Blob([payload], { type: 'application/json' }));
		} else {
			// Fallback for old browsers.
			var xhr = new XMLHttpRequest();
			xhr.open('POST', restUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/json');
			xhr.send(payload);
		}

		console.log('[smpt] Tracked: ' + key);
	}

	/**
	 * Send a watch-time update via sendBeacon.
	 *
	 * @param {object} data Watch data payload.
	 * @return {void}
	 */
	function smptWatch(data) {
		data.visitor_hash = vid;
		var payload = JSON.stringify(data);

		if (navigator.sendBeacon) {
			navigator.sendBeacon(watchUrl, new Blob([payload], { type: 'application/json' }));
		} else {
			var xhr = new XMLHttpRequest();
			xhr.open('POST', watchUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/json');
			xhr.send(payload);
		}
	}

	// --- Expose globally ---
	window.smptTrack = smptTrack;
	window.smptWatch = smptWatch;
	window.smptVisitorHash = vid;
})();
