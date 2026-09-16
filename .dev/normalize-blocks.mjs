/**
 * Rewrites every generated pattern as the block editor would serialise it.
 *
 * Block comment attributes have to match exactly what a block's save() writes.
 * Hand-built markup drifts from that the moment a block has a border radius, a
 * width or a layout -- core adds classes like `has-custom-border` and moves
 * values between the comment and the HTML in ways no generator can track. The
 * editor then flags "this block contains unexpected or invalid content", offers
 * to recover it, and nothing warns you at build time.
 *
 * So the generator writes markup that is merely *correct enough to parse*, and
 * this hands it to the real parser and takes back whatever the real serialiser
 * produces. That output is canonical by construction.
 *
 * The PHP in image URLs -- `<?php echo esc_url( get_theme_file_uri( ... ) ); ?>`
 * -- is swapped for a plain token first and put back afterwards, because the
 * parser would otherwise treat the angle brackets as markup and mangle them.
 *
 *   WP_URL=http://example.test WP_USER=admin WP_PASS=secret \
 *     node .dev/normalize-blocks.mjs
 *
 * @package Unioncorp
 */

import { chromium } from 'playwright';
import { readFileSync, writeFileSync, readdirSync } from 'node:fs';
import { join, dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname( fileURLToPath( import.meta.url ) );
// Node resolves imports from the script's own directory, so this often has to
// run from wherever Playwright is installed rather than from the theme.
// UNIONCORP_PATTERNS says where the patterns actually are in that case.
const patterns = process.env.UNIONCORP_PATTERNS
	? resolve( process.env.UNIONCORP_PATTERNS )
	: resolve( here, '../patterns' );

const url = process.env.WP_URL || 'http://localhost';
const user = process.env.WP_USER;
const pass = process.env.WP_PASS;

if ( ! user || ! pass ) {
	console.error( 'Set WP_URL, WP_USER and WP_PASS.' );
	process.exit( 1 );
}

// `<?php ... ?>` -> `UCPHP0`, and back again after the round trip.
function stash( content ) {
	const snippets = [];
	const seen = new Map();

	// Identical snippets MUST get the same token. A cover block carries the
	// same `get_theme_file_uri()` call twice -- once in the comment's `url`
	// attribute and once in the background <img> src -- and giving them two
	// different tokens makes the attribute and the markup disagree, so every
	// cover in the theme parsed as invalid. That was a bug in this script, not
	// in the patterns it was checking.
	const stashed = content.replace( /<\?php[\s\S]*?\?>/g, ( match ) => {
		if ( ! seen.has( match ) ) {
			seen.set( match, snippets.length );
			snippets.push( match );
		}
		return 'UCPHP' + seen.get( match ) + 'X';
	} );

	return { stashed, snippets };
}

function restore( content, snippets ) {
	return content.replace( /UCPHP(\d+)X/g, ( _, i ) => snippets[ Number( i ) ] );
}

const files = readdirSync( patterns ).filter( ( f ) => f.endsWith( '.php' ) );
const jobs = [];

for ( const file of files ) {
	const raw = readFileSync( join( patterns, file ), 'utf8' );
	const split = raw.indexOf( '?>\n' );
	const header = raw.slice( 0, split + 3 );
	const body = raw.slice( split + 3 );
	const { stashed, snippets } = stash( body );
	jobs.push( { file, header, snippets, stashed } );
}

const browser = await chromium.launch();
const page = await ( await browser.newContext() ).newPage();

await page.goto( url + '/wp-login.php', { waitUntil: 'commit' } );
await page.fill( '#user_login', user );
await page.fill( '#user_pass', pass );
await page.click( '#wp-submit' );
await page.waitForFunction( () => 'complete' === document.readyState );

// The site editor is what loads the full block library; the post editor omits
// template-only blocks like core/query-title and core/post-terms.
await page.goto( url + '/wp-admin/site-editor.php', { waitUntil: 'commit', timeout: 60000 } );
await page.waitForSelector( '.edit-site, #site-editor', { timeout: 60000 } );
await page.waitForTimeout( 6000 );

const results = await page.evaluate( ( input ) => {
	return input.map( ( job ) => {
		const blocks = window.wp.blocks.parse( job.stashed );
		const invalid = [];

		const walk = ( list ) => {
			for ( const block of list ) {
				if ( block.name && false === block.isValid ) {
					invalid.push( block.name );
				}
				if ( block.innerBlocks?.length ) {
					walk( block.innerBlocks );
				}
			}
		};
		walk( blocks );

		return {
			file: job.file,
			serialized: window.wp.blocks.serialize( blocks ),
			invalid,
		};
	} );
}, jobs.map( ( { file, stashed } ) => ( { file, stashed } ) ) );

await browser.close();

let changed = 0;
let stillInvalid = 0;

for ( const result of results ) {
	const job = jobs.find( ( j ) => j.file === result.file );
	const body = restore( result.serialized, job.snippets ).trim() + '\n';
	const before = restore( job.stashed, job.snippets ).trim() + '\n';

	if ( body !== before ) {
		writeFileSync( join( patterns, result.file ), job.header + body );
		changed++;
	}

	if ( result.invalid.length ) {
		stillInvalid++;
		console.log( `  ${ result.file }: parsed with invalid ${ [ ...new Set( result.invalid ) ].join( ', ' ) }` );
	}
}

console.log( `\n${ changed } of ${ results.length } patterns rewritten` );
console.log( stillInvalid
	? `${ stillInvalid } still contain invalid blocks -- these need the generator fixed`
	: 'every pattern parsed clean' );
