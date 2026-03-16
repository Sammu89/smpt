( function( $, wp ) {
	"use strict";

	if ( ! wp || ! wp.media ) {
		return;
	}

	var frames = {};

	function getFrame( mediaType ) {
		if ( frames[ mediaType ] ) {
			return frames[ mediaType ];
		}

		frames[ mediaType ] = wp.media( {
			title: mediaType === "image" ? "Choose overlay image" : "Choose video",
			button: {
				text: mediaType === "image" ? "Use this image" : "Use this video"
			},
			library: {
				type: mediaType
			},
			multiple: false
		} );

		return frames[ mediaType ];
	}

	$( document ).on( "click", ".smpt-featured-video-picker", function( event ) {
		event.preventDefault();

		var $button = $( this );
		var targetSelector = $button.data( "target" );
		var mediaType = $button.data( "media-type" ) || "image";
		var frame = getFrame( mediaType );

		frame.off( "select" );
		frame.on( "select", function() {
			var selection = frame.state().get( "selection" ).first();
			var attachment = selection ? selection.toJSON() : null;

			if ( attachment && attachment.url ) {
				$( targetSelector ).val( attachment.url ).trigger( "change" );
			}
		} );

		frame.open();
	} );

	$( document ).on( "click", ".smpt-featured-video-clear", function( event ) {
		event.preventDefault();
		$( $( this ).data( "target" ) ).val( "" ).trigger( "change" );
	} );
}( jQuery, window.wp ) );
