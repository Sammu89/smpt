( function() {
	"use strict";

	var observedVideos = new Set();

	function shouldSkipFeaturedVideo() {
		var nav = window.navigator || {};
		var connection = nav.connection || nav.mozConnection || nav.webkitConnection;
		var prefersReducedMotion = window.matchMedia && window.matchMedia( "(prefers-reduced-motion: reduce)" ).matches;
		var phoneViewport = window.matchMedia && window.matchMedia( "(max-width: 768px)" ).matches;
		var phoneUserAgent = /android|iphone|ipod|mobile/i.test( nav.userAgent || "" );
		var effectiveType = connection && connection.effectiveType ? connection.effectiveType : "";
		var saveData = !! ( connection && connection.saveData );
		var lowMemory = typeof nav.deviceMemory === "number" && nav.deviceMemory > 0 && nav.deviceMemory <= 2;
		var lowCpu = typeof nav.hardwareConcurrency === "number" && nav.hardwareConcurrency > 0 && nav.hardwareConcurrency <= 2;

		if ( prefersReducedMotion || saveData || effectiveType === "slow-2g" || effectiveType === "2g" ) {
			return true;
		}

		if ( ( phoneViewport || phoneUserAgent ) && ( lowMemory || lowCpu ) ) {
			return true;
		}

		return false;
	}

	function shouldLoop( video ) {
		return video.dataset.autoplay === "1";
	}

	function appendSource( video, src, type ) {
		if ( ! src ) {
			return;
		}

		var source = document.createElement( "source" );
		source.src = src;

		if ( type ) {
			source.type = type;
		}

		video.appendChild( source );
	}

	function loadFeaturedVideoSources( video ) {
		if ( ! video || video.dataset.loaded === "1" ) {
			return;
		}

		appendSource( video, video.dataset.srcAv1, 'video/mp4; codecs="av01.0.04M.08"' );
		appendSource( video, video.dataset.srcMp4, "video/mp4" );

		if ( ! shouldLoop( video ) ) {
			video.removeAttribute( "loop" );
		}

		video.dataset.loaded = "1";
		video.load();
	}

	function resumeFeaturedVideo( video ) {
		if ( ! video || video.ended ) {
			return;
		}

		var playPromise = video.play();

		if ( playPromise && typeof playPromise.catch === "function" ) {
			playPromise.catch( function() {} );
		}
	}

	function pauseFeaturedVideo( video ) {
		if ( ! video ) {
			return;
		}

		video.pause();
	}

	function handleIntersection( entries ) {
		entries.forEach( function( entry ) {
			var video = entry.target;

			if ( entry.isIntersecting ) {
				loadFeaturedVideoSources( video );
				resumeFeaturedVideo( video );
				return;
			}

			pauseFeaturedVideo( video );
		} );
	}

	function observeFeaturedVideo( video ) {
		if ( ! video ) {
			return;
		}

		if ( shouldSkipFeaturedVideo() ) {
			video.remove();
			return;
		}

		if ( ! ( "IntersectionObserver" in window ) ) {
			loadFeaturedVideoSources( video );
			resumeFeaturedVideo( video );
			return;
		}

		observedVideos.add( video );
	}

	function watchPageVisibility() {
		document.addEventListener( "visibilitychange", function() {
			observedVideos.forEach( function( video ) {
				if ( document.hidden ) {
					pauseFeaturedVideo( video );
					return;
				}

				if ( video.dataset.loaded === "1" && video.getBoundingClientRect().bottom > 0 && video.getBoundingClientRect().top < window.innerHeight ) {
					resumeFeaturedVideo( video );
				}
			} );
		} );
	}

	document.addEventListener( "DOMContentLoaded", function() {
		var videos = document.querySelectorAll( ".smpt-featured-media__video[data-src-av1], .smpt-featured-media__video[data-src-mp4]" );
		var observer = null;

		if ( "IntersectionObserver" in window ) {
			observer = new IntersectionObserver( handleIntersection, {
				threshold: 0.35
			} );
		}

		videos.forEach( function( video ) {
			observeFeaturedVideo( video );

			if ( observer ) {
				observer.observe( video );
			}
		} );

		watchPageVisibility();
	} );
}() );
