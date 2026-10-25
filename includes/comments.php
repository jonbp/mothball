<?php
/**
 * Comments: close the comment system, front and back.
 *
 * Loaded only while comments are mothballed. Nothing in the database is
 * changed, so existing comments and each post's discussion settings come
 * back as they were when the feature is switched back on.
 *
 * @package Mothball
 */

defined( 'ABSPATH' ) || exit;

/**
 * Drop comment and trackback support from every post type.
 *
 * This also hides the Discussion panel, meta boxes and list columns.
 */
function mothball_comments_remove_support() {

	foreach ( get_post_types() as $post_type ) {
		remove_post_type_support( $post_type, 'comments' );
		remove_post_type_support( $post_type, 'trackbacks' );
	}
}

add_action( 'init', 'mothball_comments_remove_support', 100 );

/*
 * Report comments and pings as closed. Core checks these before accepting a
 * comment, trackback or pingback, and before sending an X-Pingback header.
 */
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );

// Hand themes an empty comment list and a zero count.
add_filter( 'comments_array', '__return_empty_array', 20 );
add_filter( 'get_comments_number', '__return_zero', 20 );

// Stop linking to comment feeds.
add_filter( 'feed_links_show_comments_feed', '__return_false' );
add_filter( 'feed_links_extra_show_post_comments_feed', '__return_false' );

/**
 * Report no comments, which removes the menu bubble and the At a Glance count.
 *
 * @return stdClass
 */
function mothball_comments_count() {

	return (object) array(
		'approved'       => 0,
		'moderated'      => 0,
		'spam'           => 0,
		'trash'          => 0,
		'post-trashed'   => 0,
		'total_comments' => 0,
		'all'            => 0,
	);
}

add_filter( 'wp_count_comments', 'mothball_comments_count', 20 );

/**
 * End comment feed requests with a 404.
 */
function mothball_comments_end_feeds() {

	if ( is_comment_feed() ) {
		mothball_end_feed();
	}
}

add_action( 'template_redirect', 'mothball_comments_end_feeds', 1 );

/**
 * Drop the front end comment reply script.
 */
function mothball_comments_dequeue_scripts() {

	wp_dequeue_script( 'comment-reply' );
}

add_action( 'wp_enqueue_scripts', 'mothball_comments_dequeue_scripts', 100 );

/**
 * Retire the Recent Comments widget.
 */
function mothball_comments_unregister_widget() {

	unregister_widget( 'WP_Widget_Recent_Comments' );
}

add_action( 'widgets_init', 'mothball_comments_unregister_widget', 100 );

/**
 * Remove the Comments admin menu.
 *
 * Settings > Discussion stays, as it also holds the avatar settings.
 */
function mothball_comments_remove_menu() {

	remove_menu_page( 'edit-comments.php' );
}

add_action( 'admin_menu', 'mothball_comments_remove_menu', 100 );

/**
 * Send anyone landing on a comment admin screen back to the dashboard.
 *
 * @param WP_Screen $screen The current admin screen.
 */
function mothball_comments_redirect_screens( $screen ) {

	if ( in_array( $screen->base, array( 'edit-comments', 'comment' ), true ) ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
}

add_action( 'current_screen', 'mothball_comments_redirect_screens' );

/**
 * Remove the dashboard Activity widget, which lists recent comments.
 */
function mothball_comments_remove_dashboard_widget() {

	remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
}

add_action( 'wp_dashboard_setup', 'mothball_comments_remove_dashboard_widget', 100 );

/**
 * Remove the comments bubble from the admin bar.
 *
 * @param WP_Admin_Bar $wp_admin_bar The admin bar.
 */
function mothball_comments_remove_admin_bar_node( $wp_admin_bar ) {

	$wp_admin_bar->remove_node( 'comments' );
}

add_action( 'admin_bar_menu', 'mothball_comments_remove_admin_bar_node', 100 );
