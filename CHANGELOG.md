# Changelog

This project adheres to [Semantic Versioning](http://semver.org/).

### 1.0.0: 25/10/2026

* First release
* Switches under Settings → Mothball for comments, search, the blog and XML-RPC. Nothing is deleted and everything happens at runtime, so switching a feature back on, or deactivating the plugin, returns the site to how it was
* Clean-up switches for the page head, the Events and News dashboard widget, the block and pattern directories, and Heartbeat outside the editor and post lists
* Every group of REST API routes can be left open, limited to signed-in users or switched off, and the REST API discovery links can be removed
* Settings can be set in `wp-config.php` with the `MOTHBALL_SETTINGS` constant, and are then locked on the settings screen
* A `wp mothball` WP-CLI command with `status`, `on`, `off` and `rest` subcommands
* The `mothball_hidden_blocks`, `mothball_blog_taxonomies`, `mothball_rest_restrictions` and `mothball_heartbeat_screens` filters
* `install.sh` installs or updates the plugin on WordPress and Bedrock projects
* Pushing a tag publishes a GitHub release with a `mothball.zip`, with notes taken from its CHANGELOG.md entry
