(function($) {
    'use strict';

    var STAR_PATH = 'M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.27 5.82 22 7 14.14 2 9.27l6.91-1.01L12 2z';

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
        } catch (e) {
            // Storage full or unavailable
        }
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

    function sendInteraction(epId, action, extraData, store, callback) {
        var payload = $.extend({
            episode_id: epId,
            action: action,
            anon_uuid: window.smptVisitorHash || ''
        }, extraData || {});

        ajaxCall('POST', 'ep-interact', payload, function(err, resp) {
            if (!err && resp && resp.counters) {
                updateCountersUI(epId, resp.counters);
            }
            if (callback) callback(err, resp);
        });
    }

    // -------------------------------------------------------------------------
    // Relative Time Helper
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
                // Server wins for logged-in users
                store.set(parseInt(epId, 10), {
                    like: !!server.like,
                    dislike: !!server.dislike,
                    rating: server.rating || null,
                    watched: !!server.watched,
                    ts: Date.now()
                });
            } else {
                // For anon: server wins if it has data, otherwise keep local
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
                    store.set(parseInt(epId, 10), resp.states[epId]);
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

        // Update star display for the average when user hasn't rated
        if (!$bar.find('.smpt-ep-stars').data('userRating')) {
            renderStarState($bar.find('.smpt-ep-stars'), parseFloat(avg));
        }
    }

    // -------------------------------------------------------------------------
    // Star Rating
    // -------------------------------------------------------------------------

    function buildStarsHTML(epId) {
        var html = '';
        for (var i = 1; i <= 5; i++) {
            var leftVal = i - 0.5;
            var rightVal = i;
            html += '<span class="smpt-ep-star" data-value="' + i + '">'
                + '<span class="smpt-ep-star-half smpt-ep-star-left" data-val="' + leftVal + '"></span>'
                + '<span class="smpt-ep-star-half smpt-ep-star-right" data-val="' + rightVal + '"></span>'
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
            var $star = $(this);
            $star.removeClass('is-full is-half');

            if (rating >= starVal) {
                $star.addClass('is-full');
            } else if (rating >= starVal - 0.5) {
                $star.addClass('is-half');
            }
        });
    }

    // -------------------------------------------------------------------------
    // Watched Checkmark in Episode Headers
    // -------------------------------------------------------------------------

    function renderWatchedCheckmarks(store) {
        var config = window.smptEpInteractions;
        if (config.userId <= 0) return;

        $('.contentor_episodio').each(function() {
            var epId = parseInt($(this).find('.smpt-ep-interactions').attr('data-ep'), 10);
            if (!epId) return;

            var $header = $(this).find('.cabecalho_video h2');
            if ($header.find('.smpt-ep-seen-check').length) return; // already rendered

            var localState = store.get(epId) || {};
            var $check = $('<span class="smpt-ep-seen-check' + (localState.watched ? ' is-watched' : '') + '" data-ep="' + epId + '">&#x2713;</span>');
            $header.prepend($check);
        });
    }

    function toggleHeaderCheckmark(epId, isWatched) {
        var $check = $('.smpt-ep-seen-check[data-ep="' + epId + '"]');
        $check.toggleClass('is-watched', isWatched);
    }

    // -------------------------------------------------------------------------
    // UI Rendering
    // -------------------------------------------------------------------------

    function renderInteractionBar($placeholder, epId, serverData, store) {
        var config = window.smptEpInteractions;
        var counters = (serverData.counters && serverData.counters[epId]) || {};
        var localState = store.get(epId) || {};

        var avg = counters.rating_count > 0
            ? (counters.rating_sum / counters.rating_count).toFixed(1)
            : '0';

        var html = '<div class="smpt-ep-bar">';

        // Stars
        html += '<div class="smpt-ep-stars" data-ep="' + epId + '">';
        html += buildStarsHTML(epId);
        html += '</div>';

        // Rating info
        html += '<span class="smpt-ep-rating-info">'
            + '<span class="smpt-ep-avg">' + avg + '</span>'
            + ' (<span class="smpt-ep-rating-count">' + (counters.rating_count || 0) + '</span>)'
            + '</span>';

        // Like button
        html += '<button class="smpt-ep-like-btn' + (localState.like ? ' is-active' : '') + '" data-ep="' + epId + '">'
            + '<span class="smpt-ep-like-icon">&#x1F44D;</span> '
            + '<span class="smpt-ep-like-count">' + (counters.likes || 0) + '</span>'
            + '</button>';

        // Dislike button
        html += '<button class="smpt-ep-dislike-btn' + (localState.dislike ? ' is-active' : '') + '" data-ep="' + epId + '">'
            + '<span class="smpt-ep-dislike-icon">&#x1F44E;</span> '
            + '<span class="smpt-ep-dislike-count">' + (counters.dislikes || 0) + '</span>'
            + '</button>';

        // Comment toggle
        html += '<button class="smpt-ep-comment-toggle" data-ep="' + epId + '">'
            + '<span class="smpt-ep-comment-icon">&#x1F4AC;</span> '
            + '<span class="smpt-ep-comment-count">' + (counters.comment_count || 0) + '</span>'
            + '</button>';

        // Watched badge (logged-in only)
        if (config.userId > 0) {
            html += '<button class="smpt-ep-watched-btn' + (localState.watched ? ' is-watched' : '') + '" data-ep="' + epId + '">'
                + '&#x2713; Visto'
                + '</button>';
        }

        html += '</div>';

        // Comments section (hidden, lazy loaded)
        html += '<div class="smpt-ep-comments" data-ep="' + epId + '" style="display:none"></div>';

        $placeholder.html(html);

        // Set star display
        var $stars = $placeholder.find('.smpt-ep-stars');
        if (localState.rating) {
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
            per_page: 20
        }, function(err, resp) {
            if (err || !resp) return;

            var comments = resp.comments || [];
            var perPage = resp.per_page || 20;
            var totalPages = Math.ceil((resp.total || 0) / perPage);

            // On first page, clear and add comment form
            if (page === 1) {
                $comments.empty();
            } else {
                // Remove existing "load more" button
                $comments.find('.smpt-ep-load-more').remove();
            }

            // Render comments
            for (var i = 0; i < comments.length; i++) {
                appendCommentHTML($comments, comments[i]);
            }

            // "Load more" button
            if (page < totalPages) {
                var $moreBtn = $('<button class="smpt-ep-load-more" data-ep="' + epId + '" data-page="' + (page + 1) + '">Carregar mais</button>');
                $comments.append($moreBtn);
            }

            // Comment form
            if (page === 1) {
                appendCommentForm($comments, epId);
            }
        });
    }

    var EDIT_WINDOW_MS = 24 * 60 * 60 * 1000; // 1 day

    function isEditable(createdAt) {
        var then = new Date(createdAt).getTime();
        return (Date.now() - then) < EDIT_WINDOW_MS;
    }

    function appendCommentHTML($container, comment) {
        var config = window.smptEpInteractions;
        var $comment = $('<div class="smpt-ep-comment"></div>');
        if (comment.id) {
            $comment.attr('data-comment-id', comment.id);
        }
        var $header = $('<div class="smpt-ep-comment-header"></div>');
        var $author = $('<strong class="smpt-ep-comment-author"></strong>');
        $author.text(comment.author_name || 'Anonimo');
        var $time = $('<span class="smpt-ep-comment-time"></span>');
        $time.text(relativeTime(comment.created_at));
        $header.append($author).append($time);

        // Edit button — show if this user owns the comment and within 1 day
        var canEdit = false;
        if (comment.id && isEditable(comment.created_at)) {
            if (config.userId > 0 && comment.user_id && parseInt(comment.user_id, 10) === config.userId) {
                canEdit = true;
            } else if (config.userId <= 0 && !comment.user_id) {
                // Anon can only edit their own — matched by anon_hash on server
                canEdit = !!comment.is_own;
            }
        }
        if (canEdit) {
            var $editBtn = $('<button class="smpt-ep-comment-edit">Editar</button>');
            $header.append($editBtn);
        }

        var $text = $('<p class="smpt-ep-comment-text"></p>');
        $text.text(comment.comment_text || '');

        $comment.append($header).append($text);

        // Insert before the comment form if it exists, otherwise append
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
            // Logged in: show name as read-only
            var $nameDisplay = $('<div class="smpt-ep-comment-name-display"></div>');
            $nameDisplay.text(config.userName || 'Utilizador');
            $form.append($nameDisplay);
        } else {
            // Logged out: name + email fields
            var $name = $('<input type="text" class="smpt-ep-comment-name" placeholder="O teu nome" />');
            var $email = $('<input type="email" class="smpt-ep-comment-email" placeholder="O teu email" />');
            $form.append($name).append($email);
        }

        var $textarea = $('<textarea class="smpt-ep-comment-input" placeholder="Escreve um comentario..." rows="3"></textarea>');
        var $submit = $('<button class="smpt-ep-comment-submit" data-ep="' + epId + '">Enviar</button>');

        $form.append($textarea).append($submit);
        $container.append($form);
    }

    // -------------------------------------------------------------------------
    // Event Delegation
    // -------------------------------------------------------------------------

    function bindEvents(store) {
        var config = window.smptEpInteractions;
        var $root = $(document);

        // --- Like ---
        $root.on('click', '.smpt-ep-like-btn', function(e) {
            e.preventDefault();
            var epId = parseInt($(this).attr('data-ep'), 10);
            var $btn = $(this);
            var $dislikeBtn = $('.smpt-ep-dislike-btn[data-ep="' + epId + '"]');

            var newState = store.toggleLike(epId);
            var action = newState.like ? 'like' : 'remove_like';

            // Optimistic UI
            $btn.toggleClass('is-active', newState.like);
            if (newState.like) {
                $dislikeBtn.removeClass('is-active');
            }

            sendInteraction(epId, action, null, store);
        });

        // --- Dislike ---
        $root.on('click', '.smpt-ep-dislike-btn', function(e) {
            e.preventDefault();
            var epId = parseInt($(this).attr('data-ep'), 10);
            var $btn = $(this);
            var $likeBtn = $('.smpt-ep-like-btn[data-ep="' + epId + '"]');

            var newState = store.toggleDislike(epId);
            var action = newState.dislike ? 'dislike' : 'remove_dislike';

            // Optimistic UI
            $btn.toggleClass('is-active', newState.dislike);
            if (newState.dislike) {
                $likeBtn.removeClass('is-active');
            }

            sendInteraction(epId, action, null, store);
        });

        // --- Watched ---
        $root.on('click', '.smpt-ep-watched-btn', function(e) {
            e.preventDefault();
            if (config.userId <= 0) return;

            var epId = parseInt($(this).attr('data-ep'), 10);
            var $btn = $(this);
            var isWatched = $btn.hasClass('is-watched');

            store.setWatched(epId, !isWatched);
            $btn.toggleClass('is-watched', !isWatched);
            toggleHeaderCheckmark(epId, !isWatched);

            var action = isWatched ? 'remove_watched' : 'watched';
            sendInteraction(epId, action, null, store);
        });

        // --- Star hover (preview) ---
        $root.on('mouseenter', '.smpt-ep-star-half', function() {
            var val = parseFloat($(this).attr('data-val'));
            var $stars = $(this).closest('.smpt-ep-stars');
            renderStarState($stars, val);
        });

        // --- Star hover leave (restore) ---
        $root.on('mouseleave', '.smpt-ep-stars', function() {
            var $stars = $(this);
            var userRating = $stars.data('userRating');
            if (userRating) {
                renderStarState($stars, userRating);
            } else {
                var epId = parseInt($stars.attr('data-ep'), 10);
                var $bar = $stars.closest('.smpt-ep-interactions');
                var avgText = $bar.find('.smpt-ep-avg').text();
                renderStarState($stars, parseFloat(avgText) || 0);
            }
        });

        // --- Star click (submit rating) ---
        $root.on('click', '.smpt-ep-star-half', function(e) {
            e.preventDefault();
            var val = parseFloat($(this).attr('data-val'));
            var $stars = $(this).closest('.smpt-ep-stars');
            var epId = parseInt($stars.attr('data-ep'), 10);

            // Optimistic UI
            $stars.data('userRating', val);
            store.setRating(epId, val);
            renderStarState($stars, val);

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
                // Lazy load on first expand
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
            var epId = parseInt($(this).attr('data-ep'), 10);
            var $form = $(this).closest('.smpt-ep-comment-form');
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
                if (!authorName) { return; }
                if (!authorEmail) { return; }
            }

            // Optimistic append
            var $comments = $('.smpt-ep-comments[data-ep="' + epId + '"]');
            appendCommentHTML($comments, {
                author_name: authorName,
                comment_text: commentText,
                created_at: new Date().toISOString()
            });

            // Update comment count optimistically
            var $countEl = $('.smpt-ep-comment-toggle[data-ep="' + epId + '"] .smpt-ep-comment-count');
            var currentCount = parseInt($countEl.text(), 10) || 0;
            $countEl.text(currentCount + 1);

            // Clear textarea
            $textarea.val('');

            // Send to server
            sendInteraction(epId, 'comment', {
                author_name: authorName,
                author_email: authorEmail,
                comment_text: commentText
            }, store);
        });

        // --- Comment edit ---
        $root.on('click', '.smpt-ep-comment-edit', function(e) {
            e.preventDefault();
            var $comment = $(this).closest('.smpt-ep-comment');
            var commentId = $comment.attr('data-comment-id');
            var $textEl = $comment.find('.smpt-ep-comment-text');
            var currentText = $textEl.text();

            // Replace text with textarea for editing
            var $editArea = $('<textarea class="smpt-ep-comment-edit-input" rows="3"></textarea>');
            $editArea.val(currentText);
            var $saveBtn = $('<button class="smpt-ep-comment-save" data-comment-id="' + commentId + '">Guardar</button>');
            var $cancelBtn = $('<button class="smpt-ep-comment-cancel">Cancelar</button>');
            var $editActions = $('<div class="smpt-ep-comment-edit-actions"></div>');
            $editActions.append($saveBtn).append($cancelBtn);

            $textEl.replaceWith($editArea);
            $comment.append($editActions);
            $(this).hide();
        });

        // --- Comment save edit ---
        $root.on('click', '.smpt-ep-comment-save', function(e) {
            e.preventDefault();
            var $comment = $(this).closest('.smpt-ep-comment');
            var commentId = $(this).attr('data-comment-id');
            var $editArea = $comment.find('.smpt-ep-comment-edit-input');
            var newText = $.trim($editArea.val());

            if (!newText) return;

            // Restore display
            var $text = $('<p class="smpt-ep-comment-text"></p>');
            $text.text(newText);
            $editArea.replaceWith($text);
            $comment.find('.smpt-ep-comment-edit-actions').remove();
            $comment.find('.smpt-ep-comment-edit').show();

            // Send edit to server
            ajaxCall('POST', 'ep-interact', {
                action: 'edit_comment',
                comment_id: parseInt(commentId, 10),
                comment_text: newText,
                anon_uuid: window.smptVisitorHash || ''
            }, function() {});
        });

        // --- Comment cancel edit ---
        $root.on('click', '.smpt-ep-comment-cancel', function(e) {
            e.preventDefault();
            var $comment = $(this).closest('.smpt-ep-comment');
            var $editArea = $comment.find('.smpt-ep-comment-edit-input');
            // We don't have original text stored, so reload
            var $text = $('<p class="smpt-ep-comment-text"></p>');
            $text.text($editArea.val()); // keep current (unchanged)
            $editArea.replaceWith($text);
            $comment.find('.smpt-ep-comment-edit-actions').remove();
            $comment.find('.smpt-ep-comment-edit').show();
        });
    }

    // -------------------------------------------------------------------------
    // Page Init
    // -------------------------------------------------------------------------

    $(function() {
        var config = window.smptEpInteractions;
        if (!config) return;

        var store = new SmptEpStore();

        // 1. Collect episode IDs from placeholder elements
        var $placeholders = $('.smpt-ep-interactions[data-ep]');
        if (!$placeholders.length) return;

        var epIds = [];
        $placeholders.each(function() {
            epIds.push(parseInt($(this).attr('data-ep'), 10));
        });

        // 2. Batch load stats
        loadStats(epIds, function(data) {
            // 3. Merge and render
            mergeStates(data, store);
            $placeholders.each(function() {
                renderInteractionBar($(this), parseInt($(this).attr('data-ep'), 10), data, store);
            });
        });

        // 4. Bind delegated events
        bindEvents(store);

        // 5. Login sync if needed
        if (config.needsSync && config.userId > 0) {
            triggerSync(store);
        }

        // 6. Render watched checkmarks in episode headers
        renderWatchedCheckmarks(store);
    });

})(jQuery);
