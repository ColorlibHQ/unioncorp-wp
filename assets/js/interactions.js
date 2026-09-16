/**
 * Unioncorp: scroll reveals, figures that count up, and the video popup.
 *
 * All three are enhancements. Without this file every section is visible, every
 * figure shows its final value and the video button is a link to the video, so
 * nothing on the page depends on it running.
 *
 * Motion is skipped for a visitor who asks the system for reduced motion, and a
 * site owner can switch it off with the `unioncorp_enable_scroll_animations`
 * filter, which sets `window.unioncorpMotion.enabled` to false.
 */
( function () {
	'use strict';

	var settings = window.unioncorpMotion || {};
	var reduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var motion = settings.enabled !== false && ! reduced && 'IntersectionObserver' in window;

	/*
	 * Only what is below the first screen is ever hidden. Anything a visitor can
	 * already see stays where it is: hiding the opening screen is what makes
	 * PageSpeed report no first contentful paint, and it flashes on load besides.
	 */
	function belowTheFold( el ) {
		return el.getBoundingClientRect().top > window.innerHeight * 0.92;
	}

	function reveals() {
		if ( ! motion ) {
			return;
		}

		var candidates = Array.prototype.slice.call( document.querySelectorAll( [
			'main .unioncorp-section-head',
			'main .wp-block-columns > .wp-block-column',
			'main .wp-block-post-template > .wp-block-post'
		].join( ',' ) ) );

		// Animate the innermost element. A column that holds a row of cards then
		// lets the cards arrive one after another instead of moving as one block.
		var targets = candidates.filter( function ( el ) {
			return ! candidates.some( function ( other ) {
				return other !== el && el.contains( other );
			} );
		} ).filter( belowTheFold );

		if ( ! targets.length ) {
			return;
		}

		// Stagger siblings: the second card in a row follows the first.
		var rows = new Map();
		targets.forEach( function ( el ) {
			var row = rows.get( el.parentElement ) || [];
			row.push( el );
			rows.set( el.parentElement, row );
		} );
		rows.forEach( function ( row ) {
			row.forEach( function ( el, i ) {
				el.style.setProperty( '--unioncorp-reveal-delay', Math.min( i, 5 ) * 110 + 'ms' );
				el.classList.add( 'unioncorp-reveal' );
			} );
		} );

		document.documentElement.classList.add( 'unioncorp-motion' );

		var observer = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-revealed' );
					observer.unobserve( entry.target );
				}
			} );
		}, { rootMargin: '0px 0px -8% 0px' } );

		targets.forEach( function ( el ) {
			observer.observe( el );
		} );
	}

	function counters() {
		if ( ! motion ) {
			return;
		}

		Array.prototype.forEach.call( document.querySelectorAll( '.unioncorp-count' ), function ( el ) {
			var text = el.textContent.trim();
			var parts = text.match( /^(\D*)([\d.,]+)(\D*)$/ );

			// A figure already on screen keeps its value rather than dropping to 0.
			if ( ! parts || ! belowTheFold( el ) ) {
				return;
			}

			var target = parseFloat( parts[ 2 ].replace( /,/g, '' ) );
			if ( ! isFinite( target ) ) {
				return;
			}

			var grouped = parts[ 2 ].indexOf( ',' ) !== -1;
			var decimals = ( parts[ 2 ].split( '.' )[ 1 ] || '' ).length;

			function format( value ) {
				var number = grouped
					? value.toLocaleString( 'en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals } )
					: value.toFixed( decimals );
				return parts[ 1 ] + number + parts[ 3 ];
			}

			// Screen readers get the real figure, not every step on the way to it.
			el.setAttribute( 'aria-label', text );
			el.textContent = format( 0 );

			var observer = new IntersectionObserver( function ( entries ) {
				if ( ! entries[ 0 ].isIntersecting ) {
					return;
				}
				observer.disconnect();

				var start = null;
				var duration = 1600;

				function step( now ) {
					if ( null === start ) {
						start = now;
					}
					var progress = Math.min( ( now - start ) / duration, 1 );
					var eased = 1 - Math.pow( 1 - progress, 3 );
					el.textContent = progress < 1 ? format( target * eased ) : text;
					if ( progress < 1 ) {
						window.requestAnimationFrame( step );
					}
				}

				window.requestAnimationFrame( step );
			}, { threshold: 0.4 } );

			observer.observe( el );
		} );
	}

	function youtubeId( url ) {
		var match = url.match( /(?:youtube\.com\/(?:watch\?(?:[^#]*&)?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/ );
		return match ? match[ 1 ] : null;
	}

	function video() {
		var links = document.querySelectorAll( '.unioncorp-video a[href]' );
		if ( ! links.length || 'undefined' === typeof window.HTMLDialogElement ) {
			return;
		}

		var dialog = null;
		var frame = null;

		function build() {
			dialog = document.createElement( 'dialog' );
			dialog.className = 'unioncorp-video-dialog';
			dialog.setAttribute( 'aria-label', settings.videoLabel || 'Video' );

			var close = document.createElement( 'button' );
			close.type = 'button';
			close.className = 'unioncorp-video-dialog__close';
			close.setAttribute( 'aria-label', settings.closeLabel || 'Close video' );
			close.textContent = '×';
			close.addEventListener( 'click', function () {
				dialog.close();
			} );

			frame = document.createElement( 'iframe' );
			frame.title = settings.videoLabel || 'Video';
			frame.setAttribute( 'allow', 'autoplay; encrypted-media; picture-in-picture; fullscreen' );
			frame.setAttribute( 'allowfullscreen', '' );

			dialog.appendChild( close );
			dialog.appendChild( frame );

			// A click on the backdrop lands on the dialog itself, never on its contents.
			dialog.addEventListener( 'click', function ( event ) {
				if ( event.target === dialog ) {
					dialog.close();
				}
			} );

			// However it closes — button, backdrop or Escape — the video stops.
			dialog.addEventListener( 'close', function () {
				frame.src = 'about:blank';
			} );

			document.body.appendChild( dialog );
		}

		Array.prototype.forEach.call( links, function ( link ) {
			var id = youtubeId( link.href );
			if ( ! id ) {
				return;
			}
			link.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				if ( ! dialog ) {
					build();
				}
				frame.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0';
				dialog.showModal();
			} );
		} );
	}

	function init() {
		reveals();
		counters();
		video();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
