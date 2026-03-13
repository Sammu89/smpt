$j = jQuery.noConflict();

const trackedVideos = new Set(); // Create a set to keep track of tracked videos

function VerOnline(num) {
    const $videoContainer = $j('#episodio_' + num);
    const $elementsToHide = $videoContainer.find('.detalhes-episodio, .episodio-coluna1, .resumo-episodio');
    const $episodioColuna1 = $videoContainer.find('.episodio-coluna1');
    const $contentorVideoInner = $videoContainer.find('.contentor_video_inner');
    const $contentorEpisodio = $videoContainer;
    const $voltarElement = $videoContainer.find('.voltar');
    const $streamElement = $videoContainer.find('.stream');
    const $videoElement = $videoContainer.find('.videoPlaceholder');
    const $downloadOpcoes = $videoContainer.find('.episodio-opcoes');

    // Save the original width, max-width, and padding of contentorEpisodio and episodio-coluna1
    if (!$videoContainer.data('original-width')) {
        $videoContainer.data('original-width', $contentorEpisodio.css('width'));
    }
    if (!$videoContainer.data('original-max-width')) {
        $videoContainer.data('original-max-width', $contentorEpisodio.css('max-width'));
    }
    if (!$episodioColuna1.data('original-width')) {
        $episodioColuna1.data('original-width', $episodioColuna1.css('width'));
    }
    if (!$episodioColuna1.data('original-height')) {
        $episodioColuna1.data('original-height', $episodioColuna1.css('height'));
    }
    if (!$downloadOpcoes.data('original-padding')) {
        $downloadOpcoes.data('original-padding', $downloadOpcoes.css('padding'));
    }

    // Set image poster if not already set
    if (!$videoElement.attr('poster')) {
        $videoElement.attr('poster', $videoElement.data('poster-image'));
    }

    // Get the data-video-src value and set it as src
    const $sourceElement = $videoElement.find('source');
    const videoSrc = $sourceElement.attr('data-video-src');

    if (videoSrc) {
        // Check if the checkbox is checked and update data-video-src if necessary
        const isChecked = $j(`#${num} input[type="checkbox"]`).is(':checked');
        if (isChecked) {
            const newVideoSrc = videoSrc.replace('https://sm-portugal.com/streaming/', 'https://sm-portugal.com/streamingh264/');
            $sourceElement.attr('data-video-src', newVideoSrc); // Update data-video-src
        }
        
        // Set the src attribute to the (possibly modified) data-video-src
        $sourceElement.attr('src', $sourceElement.attr('data-video-src'));
        $videoElement[0].load(); // Reload the video to apply the new source
    }

    // Hide elements with animation
    $elementsToHide.css({
        'opacity': '0',
        'height': '0px'
    }).delay(500).queue(function(next) {
        $j(this).addClass('invisivel');
        next();
    });

    $contentorVideoInner.removeClass('invisivel');
    $contentorEpisodio.css({
        'width': '100%',
        'max-width': '640px'
    });
    $downloadOpcoes.css('padding', '20px');

    setTimeout(() => {
        $contentorEpisodio.css({
            'transition': 'width 0.5s, max-width 0.5s'
        });
    }, 10); // Small delay to ensure the transition is applied

    $voltarElement.removeClass('invisivel');
    $streamElement.addClass('invisivel');

    // Add event listener to track when the video starts playing
    $videoElement.on('play', function() {
        const episodeId = $videoContainer.attr('id');

        // Check if this episodeId has already been tracked
        if (!trackedVideos.has(episodeId)) {
            trackedVideos.add(episodeId); // Add the episodeId to the set
            smptTrack('stream', episodeId); // Call the tracking function
        }
    });
}


function voltar(num) {
    const $videoContainer = $j('#episodio_' + num);
    const $elementsToShow = $videoContainer.find('.detalhes-episodio, .episodio-coluna1, .resumo-episodio');
    const $contentorVideoInner = $videoContainer.find('.contentor_video_inner');
    const $contentorEpisodio = $videoContainer;
    const $voltarElement = $videoContainer.find('.voltar');
    const $streamElement = $videoContainer.find('.stream');
    const $downloadOpcoes = $videoContainer.find('.episodio-opcoes');

    // Stop the video if it's playing
    const $videoElement = $videoContainer.find('video');
    if ($videoElement.length) {
        $videoElement[0].pause();
        $videoElement[0].currentTime = 0;
    }

    // Show elements with animation
    $elementsToShow.removeClass('invisivel').css({
        'opacity': '1',
        'height': 'auto' // Use 'auto' to revert to original height
    });

    $contentorVideoInner.addClass('invisivel');

    // Revert the width, max-width, and padding of contentorEpisodio and downloadOpcoes to their original values
    $contentorEpisodio.css({
        'width': $videoContainer.data('original-width'),
        'max-width': $videoContainer.data('original-max-width')
    });
    $downloadOpcoes.css('padding', $downloadOpcoes.data('original-padding'));

    $voltarElement.addClass('invisivel');
    $streamElement.removeClass('invisivel');
}


    $j(document).ready(function() {
        // Function to adjust the font size to fit within a fixed width
        function adjustFontSize(element) {
            var el = $j(element);
            var maxFontSize = 14; // Maximum font size
            var minFontSize = 12; // Minimum font size
            var fixedWidth = 246; // Fixed width of .valor
            
            // Set the initial font size to maxFontSize
            el.css('font-size', maxFontSize + 'px');

            // Calculate the text width with the initial font size
            var textWidth = getTextWidth(el); 

            // If the text width exceeds the fixed width, decrease the font size
            while (textWidth > fixedWidth && maxFontSize > minFontSize) { 
                maxFontSize--; // Decrease the font size
                el.css('font-size', maxFontSize + 'px'); // Apply new font size
                textWidth = getTextWidth(el); // Recalculate the text width
            }
        }

        // Helper function to calculate the width of the text in the element
        function getTextWidth(el) {
            var text = el.text();
            var tempSpan = $j('<span>').text(text).css({
                'font-size': el.css('font-size'),
                'font-family': el.css('font-family'), // Ensure the font family is respected
                'visibility': 'hidden',
                'white-space': 'nowrap' // Prevent the span from wrapping
            }).appendTo('body');
            var width = tempSpan.width();
            tempSpan.remove();
            return width;
        }

        // Apply the font size adjustment to all .valor elements
        $j('.valor').each(function() {
            adjustFontSize(this);
        });
    });
	
	
// Modo compatibilidade

document.addEventListener('DOMContentLoaded', function() {
    // Function to check if the browser can play AV1 codec
    function canPlayAv1() {
        const video = document.createElement('video');
        return video.canPlayType('video/mp4; codecs="av01.0.05M.08,opus"') !== '';
    }

    // Check if the browser can play AV1
    const supportsAv1 = canPlayAv1();

    // Function to update video source, data-video-src, and download link
    function modoCompatibilidade() {
        const isChecked = jQuery(this).is(':checked');
        let episodeId = this.id;

        // Ensure episodeId has three digits
        episodeId = episodeId.replace(/(\D+)(\d{1,2})$/, (match, prefix, number) => {
            return `${prefix}${number.padStart(3, '0')}`;
        });

        // Define the new source prefix for h264 and the original prefix for av1
        const newSourcePrefix = 'https://sm-portugal.com/streamingh264/';
        const oldSourcePrefix = 'https://sm-portugal.com/streaming/';

        // Update video source
        const videoElement = jQuery(`#${episodeId} video source`);

        // Check if the video element exists
        if (videoElement.length) {
            // Get the current src and data-video-src attributes
            const currentSrc = videoElement.attr('src');
            const oldVideoSrc = videoElement.attr('data-video-src');

            // Determine the new video source
            let newVideoSrc;
            if (currentSrc) {
                // Replace the suffix and change the prefix based on checkbox status
                if (isChecked) {
                    newVideoSrc = currentSrc
                        .replace('[av1 opus]', '[h264 opus]')
                        .replace(oldSourcePrefix, newSourcePrefix);
                } else {
                    newVideoSrc = currentSrc
                        .replace('[h264 opus]', '[av1 opus]')
                        .replace(newSourcePrefix, oldSourcePrefix);
                }

                // Set the new src and reload the video
                videoElement.attr('src', newVideoSrc);
                videoElement.parent()[0].load(); // Reload the video with the new source
            } else {
                // If there's no src, update only data-video-src
                newVideoSrc = isChecked
                    ? oldVideoSrc.replace('[av1 opus]', '[h264 opus]').replace(oldSourcePrefix, newSourcePrefix)
                    : oldVideoSrc.replace('[h264 opus]', '[av1 opus]').replace(newSourcePrefix, oldSourcePrefix);
                videoElement.attr('data-video-src', newVideoSrc);
            }
        } else {
            console.error(`Video element not found for episode ${episodeId}`);
        }

        // Update the download link
        const downloadLink = jQuery(`#${episodeId} .download`);
        const oldHref = downloadLink.attr('href');

        // Change the href and filename based on checkbox status
        if (oldHref) {
            const newHref = isChecked
                ? oldHref.replace('[av1 opus]', '[h264 opus]').replace(oldSourcePrefix, newSourcePrefix)
                : oldHref.replace('[h264 opus]', '[av1 opus]').replace(newSourcePrefix, oldSourcePrefix);
            downloadLink.attr('href', newHref);

            // Update the download attribute to change the filename suffix
            let downloadFilename = downloadLink.attr('download');
            if (isChecked) {
                downloadFilename = downloadFilename.replace('[av1 opus]', '[h264 opus]');
            } else {
                downloadFilename = downloadFilename.replace('[h264 opus]', '[av1 opus]');
            }
            downloadLink.attr('download', downloadFilename);
        }

        // Add or remove class "tooltip-active" from the first <span> within <div class="tooltip">
        const tooltipSpan = jQuery(`#${episodeId} .tooltip > span:first-child`);
        if (tooltipSpan.length) {
            if (isChecked) {
                tooltipSpan.addClass('tooltip-active');
            } else {
                tooltipSpan.removeClass('tooltip-active');
            }
        }
    }

    // Attach change event to checkboxes
    jQuery('input[type="checkbox"]').on('change', modoCompatibilidade);

    // Function to check and set checkboxes if AV1 is not supported
    function setCheckboxesBasedOnAv1Support() {
        if (!supportsAv1) {
            jQuery('input[type="checkbox"]').prop('checked', true).trigger('change');
        }
    }

    // Call the function after DOM content is loaded
    setCheckboxesBasedOnAv1Support();
});



window.addEventListener('load', function() {
    const loadingDiv = document.getElementById('loading');
    const prismalunarDiv = document.getElementById('prismalunar');

    if (loadingDiv) {
        loadingDiv.classList.add('fade-out'); // Add fade-out class
        setTimeout(() => {
            loadingDiv.style.display = 'none'; // Hide after animation ends
        }, 1000); // Match the animation duration
    }

    if (prismalunarDiv) {
        setTimeout(() => {
            prismalunarDiv.style.display = ''; // Make visible after fade-out ends
            prismalunarDiv.classList.add('fade-in'); // Add fade-in class
        }, 1000); // Match the animation duration
    }
});
