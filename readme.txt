=== Mothball ===
Contributors: jonbp
Tags: disable comments, disable search, disable xml-rpc, rest api, performance
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Mothball the parts of WordPress you don't use. Switch off comments, search, the blog, XML-RPC and REST API routes, and get them back any time.

== Description ==

Plenty of WordPress sites are brochure sites, portfolios or headless back ends that never use comments, search or the blog. Mothball packs those features away and keeps them out of the admin, the editor and the front end.

Nothing is deleted. Mothball never changes your posts, comments, core settings or rewrite rules. It works entirely at runtime, so switching a feature back on, or deactivating the plugin, returns the site to exactly how it was.

= Comments =

* Closes comments, pingbacks and trackbacks on every post type, and hides existing comments
* Removes the Comments screens, menu, admin bar bubble and dashboard activity
* Removes the comment blocks, the Recent Comments widget, the reply script and the comment feeds
* Limits the comments REST route to signed-in users, so notes in the block editor keep working

= Search =

* Front end searches, /search/ URLs and search feeds return a 404
* Removes search forms, the Search block and widget, and the admin bar search box
* Limits the search REST route to signed-in users, so link search in the editor keeps working
* Searching inside the admin is untouched

= Blog =

* Hides Posts, Categories and Tags from the admin, menus, sitemaps and the REST API
* Single posts, the posts page, category, tag, date and author archives and blog feeds return a 404
* Removes the blog blocks and widgets, Quick Draft and the users sitemap
* Categories and tags that a custom post type also uses are left alone
* A homepage that shows your latest posts keeps working

= XML-RPC =

* Answers every request to xmlrpc.php, including its RSD document, with a 403 fault before any method runs
* Removes the RSD link and the X-Pingback header

= Clean-up =

Each of these is a separate switch.

* **Page head**: removes the emoji script and styles, the generator tag, the RSD and shortlink tags and the oEmbed discovery links
* **Events and News**: removes the dashboard widget that fetches from WordPress.org
* **Directories**: stops the editor suggesting blocks from the block directory and loading remote patterns, and switches off both REST routes
* **Heartbeat**: keeps Heartbeat in the editor and post lists, where it autosaves and locks posts, and stops it everywhere else

= REST API =

Every group of REST routes on the site is listed, split by resource for WordPress's own routes and by namespace for plugins. Each group can be:

* **Open**, as WordPress ships it
* **Signed-in only**, removed for visitors and still available to you and the block editor
* **Off**, removed for everyone

You can also remove the REST API discovery links from the page head, the Link header and the RSD document.

= Settings in code =

Set any setting in wp-config.php and it's locked on the settings screen, so it stays the same on every copy of the site:

`define( 'MOTHBALL_SETTINGS', array( 'comments' => true, 'xmlrpc' => true, 'rest' => array( 'wp/v2/users' => 'private' ) ) );`

Anything left out of the constant can still be changed on the screen. Remove a setting from the constant and the value chosen on the screen comes back.

= WP-CLI =

* `wp mothball status` lists every switch and restricted route group, and where each setting comes from
* `wp mothball off comments search` and `wp mothball on comments` switch features
* `wp mothball rest wp/v2/users private` sets a route group to open, private or off

== Frequently Asked Questions ==

= What happens when I deactivate Mothball? =

Everything comes back straight away. Mothball keeps its settings in a single option, so reactivating it picks up where you left off. Deleting the plugin removes that option.

= Will Signed-in only break the block editor? =

No. The editor sends an authenticated request, so it still sees every Signed-in only route. Off is different: it removes routes for everyone, so only use it for routes nothing on the site calls.

= I switched a feature off but still see it on the front end =

If the site uses a page cache, clear it after changing a setting.

== Developers ==

* `mothball_hidden_blocks` filters the blocks hidden from the inserter and the front end
* `mothball_blog_taxonomies` filters the taxonomies hidden along with the blog
* `mothball_rest_restrictions` filters the REST route groups that are restricted, and how
* `mothball_heartbeat_screens` filters the admin screens where Heartbeat keeps running

== Changelog ==

= 1.1.0 =
* Settings can be set in wp-config.php with the MOTHBALL_SETTINGS constant, and are locked on the screen.
* New switches: XML-RPC, page head clean-up, Events and News, the block and pattern directories, and Heartbeat.
* New wp mothball WP-CLI command.

= 1.0.0 =
* First release.
