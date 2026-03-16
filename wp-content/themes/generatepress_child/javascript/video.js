(function ($) {
    'use strict';

    var trackedVideos = new Set();
    var activeCard = null;

    /* ── AV1 support detection ── */
    var supportsAv1 = (function () {
        var v = document.createElement('video');
        return v.canPlayType('video/mp4; codecs="av01.0.05M.08,opus"') !== '';
    })();

    /* ── Accordion toggle ── */
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

    /* ── Close active player ── */
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

    /* ── Stream play logic ── */
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

        // Track play event (once per episode)
        $(video).off('play.smpt').on('play.smpt', function () {
            var itemId = 'episodio_' + ep;
            if (!trackedVideos.has(itemId)) {
                trackedVideos.add(itemId);
                if (typeof window.smptTrack === 'function') {
                    window.smptTrack('stream', itemId);
                }
            }
        });
    }

    /* ── Ver online (stream) button ── */
    $(document).on('click', '.smpt-play--stream', function () {
        var $btn = $(this);
        playStream($btn.closest('.contentor_episodio'), $btn.data('ep'), $btn.data('video-src'), $btn.data('poster') || '');
    });

    /* ── Nostalgia play ── */
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

        // Track
        var itemId = 'nostalgia_ep_' + ep;
        if (!trackedVideos.has(itemId) && typeof window.smptTrack === 'function') {
            trackedVideos.add(itemId);
            window.smptTrack('nostalgia_play', itemId);
        }
    });

    /* ── X close button ── */
    $(document).on('click', '.smpt-player-close', function () {
        closeActivePlayer();
    });

    /* ── Font size adjustment for .valor elements ── */
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

    /* ── Loading animation ── */
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

})(jQuery);
