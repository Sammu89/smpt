(function () {
	'use strict';

	// --- Session logic: 1 view per media item per 24h session ---
	var SESSION_GAP_MS = 24 * 60 * 60 * 1000; // 24 hours
	var FLUSH_INTERVAL = 30000; // 30 seconds

	var lastVisit;
	try {
		lastVisit = parseInt(localStorage.getItem('smpt_last_visit') || '0', 10);
	} catch (e) {
		lastVisit = 0;
	}

	var now = Date.now();
	if (lastVisit > 0 && (now - lastVisit) > SESSION_GAP_MS) {
		// New session — clear viewed media set.
		try {
			localStorage.removeItem('smpt_viewed_media');
		} catch (e) { /* noop */ }
	}
	try {
		localStorage.setItem('smpt_last_visit', String(now));
	} catch (e) { /* noop */ }

	function getViewedSet() {
		try {
			var raw = localStorage.getItem('smpt_viewed_media');
			return raw ? JSON.parse(raw) : [];
		} catch (e) {
			return [];
		}
	}

	function addToViewedSet(itemId) {
		var viewed = getViewedSet();
		if (viewed.indexOf(itemId) === -1) {
			viewed.push(itemId);
			try {
				localStorage.setItem('smpt_viewed_media', JSON.stringify(viewed));
			} catch (e) { /* noop */ }
		}
	}

	function isViewed(itemId) {
		return getViewedSet().indexOf(itemId) !== -1;
	}

	// --- Watch-time tracker instances ---
	var trackers = [];

	/**
	 * Create a watch-time tracker for a media element.
	 *
	 * @param {HTMLMediaElement} mediaEl  The <video> or <audio> element.
	 * @param {string}           eventType 'stream' or 'music_stream'.
	 * @param {string}           itemId    Identifier for the media item.
	 */
	function createTracker(mediaEl, eventType, itemId) {
		var state = {
			eventId: null,
			seconds: 0,
			milestone: 0,
			isVisible: false,
			pageVisible: document.visibilityState === 'visible',
			timer: null,
			lastFlush: 0
		};

		// --- Intersection Observer for viewport check ---
		var observer = new IntersectionObserver(function (entries) {
			state.isVisible = entries[0].isIntersecting;
			checkTimer();
		}, { threshold: 0.5 });

		observer.observe(mediaEl);

		// --- Visibility change ---
		document.addEventListener('visibilitychange', function () {
			state.pageVisible = document.visibilityState === 'visible';
			checkTimer();

			// Send final update when page is hidden.
			if (document.visibilityState === 'hidden' && state.eventId && state.seconds > 0) {
				flush();
			}
		});

		// --- Media events ---
		mediaEl.addEventListener('play', function () {
			// Track view event (once per session).
			if (!isViewed(itemId) && typeof window.smptTrack === 'function') {
				window.smptTrack(eventType, itemId);
				addToViewedSet(itemId);
				// Try to get event_id from the response — for sendBeacon this isn't possible,
				// so we'll store the item info and let the watch endpoint look it up.
			}
			checkTimer();
		});

		mediaEl.addEventListener('pause', checkTimer);
		mediaEl.addEventListener('ended', function () {
			checkTimer();
			if (state.eventId && state.seconds > 0) {
				state.milestone = 100;
				flush();
				document.dispatchEvent(new CustomEvent('smpt:watchComplete', { detail: { itemId: itemId } }));
			}
		});

		// --- Periodically try to get event_id if we don't have one ---
		function tryGetEventId() {
			if (state.eventId) return;
			// We'll use item_id + event_type to create a watch update.
			// The REST endpoint will need to handle lookup by these fields.
			// For now, we'll store a sentinel so the periodic flush works.
			// The track endpoint returns event_id, but sendBeacon is fire-and-forget.
			// Instead, we'll use a fetch for the initial track call.
			if (!isViewed(itemId)) return;

			// Use a fetch to get the event_id.
			var payload = JSON.stringify({
				visitor_hash: window.smptVisitorHash || '',
				event_type: eventType,
				item_id: itemId || '',
				page_url: location.pathname,
				referrer: document.referrer || '',
				meta: {}
			});

			var restUrl = (typeof smptAnalytics !== 'undefined' && smptAnalytics.rest_url)
				? smptAnalytics.rest_url
				: '/wp-json/smpt/v1/track';

			fetch(restUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: payload,
				keepalive: true
			}).then(function (r) { return r.json(); }).then(function (data) {
				if (data && data.event_id) {
					state.eventId = data.event_id;
				}
			}).catch(function () { /* noop */ });
		}

		function checkTimer() {
			var shouldRun = !mediaEl.paused && !mediaEl.ended && state.isVisible && state.pageVisible;

			if (shouldRun && !state.timer) {
				// If first play, try to get event_id.
				if (!state.eventId) {
					tryGetEventId();
				}

				state.timer = setInterval(function () {
					state.seconds++;

					// Calculate milestone.
					if (mediaEl.duration && isFinite(mediaEl.duration) && mediaEl.duration > 0) {
						var pct = (mediaEl.currentTime / mediaEl.duration) * 100;
						if (pct >= 100) state.milestone = 100;
						else if (pct >= 75) state.milestone = Math.max(state.milestone, 75);
						else if (pct >= 50) state.milestone = Math.max(state.milestone, 50);
						else if (pct >= 25) state.milestone = Math.max(state.milestone, 25);
					}

					// Periodic flush every 30s.
					var elapsed = Date.now() - state.lastFlush;
					if (elapsed >= FLUSH_INTERVAL && state.eventId) {
						flush();
					}
				}, 1000);
			} else if (!shouldRun && state.timer) {
				clearInterval(state.timer);
				state.timer = null;
			}
		}

		function flush() {
			if (!state.eventId || state.seconds === 0) return;

			if (typeof window.smptWatch === 'function') {
				window.smptWatch({
					event_id: state.eventId,
					item_id: itemId,
					event_type: eventType,
					watch_seconds: state.seconds,
					milestone: state.milestone
				});
			}

			state.lastFlush = Date.now();
			console.log('[smpt] Watch flush: ' + itemId + ' ' + state.seconds + 's milestone=' + state.milestone);
		}

		trackers.push({ el: mediaEl, state: state, flush: flush });
	}

	// --- Initialize after DOM is ready ---
	function init() {
		// Track <video class="videoPlaceholder"> elements (anime episodes).
		var videos = document.querySelectorAll('video.videoPlaceholder');
		videos.forEach(function (video) {
			var container = video.closest('[id^="episodio_"]');
			var itemId = container ? container.id : 'unknown_video';
			createTracker(video, 'stream', itemId);
		});

		// Track <audio> elements (music).
		var audios = document.querySelectorAll('audio');
		audios.forEach(function (audio) {
			var src = audio.getAttribute('src') || '';
			var fileName = src.substring(src.lastIndexOf('/') + 1);
			fileName = fileName.replace(/-/g, ' ').replace(/^[\d]{1,3}/, '');
			fileName = fileName.substring(0, fileName.lastIndexOf('.')).trim();
			if (fileName) {
				createTracker(audio, 'music_stream', fileName);
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
