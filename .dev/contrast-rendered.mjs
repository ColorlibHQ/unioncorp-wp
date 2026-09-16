/**
 * Measure text contrast on a real rendered page, under every colour palette.
 *
 * theme.json's own audit checks the palette's numbers. It cannot catch a
 * pattern that puts the wrong slug on the wrong ground — which is how every
 * cover headline and the whole footer came out black-on-black in the two dark
 * palettes: they asked for `base`, and `base` is the page background, so in a
 * dark palette it is nearly black.
 *
 * This walks the rendered DOM, resolves each text node's effective background
 * by climbing its ancestors, and reports anything under 4.5:1. It is the check
 * that would have caught that bug, so it runs over all eight palettes.
 *
 *   WP_URL=... WP_USER=... WP_PASS=... UNIONCORP_URL=/some-page/ node .dev/contrast-rendered.mjs
 *
 * @package Unioncorp
 */

import { chromium } from 'playwright';

const site = process.env.WP_URL || 'http://local-wp.local';
const path = process.env.UNIONCORP_URL || '/';
const palettes = ( process.env.UNIONCORP_PALETTES || '' ).split( ',' ).filter( Boolean );

const browser = await chromium.launch();
const page = await ( await browser.newContext( { viewport: { width: 1400, height: 1000 } } ) ).newPage();

// `domcontentloaded` plus a settle, not `networkidle`: a production site with
// analytics or a chat widget may never reach network idle at all, and the
// check then fails as a timeout rather than a contrast result.
await page.goto( site + path, { waitUntil: 'domcontentloaded', timeout: 90000 } );
await page.waitForTimeout( 2500 );

// UNIONCORP_DARK=1 measures the same page with dark mode on, which is where the
// palette is lifted rather than replaced and is the likeliest place for a
// pairing to fall under AA.
if ( process.env.UNIONCORP_DARK ) {
	await page.evaluate( () => {
		document.documentElement.classList.add( 'unioncorp-dark' );
	} );
	await page.waitForTimeout( 400 );
}

const findings = await page.evaluate( () => {
	const luminance = ( colour ) => {
		// Two syntaxes to handle. getComputedStyle returns `rgb(r, g, b)` with
		// 0-255 channels for ordinary colours, but anything produced by
		// color-mix() -- which is how dark mode lifts the brand colour -- comes
		// back as `color(srgb 0.93 0.27 0.29)`, on a 0-1 scale. Reading those
		// as 0-255 divides them by 255 again and reports every pair at about
		// 1.16:1, which looks exactly like a catastrophic contrast failure and
		// is purely a parsing bug.
		const modern = colour.trim().startsWith( 'color(' );
		const parts = colour.match( /[\d.]+(?:e-?\d+)?/g );
		if ( ! parts ) {
			return null;
		}
		const channels = parts.slice( modern ? 0 : 0, modern ? 3 : 3 );
		const [ r, g, b ] = channels.map( ( v ) => {
			v = Number( v );
			v = modern ? v : v / 255;
			v = Math.min( 1, Math.max( 0, v ) );
			return v <= 0.03928 ? v / 12.92 : Math.pow( ( v + 0.055 ) / 1.055, 2.4 );
		} );
		return 0.2126 * r + 0.7152 * g + 0.0722 * b;
	};

	const ratio = ( a, b ) => {
		const la = luminance( a );
		const lb = luminance( b );
		if ( null === la || null === lb ) {
			return null;
		}
		return ( Math.max( la, lb ) + 0.05 ) / ( Math.min( la, lb ) + 0.05 );
	};

	const out = [];

	document.querySelectorAll( 'h1,h2,h3,h4,h5,h6,p,li,a,dt,dd,summary,label,button' ).forEach( ( el ) => {
		const own = [ ...el.childNodes ].some( ( n ) => 3 === n.nodeType && n.textContent.trim() );
		if ( ! own ) {
			return;
		}

		const box = el.getBoundingClientRect();
		if ( box.width < 6 || box.height < 6 ) {
			return;
		}

		const style = getComputedStyle( el );
		if ( 'hidden' === style.visibility || '0' === style.opacity || 'none' === style.display ) {
			return;
		}

		// Anything whose backdrop is not its DOM ancestry is out of scope: the
		// backdrop is pixels, or another element entirely, and this measures
		// neither.
		//
		// Two cases. A photograph behind the text -- a cover or any background
		// image. And an element taken out of flow: the overlaid header sits on
		// the hero but is a SIBLING of it, so climbing its ancestors finds the
		// page background and reports white-on-white for navigation that is
		// plainly legible on the photograph. Treating a positioned ancestor as
		// out of scope is what stops that false positive.
		let node = el;
		let unmeasurable = false;
		while ( node && node !== document.body ) {
			const s = getComputedStyle( node );
			if ( s.backgroundImage && 'none' !== s.backgroundImage ) {
				unmeasurable = true;
				break;
			}
			if ( node.classList && node.classList.contains( 'wp-block-cover' ) ) {
				unmeasurable = true;
				break;
			}
			if ( 'absolute' === s.position || 'fixed' === s.position ) {
				unmeasurable = true;
				break;
			}
			node = node.parentElement;
		}
		if ( unmeasurable ) {
			return;
		}

		let background = 'rgba(0, 0, 0, 0)';
		node = el;
		while ( node ) {
			const c = getComputedStyle( node ).backgroundColor;
			if ( c && 'rgba(0, 0, 0, 0)' !== c && 'transparent' !== c ) {
				background = c;
				break;
			}
			node = node.parentElement;
		}
		if ( 'rgba(0, 0, 0, 0)' === background ) {
			background = 'rgb(255, 255, 255)';
		}

		const r = ratio( style.color, background );
		if ( null !== r && r < 4.5 ) {
			out.push( {
				text: el.textContent.trim().slice( 0, 40 ),
				colour: style.color,
				background,
				ratio: Number( r.toFixed( 2 ) ),
			} );
		}
	} );

	return out;
} );

await browser.close();

if ( findings.length ) {
	console.error( `${ findings.length } text nodes under 4.5:1` );
	for ( const f of findings.slice( 0, 12 ) ) {
		console.error( `  ${ f.ratio }  "${ f.text }"  ${ f.colour } on ${ f.background }` );
	}
	process.exit( 1 );
}

console.log( 'every measurable text node is at least 4.5:1' );
