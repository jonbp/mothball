<?php
/**
 * Admin: the Settings > Mothball screen.
 *
 * @package Mothball
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add the screen under Settings.
 */
function mothball_admin_menu() {

	$hook_suffix = add_options_page(
		__( 'Mothball', 'mothball' ),
		__( 'Mothball', 'mothball' ),
		'manage_options',
		'mothball',
		'mothball_render_settings_page'
	);

	add_action( 'admin_print_styles-' . $hook_suffix, 'mothball_enqueue_admin_styles' );
}

add_action( 'admin_menu', 'mothball_admin_menu' );

/**
 * Load the settings screen styles, on that screen only.
 */
function mothball_enqueue_admin_styles() {

	wp_enqueue_style( 'mothball-admin', plugins_url( 'assets/css/admin.css', MOTHBALL_FILE ), array( 'dashicons' ), MOTHBALL_VERSION );
}

/**
 * Register the option.
 */
function mothball_register_setting() {

	register_setting(
		'mothball',
		MOTHBALL_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'mothball_sanitize_settings',
			'default'           => mothball_defaults(),
			'show_in_rest'      => false,
		)
	);
}

add_action( 'admin_init', 'mothball_register_setting' );

/**
 * Notes for route groups that need explaining.
 *
 * @return array<string, string> Note by route group.
 */
function mothball_rest_group_notes() {

	return array(
		'wp/v2/users'        => __( 'Lists everyone who has published content, with their usernames. Signed-in only stops visitors collecting them.', 'mothball' ),
		'wp-block-editor/v1' => __( 'Used by the block editor.', 'mothball' ),
		'oembed/1.0'         => __( 'Used when other sites embed links to your pages.', 'mothball' ),
	);
}

/**
 * Render the note shown beside a setting that wp-config.php sets.
 */
function mothball_render_locked_note() {

	?>
	<p class="description mothball-locked">
		<span class="dashicons dashicons-lock" aria-hidden="true"></span>
		<?php esc_html_e( 'Set in wp-config.php.', 'mothball' ); ?>
	</p>
	<?php
}

/**
 * Render a table of route groups with an access level select for each.
 *
 * @param array<string, string[]> $groups Routes by route group.
 */
function mothball_render_rest_table( $groups ) {

	$settings = mothball_settings();
	$stored   = mothball_stored_settings();
	$levels   = mothball_rest_access_levels();
	$features = mothball_rest_feature_levels();
	$notes    = mothball_rest_group_notes();

	?>
	<table class="widefat striped">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Routes', 'mothball' ); ?></th>
				<th scope="col" style="width: 12em;"><?php esc_html_e( 'Access', 'mothball' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $groups as $group => $routes ) : ?>
				<?php
				$id     = 'mothball-route-' . sanitize_html_class( str_replace( '/', '-', $group ) );
				$name   = MOTHBALL_OPTION . '[rest][' . $group . ']';
				$value  = isset( $settings['rest'][ $group ] ) ? $settings['rest'][ $group ] : 'open';
				$saved  = isset( $stored['rest'][ $group ] ) ? $stored['rest'][ $group ] : 'open';
				$locked = mothball_rest_is_locked( $group );
				$forced = ! $locked && isset( $features[ $group ] ) && 'open' === $value;

				if ( $forced ) {
					$value = $features[ $group ];
				}
				?>
				<tr>
					<td>
						<label for="<?php echo esc_attr( $id ); ?>"><code>/<?php echo esc_html( $group ); ?></code></label>
						<span class="description">
							<?php
							/* translators: %s: number of routes */
							echo esc_html( sprintf( _n( '%s route', '%s routes', count( $routes ), 'mothball' ), number_format_i18n( count( $routes ) ) ) );
							?>
						</span>
						<?php if ( $locked ) : ?>
							<?php mothball_render_locked_note(); ?>
						<?php elseif ( $forced ) : ?>
							<p class="description"><?php esc_html_e( 'Set by a mothballed feature above.', 'mothball' ); ?></p>
						<?php elseif ( isset( $notes[ $group ] ) ) : ?>
							<p class="description"><?php echo esc_html( $notes[ $group ] ); ?></p>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $forced ) : ?>
							<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $saved ); ?>" />
						<?php endif; ?>
						<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" <?php disabled( $locked || $forced ); ?>>
							<?php foreach ( $levels as $level => $label ) : ?>
								<option value="<?php echo esc_attr( $level ); ?>" <?php selected( $value, $level ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Render one section's switches as a table of toggles.
 *
 * @param array<string, array{label: string, title: string, description: string}> $switches Switches by setting name.
 */
function mothball_render_switches( $switches ) {

	?>
	<table class="form-table" role="presentation">
		<?php foreach ( $switches as $switch => $details ) : ?>
			<?php $id = 'mothball-' . str_replace( '_', '-', $switch ); ?>
			<tr class="mothball-anchor" id="mothball-switch-<?php echo esc_attr( $switch ); ?>">
				<th scope="row"><?php echo esc_html( $details['label'] ); ?></th>
				<td>
					<label for="<?php echo esc_attr( $id ); ?>">
						<input type="checkbox" class="mothball-toggle" role="switch" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( MOTHBALL_OPTION . '[' . $switch . ']' ); ?>" value="1" <?php checked( mothball_is_off( $switch ) ); ?> <?php disabled( mothball_is_locked( $switch ) ); ?> />
						<?php echo esc_html( $details['title'] ); ?>
					</label>
					<?php if ( mothball_is_locked( $switch ) ) : ?>
						<?php mothball_render_locked_note(); ?>
					<?php endif; ?>
					<p class="description"><?php echo esc_html( $details['description'] ); ?></p>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
	<?php
}

/**
 * Render the sidebar that links to each part of the settings screen.
 *
 * @param bool $has_core  Whether the WordPress routes table is shown.
 * @param bool $has_other Whether the other namespaces table is shown.
 */
function mothball_render_nav( $has_core, $has_other ) {

	$tables = array();

	if ( $has_core ) {
		$tables['mothball-rest-core'] = __( 'WordPress routes', 'mothball' );
	}

	if ( $has_other ) {
		$tables['mothball-rest-other'] = __( 'Other namespaces', 'mothball' );
	}

	?>
	<nav class="mothball-nav" aria-label="<?php esc_attr_e( 'Mothball settings', 'mothball' ); ?>">
		<ul>
			<?php foreach ( mothball_sections() as $section => $details ) : ?>
				<li>
					<a href="#mothball-section-<?php echo esc_attr( $section ); ?>"><?php echo esc_html( $details['label'] ); ?></a>
					<ul>
						<?php foreach ( $details['switches'] as $switch => $switch_details ) : ?>
							<li>
								<a href="#mothball-switch-<?php echo esc_attr( $switch ); ?>">
									<?php echo esc_html( $switch_details['label'] ); ?>
									<?php if ( mothball_is_off( $switch ) ) : ?>
										<span class="mothball-nav-badge"><?php esc_html_e( 'Off', 'mothball' ); ?></span>
									<?php endif; ?>
								</a>
							</li>
						<?php endforeach; ?>
						<?php if ( 'rest' === $section ) : ?>
							<?php foreach ( $tables as $anchor => $label ) : ?>
								<li><a href="#<?php echo esc_attr( $anchor ); ?>"><?php echo esc_html( $label ); ?></a></li>
							<?php endforeach; ?>
						<?php endif; ?>
					</ul>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php
}

/**
 * Render the small links under the sidebar, to the project and its author.
 */
function mothball_render_links() {

	$links = array(
		'https://github.com/jonbp/mothball' => __( 'GitHub', 'mothball' ),
		'https://jonbp.co.uk/'              => __( 'Jon Beaumont-Pike', 'mothball' ),
	);

	?>
	<ul class="mothball-links">
		<?php foreach ( $links as $url => $label ) : ?>
			<li>
				<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
					<?php echo esc_html( $label ); ?>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'mothball' ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Render the settings screen.
 */
function mothball_render_settings_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$groups = mothball_rest_route_groups();
	$core   = array();
	$other  = array();

	foreach ( $groups as $group => $routes ) {

		if ( 0 === strpos( $group, 'wp/v2/' ) ) {
			$core[ $group ] = $routes;
		} else {
			$other[ $group ] = $routes;
		}
	}

	?>
	<div class="wrap mothball">

		<form class="mothball-layout" action="options.php" method="post">

			<?php settings_fields( 'mothball' ); ?>

			<div class="mothball-sidebar">
				<h1 class="mothball-title">
					<img src="<?php echo esc_url( plugins_url( 'assets/images/mothball.svg', MOTHBALL_FILE ) ); ?>" width="32" height="32" alt="" />
					<?php echo esc_html( get_admin_page_title() ); ?>
				</h1>
				<?php mothball_render_nav( ! empty( $core ), ! empty( $other ) ); ?>
				<?php submit_button(); ?>
				<?php mothball_render_links(); ?>
			</div>

			<div class="mothball-content">

				<hr class="wp-header-end">

				<p class="mothball-intro"><?php esc_html_e( 'Switch off the parts of WordPress this site doesn\'t use. Nothing is deleted, so you can switch a feature back on at any time.', 'mothball' ); ?></p>

				<?php if ( mothball_config() ) : ?>
					<div class="notice notice-info inline">
						<p><?php esc_html_e( 'Some settings are set in wp-config.php with the MOTHBALL_SETTINGS constant. They are locked here and can only be changed there.', 'mothball' ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( mothball_is_off( 'blog' ) && 'posts' === get_option( 'show_on_front' ) ) : ?>
					<div class="notice notice-warning inline">
						<p>
							<?php
							printf(
								/* translators: %s: link to the Reading settings */
								esc_html__( 'Your homepage shows your latest posts, so it stays up while the blog is mothballed. Choose a static homepage under %s to hide the blog completely.', 'mothball' ),
								'<a href="' . esc_url( admin_url( 'options-reading.php' ) ) . '">' . esc_html__( 'Settings > Reading', 'mothball' ) . '</a>'
							);
							?>
						</p>
					</div>
				<?php endif; ?>

				<?php foreach ( mothball_sections() as $section => $details ) : ?>

					<h2 class="mothball-anchor" id="mothball-section-<?php echo esc_attr( $section ); ?>"><?php echo esc_html( $details['label'] ); ?></h2>

					<?php mothball_render_switches( $details['switches'] ); ?>

				<?php endforeach; ?>

				<p><?php esc_html_e( 'Signed-in only hides a group of routes from visitors and keeps it working for you, so it is the safe choice for anything the block editor uses. Off removes the routes for everyone, including the editor, so use it only for routes nothing on the site needs.', 'mothball' ); ?></p>
				<p><?php esc_html_e( 'Some plugins call their own routes from the front end for visitors, for analytics or forms. Leave those open.', 'mothball' ); ?></p>

				<?php if ( $core ) : ?>
					<h3 class="mothball-anchor" id="mothball-rest-core"><?php esc_html_e( 'WordPress routes', 'mothball' ); ?></h3>
					<?php mothball_render_rest_table( $core ); ?>
				<?php endif; ?>

				<?php if ( $other ) : ?>
					<h3 class="mothball-anchor" id="mothball-rest-other"><?php esc_html_e( 'Other namespaces', 'mothball' ); ?></h3>
					<?php mothball_render_rest_table( $other ); ?>
				<?php endif; ?>

			</div>

		</form>

	</div>
	<?php
}

/**
 * Link to the settings from the Plugins screen.
 *
 * @param string[] $links Action links.
 * @return string[]
 */
function mothball_action_links( $links ) {

	array_unshift(
		$links,
		sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=mothball' ) ),
			esc_html__( 'Settings', 'mothball' )
		)
	);

	return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename( MOTHBALL_FILE ), 'mothball_action_links' );
