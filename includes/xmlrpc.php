<?php
/**
 * XML-RPC: switch off xmlrpc.php and the pingbacks it receives.
 *
 * Loaded only while XML-RPC is mothballed.
 *
 * @package Mothball
 */

defined( 'ABSPATH' ) || exit;

/**
 * Answer every XML-RPC request with a fault before any method runs.
 *
 * The xmlrpc.php endpoint loads WordPress before handling the request, so this
 * runs first, and catches its ?rsd discovery document too. The XML-RPC server
 * doesn't exist yet, so wp_die() would send an empty page. The fault is built
 * here instead, with the same class the server uses.
 */
function mothball_xmlrpc_block_requests() {

	if ( ! defined( 'XMLRPC_REQUEST' ) || ! XMLRPC_REQUEST ) {
		return;
	}

	require_once ABSPATH . WPINC . '/class-IXR.php';

	$error = new IXR_Error( 403, __( 'XML-RPC is switched off on this site.', 'mothball' ) );

	status_header( 403 );
	header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ) );

	// IXR_Error escapes the message itself.
	echo '<?xml version="1.0"?>' . "\n" . $error->getXml(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}

add_action( 'init', 'mothball_xmlrpc_block_requests', 0 );

// In case anything calls the server directly, give it nothing to run.
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' );

// Stop advertising the API: the RSD link points to it.
remove_action( 'wp_head', 'rsd_link' );

/**
 * Drop the X-Pingback header, which points to xmlrpc.php.
 *
 * @param array $headers HTTP headers by name.
 * @return array
 */
function mothball_xmlrpc_remove_pingback_header( $headers ) {

	unset( $headers['X-Pingback'] );

	return $headers;
}

add_filter( 'wp_headers', 'mothball_xmlrpc_remove_pingback_header' );
