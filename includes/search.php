<?php
/**
 * Search: switch off front end search.
 *
 * Loaded only while search is mothballed. Admin search is never touched.
 *
 * @package Mothball
 */

defined( 'ABSPATH' ) || exit;

/**
 * Skip the database query for front end searches, which are about to 404.
 *
 * @param WP_Post[]|int[]|null $posts Posts to use instead, or null to query.
 * @param WP_Query             $query The query.
 * @return WP_Post[]|int[]|null
 */
function mothball_search_skip_query( $posts, $query ) {

	if ( null === $posts && ! is_admin() && $query->is_main_query() && $query->is_search() ) {
		return array();
	}

	return $posts;
}

add_filter( 'posts_pre_query', 'mothball_search_skip_query', 10, 2 );

/**
 * Serve a 404 for searches, including /search/ permalinks.
 *
 * Core never 404s a search on its own, even an empty one.
 *
 * @param bool     $preempt  Whether something has already handled the 404.
 * @param WP_Query $wp_query The main query.
 * @return bool
 */
function mothball_search_handle_404( $preempt, $wp_query ) {

	if ( false !== $preempt || ! $wp_query->is_search() || $wp_query->is_feed() ) {
		return $preempt;
	}

	return mothball_send_404( $wp_query );
}

add_filter( 'pre_handle_404', 'mothball_search_handle_404', 10, 2 );

/**
 * End search feed requests with a 404.
 */
function mothball_search_end_feeds() {

	if ( is_search() && is_feed() ) {
		mothball_end_feed();
	}
}

add_action( 'template_redirect', 'mothball_search_end_feeds', 1 );

// Stop themes rendering a search form.
add_filter( 'get_search_form', '__return_empty_string', 100 );

/**
 * Retire the Search widget.
 */
function mothball_search_unregister_widget() {

	unregister_widget( 'WP_Widget_Search' );
}

add_action( 'widgets_init', 'mothball_search_unregister_widget', 100 );

/**
 * Remove the search box from the admin bar on the front end.
 *
 * Core adds it at priority 9999, so this runs last to find it there.
 *
 * @param WP_Admin_Bar $wp_admin_bar The admin bar.
 */
function mothball_search_remove_admin_bar_node( $wp_admin_bar ) {

	$wp_admin_bar->remove_node( 'search' );
}

add_action( 'admin_bar_menu', 'mothball_search_remove_admin_bar_node', PHP_INT_MAX );
