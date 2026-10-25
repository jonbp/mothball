/**
 * Hides the blocks of mothballed features from the inserter.
 *
 * Blocks already in your content still load, so they can be edited or removed,
 * they just can't be added again. Nothing is unregistered, which keeps the
 * editor from flagging saved blocks as missing.
 *
 * @param {Object}   hooks  The wp.hooks package.
 * @param {string[]} hidden Block names, optionally ending in * to match a prefix.
 */

( function ( hooks, hidden ) {
	function isHidden( name ) {
		return hidden.some( function ( pattern ) {
			if ( '*' === pattern.slice( -1 ) ) {
				return 0 === name.indexOf( pattern.slice( 0, -1 ) );
			}

			return name === pattern;
		} );
	}

	hooks.addFilter(
		'blocks.registerBlockType',
		'mothball/hide-blocks',
		function ( settings, name ) {
			if ( ! isHidden( name ) ) {
				return settings;
			}

			return Object.assign( {}, settings, {
				supports: Object.assign( {}, settings.supports, {
					inserter: false,
				} ),
			} );
		}
	);
} )( window.wp.hooks, window.mothballHiddenBlocks || [] );
