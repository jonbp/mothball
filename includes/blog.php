<?php
/**
 * Blog: hide posts and the taxonomies that only posts use.
 *
 * Loaded only while the blog is mothballed. The post type stays registered,
 * so posts, their URLs and their terms are all still there when the blog is
 * switched back on.
 *
 * @package Mothball
 */

defined( 'ABSPATH' ) || exit;

/**
 * Take the post type out of the admin, menus, search, sitemaps and the REST API.
 *
 * Core registers post types again on init, after plugins load, so this
 * filter applies to the built-in post type too.
 *
 * @param array  $args      Post type arguments.
 * @param string $post_type Post type name.
 * @return array
 */
function mothball_blog_post_type_args( $args, $post_type ) {

	if ( 'post' !== $post_type ) {
		return $args;
	}

	return array_merge(
		$args,
		array(
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'show_in_rest'        => false,
		)
	);
}

add_filter( 'register_post_type_args', 'mothball_blog_post_type_args', 10, 2 );

/**
 * Hide the blog taxonomies the same way.
 *
 * This runs once every post type has had the chance to borrow a taxonomy,
 * which register_taxonomy_args fires too early to know about. It changes the
 * registered objects in memory only.
 */
function mothball_blog_hide_taxonomies() {

	foreach ( mothball_blog_taxonomies() as $taxonomy ) {

		$object = get_taxonomy( $taxonomy );

		if ( ! $object ) {
			continue;
		}

		$object->public             = false;
		$object->publicly_queryable = false;
		$object->show_ui            = false;
		$object->show_in_menu       = false;
		$object->show_in_nav_menus  = false;
		$object->show_in_rest       = false;
		$object->show_tagcloud      = false;
		$object->show_in_quick_edit = false;
		$object->show_admin_column  = false;
	}
}

add_action( 'init', 'mothball_blog_hide_taxonomies', PHP_INT_MAX );

/**
 * Whether the current request is for blog content.
 *
 * A front page that shows the latest posts is left alone, so mothballing the
 * blog never takes the homepage down with it.
 *
 * @return bool
 */
function mothball_is_blog_request() {

	if ( is_home() ) {
		return ! is_front_page();
	}

	// The main feed, which core never flags as is_home().
	if ( is_feed() && ! is_comment_feed() && ! is_singular() && ! is_archive() && ! is_search() ) {
		return 'posts' !== get_option( 'show_on_front' );
	}

	if ( is_singular( 'post' ) || is_date() || is_author() ) {
		return true;
	}

	foreach ( mothball_blog_taxonomies() as $taxonomy ) {

		if ( ( 'category' === $taxonomy && is_category() ) || ( 'post_tag' === $taxonomy && is_tag() ) || is_tax( $taxonomy ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Serve a 404 for posts, the posts page and blog archives.
 *
 * Feeds are left to mothball_blog_end_feeds(), since set_404() resets the
 * flags that function relies on.
 *
 * @param bool     $preempt  Whether something has already handled the 404.
 * @param WP_Query $wp_query The main query.
 * @return bool
 */
function mothball_blog_handle_404( $preempt, $wp_query ) {

	if ( false !== $preempt || $wp_query->is_feed() || ! mothball_is_blog_request() ) {
		return $preempt;
	}

	return mothball_send_404( $wp_query );
}

add_filter( 'pre_handle_404', 'mothball_blog_handle_404', 10, 2 );

/**
 * End blog feed requests, including a post's comment feed, with a 404.
 */
function mothball_blog_end_feeds() {

	if ( is_feed() && mothball_is_blog_request() ) {
		mothball_end_feed();
	}
}

add_action( 'template_redirect', 'mothball_blog_end_feeds', 1 );

// Stop linking to the posts feed.
add_filter( 'feed_links_show_posts_feed', '__return_false' );

/**
 * Drop the users sitemap, which exists to list author archives.
 *
 * @param WP_Sitemaps_Provider|false $provider The provider, or false to skip it.
 * @param string                     $name     Provider name.
 * @return WP_Sitemaps_Provider|false
 */
function mothball_blog_remove_users_sitemap( $provider, $name ) {

	return 'users' === $name ? false : $provider;
}

add_filter( 'wp_sitemaps_add_provider', 'mothball_blog_remove_users_sitemap', 10, 2 );

/**
 * Retire the widgets that list posts or their archives.
 */
function mothball_blog_unregister_widgets() {

	unregister_widget( 'WP_Widget_Recent_Posts' );
	unregister_widget( 'WP_Widget_Archives' );
	unregister_widget( 'WP_Widget_Calendar' );

	if ( in_array( 'category', mothball_blog_taxonomies(), true ) ) {
		unregister_widget( 'WP_Widget_Categories' );
	}
}

add_action( 'widgets_init', 'mothball_blog_unregister_widgets', 100 );

/**
 * Send anyone landing on a post or blog taxonomy screen back to the dashboard.
 *
 * @param WP_Screen $screen The current admin screen.
 */
function mothball_blog_redirect_screens( $screen ) {

	$post_screen = 'post' === $screen->post_type && in_array( $screen->base, array( 'edit', 'post' ), true );
	$term_screen = in_array( $screen->base, array( 'edit-tags', 'term' ), true ) && in_array( $screen->taxonomy, mothball_blog_taxonomies(), true );

	if ( $post_screen || $term_screen ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
}

add_action( 'current_screen', 'mothball_blog_redirect_screens' );

/**
 * Remove the Quick Draft dashboard widget, which writes blog posts.
 */
function mothball_blog_remove_dashboard_widget() {

	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
}

add_action( 'wp_dashboard_setup', 'mothball_blog_remove_dashboard_widget', 100 );

/**
 * Leave posts out of the dashboard Activity widget.
 *
 * @param array $query_args WP_Query arguments for the recent posts list.
 * @return array
 */
function mothball_blog_dashboard_activity( $query_args ) {

	$query_args['post__in'] = array( 0 );

	return $query_args;
}

add_filter( 'dashboard_recent_posts_query_args', 'mothball_blog_dashboard_activity' );

/**
 * Drop the post count column from the Users screen.
 *
 * @param array $columns Column labels by key.
 * @return array
 */
function mothball_blog_remove_users_column( $columns ) {

	unset( $columns['posts'] );

	return $columns;
}

add_filter( 'manage_users_columns', 'mothball_blog_remove_users_column' );
