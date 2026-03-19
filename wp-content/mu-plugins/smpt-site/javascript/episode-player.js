/**
 * episode-player.js — Unified episode playback module
 *
 * Merges:
 *   1. episode-limit-control.js  — daily view-limit enforcement
 *   2. video.js                  — accordion toggles, shared player, AV1 detection, stream/nostalgia playback
 *   3. watch-tracker.js          — watch-time milestone tracking (IntersectionObserver + visibility API)
 *
 * Dependencies: jQuery ($), window.smptTrack, window.smptWatch,
 *               window.smptVisitorHash, smptAnalytics, smptEpInteractions
 */
(function ($) {
	'use strict';

	// =====================================================================
	// SHARED STATE
	// =====================================================================

	/**
	 * Unified in-memory dedup set.  Used by both the playback tracker
	 * (video.js layer — keyed "episodio_NNN" / "nostalgia_ep_NNN") and the
	 * watch-tracker layer (keyed the same way via createTracker).
	 */
	var trackedVideos = new Set();

	var activeCard = null;

	// =====================================================================
	// ACTIVITY LOG (localStorage-based, max 30 entries)
	// =====================================================================

	var ACTIVITY_KEY = 'smpt_activity_log';
	var ACTIVITY_DISMISSED_KEY = 'smpt_activity_dismissed';
	var ACTIVITY_MAX = 30;

	window.smptLogActivity = function (type, message) {
		try {
			var log = JSON.parse(localStorage.getItem(ACTIVITY_KEY) || '[]');
			// Dedup: skip if the most recent entry has the same type + message
			if (log.length && log[0].type === type && log[0].msg === message) { return; }
			log.unshift({ type: type, msg: message, ts: Date.now() });
			if (log.length > ACTIVITY_MAX) { log = log.slice(0, ACTIVITY_MAX); }
			localStorage.setItem(ACTIVITY_KEY, JSON.stringify(log));
			localStorage.removeItem(ACTIVITY_DISMISSED_KEY);
		} catch (e) { /* noop */ }
	};

	window.smptGetActivityLog = function () {
		try {
			return JSON.parse(localStorage.getItem(ACTIVITY_KEY) || '[]');
		} catch (e) {
			return [];
		}
	};

	window.smptIsActivityDismissed = function () {
		try {
			return localStorage.getItem(ACTIVITY_DISMISSED_KEY) === '1';
		} catch (e) {
			return false;
		}
	};

	window.smptDismissActivity = function () {
		try {
			localStorage.setItem(ACTIVITY_DISMISSED_KEY, '1');
		} catch (e) { /* noop */ }
	};

	// =====================================================================
	// AV1 SUPPORT DETECTION
	// =====================================================================

	var supportsAv1 = (function () {
		var v = document.createElement('video');
		return v.canPlayType('video/mp4; codecs="av01.0.05M.08,opus"') !== '';
	})();

	// =====================================================================
	// EPISODE VIEW LIMIT CONTROL
	// =====================================================================

	var DAILY_VIEWS_KEY = 'smpt_daily_views';
	var DAILY_VIEWS_DATE_KEY = 'smpt_daily_views_date';
	var CAP_TOAST_SHOWN_KEY = 'smpt_cap_toast_shown';

	var viewStatus = {
		allowed: true,
		viewsToday: 0,
		limit: 5,
		isAdmin: false,
		secondsUntilReset: 0,
		ready: false
	};

	// ── localStorage — calendar-day based (not rolling 24h) ──

	function getTodayDate() {
		var d = new Date();
		return d.getFullYear() + '-' +
			String(d.getMonth() + 1).padStart(2, '0') + '-' +
			String(d.getDate()).padStart(2, '0');
	}

	function getDailyViews() {
		try {
			var storedDate = localStorage.getItem(DAILY_VIEWS_DATE_KEY);
			if (storedDate !== getTodayDate()) {
				localStorage.removeItem(DAILY_VIEWS_KEY);
				localStorage.removeItem(CAP_TOAST_SHOWN_KEY);
				localStorage.setItem(DAILY_VIEWS_DATE_KEY, getTodayDate());
				return {};
			}
			var raw = localStorage.getItem(DAILY_VIEWS_KEY);
			return raw ? JSON.parse(raw) : {};
		} catch (e) {
			return {};
		}
	}

	function addToDailyViews(episodeNum) {
		try {
			var views = getDailyViews();
			var padded = String(episodeNum).padStart(3, '0');
			views[padded] = true;
			localStorage.setItem(DAILY_VIEWS_KEY, JSON.stringify(views));
			localStorage.setItem(DAILY_VIEWS_DATE_KEY, getTodayDate());
		} catch (e) { /* noop */ }
	}

	function hasViewedEpisode(episodeNum) {
		var views = getDailyViews();
		var padded = String(episodeNum).padStart(3, '0');
		return views.hasOwnProperty(padded);
	}

	function getDailyViewCount() {
		return Object.keys(getDailyViews()).length;
	}

	// ── Initialize view status from server ──

	function initViewStatus() {
		$.getJSON('/wp-json/smpt/v1/ep-view-status')
			.done(function (data) {
				viewStatus.allowed = data.allowed;
				viewStatus.viewsToday = data.views_today;
				viewStatus.limit = data.limit;
				viewStatus.isAdmin = data.limit >= 999;
				viewStatus.secondsUntilReset = data.seconds_until_reset || 0;
				viewStatus.ready = true;

				var localCount = getDailyViewCount();
				if (data.views_today > localCount) {
					viewStatus.viewsToday = data.views_today;
				}

				if (!viewStatus.allowed && !viewStatus.isAdmin) {
					disableNewEpisodeButtons();
				}
			})
			.fail(function () {
				viewStatus.ready = true;
			});
	}

	// ── Check / record views ──

	function isViewAllowed(episodeNum) {
		if (viewStatus.isAdmin) return true;
		if (hasViewedEpisode(episodeNum)) return true;

		var localCount = getDailyViewCount();
		var serverCount = viewStatus.viewsToday;
		var effectiveCount = Math.max(localCount, serverCount);

		return effectiveCount < viewStatus.limit;
	}

	function recordView(episodeNum) {
		addToDailyViews(episodeNum);
		viewStatus.viewsToday += 1;

		if (viewStatus.viewsToday >= viewStatus.limit && !viewStatus.isAdmin) {
			viewStatus.allowed = false;
			disableNewEpisodeButtons();
		}

		$.post('/wp-json/smpt/v1/ep-record-view', {
			episode_id: episodeNum
		});
	}

	// ── Disable buttons for unwatched episodes ──

	function formatSecondsToHours(seconds) {
		if (!seconds || seconds < 0) return '24';
		return String(Math.ceil(seconds / 3600));
	}

	function buildLimitDisabledMessage() {
		return 'Limite diário atingido. Volta em ' + formatSecondsToHours(viewStatus.secondsUntilReset) + ' horas.';
	}

	function disableNewEpisodeButtons() {
		window.smptLogActivity('limit_reached', 'Limite di\u00e1rio de epis\u00f3dios atingido (' + viewStatus.limit + '/' + viewStatus.limit + ')');
		$('.smpt-toggle').each(function () {
			var $btn = $(this);
			var epNum = parseInt($btn.data('ep'), 10);

			if (hasViewedEpisode(epNum)) {
				return;
			}

			var reason = buildLimitDisabledMessage();
			$btn.addClass('smpt-toggle--disabled').attr('disabled', 'disabled').css({
				opacity: '0.5',
				cursor: 'not-allowed'
			}).attr({
				title: reason,
				'aria-label': reason
			});

			var target = $btn.data('target');
			var $card = $btn.closest('.contentor_episodio');
			var $subsection = $card.find('.smpt-subsection--' + target);
			$subsection.html(
				'<div class="smpt-limit-message" style="padding: 20px; text-align: center; background: #f9f9f9; border-radius: 4px;">' +
				'<p style="font-size: 16px; margin: 0 0 12px 0;"><strong>Limite atingido</strong></p>' +
				'<p style="font-size: 14px; color: #666; margin: 0 0 16px 0;">Volta em ' +
				formatSecondsToHours(viewStatus.secondsUntilReset) + ' horas</p>' +
				'<a href="/painel/" style="display: inline-block; padding: 8px 16px; background: #8b5cf6; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;">Ver meu progresso &rarr;</a>' +
				'</div>'
			);
		});
	}

	// ── Limit-check click interceptors (must fire BEFORE playback handlers) ──

	$(document).on('click', '.smpt-toggle', function (e) {
		var $btn = $(this);
		var epNum = parseInt($btn.data('ep'), 10);

		if ($btn.hasClass('smpt-toggle--disabled') || $btn.is('[disabled]')) {
			e.preventDefault();
			e.stopImmediatePropagation();
			return false;
		}

		if (!hasViewedEpisode(epNum) && !viewStatus.isAdmin) {
			if (!isViewAllowed(epNum)) {
				e.preventDefault();
				e.stopImmediatePropagation();
				disableNewEpisodeButtons();
				return false;
			}
			recordView(epNum);
		}
	});

	$(document).on('click', '.smpt-play--stream', function (e) {
		var $btn = $(this);
		var epNum = parseInt($btn.data('ep'), 10);

		if (viewStatus.isAdmin || hasViewedEpisode(epNum)) {
			return;
		}

		if (!isViewAllowed(epNum)) {
			e.preventDefault();
			e.stopImmediatePropagation();
			return false;
		}

		recordView(epNum);
	});

	$(document).on('click', '.smpt-play--nostalgia', function (e) {
		var $btn = $(this);
		var epNum = parseInt($btn.data('ep'), 10);

		if (viewStatus.isAdmin || hasViewedEpisode(epNum)) {
			return;
		}

		if (!isViewAllowed(epNum)) {
			e.preventDefault();
			e.stopImmediatePropagation();
			return false;
		}

		recordView(epNum);
	});

	$(document).on('click', '.smpt-dl', function (e) {
		var $link = $(this);

		if ($link.hasClass('smpt-dl--hd')) {
			return;
		}

		var $card = $link.closest('.contentor_episodio');
		var $toggle = $card.find('.smpt-toggle[data-ep]').first();
		var epNum = $toggle.length ? parseInt($toggle.data('ep'), 10) : 0;

		if (!epNum) return;

		if (viewStatus.isAdmin || hasViewedEpisode(epNum)) {
			return;
		}

		e.preventDefault();

		if (!isViewAllowed(epNum)) {
			disableNewEpisodeButtons();
			return;
		}

		recordView(epNum);
		window.smptLogActivity('download', 'Fizeste download do epis\u00f3dio ' + epNum);
		window.location.href = $link.attr('href');
	});

	// ── 50-point cap toast (once per day) ──

	function showPointCapToast() {
		try {
			if (localStorage.getItem(CAP_TOAST_SHOWN_KEY) === getTodayDate()) {
				return;
			}
			localStorage.setItem(CAP_TOAST_SHOWN_KEY, getTodayDate());
			window.smptLogActivity('cap_reached', 'Limite m\u00e1ximo de 50 pontos por dia atingido');
		} catch (e) { /* noop */ }

		var toastId = 'smpt-point-cap-toast';
		if ($('#' + toastId).length) return;

		if (!$('#smpt-toast-styles').length) {
			$('head').append(
				'<style id="smpt-toast-styles">' +
				'@keyframes smptSlideIn { from { transform: translateX(-400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }' +
				'@keyframes smptSlideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(-400px); opacity: 0; } }' +
				'</style>'
			);
		}

		$('body').append(
			'<div id="' + toastId + '" style="' +
			'position: fixed; bottom: 20px; left: 20px; ' +
			'background: #2d3748; color: white; ' +
			'padding: 16px 20px; border-radius: 4px; ' +
			'font-size: 14px; max-width: 300px; ' +
			'box-shadow: 0 4px 12px rgba(0,0,0,0.3); ' +
			'z-index: 9999; ' +
			'animation: smptSlideIn 0.3s ease-out;' +
			'">' +
			'<strong>Limite m&aacute;ximo de 50 pontos por dia atingido.</strong> ' +
			'Podes continuar a interagir, mas mais pontos n&atilde;o ser&atilde;o ganhos hoje.' +
			'</div>'
		);

		setTimeout(function () {
			$('#' + toastId).css('animation', 'smptSlideOut 0.3s ease-out');
			setTimeout(function () { $('#' + toastId).remove(); }, 300);
		}, 8000);
	}

	window.smptShowPointCapToast = showPointCapToast;

	// ── Hook into SMPT REST responses for cap_reached ──

	var _origFetch = window.fetch;
	window.fetch = function () {
		var url = typeof arguments[0] === 'string' ? arguments[0] : '';
		var promise = _origFetch.apply(this, arguments);

		if (url.indexOf('/smpt/v1/') === -1) {
			return promise;
		}

		return promise.then(function (response) {
			var cloned = response.clone();
			cloned.json().then(function (data) {
				if (data && data.cap_reached) {
					showPointCapToast();
				}
			}).catch(function () { /* not JSON */ });
			return response;
		});
	};

	// =====================================================================
	// ACCORDION TOGGLE (video.js layer)
	// =====================================================================

	$(document).on('click', '.smpt-toggle', function () {
		var $btn = $(this);

		// Direct play: toggle has data-video-src (no nostalgia subsection)
		if ($btn.data('video-src')) {
			playStream($btn.closest('.contentor_episodio'), $btn.data('ep'), $btn.data('video-src'), $btn.data('poster') || '');
			return;
		}

		var target = $btn.data('target');
		var $card = $btn.closest('.contentor_episodio');
		var $targetSection = $card.find('.smpt-subsection--' + target);
		var $otherSection = $card.find('.smpt-subsection').not($targetSection);

		// Close other
		$otherSection.attr('hidden', '');
		$card.find('.smpt-toggle').not($btn).removeClass('smpt-toggle--active');

		// Toggle target
		if ($targetSection.is('[hidden]')) {
			$targetSection.removeAttr('hidden');
			$btn.addClass('smpt-toggle--active');
		} else {
			$targetSection.attr('hidden', '');
			$btn.removeClass('smpt-toggle--active');
		}
	});

	// =====================================================================
	// CLOSE ACTIVE PLAYER
	// =====================================================================

	function closeActivePlayer() {
		if (!activeCard) return;

		var $card = activeCard;
		var $player = $card.find('#smpt-shared-player');
		var $tv = $card.find('#smpt-shared-tv');

		// Pause video
		var video = $player.find('video')[0];
		if (video) {
			video.pause();
			video.removeAttribute('src');
			video.load();
		}

		// Clear iframe
		$tv.find('iframe').attr('src', '');

		// Move back to parking
		$player.attr('hidden', '').appendTo('#smpt-player-parking');
		$tv.attr('hidden', '').appendTo('#smpt-player-parking');

		// Restore card
		$card.find('.episodio-card-body').show();
		$card.css({ 'width': '', 'max-width': '' });

		activeCard = null;
	}

	// =====================================================================
	// STREAM PLAY LOGIC
	// =====================================================================

	function playStream($card, ep, videoSrc, poster) {
		closeActivePlayer();

		// Determine source based on AV1 support
		var src = videoSrc;
		if (!supportsAv1) {
			src = src
				.replace('https://sm-portugal.com/streaming/', 'https://sm-portugal.com/streamingh264/')
				.replace('[av1 opus]', '[h264 opus]');
		}

		// Hide card body
		$card.find('.episodio-card-body').hide();

		// Move shared player into card
		var $player = $('#smpt-shared-player');
		$player.removeAttr('hidden');
		$card.find('.nome_pt').after($player);

		// Set poster and source, then load
		var video = $player.find('video')[0];
		if (poster) {
			video.setAttribute('poster', poster);
		}
		var $source = $player.find('source');
		$source.attr('src', src);
		video.load();

		// Expand card
		$card.css({ 'width': '100%', 'max-width': '640px' });

		activeCard = $card;

		// Track play event (once per episode) — uses shared trackedVideos Set
		$(video).off('play.smpt').on('play.smpt', function () {
			var itemId = 'episodio_' + ep;
			if (!trackedVideos.has(itemId)) {
				trackedVideos.add(itemId);
				if (typeof window.smptTrack === 'function') {
					window.smptTrack('stream', itemId);
				}
				document.dispatchEvent(new CustomEvent('smpt:streamTracked', { detail: { ep: ep } }));
				window.smptLogActivity('stream', 'Viste o epis\u00f3dio ' + ep + ' (vers\u00e3o remasterizada)');
			}
		});
	}

	// ── Ver online (stream) button ──

	$(document).on('click', '.smpt-play--stream', function () {
		var $btn = $(this);
		playStream($btn.closest('.contentor_episodio'), $btn.data('ep'), $btn.data('video-src'), $btn.data('poster') || '');
	});

	// ── Nostalgia play ──

	$(document).on('click', '.smpt-play--nostalgia', function () {
		var $btn = $(this);
		var ep = $btn.data('ep');
		var url = $btn.data('nostalgia-url');
		var type = $btn.data('nostalgia-type');
		var $card = $btn.closest('.contentor_episodio');

		closeActivePlayer();

		// Hide card body
		$card.find('.episodio-card-body').hide();

		// Move shared TV into card
		var $tv = $('#smpt-shared-tv');
		$tv.removeAttr('hidden');
		$card.find('.nome_pt').after($tv);

		// Set iframe src
		$tv.find('iframe').attr('src', url);

		// Expand card
		$card.css({ 'width': '100%', 'max-width': '640px' });

		activeCard = $card;

		// Track (once per episode per session) — uses shared trackedVideos Set
		var itemId = 'nostalgia_ep_' + ep;
		if (!trackedVideos.has(itemId)) {
			trackedVideos.add(itemId);
			if (typeof window.smptTrack === 'function') {
				window.smptTrack('nostalgia_play', itemId);
			}
			document.dispatchEvent(new CustomEvent('smpt:streamTracked', { detail: { ep: ep } }));
			window.smptLogActivity('nostalgia', 'Viste o epis\u00f3dio ' + ep + ' (modo nostalgia)');
		}
	});

	// ── X close button ──

	$(document).on('click', '.smpt-player-close', function () {
		closeActivePlayer();
	});

	// Expose play function for external callers
	window.smptPlayStream = playStream;

	// =====================================================================
	// FONT SIZE ADJUSTMENT FOR .valor ELEMENTS
	// =====================================================================

	$(document).ready(function () {
		function adjustFontSize(element) {
			var el = $(element);
			var maxFontSize = 14;
			var minFontSize = 12;
			var fixedWidth = 246;

			el.css('font-size', maxFontSize + 'px');
			var textWidth = getTextWidth(el);

			while (textWidth > fixedWidth && maxFontSize > minFontSize) {
				maxFontSize--;
				el.css('font-size', maxFontSize + 'px');
				textWidth = getTextWidth(el);
			}
		}

		function getTextWidth(el) {
			var text = el.text();
			var tempSpan = $('<span>').text(text).css({
				'font-size': el.css('font-size'),
				'font-family': el.css('font-family'),
				'visibility': 'hidden',
				'white-space': 'nowrap'
			}).appendTo('body');
			var width = tempSpan.width();
			tempSpan.remove();
			return width;
		}

		$('.valor').each(function () {
			adjustFontSize(this);
		});
	});

	// =====================================================================
	// LOADING ANIMATION
	// =====================================================================

	window.addEventListener('load', function () {
		var loadingDiv = document.getElementById('loading');
		var prismalunarDiv = document.getElementById('prismalunar');

		if (loadingDiv) {
			loadingDiv.classList.add('fade-out');
			setTimeout(function () {
				loadingDiv.style.display = 'none';
			}, 1000);
		}

		if (prismalunarDiv) {
			setTimeout(function () {
				prismalunarDiv.style.display = '';
				prismalunarDiv.classList.add('fade-in');
			}, 1000);
		}
	});

	// =====================================================================
	// WATCH-TIME MILESTONE TRACKER
	// =====================================================================

	var SESSION_GAP_MS = 24 * 60 * 60 * 1000; // 24 hours
	var FLUSH_INTERVAL = 30000; // 30 seconds

	// ── localStorage session layer (kept alongside the in-memory Set) ──

	var lastVisit;
	try {
		lastVisit = parseInt(localStorage.getItem('smpt_last_visit') || '0', 10);
	} catch (e) {
		lastVisit = 0;
	}

	var _now = Date.now();
	if (lastVisit > 0 && (_now - lastVisit) > SESSION_GAP_MS) {
		try {
			localStorage.removeItem('smpt_viewed_media');
		} catch (e) { /* noop */ }
	}
	try {
		localStorage.setItem('smpt_last_visit', String(_now));
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

	// ── Tracker instances ──

	var trackers = [];

	/**
	 * Create a watch-time tracker for a media element.
	 *
	 * @param {HTMLMediaElement} mediaEl   The <video> or <audio> element.
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

		// Intersection Observer for viewport check
		var observer = new IntersectionObserver(function (entries) {
			state.isVisible = entries[0].isIntersecting;
			checkTimer();
		}, { threshold: 0.5 });

		observer.observe(mediaEl);

		// Visibility change
		document.addEventListener('visibilitychange', function () {
			state.pageVisible = document.visibilityState === 'visible';
			checkTimer();

			if (document.visibilityState === 'hidden' && state.eventId && state.seconds > 0) {
				flush();
			}
		});

		// Media events
		mediaEl.addEventListener('play', function () {
			// Track view event (once per session) — check BOTH dedup layers
			if (!isViewed(itemId) && !trackedVideos.has(itemId)) {
				addToViewedSet(itemId);
				trackedVideos.add(itemId);

				var payload = JSON.stringify({
					visitor_hash: window.smptVisitorHash || '',
					event_type: eventType,
					item_id: itemId || '',
					page_url: location.pathname,
					referrer: document.referrer || '',
					meta: {}
				});

				var trackUrl = (typeof smptAnalytics !== 'undefined' && smptAnalytics.rest_url)
					? smptAnalytics.rest_url
					: '/wp-json/smpt/v1/track';

				fetch(trackUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: payload,
					keepalive: true
				}).then(function (r) { return r.json(); }).then(function (data) {
					if (data && data.event_id) {
						state.eventId = data.event_id;
					}
				}).catch(function () { /* noop */ });
			} else if (!isViewed(itemId)) {
				// In-memory Set already had it but localStorage did not — sync
				addToViewedSet(itemId);
			} else if (!trackedVideos.has(itemId)) {
				// localStorage had it but in-memory Set did not — sync
				trackedVideos.add(itemId);
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

		function checkTimer() {
			var shouldRun = !mediaEl.paused && !mediaEl.ended && state.isVisible && state.pageVisible;

			if (shouldRun && !state.timer) {
				state.timer = setInterval(function () {
					state.seconds++;

					// Calculate milestone
					if (mediaEl.duration && isFinite(mediaEl.duration) && mediaEl.duration > 0) {
						var pct = (mediaEl.currentTime / mediaEl.duration) * 100;
						if (pct >= 100) state.milestone = 100;
						else if (pct >= 75) state.milestone = Math.max(state.milestone, 75);
						else if (pct >= 50) state.milestone = Math.max(state.milestone, 50);
						else if (pct >= 25) state.milestone = Math.max(state.milestone, 25);
					}

					// Periodic flush every 30s
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

	// ── Initialize watch trackers after DOM is ready ──

	function initWatchTrackers() {
		// Track <video class="smpt-video"> elements (anime episodes).
		// Note: the PHP template (tabelas_episodios.php) outputs class="smpt-video"
		// on the shared player <video> tag.
		var videos = document.querySelectorAll('video.smpt-video');
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

	// =====================================================================
	// INIT — everything that runs on DOM ready
	// =====================================================================

	$(document).ready(function () {
		// Watch-tracker init — runs on any page with <video> or <audio>
		initWatchTrackers();

		// Episode-limit-control init — only on episode pages
		if ($('.contentor_episodio').length) {
			getDailyViews();
			initViewStatus();
		}
	});

})(jQuery);
