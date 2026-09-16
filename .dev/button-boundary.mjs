/**
 * Every button must be visible as a button: its fill (or border) against the
 * surface it sits on, at 3:1 — WCAG 1.4.11, non-text contrast.
 *
 * contrast-rendered.mjs cannot see this, and that is not a gap in it so much as
 * a different question. It measures TEXT against its effective background. A
 * button whose fill has gone transparent still has a perfectly readable label —
 * white on the dark page behind it — so every text check passes while every
 * button on the site has stopped looking like one. That is exactly what shipped
 * in dark mode: scheme.css lifted `primary` with a colour-mix on a `flame` slug
 * this theme does not have, the variable went invalid, and all 22 buttons
 * rendered as bare text. Nothing failed.
 *
 * So this measures the thing itself. For each visible button it resolves the
 * surface by climbing ancestors to the first opaque background, and requires
 * the larger of fill-vs-surface and border-vs-surface to reach 3:1. It also
 * reports the label against the fill, at 4.5:1, because fixing the fill is
 * exactly the change that can make a white label unreadable.
 *
 *   WP_URL=https://colorlibhub.com/unioncorp node .dev/button-boundary.mjs
 *   UNIONCORP_DARK=1 WP_URL=… node .dev/button-boundary.mjs
 *
 * Exits non-zero on any failure.
 */

import { chromium } from 'playwright';

const base = ( process.env.WP_URL || 'http://127.0.0.1:9481' ).replace( /\/$/, '' );
const dark = process.env.UNIONCORP_DARK === '1';
const paths = ( process.env.PATHS || '/,/about/,/services/,/work/,/pricing/,/contact/,/blog/' ).split( ',' );

const browser = await chromium.launch();
const failures = [];
let checked = 0;

for ( const path of paths ) {
	const context = await browser.newContext( {
		viewport: { width: 1440, height: 900 },
		colorScheme: dark ? 'dark' : 'light',
	} );
	const page = await context.newPage();
	await page.goto( base + path, { waitUntil: 'networkidle', timeout: 60000 } );
	await page.waitForTimeout( 600 );

	const results = await page.evaluate( () => {
		// Both forms matter: color-mix() computes to color(srgb r g b) in the
		// 0-1 range, not rgb() in 0-255. Reading one as the other reports every
		// colour at about 1.16:1 — a parser bug that looks like a catastrophe.
		const parse = ( value ) => {
			let m = value.match( /rgba?\(([^)]+)\)/ );
			if ( m ) {
				const v = m[ 1 ].split( /[\s,/]+/ ).filter( Boolean ).map( Number );
				return { r: v[ 0 ], g: v[ 1 ], b: v[ 2 ], a: v.length > 3 ? v[ 3 ] : 1 };
			}
			m = value.match( /color\(srgb ([^)]+)\)/ );
			if ( m ) {
				const v = m[ 1 ].split( /[\s/]+/ ).filter( Boolean ).map( Number );
				return { r: v[ 0 ] * 255, g: v[ 1 ] * 255, b: v[ 2 ] * 255, a: v.length > 3 ? v[ 3 ] : 1 };
			}
			return null;
		};
		const channel = ( x ) => {
			x /= 255;
			return x <= 0.03928 ? x / 12.92 : Math.pow( ( x + 0.055 ) / 1.055, 2.4 );
		};
		const luminance = ( c ) => 0.2126 * channel( c.r ) + 0.7152 * channel( c.g ) + 0.0722 * channel( c.b );
		const ratio = ( a, b ) => {
			const [ hi, lo ] = [ luminance( a ), luminance( b ) ].sort( ( x, y ) => y - x );
			return ( hi + 0.05 ) / ( lo + 0.05 );
		};
		const surfaceOf = ( el ) => {
			for ( let node = el.parentElement; node; node = node.parentElement ) {
				const c = parse( getComputedStyle( node ).backgroundColor );
				if ( c && c.a > 0.5 ) {
					return c;
				}
			}
			return { r: 255, g: 255, b: 255, a: 1 };
		};

		return [ ...document.querySelectorAll( '.wp-block-button__link, .wp-element-button' ) ]
			// The scheme toggle is an icon-only ghost control by design; its
			// boundary is the 44px hit area, not a fill.
			.filter( ( el ) => el.offsetWidth > 0 && ! el.closest( '.unioncorp-scheme-toggle' ) )
			.map( ( el ) => {
				const cs = getComputedStyle( el );
				const fill = parse( cs.backgroundColor );
				const border = parse( cs.borderTopColor );
				const label = parse( cs.color );
				const surface = surfaceOf( el );
				const fillRatio = fill && fill.a > 0.5 ? ratio( fill, surface ) : 0;
				const borderRatio = border && border.a > 0.5 && parseFloat( cs.borderTopWidth ) > 0 ? ratio( border, surface ) : 0;
				const ground = fill && fill.a > 0.5 ? fill : surface;
				return {
					text: el.innerText.trim().slice( 0, 28 ),
					boundary: Math.max( fillRatio, borderRatio ),
					label: label ? ratio( label, ground ) : 0,
					transparent: ! fill || fill.a <= 0.5,
				};
			} );
	} );

	for ( const r of results ) {
		checked++;
		const problems = [];
		if ( r.boundary < 3 ) {
			problems.push( `boundary ${ r.boundary.toFixed( 2 ) }:1${ r.transparent ? ' (fill is transparent)' : '' }` );
		}
		if ( r.label < 4.5 ) {
			problems.push( `label ${ r.label.toFixed( 2 ) }:1` );
		}
		if ( problems.length ) {
			failures.push( `${ path } "${ r.text }": ${ problems.join( ', ' ) }` );
		}
	}

	await context.close();
}

await browser.close();

const scheme = dark ? 'dark' : 'light';
if ( failures.length ) {
	console.error( `${ failures.length } of ${ checked } buttons fail in ${ scheme } mode:` );
	failures.forEach( ( f ) => console.error( '  ' + f ) );
	process.exit( 1 );
}
console.log( `All ${ checked } buttons are visible as buttons in ${ scheme } mode (boundary >= 3:1, label >= 4.5:1).` );
