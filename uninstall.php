<?php
/**
 * Deleting the plugin removes its settings, the only thing it ever stores.
 *
 * @package Mothball
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! is_multisite() ) {
	delete_option( 'mothball_settings' );
	return;
}

$mothball_site_ids = get_sites(
	array(
		'fields' => 'ids',
		'number' => 0,
	)
);

foreach ( $mothball_site_ids as $mothball_site_id ) {
	switch_to_blog( $mothball_site_id );
	delete_option( 'mothball_settings' );
	restore_current_blog();
}
