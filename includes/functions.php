<?php
/**
 * Settings, and the helpers the feature files share.
 *
 * Settings come from two places. The Settings > Mothball screen saves them to
 * an option, and a MOTHBALL_SETTINGS constant in wp-config.php can set any of
 * them in code. Anything the constant sets wins, and is locked on the screen.
 *
 * @package Mothball
 */

defined( 'ABSPATH' ) || exit;

/**
 * The sections of the settings screen and the switches in each.
 *
 * @return array<string, array{label: string, switches: array<string, array{label: string, title: string, description: string}>}>
 */
function mothball_sections() {

	return array(
		'features' => array(
			'label'    => __( 'Features', 'mothball' ),
			'switches' => array(
				'comments' => array(
					'label'       => __( 'Comments', 'mothball' ),
					'title'       => __( 'Mothball comments', 'mothball' ),
					'description' => __( 'Closes comments, pingbacks and trackbacks everywhere and hides any existing comments. Removes the Comments screens, blocks, widget, feeds and dashboard activity, and limits the comments REST route to signed-in users so editor notes keep working. Existing comments stay in the database.', 'mothball' ),
				),
				'search'   => array(
					'label'       => __( 'Search', 'mothball' ),
					'title'       => __( 'Mothball front end search', 'mothball' ),
					'description' => __( 'Searches and search feeds return a 404, and search forms, blocks and the widget disappear. The search REST route is limited to signed-in users so link search in the editor still works. Searching inside the admin is untouched.', 'mothball' ),
				),
				'blog'     => array(
					'label'       => __( 'Blog', 'mothball' ),
					'title'       => __( 'Mothball the blog', 'mothball' ),
					'description' => __( 'Hides Posts, Categories and Tags in the admin, and returns a 404 for single posts, the posts page, category, tag, date and author archives, and blog feeds. Pages and custom post types are untouched, and your posts stay in the database.', 'mothball' ),
				),
				'xmlrpc'   => array(
					'label'       => __( 'XML-RPC', 'mothball' ),
					'title'       => __( 'Mothball XML-RPC', 'mothball' ),
					'description' => __( 'Turns off xmlrpc.php, the remote publishing API older apps used before the REST API, along with incoming pingbacks and its discovery link. Requests get an error before any method runs.', 'mothball' ),
				),
			),
		),
		'cleanup'  => array(
			'label'    => __( 'Clean-up', 'mothball' ),
			'switches' => array(
				'head'           => array(
					'label'       => __( 'Page head', 'mothball' ),
					'title'       => __( 'Tidy the page head', 'mothball' ),
					'description' => __( 'Removes the emoji script and styles, the generator tag with your WordPress version, the RSD and shortlink tags, and the oEmbed discovery links from the front end. Emoji still show, drawn by the visitor\'s own fonts.', 'mothball' ),
				),
				'dashboard_news' => array(
					'label'       => __( 'Events and News', 'mothball' ),
					'title'       => __( 'Remove WordPress Events and News', 'mothball' ),
					'description' => __( 'Removes the dashboard widget, which fetches events and news from WordPress.org whenever the dashboard loads.', 'mothball' ),
				),
				'directories'    => array(
					'label'       => __( 'Directories', 'mothball' ),
					'title'       => __( 'Mothball the block and pattern directories', 'mothball' ),
					'description' => __( 'Stops the editor suggesting blocks to install from WordPress.org and loading patterns from the public pattern directory, and switches off their REST routes. Patterns from your theme and plugins still show.', 'mothball' ),
				),
				'heartbeat'      => array(
					'label'       => __( 'Heartbeat', 'mothball' ),
					'title'       => __( 'Limit Heartbeat to the editor', 'mothball' ),
					'description' => __( 'Heartbeat checks in with the server every 15 to 60 seconds from every open admin tab. This keeps it in the editor and post lists, where it autosaves and shows who is editing what, and stops it everywhere else. Outside those screens an expired login shows up on your next click, rather than as a pop-up.', 'mothball' ),
				),
			),
		),
		'rest'     => array(
			'label'    => __( 'REST API', 'mothball' ),
			'switches' => array(
				'rest_links' => array(
					'label'       => __( 'Discovery links', 'mothball' ),
					'title'       => __( 'Remove the REST API links', 'mothball' ),
					'description' => __( 'Takes the API link out of the page head, the Link header and the RSD document. The API itself keeps working.', 'mothball' ),
				),
			),
		),
	);
}

/**
 * Every switch, keyed by setting name.
 *
 * @return array<string, array{label: string, title: string, description: string}>
 */
function mothball_switches() {

	return array_merge( ...array_values( wp_list_pluck( mothball_sections(), 'switches' ) ) );
}

/**
 * The settings before anything is saved. Nothing is switched off until you choose.
 *
 * @return array
 */
function mothball_defaults() {

	$defaults = array_fill_keys( array_keys( mothball_switches() ), false );

	$defaults['rest'] = array();

	return $defaults;
}

/**
 * Keep only known switches, as booleans, and valid route group levels.
 *
 * Only the keys present in the input come back, so this suits both a full
 * set of settings and the partial set in wp-config.php.
 *
 * @param mixed $input Settings to clean.
 * @return array
 */
function mothball_clean_settings( $input ) {

	$input = is_array( $input ) ? $input : array();
	$clean = array();

	foreach ( array_keys( mothball_switches() ) as $switch ) {

		if ( array_key_exists( $switch, $input ) ) {
			$clean[ $switch ] = (bool) $input[ $switch ];
		}
	}

	if ( isset( $input['rest'] ) && is_array( $input['rest'] ) ) {

		$clean['rest'] = array();

		foreach ( $input['rest'] as $group => $level ) {

			if ( mothball_is_rest_level( $level ) && mothball_is_rest_group( $group ) ) {
				$clean['rest'][ (string) $group ] = $level;
			}
		}
	}

	return $clean;
}

/**
 * Whether a value is a REST access level.
 *
 * @param mixed $level Value to check.
 * @return bool
 */
function mothball_is_rest_level( $level ) {

	return in_array( $level, array( 'open', 'private', 'off' ), true );
}

/**
 * Whether a value looks like a REST route group, such as wp/v2/users.
 *
 * @param mixed $group Value to check.
 * @return bool
 */
function mothball_is_rest_group( $group ) {

	return is_string( $group ) && (bool) preg_match( '#^[\w.-]+(?:/[\w.-]+)+$#', $group );
}

/**
 * The settings set in wp-config.php with the MOTHBALL_SETTINGS constant.
 *
 * For example:
 *
 *     define( 'MOTHBALL_SETTINGS', array(
 *         'comments' => true,
 *         'rest'     => array( 'wp/v2/users' => 'private' ),
 *     ) );
 *
 * @return array
 */
function mothball_config() {

	if ( ! defined( 'MOTHBALL_SETTINGS' ) || ! is_array( MOTHBALL_SETTINGS ) ) {
		return array();
	}

	return mothball_clean_settings( MOTHBALL_SETTINGS );
}

/**
 * Whether wp-config.php sets a switch, which locks it on the settings screen.
 *
 * @param string $key A key from mothball_switches().
 * @return bool
 */
function mothball_is_locked( $key ) {

	return array_key_exists( $key, mothball_config() );
}

/**
 * Whether wp-config.php sets a REST route group's access level.
 *
 * @param string $group A route group, such as wp/v2/users.
 * @return bool
 */
function mothball_rest_is_locked( $group ) {

	$config = mothball_config();

	return isset( $config['rest'][ $group ] );
}

/**
 * The settings saved on the settings screen, filled in with defaults.
 *
 * @return array
 */
function mothball_stored_settings() {

	$stored = get_option( MOTHBALL_OPTION, array() );

	return array_merge( mothball_defaults(), mothball_clean_settings( $stored ) );
}

/**
 * The settings in force: what's saved, overridden by wp-config.php.
 *
 * @return array
 */
function mothball_settings() {

	$settings = mothball_stored_settings();
	$config   = mothball_config();

	if ( isset( $config['rest'] ) ) {
		$config['rest'] = array_merge( $settings['rest'], $config['rest'] );
	}

	return array_merge( $settings, $config );
}

/**
 * Whether a feature or switch is currently turned off.
 *
 * @param string $key A key from mothball_switches().
 * @return bool
 */
function mothball_is_off( $key ) {

	$settings = mothball_settings();

	return ! empty( $settings[ $key ] );
}

/**
 * Clean settings for saving. Open route groups aren't stored.
 *
 * Whatever wp-config.php sets keeps its saved value, so removing a setting
 * from the constant brings back what was chosen on the screen before.
 *
 * @param mixed $input Settings to save, as submitted.
 * @return array
 */
function mothball_sanitize_settings( $input ) {

	$input  = is_array( $input ) ? $input : array();
	$clean  = mothball_defaults();
	$stored = mothball_stored_settings();
	$config = mothball_config();

	foreach ( array_keys( mothball_switches() ) as $switch ) {
		$clean[ $switch ] = array_key_exists( $switch, $config ) ? $stored[ $switch ] : ! empty( $input[ $switch ] );
	}

	$submitted = mothball_clean_settings( array( 'rest' => isset( $input['rest'] ) ? $input['rest'] : array() ) );
	$locked    = isset( $config['rest'] ) ? $config['rest'] : array();
	$levels    = array_merge( array_diff_key( $submitted['rest'], $locked ), array_intersect_key( $stored['rest'], $locked ) );

	$clean['rest'] = array_diff( $levels, array( 'open' ) );

	return $clean;
}

/**
 * The core taxonomies that belong to the blog alone.
 *
 * A taxonomy another post type has borrowed is left out, so mothballing the
 * blog never takes categories or tags away from a custom post type.
 *
 * @return string[]
 */
function mothball_blog_taxonomies() {

	$taxonomies = array();

	foreach ( array( 'category', 'post_tag', 'post_format' ) as $taxonomy ) {

		$object = get_taxonomy( $taxonomy );

		if ( $object && array() === array_diff( $object->object_type, array( 'post' ) ) ) {
			$taxonomies[] = $taxonomy;
		}
	}

	/**
	 * Filters the taxonomies hidden along with the blog.
	 *
	 * @param string[] $taxonomies Taxonomy names.
	 */
	return apply_filters( 'mothball_blog_taxonomies', $taxonomies );
}

/**
 * Send a 404 for the current front end request.
 *
 * Meant to be returned from a pre_handle_404 callback, so the theme's 404
 * template renders as it would for any other missing page.
 *
 * @param WP_Query $wp_query The main query.
 * @return bool Always true, to stop core handling the 404 itself.
 */
function mothball_send_404( $wp_query ) {

	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();

	return true;
}

/**
 * Stop a feed request with a 404.
 *
 * Feeds keep their feed flag through WP_Query::set_404(), so they would still
 * render. wp_die() answers a feed request with an XML error, which feed
 * readers understand.
 */
function mothball_end_feed() {

	wp_die(
		esc_html__( 'This feed is not available.', 'mothball' ),
		esc_html__( 'Not found', 'mothball' ),
		array( 'response' => 404 )
	);
}
