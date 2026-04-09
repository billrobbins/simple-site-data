/**
 * Simple Site Data - Product Data Metabox Scripts
 *
 * Provides syntax highlighting, collapsible sections, search, and copy
 * for the JSON debug output.
 *
 * Security note: All user-sourced strings are passed through escapeHTML()
 * before insertion. The innerHTML assignments contain only markup we
 * generate from the parsed JSON object -- never raw user HTML.
 */
( function () {
	'use strict';

	var output = document.getElementById( 'ssd-json-output' );

	if ( ! output ) {
		return;
	}

	var rawJSON = output.textContent;
	var parsed;

	try {
		parsed = JSON.parse( rawJSON );
	} catch ( e ) {
		return; // Leave raw text if JSON is invalid.
	}

	/**
	 * Render a JSON value as syntax-highlighted HTML with collapsible sections.
	 *
	 * Every string value is escaped via escapeHTML() to prevent XSS.
	 *
	 * @param {*}      value  The value to render.
	 * @param {string} indent Current indentation string.
	 * @return {string} HTML string.
	 */
	function renderValue( value, indent ) {
		if ( value === null ) {
			return '<span class="ssd-null">null</span>';
		}

		switch ( typeof value ) {
			case 'string':
				return '<span class="ssd-string">"' + escapeHTML( value ) + '"</span>';
			case 'number':
				return '<span class="ssd-number">' + value + '</span>';
			case 'boolean':
				return '<span class="ssd-boolean">' + value + '</span>';
			case 'object':
				return renderObject( value, indent );
			default:
				return escapeHTML( String( value ) );
		}
	}

	/**
	 * Render an object or array as collapsible HTML.
	 *
	 * @param {Object|Array} obj    The object or array.
	 * @param {string}       indent Current indentation.
	 * @return {string} HTML string.
	 */
	function renderObject( obj, indent ) {
		var isArray = Array.isArray( obj );
		var keys    = Object.keys( obj );
		var open    = isArray ? '[' : '{';
		var close   = isArray ? ']' : '}';
		var deeper  = indent + '  ';

		if ( keys.length === 0 ) {
			return open + close;
		}

		var preview = keys.length + ( isArray ? ' items' : ' keys' );
		var lines   = [];

		lines.push(
			'<span class="ssd-collapsible">' + open +
			'<span class="ssd-preview"> // ' + escapeHTML( preview ) + '</span></span>'
		);
		lines.push( '<span class="ssd-collapsible-content">' );

		keys.forEach( function ( key, i ) {
			var comma = ( i < keys.length - 1 ) ? ',' : '';
			var label = isArray ? '' : '<span class="ssd-key">"' + escapeHTML( key ) + '"</span>: ';
			lines.push( deeper + label + renderValue( obj[ key ], deeper ) + comma );
		} );

		lines.push( '</span>' );
		lines.push( '<span class="ssd-bracket-close">' + indent + close + '</span>' );

		return lines.join( '\n' );
	}

	/**
	 * Escape HTML special characters to prevent XSS.
	 *
	 * @param {string} str Raw string.
	 * @return {string} Escaped string safe for HTML insertion.
	 */
	function escapeHTML( str ) {
		return str
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	// Render highlighted JSON into the output element.
	// Safe: renderValue escapes all dynamic content via escapeHTML().
	output.innerHTML = renderValue( parsed, '' );

	// Collapsible toggle via event delegation.
	output.addEventListener( 'click', function ( e ) {
		var target = e.target.closest( '.ssd-collapsible' );
		if ( target ) {
			target.classList.toggle( 'ssd-collapsed' );
		}
	} );

	// Copy button.
	var copyBtn = document.querySelector( '.ssd-copy-btn' );
	if ( copyBtn ) {
		copyBtn.addEventListener( 'click', function () {
			navigator.clipboard.writeText( rawJSON ).then( function () {
				copyBtn.textContent = 'Copied!';
				setTimeout( function () {
					copyBtn.textContent = 'Copy JSON';
				}, 2000 );
			} );
		} );
	}

	// Collapse/expand all toggle.
	var toggleBtn = document.querySelector( '.ssd-toggle-btn' );
	if ( toggleBtn ) {
		toggleBtn.addEventListener( 'click', function () {
			var expanded  = toggleBtn.getAttribute( 'data-expanded' ) === 'true';
			var items     = output.querySelectorAll( '.ssd-collapsible' );

			items.forEach( function ( item ) {
				if ( expanded ) {
					item.classList.add( 'ssd-collapsed' );
				} else {
					item.classList.remove( 'ssd-collapsed' );
				}
			} );

			expanded = ! expanded;
			toggleBtn.setAttribute( 'data-expanded', String( expanded ) );
			toggleBtn.textContent = expanded ? 'Collapse All' : 'Expand All';
		} );
	}

	// Search / filter.
	var searchInput = document.getElementById( 'ssd-search' );
	if ( searchInput ) {
		var debounceTimer;

		searchInput.addEventListener( 'input', function () {
			clearTimeout( debounceTimer );
			debounceTimer = setTimeout( function () {
				applySearch( searchInput.value.trim().toLowerCase() );
			}, 200 );
		} );
	}

	/**
	 * Highlight matching text inside the JSON output.
	 *
	 * @param {string} query The search term.
	 */
	function applySearch( query ) {
		// Re-render clean HTML first to strip previous highlights.
		// Safe: renderValue escapes all dynamic content via escapeHTML().
		output.innerHTML = renderValue( parsed, '' );

		if ( ! query ) {
			return;
		}

		highlightNode( output, query );
	}

	/**
	 * Walk text nodes and wrap matches in a highlight span.
	 * Uses safe DOM methods (createTextNode, createElement) -- no innerHTML.
	 *
	 * @param {Node}   node  The DOM node to search.
	 * @param {string} query Lowercased search term.
	 */
	function highlightNode( node, query ) {
		if ( node.nodeType === Node.TEXT_NODE ) {
			var text  = node.textContent;
			var lower = text.toLowerCase();
			var idx   = lower.indexOf( query );

			if ( idx === -1 ) {
				return;
			}

			var frag   = document.createDocumentFragment();
			var cursor = 0;

			while ( idx !== -1 ) {
				if ( idx > cursor ) {
					frag.appendChild( document.createTextNode( text.slice( cursor, idx ) ) );
				}

				var mark = document.createElement( 'span' );
				mark.className = 'ssd-highlight';
				mark.textContent = text.slice( idx, idx + query.length );
				frag.appendChild( mark );

				cursor = idx + query.length;
				idx    = lower.indexOf( query, cursor );
			}

			if ( cursor < text.length ) {
				frag.appendChild( document.createTextNode( text.slice( cursor ) ) );
			}

			node.parentNode.replaceChild( frag, node );
		} else if ( node.nodeType === Node.ELEMENT_NODE ) {
			// Copy child nodes array since we mutate the DOM.
			var children = Array.prototype.slice.call( node.childNodes );
			children.forEach( function ( child ) {
				highlightNode( child, query );
			} );
		}
	}
} )();
