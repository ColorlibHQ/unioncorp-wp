/**
 * The dark-mode switch.
 *
 * The preference is applied in the head, before paint, by the inline script in
 * inc/scheme.php. This file only handles the click and keeps the label honest,
 * so it can be deferred without the page flashing.
 *
 * @package Unioncorp
 */

( function () {
	'use strict';

	var KEY = 'unioncorp-scheme';
	var root = document.documentElement;
	var strings = window.unioncorpScheme || { toDark: 'Switch to dark mode', toLight: 'Switch to light mode' };

	function isDark() {
		return root.classList.contains( 'unioncorp-dark' );
	}

	function label( button ) {
		var dark = isDark();
		button.setAttribute( 'aria-pressed', dark ? 'true' : 'false' );
		button.setAttribute( 'aria-label', dark ? strings.toLight : strings.toDark );
	}

	function apply( dark ) {
		root.classList.toggle( 'unioncorp-dark', dark );
		root.style.colorScheme = dark ? 'dark' : 'light';
		try {
			localStorage.setItem( KEY, dark ? 'dark' : 'light' );
		} catch ( e ) {}
	}

	function init() {
		// The class is on the wrapper the editor writes; the control is the
		// link inside it. Binding the wrapper as well would attach two
		// listeners, and one click would toggle twice and land back where it
		// started.
		var buttons = document.querySelectorAll( '.unioncorp-scheme-toggle .wp-block-button__link' );

		if ( ! buttons.length ) {
			return;
		}

		Array.prototype.forEach.call( buttons, function ( button ) {
			label( button );

			button.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				apply( ! isDark() );
				Array.prototype.forEach.call( buttons, label );
			} );
		} );

		// Follow the system until someone chooses for themselves.
		try {
			if ( ! localStorage.getItem( KEY ) && window.matchMedia ) {
				window.matchMedia( '(prefers-color-scheme: dark)' ).addEventListener( 'change', function ( e ) {
					if ( localStorage.getItem( KEY ) ) {
						return;
					}
					root.classList.toggle( 'unioncorp-dark', e.matches );
					Array.prototype.forEach.call( buttons, label );
				} );
			}
		} catch ( e ) {}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
