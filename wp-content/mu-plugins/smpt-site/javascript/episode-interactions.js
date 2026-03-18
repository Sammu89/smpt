(function($) {
    'use strict';

    // -------------------------------------------------------------------------
    // SVG icon paths (copied from fanfiction plugin)
    // -------------------------------------------------------------------------

    var STAR_PATH = 'M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.27 5.82 22 7 14.14 2 9.27l6.91-1.01L12 2z';

    var THUMB_UP_OUTLINE   = 'M19.017 31.992c-9.088 0-9.158-0.377-10.284-1.224-0.597-0.449-1.723-0.76-5.838-1.028-0.298-0.020-0.583-0.134-0.773-0.365-0.087-0.107-2.143-3.105-2.143-7.907 0-4.732 1.472-6.89 1.534-6.99 0.182-0.293 0.503-0.47 0.847-0.47 3.378 0 8.062-4.313 11.21-11.841 0.544-1.302 0.657-2.159 2.657-2.159 1.137 0 2.413 0.815 3.042 1.86 1.291 2.135 0.636 6.721 0.029 9.171 2.063-0.017 5.796-0.045 7.572-0.045 2.471 0 4.107 1.473 4.156 3.627 0.017 0.711-0.077 1.619-0.282 2.089 0.544 0.543 1.245 1.36 1.276 2.414 0.038 1.36-0.852 2.395-1.421 2.989 0.131 0.395 0.391 0.92 0.366 1.547-0.063 1.542-1.253 2.535-1.994 3.054 0.061 0.422 0.11 1.218-0.026 1.834-0.535 2.457-4.137 3.443-9.928 3.443zM3.426 27.712c3.584 0.297 5.5 0.698 6.51 1.459 0.782 0.589 0.662 0.822 9.081 0.822 2.568 0 7.59-0.107 7.976-1.87 0.153-0.705-0.59-1.398-0.593-1.403-0.203-0.501 0.023-1.089 0.518-1.305 0.008-0.004 2.005-0.719 2.050-1.835 0.030-0.713-0.46-1.142-0.471-1.16-0.291-0.452-0.185-1.072 0.257-1.38 0.005-0.004 1.299-0.788 1.267-1.857-0.024-0.849-1.143-1.447-1.177-1.466-0.25-0.143-0.432-0.39-0.489-0.674-0.056-0.282 0.007-0.579 0.183-0.808 0 0 0.509-0.808 0.49-1.566-0.037-1.623-1.782-1.674-2.156-1.674-2.523 0-9.001 0.025-9.001 0.025-0.349 0.002-0.652-0.164-0.84-0.443s-0.201-0.627-0.092-0.944c0.977-2.813 1.523-7.228 0.616-8.736-0.267-0.445-0.328-0.889-1.328-0.889-0.139 0-0.468 0.11-0.812 0.929-3.341 7.995-8.332 12.62-12.421 13.037-0.353 0.804-1.016 2.47-1.016 5.493 0 3.085 0.977 5.473 1.447 6.245z';
    var THUMB_UP_FILLED    = 'M19.017 31.992c-9.088 0-9.158-0.377-10.284-1.224-0.597-0.449-1.723-0.76-5.838-1.028-0.298-0.020-0.583-0.134-0.773-0.365-0.087-0.107-2.143-3.105-2.143-7.907 0-4.732 1.472-6.89 1.534-6.99 0.182-0.293 0.503-0.47 0.847-0.47 3.378 0 8.062-4.313 11.21-11.841 0.544-1.302 0.657-2.159 2.657-2.159 1.137 0 2.413 0.815 3.042 1.86 1.291 2.135 0.636 6.721 0.029 9.171 2.063-0.017 5.796-0.045 7.572-0.045 2.471 0 4.107 1.473 4.156 3.627 0.017 0.711-0.077 1.619-0.282 2.089 0.544 0.543 1.245 1.36 1.276 2.414 0.038 1.36-0.852 2.395-1.421 2.989 0.131 0.395 0.391 0.92 0.366 1.547-0.063 1.542-1.253 2.535-1.994 3.054 0.061 0.422 0.11 1.218-0.026 1.834-0.535 2.457-4.137 3.443-9.928 3.443z';
    var THUMB_DOWN_OUTLINE = 'M12.982 0.007c9.088 0 9.159 0.377 10.284 1.225 0.597 0.449 1.723 0.76 5.838 1.028 0.299 0.019 0.583 0.134 0.773 0.365 0.087 0.107 2.143 3.105 2.143 7.907 0 4.732-1.471 6.89-1.534 6.991-0.183 0.292-0.503 0.469-0.848 0.469-3.378 0-8.062 4.313-11.211 11.841-0.544 1.302-0.657 2.158-2.657 2.158-1.137 0-2.412-0.814-3.043-1.86-1.291-2.135-0.636-6.721-0.028-9.171-2.063 0.017-5.796 0.045-7.572 0.045-2.471 0-4.106-1.474-4.157-3.628-0.016-0.711 0.077-1.62 0.283-2.088-0.543-0.543-1.245-1.361-1.276-2.415-0.038-1.36 0.852-2.395 1.42-2.989-0.13-0.396-0.391-0.92-0.366-1.547 0.063-1.542 1.253-2.536 1.995-3.054-0.061-0.42-0.109-1.217 0.026-1.832 0.535-2.457 4.138-3.445 9.928-3.445zM28.575 4.289c-3.584-0.296-5.5-0.698-6.51-1.459-0.782-0.588-0.661-0.822-9.082-0.822-2.568 0-7.59 0.107-7.976 1.869-0.154 0.705 0.59 1.398 0.593 1.403 0.203 0.502-0.024 1.089-0.518 1.305-0.008 0.004-2.004 0.72-2.050 1.836-0.030 0.713 0.46 1.142 0.471 1.159 0.291 0.452 0.184 1.072-0.257 1.38-0.005 0.004-1.299 0.788-1.267 1.857 0.025 0.848 1.143 1.447 1.177 1.466 0.25 0.143 0.432 0.39 0.489 0.674 0.057 0.282-0.007 0.579-0.182 0.807 0 0-0.509 0.808-0.49 1.566 0.037 1.623 1.782 1.674 2.156 1.674 2.522 0 9.001-0.026 9.001-0.026 0.35-0.001 0.652 0.164 0.839 0.444s0.202 0.627 0.091 0.945c-0.976 2.814-1.522 7.227-0.616 8.735 0.267 0.445 0.328 0.889 1.328 0.889 0.139 0 0.468-0.11 0.812-0.93 3.343-7.994 8.334-12.619 12.423-13.036 0.352-0.804 1.015-2.47 1.015-5.493-0.001-3.085-0.979-5.472-1.449-6.245z';
    var THUMB_DOWN_FILLED  = 'M12.982 0.007c9.088 0 9.159 0.377 10.284 1.225 0.597 0.449 1.723 0.76 5.838 1.028 0.299 0.019 0.583 0.134 0.773 0.365 0.087 0.107 2.143 3.105 2.143 7.907 0 4.732-1.471 6.89-1.534 6.991-0.183 0.292-0.503 0.469-0.848 0.469-3.378 0-8.062 4.313-11.211 11.841-0.544 1.302-0.657 2.158-2.657 2.158-1.137 0-2.412-0.814-3.043-1.86-1.291-2.135-0.636-6.721-0.028-9.171-2.063 0.017-5.796 0.045-7.572 0.045-2.471 0-4.106-1.474-4.157-3.628-0.016-0.711 0.077-1.62 0.283-2.088-0.543-0.543-1.245-1.361-1.276-2.415-0.038-1.36 0.852-2.395 1.42-2.989-0.13-0.396-0.391-0.92-0.366-1.547 0.063-1.542 1.253-2.536 1.995-3.054-0.061-0.42-0.109-1.217 0.026-1.832 0.535-2.457 4.138-3.445 9.928-3.445z';

    function thumbUpSVG() {
        return '<svg class="smpt-ep-thumb-svg" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
            + '<path class="smpt-ep-thumb-bg" d="' + THUMB_UP_OUTLINE + '"/>'
            + '<path class="smpt-ep-thumb-fg" d="' + THUMB_UP_FILLED + '"/>'
            + '</svg>';
    }

    function thumbDownSVG() {
        return '<svg class="smpt-ep-thumb-svg" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
            + '<path class="smpt-ep-thumb-bg" d="' + THUMB_DOWN_OUTLINE + '"/>'
            + '<path class="smpt-ep-thumb-fg" d="' + THUMB_DOWN_FILLED + '"/>'
            + '</svg>';
    }

    function favoriteStarSVG(extraClass) {
        var svgClass = 'smpt-ep-favorite-star-svg';
        if (extraClass) {
            svgClass += ' ' + extraClass;
        }
        return '<svg class="' + svgClass + '" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
            + '<path d="' + STAR_PATH + '"/>'
            + '</svg>';
    }

    // -------------------------------------------------------------------------
    // SmptEpStore — localStorage wrapper
    // -------------------------------------------------------------------------

    var STORAGE_KEY = 'smpt_ep_interactions';

    function SmptEpStore() {}

    SmptEpStore.prototype.getAll = function() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {};
        } catch (e) {
            return {};
        }
    };

    SmptEpStore.prototype.get = function(epId) {
        var all = this.getAll();
        return all['ep_' + epId] || null;
    };

    SmptEpStore.prototype.set = function(epId, entry) {
        var all = this.getAll();
        var existing = all['ep_' + epId] || {};
        all['ep_' + epId] = $.extend(existing, entry);
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(all));
        } catch (e) {}
    };

    SmptEpStore.prototype.toggleLike = function(epId) {
        var entry = this.get(epId) || {};
        if (entry.like) {
            entry.like = false;
        } else {
            entry.like = true;
            entry.dislike = false;
        }
        entry.ts = Date.now();
        this.set(epId, entry);
        return entry;
    };

    SmptEpStore.prototype.toggleDislike = function(epId) {
        var entry = this.get(epId) || {};
        if (entry.dislike) {
            entry.dislike = false;
        } else {
            entry.dislike = true;
            entry.like = false;
        }
        entry.ts = Date.now();
        this.set(epId, entry);
        return entry;
    };

    SmptEpStore.prototype.setRating = function(epId, val) {
        var entry = this.get(epId) || {};
        entry.rating = val;
        entry.ts = Date.now();
        this.set(epId, entry);
    };

    SmptEpStore.prototype.setWatched = function(epId, bool) {
        var entry = this.get(epId) || {};
        entry.watched = !!bool;
        entry.ts = Date.now();
        this.set(epId, entry);
    };

    SmptEpStore.prototype.setWant = function(epId, bool) {
        var entry = this.get(epId) || {};
        entry.want = !!bool;
        entry.ts = Date.now();
        this.set(epId, entry);
    };

    SmptEpStore.prototype.setFavorite = function(epId, bool) {
        var entry = this.get(epId) || {};
        entry.favorite = !!bool;
        entry.ts = Date.now();
        this.set(epId, entry);
    };

    // -------------------------------------------------------------------------
    // REST Helpers
    // -------------------------------------------------------------------------

    function ajaxCall(method, endpoint, data, callback) {
        var config = window.smptEpInteractions;
        $.ajax({
            url: config.restBase + endpoint,
            method: method,
            data: method === 'GET' ? data : JSON.stringify(data),
            contentType: method === 'GET' ? undefined : 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', config.nonce);
            },
            success: function(resp) { callback(null, resp); },
            error: function(xhr) { callback(xhr); }
        });
    }

    function loadStats(epIds, callback) {
        ajaxCall('GET', 'ep-stats', {
            episodes: epIds.join(','),
            anon_uuid: window.smptVisitorHash || ''
        }, function(err, data) {
            callback(data || { counters: {}, states: {} });
        });
    }

    // Honeypot: real users always send empty; bots auto-fill it.
    var _hp = '';

    function sendInteraction(epId, action, extraData, store, callback) {
        var payload = $.extend({
            episode_id: epId,
            action: action,
            anon_uuid: window.smptVisitorHash || '',
            website: _hp
        }, extraData || {});

        ajaxCall('POST', 'ep-interact', payload, function(err, resp) {
            if (err && err.status === 429) {
                // Rate-limited or too fast — silently ignore, UI already updated optimistically
                if (callback) callback(err, null);
                return;
            }
            if (!err && resp && resp.counters) {
                updateCountersUI(epId, resp.counters);
            }
            if (callback) callback(err, resp);
        });
    }

    // -------------------------------------------------------------------------
    // Relative Time
    // -------------------------------------------------------------------------

    function relativeTime(dateStr) {
        var now = Date.now();
        var then = new Date(dateStr).getTime();
        var diff = Math.floor((now - then) / 1000);
        if (diff < 60) return 'agora mesmo';
        if (diff < 3600) return 'ha ' + Math.floor(diff / 60) + ' min';
        if (diff < 86400) return 'ha ' + Math.floor(diff / 3600) + ' horas';
        if (diff < 2592000) return 'ha ' + Math.floor(diff / 86400) + ' dias';
        return new Date(dateStr).toLocaleDateString('pt-PT');
    }

    // -------------------------------------------------------------------------
    // State Merging
    // -------------------------------------------------------------------------

    function mergeStates(serverData, store) {
        var config = window.smptEpInteractions;
        var states = serverData.states || {};

        for (var epId in states) {
            var server = states[epId];
            var local = store.get(parseInt(epId, 10)) || {};

            if (config.userId > 0) {
                store.set(parseInt(epId, 10), {
                    like: !!server.like,
                    dislike: !!server.dislike,
                    rating: server.rating || null,
                    watched: !!server.watched,
                    want: !!server.want_watch,
                    favorite: !!server.favorite,
                    ts: Date.now()
                });
            } else {
                if (server.like || server.dislike || server.rating) {
                    store.set(parseInt(epId, 10), {
                        like: !!server.like,
                        dislike: !!server.dislike,
                        rating: server.rating || local.rating || null,
                        watched: false,
                        ts: Date.now()
                    });
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // Login Sync
    // -------------------------------------------------------------------------

    function triggerSync(store) {
        var allStates = store.getAll();
        var localStates = {};
        for (var key in allStates) {
            var m = key.match(/^ep_(\d+)$/);
            if (m) {
                localStates[m[1]] = allStates[key];
            }
        }
        ajaxCall('POST', 'ep-sync', {
            anon_uuid: window.smptVisitorHash || '',
            local_states: localStates
        }, function(err, resp) {
            if (!err && resp && resp.states) {
                for (var epId in resp.states) {
                    syncEpisodeStateFromResponse(parseInt(epId, 10), resp.states[epId], store);
                }
            }
        });
    }

    // -------------------------------------------------------------------------
    // DOM Update Helpers
    // -------------------------------------------------------------------------

    function updateCountersUI(epId, counters) {
        var $bar = $('.smpt-ep-interactions[data-ep="' + epId + '"]');
        $bar.find('.smpt-ep-like-count').text(counters.likes || 0);
        $bar.find('.smpt-ep-dislike-count').text(counters.dislikes || 0);
        $bar.find('.smpt-ep-comment-count').text(counters.comment_count || 0);

        var avg = counters.rating_count > 0
            ? (counters.rating_sum / counters.rating_count).toFixed(1)
            : '0';
        $bar.find('.smpt-ep-avg').text(avg);
        $bar.find('.smpt-ep-rating-count').text(counters.rating_count || 0);

        if (!$bar.find('.smpt-ep-stars').data('userRating')) {
            renderStarState($bar.find('.smpt-ep-stars'), parseFloat(avg));
        }
    }

    function applyEpisodeStateUI(epId, state) {
        var $bar = $('.smpt-ep-interactions[data-ep="' + epId + '"]');
        if (!$bar.length) return;

        $bar.find('.smpt-ep-like-btn').toggleClass('is-active', !!state.like);
        $bar.find('.smpt-ep-dislike-btn').toggleClass('is-active', !!state.dislike);
        $bar.find('.smpt-ep-watched-btn').toggleClass('is-watched', !!state.watched);
        $bar.find('.smpt-ep-want-btn').toggleClass('is-active', !!state.want);
        $bar.find('.smpt-ep-favorite-btn').toggleClass('is-active', !!state.favorite);

        toggleHeaderCheckmark(epId, !!state.watched);
        toggleHeaderFavorite(epId, !!state.favorite);
    }

    function syncEpisodeStateFromResponse(epId, state, store) {
        store.set(epId, {
            like: !!state.like,
            dislike: !!state.dislike,
            rating: typeof state.rating === 'number' ? state.rating : null,
            watched: !!state.watched,
            want: !!state.want_watch,
            favorite: !!state.favorite,
            ts: Date.now()
        });
        applyEpisodeStateUI(epId, store.get(epId) || {});
    }

    // -------------------------------------------------------------------------
    // Animations (adapted from fanfiction plugin)
    // -------------------------------------------------------------------------

    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function playLikeConfetti($button) {
        if (reducedMotion || !$button.length) return;

        $button.addClass('smpt-ep-effect-host');
        $button.find('.smpt-ep-like-confetti-burst').remove();

        var rect = $button.get(0).getBoundingClientRect();
        var x = rect.width / 2;
        var y = rect.height / 2;

        var palette = ['#ff4d6d', '#ffb703', '#3ec1d3', '#7bd389', '#9b5de5', '#f15bb5', '#00bbf9'];
        var $burst = $('<span class="smpt-ep-like-confetti-burst" aria-hidden="true"></span>');
        $burst.css({ '--smpt-origin-x': x.toFixed(1) + 'px', '--smpt-origin-y': y.toFixed(1) + 'px' });

        for (var i = 0; i < 16; i++) {
            var angle = (Math.PI * 2 * i) / 16 + (Math.random() - 0.5) * 0.35;
            var dist  = 28 + Math.random() * 24;
            var cx    = Math.cos(angle) * dist;
            var cy    = (Math.sin(angle) * dist) - (16 + Math.random() * 20);
            var $p    = $('<span class="smpt-ep-like-confetti-piece"></span>');
            $p.css({
                '--smpt-confetti-x':     cx.toFixed(1) + 'px',
                '--smpt-confetti-y':     cy.toFixed(1) + 'px',
                '--smpt-confetti-rot':   ((-260 + Math.random() * 520).toFixed(0)) + 'deg',
                '--smpt-confetti-delay': (Math.random() * 110).toFixed(0) + 'ms',
                '--smpt-confetti-size':  (5 + Math.random() * 4).toFixed(1) + 'px',
                '--smpt-confetti-color': palette[Math.floor(Math.random() * palette.length)],
                '--smpt-confetti-radius': Math.random() < 0.25 ? '50%' : '2px'
            });
            $burst.append($p);
        }

        $button.append($burst);
        window.setTimeout(function() { $burst.remove(); }, 1400);
    }

    function playDislikeGloom($button) {
        if (reducedMotion || !$button.length) return;

        $button.addClass('smpt-ep-effect-host');
        $button.find('.smpt-ep-dislike-gloom-burst').remove();

        var rect = $button.get(0).getBoundingClientRect();
        var x = rect.width / 2;
        var y = rect.height / 2;

        var palette = ['#1b1f2a', '#232834', '#2b3140', '#343b49', '#3d4452', '#4a5260'];
        var $burst = $('<span class="smpt-ep-dislike-gloom-burst" aria-hidden="true"></span>');
        $burst.css({ '--smpt-origin-x': x.toFixed(1) + 'px', '--smpt-origin-y': y.toFixed(1) + 'px' });

        // Deflate animation on icon
        var $icon = $button.find('.smpt-ep-thumb-svg');
        if ($icon.length) {
            $icon.removeClass('smpt-ep-dislike-deflate');
            void $icon.get(0).offsetWidth;
            $icon.addClass('smpt-ep-dislike-deflate');
            window.setTimeout(function() { $icon.removeClass('smpt-ep-dislike-deflate'); }, 640);
        }

        for (var i = 0; i < 8; i++) {
            var driftX = ((Math.random() * 14) - 7).toFixed(1) + 'px';
            var fallY  = (36 + Math.random() * 26).toFixed(1) + 'px';
            var $drop  = $('<span class="smpt-ep-dislike-gloom-drop"></span>');
            $drop.css({
                '--smpt-gloom-x':        driftX,
                '--smpt-gloom-fall':     fallY,
                '--smpt-gloom-tilt':     ((Math.random() * 26) - 13).toFixed(1) + 'deg',
                '--smpt-gloom-delay':    (Math.random() * 130).toFixed(0) + 'ms',
                '--smpt-drop-width':     (3.2 + Math.random() * 2.4).toFixed(1) + 'px',
                '--smpt-drop-height':    (9 + Math.random() * 6).toFixed(1) + 'px',
                '--smpt-drop-duration':  (760 + Math.random() * 220).toFixed(0) + 'ms',
                '--smpt-gloom-color':    palette[Math.floor(Math.random() * palette.length)]
            });
            $burst.append($drop);
        }

        $burst.append($('<span class="smpt-ep-dislike-sigh-wisp" aria-hidden="true"></span>').css({
            '--smpt-sigh-rise':     (6 + Math.random() * 4).toFixed(1) + 'px',
            '--smpt-sigh-drift':    ((Math.random() * 6) - 3).toFixed(1) + 'px',
            '--smpt-sigh-duration': (320 + Math.random() * 130).toFixed(0) + 'ms'
        }));

        $button.append($burst);
        window.setTimeout(function() { $burst.remove(); }, 980);
    }

    function playStarGlow($stars) {
        if (reducedMotion) return;
        var hasFilledStars = $stars.find('.smpt-ep-star.is-full, .smpt-ep-star.is-half').length > 0;
        if (!hasFilledStars) return;
        $stars.removeClass('smpt-ep-rating-glow-active');
        void $stars.get(0).offsetWidth;
        $stars.addClass('smpt-ep-rating-glow-active');
        window.setTimeout(function() { $stars.removeClass('smpt-ep-rating-glow-active'); }, 700);
    }

    // -------------------------------------------------------------------------
    // Star Rating
    // -------------------------------------------------------------------------

    function buildStarsHTML(epId) {
        var html = '';
        for (var i = 1; i <= 5; i++) {
            html += '<span class="smpt-ep-star" data-value="' + i + '">'
                + '<span class="smpt-ep-star-half smpt-ep-star-left" data-val="' + (i - 0.5) + '"></span>'
                + '<span class="smpt-ep-star-half smpt-ep-star-right" data-val="' + i + '"></span>'
                + '<svg viewBox="0 0 24 24" width="20" height="20"><path d="' + STAR_PATH + '"/></svg>'
                + '<svg class="smpt-ep-star-fill" viewBox="0 0 24 24" width="20" height="20"><path d="' + STAR_PATH + '"/></svg>'
                + '</span>';
        }
        return html;
    }

    function renderStarState($starsContainer, rating) {
        if (!rating || rating <= 0) {
            $starsContainer.find('.smpt-ep-star').removeClass('is-full is-half');
            return;
        }
        $starsContainer.find('.smpt-ep-star').each(function() {
            var starVal = parseInt($(this).attr('data-value'), 10);
            $(this).removeClass('is-full is-half');
            if (rating >= starVal) {
                $(this).addClass('is-full');
            } else if (rating >= starVal - 0.5) {
                $(this).addClass('is-half');
            }
        });
    }

    // -------------------------------------------------------------------------
    // Header state markers in episode headers
    // -------------------------------------------------------------------------

    function renderHeaderStateMarkers(store) {
        var config = window.smptEpInteractions;
        if (config.userId <= 0) return;

        $('.contentor_episodio').each(function() {
            var epId = parseInt($(this).find('.smpt-ep-interactions').attr('data-ep'), 10);
            if (!epId) return;

            var $header = $(this).find('.cabecalho_video h2');
            if ($header.find('.smpt-ep-header-statuses').length) return;

            var localState = store.get(epId) || {};
            var $statuses = $('<span class="smpt-ep-header-statuses"></span>');
            var $favorite = $('<span class="smpt-ep-header-star' + (localState.favorite ? ' is-favorite' : '') + '" data-ep="' + epId + '" aria-hidden="true">' + favoriteStarSVG('smpt-ep-header-star-icon') + '</span>');
            var $check = $('<span class="smpt-ep-seen-check' + (localState.watched ? ' is-watched' : '') + '" data-ep="' + epId + '">&#x2713;</span>');

            $statuses.append($favorite).append($check);
            $header.prepend($statuses);
        });
    }

    function toggleHeaderCheckmark(epId, isWatched) {
        var $check = $('.smpt-ep-seen-check[data-ep="' + epId + '"]');
        $check.toggleClass('is-watched', isWatched);
    }

    function toggleHeaderFavorite(epId, isFavorite) {
        var $star = $('.smpt-ep-header-star[data-ep="' + epId + '"]');
        $star.toggleClass('is-favorite', isFavorite);
    }

    // -------------------------------------------------------------------------
    // UI Rendering
    // -------------------------------------------------------------------------

    function loginTooltipAttr() {
        return ' title="Faz login para poderes interagir!" aria-label="Faz login para poderes interagir!"';
    }

    function renderInteractionBar($placeholder, epId, serverData, store) {
        var config = window.smptEpInteractions;
        var counters = (serverData.counters && serverData.counters[epId]) || {};
        var localState = store.get(epId) || {};
        var isLoggedIn = config.userId > 0;

        var avg = counters.rating_count > 0
            ? (counters.rating_sum / counters.rating_count).toFixed(1)
            : '0';

        // Helper: builds a button that requires login. When logged out the button
        // is rendered disabled with a tooltip; when logged in it gets data-ep and
        // an optional active-state class.
        function authButton(baseClass, activeClass, isActive, content) {
            if (isLoggedIn) {
                return '<button class="' + baseClass + (isActive ? ' ' + activeClass : '') + '" data-ep="' + epId + '">'
                    + content + '</button>';
            }
            return '<button class="' + baseClass + ' smpt-ep-btn--disabled" disabled' + loginTooltipAttr() + '>'
                + content + '</button>';
        }

        var html = '<div class="smpt-ep-bar">';

        // Stars — available to all (localStorage dedup for anon)
        html += '<div class="smpt-ep-stars" data-ep="' + epId + '">';
        html += buildStarsHTML(epId);
        html += '</div>';

        // Rating info
        html += '<span class="smpt-ep-rating-info">'
            + '<span class="smpt-ep-avg">' + avg + '</span>'
            + ' (<span class="smpt-ep-rating-count">' + (counters.rating_count || 0) + '</span>)'
            + '</span>';

        // Like button — available to all (localStorage dedup for anon)
        html += '<button class="smpt-ep-like-btn' + (localState.like ? ' is-active' : '') + '" data-ep="' + epId + '">'
            + '<span class="smpt-ep-btn-icon">' + thumbUpSVG() + '</span>'
            + '<span class="smpt-ep-like-count">' + (counters.likes || 0) + '</span>'
            + '</button>';

        // Dislike button — available to all (localStorage dedup for anon)
        html += '<button class="smpt-ep-dislike-btn' + (localState.dislike ? ' is-active' : '') + '" data-ep="' + epId + '">'
            + '<span class="smpt-ep-btn-icon">' + thumbDownSVG() + '</span>'
            + '<span class="smpt-ep-dislike-count">' + (counters.dislikes || 0) + '</span>'
            + '</button>';

        // View count (read-only, from analytics)
        var viewCount = counters.views || 0;
        html += '<span class="smpt-ep-views"' + (viewCount === 0 ? ' hidden' : '') + '>'
            + '&#x1F441; <span class="smpt-ep-view-count">' + viewCount + '</span>'
            + '</span>';

        // Comment toggle (available to all)
        html += '<button class="smpt-ep-comment-toggle" data-ep="' + epId + '">'
            + '&#x1F4AC; <span class="smpt-ep-comment-count">' + (counters.comment_count || 0) + '</span>'
            + '</button>';

        // Watched badge (logged-in only)
        html += authButton('smpt-ep-watched-btn', 'is-watched', localState.watched,
            '&#x2713; Visto');

        // Quero ver (logged-in only)
        html += authButton('smpt-ep-want-btn', 'is-active', localState.want,
            '&#x2665; Quero ver');

        // Favorito (logged-in only)
        html += authButton('smpt-ep-favorite-btn', 'is-active', localState.favorite,
            '<span class="smpt-ep-btn-icon">' + favoriteStarSVG() + '</span>Favorito');

        html += '</div>';

        // Comments section
        html += '<div class="smpt-ep-comments" data-ep="' + epId + '" style="display:none"></div>';

        $placeholder.html(html);

        // Set initial star display
        var $stars = $placeholder.find('.smpt-ep-stars');
        if (localState.rating && isLoggedIn) {
            $stars.data('userRating', localState.rating);
            renderStarState($stars, localState.rating);
        } else {
            renderStarState($stars, parseFloat(avg));
        }
    }

    // -------------------------------------------------------------------------
    // Comments
    // -------------------------------------------------------------------------

    function loadComments(epId, page) {
        var $comments = $('.smpt-ep-comments[data-ep="' + epId + '"]');

        ajaxCall('GET', 'ep-comments/' + epId, {
            page: page,
            per_page: 20,
            anon_uuid: window.smptVisitorHash || ''
        }, function(err, resp) {
            if (err || !resp) return;

            var comments = resp.comments || [];
            var perPage = resp.per_page || 20;
            var totalPages = Math.ceil((resp.total || 0) / perPage);

            if (page === 1) {
                $comments.empty();
            } else {
                $comments.find('.smpt-ep-load-more').remove();
            }

            for (var i = 0; i < comments.length; i++) {
                appendCommentHTML($comments, comments[i]);
            }

            if (page < totalPages) {
                var $moreBtn = $('<button class="smpt-ep-load-more" data-ep="' + epId + '" data-page="' + (page + 1) + '">Carregar mais</button>');
                $comments.append($moreBtn);
            }

            if (page === 1) {
                appendCommentForm($comments, epId);
            }
        });
    }

    var EDIT_WINDOW_MS = 24 * 60 * 60 * 1000;

    function isEditable(createdAt) {
        return (Date.now() - new Date(createdAt).getTime()) < EDIT_WINDOW_MS;
    }

    function appendCommentHTML($container, comment) {
        var config = window.smptEpInteractions;
        var $comment = $('<div class="smpt-ep-comment"></div>');
        if (comment.id) $comment.attr('data-comment-id', comment.id);

        var $header = $('<div class="smpt-ep-comment-header"></div>');
        var $author = $('<strong class="smpt-ep-comment-author"></strong>').text(comment.author_name || 'Anonimo');
        var $time = $('<span class="smpt-ep-comment-time"></span>').text(relativeTime(comment.created_at));
        $header.append($author).append($time);

        // Determine edit/delete rights
        var canEdit = false;
        var isAdmin = !!config.isAdmin;

        if (comment.id) {
            if (isAdmin) {
                canEdit = true; // Admins can always edit
            } else if (isEditable(comment.created_at)) {
                if (config.userId > 0 && comment.user_id && parseInt(comment.user_id, 10) === config.userId) {
                    canEdit = true;
                } else if (config.userId <= 0 && !!comment.is_own) {
                    canEdit = true;
                }
            }
        }

        if (canEdit) {
            var $editBtn = $('<button class="smpt-ep-comment-action smpt-ep-comment-edit">Editar</button>');
            $header.append($editBtn);
        }

        // Delete: own comments (within window) or admin (always)
        var canDelete = (comment.id && (isAdmin || canEdit));
        if (canDelete) {
            var $delBtn = $('<button class="smpt-ep-comment-action smpt-ep-comment-delete">Apagar</button>');
            $header.append($delBtn);
        }

        var $text = $('<p class="smpt-ep-comment-text"></p>').text(comment.comment_text || '');
        $comment.append($header).append($text);

        var $form = $container.find('.smpt-ep-comment-form');
        if ($form.length) {
            $form.before($comment);
        } else {
            $container.append($comment);
        }
    }

    function appendCommentForm($container, epId) {
        var config = window.smptEpInteractions;
        var $form = $('<div class="smpt-ep-comment-form"></div>');

        if (config.userId > 0) {
            $form.append($('<div class="smpt-ep-comment-name-display"></div>').text(config.userName || 'Utilizador'));
        } else {
            $form.append($('<input type="text" class="smpt-ep-comment-name" placeholder="O teu nome" />'));
            $form.append($('<input type="email" class="smpt-ep-comment-email" placeholder="O teu email" />'));
        }

        $form.append($('<textarea class="smpt-ep-comment-input" placeholder="Escreve um comentario..." rows="3"></textarea>'));
        $form.append($('<button class="smpt-ep-comment-submit" data-ep="' + epId + '">Enviar</button>'));
        $container.append($form);
    }

    // -------------------------------------------------------------------------
    // Event Delegation
    // -------------------------------------------------------------------------

    function bindEvents(store) {
        var config = window.smptEpInteractions;
        var $root = $(document);

        // --- Like ---
        $root.on('click', '.smpt-ep-like-btn:not(.smpt-ep-btn--disabled)', function(e) {
            e.preventDefault();
            var epId = parseInt($(this).attr('data-ep'), 10);
            var $btn = $(this);
            var $dislikeBtn = $('.smpt-ep-dislike-btn[data-ep="' + epId + '"]');

            var newState = store.toggleLike(epId);
            var action = newState.like ? 'like' : 'remove_like';

            applyEpisodeStateUI(epId, newState);
            if (newState.like) {
                playLikeConfetti($btn);
            }

            sendInteraction(epId, action, null, store, function(err, resp) {
                if (!err && resp && resp.state) {
                    syncEpisodeStateFromResponse(epId, resp.state, store);
                }
            });
        });

        // --- Dislike ---
        $root.on('click', '.smpt-ep-dislike-btn:not(.smpt-ep-btn--disabled)', function(e) {
            e.preventDefault();
            var epId = parseInt($(this).attr('data-ep'), 10);
            var $btn = $(this);
            var $likeBtn = $('.smpt-ep-like-btn[data-ep="' + epId + '"]');

            var newState = store.toggleDislike(epId);
            var action = newState.dislike ? 'dislike' : 'remove_dislike';

            applyEpisodeStateUI(epId, newState);
            if (newState.dislike) {
                playDislikeGloom($btn);
            }

            sendInteraction(epId, action, null, store, function(err, resp) {
                if (!err && resp && resp.state) {
                    syncEpisodeStateFromResponse(epId, resp.state, store);
                }
            });
        });

        // --- Watched ---
        $root.on('click', '.smpt-ep-watched-btn:not(.smpt-ep-btn--disabled)', function(e) {
            e.preventDefault();
            if (config.userId <= 0) return;

            var epId = parseInt($(this).attr('data-ep'), 10);
            var $btn = $(this);
            var isWatched = $btn.hasClass('is-watched');
            var nextWatched = !isWatched;

            store.set(epId, {
                watched: nextWatched,
                want: nextWatched ? false : !!((store.get(epId) || {}).want),
                ts: Date.now()
            });
            applyEpisodeStateUI(epId, store.get(epId) || {});

            sendInteraction(epId, isWatched ? 'remove_watched' : 'watched', null, store, function(err, resp) {
                if (!err && resp && resp.state) {
                    syncEpisodeStateFromResponse(epId, resp.state, store);
                }
            });
        });

        // --- Quero ver ---
        $root.on('click', '.smpt-ep-want-btn:not(.smpt-ep-btn--disabled)', function(e) {
            e.preventDefault();
            if (config.userId <= 0) return;

            var epId = parseInt($(this).attr('data-ep'), 10);
            var $btn = $(this);
            var isWant = $btn.hasClass('is-active');
            var nextWant = !isWant;

            store.set(epId, {
                want: nextWant,
                watched: nextWant ? false : !!((store.get(epId) || {}).watched),
                ts: Date.now()
            });
            applyEpisodeStateUI(epId, store.get(epId) || {});

            sendInteraction(epId, isWant ? 'remove_want_watch' : 'want_watch', null, store, function(err, resp) {
                if (!err && resp && resp.state) {
                    syncEpisodeStateFromResponse(epId, resp.state, store);
                }
            });
        });

        // --- Favorito ---
        $root.on('click', '.smpt-ep-favorite-btn:not(.smpt-ep-btn--disabled)', function(e) {
            e.preventDefault();
            if (config.userId <= 0) return;

            var epId = parseInt($(this).attr('data-ep'), 10);
            var $btn = $(this);
            var isFavorite = $btn.hasClass('is-active');

            store.setFavorite(epId, !isFavorite);
            applyEpisodeStateUI(epId, store.get(epId) || {});

            sendInteraction(epId, isFavorite ? 'remove_favorite' : 'favorite', null, store, function(err, resp) {
                if (!err && resp && resp.state) {
                    syncEpisodeStateFromResponse(epId, resp.state, store);
                }
            });
        });

        // --- Star hover ---
        $root.on('mouseenter', '.smpt-ep-stars:not(.smpt-ep-stars--readonly) .smpt-ep-star-half', function() {
            var val = parseFloat($(this).attr('data-val'));
            renderStarState($(this).closest('.smpt-ep-stars'), val);
        });

        // --- Star hover leave ---
        $root.on('mouseleave', '.smpt-ep-stars:not(.smpt-ep-stars--readonly)', function() {
            var $stars = $(this);
            var userRating = $stars.data('userRating');
            if (userRating) {
                renderStarState($stars, userRating);
            } else {
                var avg = $stars.closest('.smpt-ep-interactions').find('.smpt-ep-avg').text();
                renderStarState($stars, parseFloat(avg) || 0);
            }
        });

        // --- Star click ---
        $root.on('click', '.smpt-ep-stars:not(.smpt-ep-stars--readonly) .smpt-ep-star-half', function(e) {
            e.preventDefault();

            var val = parseFloat($(this).attr('data-val'));
            var $stars = $(this).closest('.smpt-ep-stars');
            var epId = parseInt($stars.attr('data-ep'), 10);

            $stars.data('userRating', val);
            store.setRating(epId, val);
            renderStarState($stars, val);
            playStarGlow($stars);

            sendInteraction(epId, 'rate', { value: val }, store);
        });

        // --- Comment toggle ---
        $root.on('click', '.smpt-ep-comment-toggle', function(e) {
            e.preventDefault();
            var epId = parseInt($(this).attr('data-ep'), 10);
            var $comments = $('.smpt-ep-comments[data-ep="' + epId + '"]');

            if ($comments.is(':visible')) {
                $comments.slideUp(200);
            } else {
                $comments.slideDown(200);
                if (!$comments.data('loaded')) {
                    $comments.data('loaded', true);
                    loadComments(epId, 1);
                }
            }
        });

        // --- Load more comments ---
        $root.on('click', '.smpt-ep-load-more', function(e) {
            e.preventDefault();
            var epId = parseInt($(this).attr('data-ep'), 10);
            var page = parseInt($(this).attr('data-page'), 10);
            loadComments(epId, page);
        });

        // --- Comment submit ---
        $root.on('click', '.smpt-ep-comment-submit', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var epId = parseInt($btn.attr('data-ep'), 10);
            var $form = $btn.closest('.smpt-ep-comment-form');
            var $textarea = $form.find('.smpt-ep-comment-input');
            var commentText = $.trim($textarea.val());
            if (!commentText) return;

            var authorName, authorEmail;
            if (config.userId > 0) {
                authorName = config.userName || 'Utilizador';
                authorEmail = '';
            } else {
                authorName = $.trim($form.find('.smpt-ep-comment-name').val());
                authorEmail = $.trim($form.find('.smpt-ep-comment-email').val());
                if (!authorName || !authorEmail) return;
            }

            function doSubmit(recaptchaToken) {
                var $comments = $('.smpt-ep-comments[data-ep="' + epId + '"]');

                // Optimistic UI
                appendCommentHTML($comments, {
                    author_name: authorName,
                    comment_text: commentText,
                    created_at: new Date().toISOString()
                });

                var $countEl = $('.smpt-ep-comment-toggle[data-ep="' + epId + '"] .smpt-ep-comment-count');
                var prevCount = parseInt($countEl.text(), 10) || 0;
                $countEl.text(prevCount + 1);

                $textarea.val('');
                $btn.prop('disabled', false);

                var extra = {
                    author_name: authorName,
                    author_email: authorEmail,
                    comment_text: commentText
                };
                if (recaptchaToken) {
                    extra.recaptcha_token = recaptchaToken;
                }

                sendInteraction(epId, 'comment', extra, store, function(err) {
                    if (err) {
                        var errorMsg = '';
                        var resp = err.responseJSON || {};
                        if (resp.error === 'captcha_failed') {
                            errorMsg = 'Verificação anti-spam falhou. Tenta novamente.';
                        } else if (resp.error === 'spam_detected') {
                            errorMsg = 'O comentário foi identificado como spam.';
                        } else if (resp.error === 'profanity_blocked') {
                            errorMsg = 'O comentário contém linguagem inapropriada.';
                        }
                        if (errorMsg) {
                            // Rollback optimistic UI
                            $comments.find('.smpt-ep-comment').last().remove();
                            $countEl.text(prevCount);
                            $textarea.val(commentText);
                            alert(errorMsg);
                        }
                    }
                });
            }

            // Get reCAPTCHA token for anon users if configured.
            $btn.prop('disabled', true);
            if (config.userId <= 0 && window.smptRecaptcha && window.grecaptcha) {
                grecaptcha.ready(function() {
                    grecaptcha.execute(window.smptRecaptcha.siteKey, { action: 'ep_comment' }).then(function(token) {
                        doSubmit(token);
                    }).catch(function() {
                        doSubmit('');
                    });
                });
            } else {
                doSubmit('');
            }
        });

        // --- Comment edit (open inline editor) ---
        $root.on('click', '.smpt-ep-comment-edit', function(e) {
            e.preventDefault();
            var $comment = $(this).closest('.smpt-ep-comment');
            var commentId = $comment.attr('data-comment-id');
            var $textEl = $comment.find('.smpt-ep-comment-text');
            var originalText = $textEl.text();

            $comment.data('originalText', originalText);

            var $editArea = $('<textarea class="smpt-ep-comment-edit-input" rows="3"></textarea>').val(originalText);
            var $actions = $('<div class="smpt-ep-comment-edit-actions"></div>');
            $actions.append($('<button class="smpt-ep-comment-save" data-comment-id="' + commentId + '">Guardar</button>'));
            $actions.append($('<button class="smpt-ep-comment-cancel">Cancelar</button>'));

            $textEl.replaceWith($editArea);
            $comment.append($actions);
            $(this).hide();
        });

        // --- Comment save edit ---
        $root.on('click', '.smpt-ep-comment-save', function(e) {
            e.preventDefault();
            var $comment = $(this).closest('.smpt-ep-comment');
            var commentId = parseInt($(this).attr('data-comment-id'), 10);
            var $editArea = $comment.find('.smpt-ep-comment-edit-input');
            var newText = $.trim($editArea.val());
            if (!newText) return;

            var $text = $('<p class="smpt-ep-comment-text"></p>').text(newText);
            $editArea.replaceWith($text);
            $comment.find('.smpt-ep-comment-edit-actions').remove();
            $comment.find('.smpt-ep-comment-edit').show();

            ajaxCall('POST', 'ep-interact', {
                episode_id: 0, // not needed for edit, but required by schema
                action: 'edit_comment',
                comment_id: commentId,
                comment_text: newText,
                anon_uuid: window.smptVisitorHash || '',
                website: _hp
            }, function() {});
        });

        // --- Comment cancel edit ---
        $root.on('click', '.smpt-ep-comment-cancel', function(e) {
            e.preventDefault();
            var $comment = $(this).closest('.smpt-ep-comment');
            var originalText = $comment.data('originalText') || '';
            var $editArea = $comment.find('.smpt-ep-comment-edit-input');
            var $text = $('<p class="smpt-ep-comment-text"></p>').text(originalText);
            $editArea.replaceWith($text);
            $comment.find('.smpt-ep-comment-edit-actions').remove();
            $comment.find('.smpt-ep-comment-edit').show();
        });

        // --- Comment delete ---
        $root.on('click', '.smpt-ep-comment-delete', function(e) {
            e.preventDefault();
            if (!window.confirm('Apagar este comentario?')) return;

            var $comment = $(this).closest('.smpt-ep-comment');
            var commentId = parseInt($comment.attr('data-comment-id'), 10);
            var epId = parseInt($comment.closest('.smpt-ep-comments').attr('data-ep'), 10);

            ajaxCall('POST', 'ep-interact', {
                episode_id: epId,
                action: 'delete_comment',
                comment_id: commentId,
                anon_uuid: window.smptVisitorHash || '',
                website: _hp
            }, function(err, resp) {
                if (!err && resp && resp.ok) {
                    $comment.fadeOut(200, function() { $(this).remove(); });
                    if (resp.counters) updateCountersUI(epId, resp.counters);
                }
            });
        });
    }

    // -------------------------------------------------------------------------
    // Page Init
    // -------------------------------------------------------------------------

    $(function() {
        var config = window.smptEpInteractions;
        if (!config) return;

        var store = new SmptEpStore();

        var $placeholders = $('.smpt-ep-interactions[data-ep]');
        if (!$placeholders.length) return;

        var epIds = [];
        $placeholders.each(function() {
            epIds.push(parseInt($(this).attr('data-ep'), 10));
        });

        loadStats(epIds, function(data) {
            mergeStates(data, store);
            $placeholders.each(function() {
                renderInteractionBar($(this), parseInt($(this).attr('data-ep'), 10), data, store);
            });
        });

        bindEvents(store);

        if (config.needsSync && config.userId > 0) {
            triggerSync(store);
        }

        renderHeaderStateMarkers(store);

        // Optimistically increment the view counter when a stream event fires
        document.addEventListener('smpt:streamTracked', function(e) {
            var ep = parseInt(e.detail && e.detail.ep, 10);
            if (!ep) return;
            var $views = $('.smpt-ep-interactions[data-ep="' + ep + '"] .smpt-ep-views');
            if (!$views.length) return;
            var $count = $views.find('.smpt-ep-view-count');
            $count.text(parseInt($count.text(), 10) + 1);
            $views.removeAttr('hidden');
        });
    });

})(jQuery);
