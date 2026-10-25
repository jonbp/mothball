<?php
/**
 * Admin clean-up: Events and News, the remote directories, and Heartbeat.
 *
 * Each is switched separately, so each hooks in only when it's off.
 *
 * @package Mothball
 */

defined( 'ABSPATH' ) || exit;

/**
 * Remove the WordPress Events and News dashboard widget.
 */
function mothball_remove_dashboard_news() {

	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
}

if ( mothball_is_off( 'dashboard_news' ) ) {
	add_action( 'wp_dashboard_setup', 'mothball_remove_dashboard_news', 100 );
}

/*
 * The block directory suggests blocks to install from WordPress.org as you
 * search the inserter. Remote patterns come from the public pattern directory.
 * Their REST routes are switched off in rest-api.php.
 */
if ( mothball_is_off( 'directories' ) ) {
	remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
	add_filter( 'should_load_remote_block_patterns', '__return_false' );
}

/**
 * The admin screens where Heartbeat keeps running.
 *
 * The editor uses it to autosave and lock posts, and post lists use it to show
 * who is editing what.
 *
 * @return string[] Screen bases.
 */
function mothball_heartbeat_screens() {

	/**
	 * Filters the admin screens where Heartbeat keeps running.
	 *
	 * @param string[] $bases Screen bases, such as post and edit.
	 */
	return apply_filters( 'mothball_heartbeat_screens', array( 'post', 'edit' ) );
}

/**
 * Whether the current admin screen keeps Heartbeat.
 *
 * @return bool
 */
function mothball_heartbeat_keeps_running() {

	$screen = is_admin() && function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	return $screen && in_array( $screen->base, mothball_heartbeat_screens(), true );
}

/**
 * Skip the login check pop-up outside the Heartbeat screens, as it needs Heartbeat.
 *
 * @param bool      $show   Whether to load the login check.
 * @param WP_Screen $screen The current admin screen.
 * @return bool
 */
function mothball_heartbeat_auth_check( $show, $screen ) {

	return in_array( $screen->base, mothball_heartbeat_screens(), true ) ? $show : false;
}

/**
 * Take Heartbeat off the page, unless this screen keeps it.
 *
 * Dequeuing rather than deregistering means any plugin script that depends
 * on Heartbeat still brings it back, instead of quietly failing to load.
 */
function mothball_heartbeat_dequeue() {

	if ( ! mothball_heartbeat_keeps_running() ) {
		wp_dequeue_script( 'heartbeat' );
	}
}

if ( mothball_is_off( 'heartbeat' ) ) {
	add_filter( 'wp_auth_check_load', 'mothball_heartbeat_auth_check', 10, 2 );
	add_action( 'admin_enqueue_scripts', 'mothball_heartbeat_dequeue', PHP_INT_MAX );
	add_action( 'wp_enqueue_scripts', 'mothball_heartbeat_dequeue', PHP_INT_MAX );
}
