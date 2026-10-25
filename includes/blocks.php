<?php
/**
 * Blocks: keep the blocks of mothballed features out of the inserter and off the page.
 *
 * @package Mothball
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block names belonging to whichever features are switched off.
 *
 * A trailing * matches every block whose name starts with what comes before it.
 *
 * @return string[]
 */
function mothball_hidden_blocks() {

	$blocks = array();

	if ( mothball_is_off( 'comments' ) ) {
		array_push( $blocks, 'core/comment*', 'core/post-comments*', 'core/latest-comments' );
	}

	if ( mothball_is_off( 'search' ) ) {
		$blocks[] = 'core/search';
	}

	if ( mothball_is_off( 'blog' ) ) {
		array_push( $blocks, 'core/latest-posts', 'core/archives', 'core/calendar', 'core/categories', 'core/tag-cloud' );
	}

	/**
	 * Filters the blocks hidden from the inserter and the front end.
	 *
	 * @param string[] $blocks Block names, optionally ending in * to match a prefix.
	 */
	return apply_filters( 'mothball_hidden_blocks', $blocks );
}

/**
 * Whether a block name matches any of the hidden block patterns.
 *
 * @param string   $name     Block name, such as core/search.
 * @param string[] $patterns Patterns from mothball_hidden_blocks().
 * @return bool
 */
function mothball_block_matches( $name, $patterns ) {

	foreach ( $patterns as $pattern ) {

		if ( '*' === substr( $pattern, -1 ) ) {

			if ( 0 === strpos( $name, substr( $pattern, 0, -1 ) ) ) {
				return true;
			}
		} elseif ( $name === $pattern ) {
			return true;
		}
	}

	return false;
}

/**
 * Render nothing for hidden blocks already saved into content.
 *
 * @param string|null $pre_render   The pre-rendered content, null to render normally.
 * @param array       $parsed_block The block being rendered.
 * @return string|null
 */
function mothball_pre_render_block( $pre_render, $parsed_block ) {

	if ( null !== $pre_render || empty( $parsed_block['blockName'] ) ) {
		return $pre_render;
	}

	$name = $parsed_block['blockName'];

	if ( ! mothball_block_matches( $name, mothball_hidden_blocks() ) ) {
		return $pre_render;
	}

	// Categories and Tag Cloud can list any taxonomy, so only hide them when they list a blog one.
	if ( in_array( $name, array( 'core/categories', 'core/tag-cloud' ), true ) ) {

		$taxonomy = 'core/categories' === $name ? 'category' : 'post_tag';

		if ( ! empty( $parsed_block['attrs']['taxonomy'] ) ) {
			$taxonomy = $parsed_block['attrs']['taxonomy'];
		}

		if ( ! in_array( $taxonomy, mothball_blog_taxonomies(), true ) ) {
			return $pre_render;
		}
	}

	return '';
}

add_filter( 'pre_render_block', 'mothball_pre_render_block', 10, 2 );

/**
 * Hide the blocks from the editor inserter.
 *
 * Loaded in the head so its filter is in place before core registers blocks.
 */
function mothball_enqueue_editor_script() {

	$blocks = mothball_hidden_blocks();

	if ( empty( $blocks ) ) {
		return;
	}

	wp_enqueue_script(
		'mothball-editor',
		plugins_url( 'assets/js/editor.js', MOTHBALL_FILE ),
		array( 'wp-hooks' ),
		MOTHBALL_VERSION,
		array( 'in_footer' => false )
	);

	wp_add_inline_script(
		'mothball-editor',
		'window.mothballHiddenBlocks = ' . wp_json_encode( array_values( $blocks ) ) . ';',
		'before'
	);
}

add_action( 'enqueue_block_editor_assets', 'mothball_enqueue_editor_script' );
