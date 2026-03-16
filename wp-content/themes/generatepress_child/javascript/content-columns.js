( function() {
	"use strict";

	var maxColumnWidth = 80;
	var minColumnWidth = 40;
	var columnInset = 16;
	var resizeObserver = null;
	var observedElements = [];
	var stickyObserver = null;
	var navBaselineHeight = 0;
	var columnProperties = [
		"--smpt-greek-column-top",
		"--smpt-greek-column-height",
		"--smpt-greek-column-width",
		"--smpt-greek-column-left",
		"--smpt-greek-column-right"
	];

	function getAdminBarHeight() {
		var adminBar = document.getElementById( "wpadminbar" );

		return adminBar ? adminBar.offsetHeight : 0;
	}

	function updateNavBaseline( nav ) {
		if ( ! nav || document.body.classList.contains( "smpt-nav-is-sticky" ) ) {
			return;
		}

		navBaselineHeight = nav.offsetHeight;
	}

	function getFrameTargets() {
		var nav = document.querySelector( "#site-navigation, .main-navigation" );
		var navSentinel = document.querySelector( ".smpt-sticky-nav-sentinel" );
		var footer = document.querySelector( ".site-footer" );
		var page = document.querySelector( "#page" );
		var featured = document.querySelector( ".featured-image.page-header-image, .smpt-featured-media" );
		var title = document.querySelector( ".single-post-title" );
		var titleWrap = title ? title.parentElement : null;

		return {
			nav: nav,
			navSentinel: navSentinel,
			footer: footer,
			candidates: [ featured, titleWrap, page ].filter( Boolean )
		};
	}

	function clearColumns( body ) {
		columnProperties.forEach( function( property ) {
			if ( body.style.getPropertyValue( property ) ) {
				body.style.removeProperty( property );
			}
		} );

		if ( body.classList.contains( "smpt-has-greek-columns" ) ) {
			body.classList.remove( "smpt-has-greek-columns" );
		}
	}

	function setColumnProperty( body, property, value ) {
		if ( body.style.getPropertyValue( property ) !== value ) {
			body.style.setProperty( property, value );
		}
	}

	function setColumns() {
		var body = document.body;
		var targets = getFrameTargets();
		var nav = targets.nav;
		var navSentinel = targets.navSentinel;
		var footer = targets.footer;
		var candidates = targets.candidates;

		updateNavBaseline( nav );

		if ( ! nav || ! footer || ! candidates.length ) {
			clearColumns( body );
			return;
		}

		var left = candidates[0].getBoundingClientRect().left + window.scrollX;
		var right = candidates[0].getBoundingClientRect().right + window.scrollX;

		candidates.forEach( function( el ) {
			var rect = el.getBoundingClientRect();
			left = Math.min( left, rect.left + window.scrollX );
			right = Math.max( right, rect.right + window.scrollX );
		} );

		var navRect = nav.getBoundingClientRect();
		var sentinelRect = navSentinel ? navSentinel.getBoundingClientRect() : null;
		var footerRect = footer.getBoundingClientRect();
		var adminBarOffset = body.classList.contains( "admin-bar" ) ? getAdminBarHeight() : 0;
		var top = sentinelRect && navBaselineHeight
			? ( sentinelRect.top + window.scrollY + navBaselineHeight )
			: ( navRect.bottom + window.scrollY );
		var bottom = footerRect.top + window.scrollY;

		if ( adminBarOffset ) {
			top -= adminBarOffset;
			bottom -= adminBarOffset;
		}

		var viewportWidth = document.documentElement.clientWidth;
		var availableWidth = Math.floor(
			Math.min(
				maxColumnWidth,
				left + columnInset,
				viewportWidth - right + columnInset
			)
		);
		var width = Math.max( 0, availableWidth );
		var leftPos = Math.round( left - width + columnInset );
		var rightPos = Math.round( right - columnInset );
		var height = Math.max( 0, Math.round( bottom - top ) );

		if ( height <= 120 || width < minColumnWidth || leftPos < 0 || rightPos + width > viewportWidth ) {
			clearColumns( body );
			return;
		}

		setColumnProperty( body, "--smpt-greek-column-top", top + "px" );
		setColumnProperty( body, "--smpt-greek-column-height", height + "px" );
		setColumnProperty( body, "--smpt-greek-column-width", width + "px" );
		setColumnProperty( body, "--smpt-greek-column-left", leftPos + "px" );
		setColumnProperty( body, "--smpt-greek-column-right", rightPos + "px" );

		if ( ! body.classList.contains( "smpt-has-greek-columns" ) ) {
			body.classList.add( "smpt-has-greek-columns" );
		}
	}

	function syncObservers( refresh ) {
		var targets = getFrameTargets();
		var elements = [ targets.footer ].concat( targets.candidates ).filter( Boolean );

		if ( ! window.ResizeObserver ) {
			return;
		}

		if ( ! resizeObserver ) {
			resizeObserver = new window.ResizeObserver( refresh );
		}

		observedElements.forEach( function( element ) {
			if ( elements.indexOf( element ) === -1 ) {
				resizeObserver.unobserve( element );
			}
		} );

		elements.forEach( function( element ) {
			if ( observedElements.indexOf( element ) === -1 ) {
				resizeObserver.observe( element );
			}
		} );

		observedElements = elements;
	}

	function syncStickyObserver( refresh ) {
		var body = document.body;

		if ( ! window.MutationObserver ) {
			return;
		}

		if ( stickyObserver ) {
			return;
		}

		stickyObserver = new window.MutationObserver( function( mutations ) {
			mutations.forEach( function( mutation ) {
				if ( mutation.type !== "attributes" || mutation.attributeName !== "class" ) {
					return;
				}

				refresh();
			} );
		} );

		stickyObserver.observe( body, {
			attributes: true,
			attributeFilter: [ "class" ]
		} );
	}

	function debounce( fn ) {
		var frame = null;
		return function() {
			if ( frame ) {
				window.cancelAnimationFrame( frame );
			}
			frame = window.requestAnimationFrame( function() {
				frame = null;
				fn();
			} );
		};
	}

	var refresh = debounce( setColumns );
	var refreshAndObserve = function() {
		refresh();
		syncObservers( refresh );
		syncStickyObserver( refresh );
	};

	window.addEventListener( "load", refreshAndObserve );
	window.addEventListener( "resize", refreshAndObserve );
	window.addEventListener( "orientationchange", refreshAndObserve );
	document.addEventListener( "DOMContentLoaded", refreshAndObserve );
}() );
