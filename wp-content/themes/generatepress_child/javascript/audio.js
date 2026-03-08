$j = jQuery.noConflict();

$j(document).ready(function() {
    // Select all <audio> elements on the page
    $j('audio').each(function() {
        var $audio = $j(this);
        
        // Extract file name from src URL
        var src = $audio.attr('src');
        var fileName = src.substring(src.lastIndexOf('/') + 1); // Get filename from URL
        fileName = fileName.replace(/-/g, ' ');
        fileName = fileName.replace(/^[\d]{1,3}/, '');
        fileName = fileName.substring(0, fileName.lastIndexOf('.'));
        fileName = fileName.trim();
        if (fileName.charAt(0) === ' ') {
            fileName = fileName.substring(1);
        }

        // Set the file name as data attribute for future use
        $audio.data('file-name', fileName);
    });

    // Add event listeners to apply 'isPlaying' class when audio starts playing
    $j('audio').on('play', function() {
        var $currentAudio = $j(this);

        // Get the file name from data attribute and track the music
        var currentFileName = $currentAudio.data('file-name');
        trackMusica(currentFileName); // Call trackMusica function with file name

        // Pause all other audio elements
        $j('audio').each(function() {
            var $audio = $j(this);
            if ($audio[0] !== $currentAudio[0] && !$audio[0].paused) {
                $audio[0].pause();
                $audio.removeClass('isPlaying');
            }
        });

        // Add 'isPlaying' class to the current audio element
        $currentAudio.addClass('isPlaying');
    });

    // Remove 'isPlaying' class when audio is paused or ended
    $j('audio').on('pause ended', function() {
        $j(this).removeClass('isPlaying');
    });

    // Remove the symbol of the moon from the titles of the music
    $j('h3').removeClass('lua');
});