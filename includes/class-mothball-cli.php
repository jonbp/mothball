<?php
/**
 * WP-CLI: the wp mothball command.
 *
 * @package Mothball
 */

defined( 'ABSPATH' ) || exit;

/**
 * Switch Mothball features on and off, and set REST API access, from the command line.
 *
 * ## EXAMPLES
 *
 *     # See what's mothballed.
 *     $ wp mothball status
 *
 *     # Mothball comments and search.
 *     $ wp mothball off comments search
 *
 *     # Hide the users route from visitors.
 *     $ wp mothball rest wp/v2/users private
 */
class Mothball_CLI {

	/**
	 * Show every switch and restricted REST route group, and where each setting comes from.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp mothball status
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function status( $args, $assoc_args ) {

		$stored = get_option( MOTHBALL_OPTION, array() );
		$stored = is_array( $stored ) ? mothball_clean_settings( $stored ) : array();
		$rows   = array();

		foreach ( mothball_switches() as $switch => $details ) {

			if ( mothball_is_locked( $switch ) ) {
				$source = 'wp-config.php';
			} elseif ( array_key_exists( $switch, $stored ) ) {
				$source = 'settings';
			} else {
				$source = 'default';
			}

			$rows[] = array(
				'switch' => $switch,
				'name'   => $details['label'],
				'state'  => mothball_is_off( $switch ) ? 'off' : 'on',
				'source' => $source,
			);
		}

		WP_CLI\Utils\format_items( $assoc_args['format'], $rows, array( 'switch', 'name', 'state', 'source' ) );

		$restrictions = mothball_rest_restrictions();

		if ( empty( $restrictions ) ) {
			WP_CLI::log( 'Every REST route group is open.' );
			return;
		}

		$features = mothball_rest_feature_levels();
		$rows     = array();

		foreach ( $restrictions as $group => $level ) {

			if ( mothball_rest_is_locked( $group ) ) {
				$source = 'wp-config.php';
			} elseif ( isset( $features[ $group ] ) && $features[ $group ] === $level ) {
				$source = 'feature';
			} else {
				$source = 'settings';
			}

			$rows[] = array(
				'group'  => $group,
				'access' => $level,
				'source' => $source,
			);
		}

		WP_CLI::log( '' );
		WP_CLI\Utils\format_items( $assoc_args['format'], $rows, array( 'group', 'access', 'source' ) );
	}

	/**
	 * Mothball one or more features.
	 *
	 * ## OPTIONS
	 *
	 * <switch>...
	 * : The switches to turn off. Run `wp mothball status` to list them.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp mothball off comments search
	 *     Success: Mothballed comments, search.
	 *
	 * @param array $args Positional arguments.
	 */
	public function off( $args ) {

		$this->set_switches( $args, true );
		WP_CLI::success( 'Mothballed ' . implode( ', ', $args ) . '.' );
	}

	/**
	 * Bring one or more mothballed features back.
	 *
	 * ## OPTIONS
	 *
	 * <switch>...
	 * : The switches to turn back on. Run `wp mothball status` to list them.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp mothball on comments
	 *     Success: Switched comments back on.
	 *
	 * @param array $args Positional arguments.
	 */
	public function on( $args ) {

		$this->set_switches( $args, false );
		WP_CLI::success( 'Switched ' . implode( ', ', $args ) . ' back on.' );
	}

	/**
	 * Set who can use a group of REST API routes.
	 *
	 * ## OPTIONS
	 *
	 * <group>
	 * : The route group, such as wp/v2/users or a plugin namespace like my-plugin/v1.
	 *
	 * <access>
	 * : Who can use it.
	 * ---
	 * options:
	 *   - open
	 *   - private
	 *   - off
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp mothball rest wp/v2/users private
	 *     Success: /wp/v2/users is now private.
	 *
	 * @param array $args Positional arguments.
	 */
	public function rest( $args ) {

		list( $group, $level ) = $args;

		$group = trim( $group, '/' );

		if ( ! mothball_is_rest_group( $group ) ) {
			WP_CLI::error( "'{$group}' isn't a route group. Use a namespace like my-plugin/v1, or a core group like wp/v2/users." );
		}

		if ( mothball_rest_is_locked( $group ) ) {
			WP_CLI::error( "/{$group} is set in wp-config.php, so change it there." );
		}

		if ( ! array_key_exists( $group, mothball_rest_route_groups() ) ) {
			WP_CLI::warning( "No routes are registered under /{$group} right now. Saving the setting anyway." );
		}

		$settings                   = mothball_stored_settings();
		$settings['rest'][ $group ] = $level;

		update_option( MOTHBALL_OPTION, mothball_sanitize_settings( $settings ) );

		WP_CLI::success( "/{$group} is now {$level}." );
	}

	/**
	 * Save switches, after checking every one exists and isn't set in wp-config.php.
	 *
	 * @param string[] $switches Switch names.
	 * @param bool     $off      True to mothball them, false to switch them back on.
	 */
	private function set_switches( $switches, $off ) {

		$known = mothball_switches();

		foreach ( $switches as $switch ) {

			if ( ! isset( $known[ $switch ] ) ) {
				WP_CLI::error( "'{$switch}' isn't a Mothball switch. Choose from: " . implode( ', ', array_keys( $known ) ) . '.' );
			}

			if ( mothball_is_locked( $switch ) ) {
				WP_CLI::error( "'{$switch}' is set in wp-config.php, so change it there." );
			}
		}

		$settings = mothball_stored_settings();

		foreach ( $switches as $switch ) {
			$settings[ $switch ] = $off;
		}

		update_option( MOTHBALL_OPTION, mothball_sanitize_settings( $settings ) );
	}
}

WP_CLI::add_command( 'mothball', 'Mothball_CLI' );
