<?php
/**
 * Plugin Name:       Mothball
 * Plugin URI:        https://github.com/jonbp/mothball
 * Description:       Mothball the parts of WordPress you don't use. Switch off comments, search, the blog, XML-RPC and individual REST API routes. Nothing is deleted, so you can switch a feature back on at any time.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Jon Beaumont-Pike
 * Author URI:        https://jonbp.co.uk/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mothball
 *
 * @package Mothball
 */

/*
 * Mothball never writes to core options, posts, comments or rewrite rules.
 * Everything it does happens through filters at runtime, so deactivating it
 * hands the site straight back to stock WordPress. Its own settings live in a
 * single option, kept on deactivation and removed by uninstall.php, and can
 * also be set in wp-config.php with a MOTHBALL_SETTINGS constant.
 */

defined( 'ABSPATH' ) || exit;

define( 'MOTHBALL_VERSION', '1.0.0' );
define( 'MOTHBALL_FILE', __FILE__ );
define( 'MOTHBALL_DIR', plugin_dir_path( __FILE__ ) );
define( 'MOTHBALL_OPTION', 'mothball_settings' );

require_once MOTHBALL_DIR . 'includes/functions.php';
require_once MOTHBALL_DIR . 'includes/blocks.php';
require_once MOTHBALL_DIR . 'includes/rest-api.php';

if ( mothball_is_off( 'comments' ) ) {
	require_once MOTHBALL_DIR . 'includes/comments.php';
}

if ( mothball_is_off( 'search' ) ) {
	require_once MOTHBALL_DIR . 'includes/search.php';
}

if ( mothball_is_off( 'blog' ) ) {
	require_once MOTHBALL_DIR . 'includes/blog.php';
}

if ( mothball_is_off( 'xmlrpc' ) ) {
	require_once MOTHBALL_DIR . 'includes/xmlrpc.php';
}

if ( mothball_is_off( 'head' ) ) {
	require_once MOTHBALL_DIR . 'includes/head.php';
}

require_once MOTHBALL_DIR . 'includes/admin-tidy.php';

if ( is_admin() ) {
	require_once MOTHBALL_DIR . 'includes/admin.php';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once MOTHBALL_DIR . 'includes/class-mothball-cli.php';
}
