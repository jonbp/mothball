<?php
/**
 * REST API: open, close or remove groups of routes, and its discovery links.
 *
 * Routes are grouped by resource for the core wp/v2 namespace (wp/v2/users,
 * wp/v2/media and so on) and by namespace for everything else. Each group is
 * open, signed-in only, or off. A signed-in only group disappears for visitors,
 * while off removes it for everyone.
 *
 * @package Mothball
 */

defined( 'ABSPATH' ) || exit;

/**
 * The access levels a route group can have, least to most restrictive.
 *
 * @return array<string, string>
 */
function mothball_rest_access_levels() {

	return array(
		'open'    => __( 'Open', 'mothball' ),
		'private' => __( 'Signed-in only', 'mothball' ),
		'off'     => __( 'Off', 'mothball' ),
	);
}

/**
 * Access levels set by mothballed features, which override the REST settings.
 *
 * The comments route stays open to signed-in users because the editor's
 * notes are stored as comments, and the search route does too because the
 * editor's link search uses it. The directory routes go completely, since
 * nothing else calls them once the editor stops.
 *
 * @return array<string, string> Access level by route group.
 */
function mothball_rest_feature_levels() {

	$levels = array();

	if ( mothball_is_off( 'comments' ) ) {
		$levels['wp/v2/comments'] = 'private';
	}

	if ( mothball_is_off( 'search' ) ) {
		$levels['wp/v2/search'] = 'private';
	}

	if ( mothball_is_off( 'directories' ) ) {
		$levels['wp/v2/block-directory']   = 'off';
		$levels['wp/v2/pattern-directory'] = 'off';
	}

	return $levels;
}

/**
 * Every restricted route group with the access level that applies to it.
 *
 * @return array<string, string> Access level by route group, open groups left out.
 */
function mothball_rest_restrictions() {

	$settings = mothball_settings();
	$ranks    = array_flip( array_keys( mothball_rest_access_levels() ) );
	$levels   = is_array( $settings['rest'] ) ? $settings['rest'] : array();

	foreach ( mothball_rest_feature_levels() as $group => $level ) {

		if ( ! isset( $levels[ $group ], $ranks[ $levels[ $group ] ] ) || $ranks[ $level ] > $ranks[ $levels[ $group ] ] ) {
			$levels[ $group ] = $level;
		}
	}

	/**
	 * Filters the REST route groups Mothball restricts.
	 *
	 * @param array<string, string> $levels Access level (private or off) by route group.
	 */
	$levels = apply_filters( 'mothball_rest_restrictions', $levels );

	return array_intersect( $levels, array( 'private', 'off' ) );
}

/**
 * Whether a route belongs to a route group.
 *
 * @param string $route A route pattern, such as /wp/v2/users/(?P<id>[\d]+).
 * @param string $group A route group, such as wp/v2/users.
 * @return bool
 */
function mothball_rest_route_in_group( $route, $group ) {

	$prefix = '/' . $group;

	if ( 0 !== strpos( $route, $prefix ) ) {
		return false;
	}

	// The group has to end on a segment boundary, so wp/v2/block never catches wp/v2/blocks.
	return in_array( substr( $route, strlen( $prefix ), 1 ), array( '', '/', '(' ), true );
}

/**
 * Remove restricted routes from the REST server.
 *
 * Signed-in only routes are removed for visitors, who get the same 404 as for
 * a route that was never registered. Off routes are removed for everyone.
 *
 * @param array $endpoints Routes and their handlers.
 * @return array
 */
function mothball_rest_filter_endpoints( $endpoints ) {

	$restrictions = mothball_rest_restrictions();

	if ( empty( $restrictions ) ) {
		return $endpoints;
	}

	$signed_in = is_user_logged_in();

	foreach ( array_keys( $endpoints ) as $route ) {

		foreach ( $restrictions as $group => $level ) {

			if ( 'private' === $level && $signed_in ) {
				continue;
			}

			if ( mothball_rest_route_in_group( $route, $group ) ) {
				unset( $endpoints[ $route ] );
				break;
			}
		}
	}

	return $endpoints;
}

add_filter( 'rest_endpoints', 'mothball_rest_filter_endpoints', 999 );

/**
 * Every route group the REST server knows about, with its routes.
 *
 * Read with Mothball's own filter lifted, so groups that are switched off can
 * still be switched back on.
 *
 * @return array<string, string[]> Routes by route group, core groups first.
 */
function mothball_rest_route_groups() {

	remove_filter( 'rest_endpoints', 'mothball_rest_filter_endpoints', 999 );

	$server = rest_get_server();
	$routes = array_keys( $server->get_routes() );

	add_filter( 'rest_endpoints', 'mothball_rest_filter_endpoints', 999 );

	$namespaces = $server->get_namespaces();

	// Longest first, so a route lands in the most specific namespace it matches.
	usort(
		$namespaces,
		function ( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		}
	);

	$groups = array();

	foreach ( $routes as $route ) {

		foreach ( $namespaces as $namespace ) {

			if ( ! mothball_rest_route_in_group( $route, $namespace ) ) {
				continue;
			}

			$group = $namespace;

			// The core namespace is split by resource. Its own index stays open.
			if ( 'wp/v2' === $namespace ) {

				$resource = strtok( substr( $route, strlen( '/wp/v2/' ) ), '/(' );

				if ( false === $resource || '' === $resource ) {
					break;
				}

				$group = 'wp/v2/' . $resource;
			}

			$groups[ $group ][] = $route;
			break;
		}
	}

	uksort(
		$groups,
		function ( $a, $b ) {
			$a_core = 0 === strpos( $a, 'wp/v2/' );
			$b_core = 0 === strpos( $b, 'wp/v2/' );

			if ( $a_core !== $b_core ) {
				return $a_core ? -1 : 1;
			}

			return strcmp( $a, $b );
		}
	);

	return $groups;
}

/**
 * Take the REST API discovery links out of the page head, HTTP headers and RSD.
 */
function mothball_rest_remove_links() {

	if ( ! mothball_is_off( 'rest_links' ) ) {
		return;
	}

	remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
	remove_action( 'template_redirect', 'rest_output_link_header', 11 );
	remove_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );
}

add_action( 'init', 'mothball_rest_remove_links' );
