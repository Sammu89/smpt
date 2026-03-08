// Modo Compatibilidade

$j(document).ready(function() {
    // Function to check if the browser can play AV1 codec
    function canPlayAv1() {
        const video = document.createElement('video');
        return video.canPlayType('video/mp4; codecs="av01.0.05M.08,opus"') !== '';
    }

    // Check if the browser can play AV1
    const supportsAv1 = canPlayAv1();

    // If browser doesn't support AV1, check all compatibility checkboxes by default
  if (!supportsAv1) {
     					  $j('input[type="checkbox"]').prop('checked', true);
 					}

    // Function to update video source, data-video-src, and download link
function modoCompatibilidade() {
    const isChecked = $j(this).is(':checked');
    const episodeId = this.id;

    // Define the new source prefix
    const newSourcePrefix = 'https://sm-portugal.com/streaming/h264/';
    const oldSourcePrefix = 'https://sm-portugal.com/streaming/';

    // Update video source
    const videoElement = $j(`#${episodeId} video source`);

    // Check if the video element exists
    if (videoElement.length) {
        // Get the current src and data-video-src attributes
        const currentSrc = videoElement.attr('src');
        const oldVideoSrc = videoElement.attr('data-video-src');

        // Determine the new video filename suffix
        let newVideoSrc;
        if (currentSrc) {
            // Replace the suffix based on checkbox status
            if (isChecked) {
                newVideoSrc = currentSrc.replace('[av1 opus]', '[h264 opus]');
            } else {
                newVideoSrc = currentSrc.replace('[h264 opus]', '[av1 opus]');
            }
            // Set the new src
            videoElement.attr('src', newVideoSrc);
            videoElement.parent()[0].load(); // Reload the video with the new source
        } else {
            // If there's no src, update only data-video-src
            newVideoSrc = isChecked
                ? oldVideoSrc.replace('[av1 opus]', '[h264 opus]')
                : oldVideoSrc.replace('[h264 opus]', '[av1 opus]');
            videoElement.attr('data-video-src', newVideoSrc);
        }
    } else {
        console.error(`Video element not found for episode ${episodeId}`);
    }

    // Update the download link
    const downloadLink = $j(`#${episodeId} .download`);
    const oldHref = downloadLink.attr('href');

    // Change the href and filename based on checkbox status
    if (oldHref) {
        const newHref = isChecked
            ? oldHref.replace('[av1 opus]', '[h264 opus]')
            : oldHref.replace('[h264 opus]', '[av1 opus]');
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
    const tooltipSpan = $j(`#${episodeId} .tooltip > span:first-child`);
    if (tooltipSpan.length) {
        if (isChecked) {
            tooltipSpan.addClass('tooltip-active');
        } else {
            tooltipSpan.removeClass('tooltip-active');
        }
    }
}


// Attach change event to checkboxes
$j('input[type="checkbox"]').on('change', modoCompatibilidade);



});