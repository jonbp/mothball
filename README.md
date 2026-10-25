# Mothball

<a href="https://github.com/jonbp/mothball"><img alt="Mothball" src="https://jonbp.github.io/project-icons/mothball.svg" align="right" /></a>

[![GitHub Open Issues](https://img.shields.io/github/issues-raw/jonbp/mothball)](https://github.com/jonbp/mothball/issues)
[![GitHub Open Pull Requests](https://img.shields.io/github/issues-pr-raw/jonbp/mothball)](https://github.com/jonbp/mothball/pulls)

## About

Mothball the parts of WordPress you don't use.

Plenty of WordPress sites are brochure sites, portfolios or headless back ends that never touch comments, search or the blog. Mothball packs those features away and keeps them out of the admin, the editor and the front end. It can also close off parts of the REST API, switch off XML-RPC and tidy up the page head and admin.

Nothing is deleted. Mothball never changes your posts, comments, core settings or rewrite rules. Everything it does happens at runtime, so switching a feature back on, or deactivating the plugin, returns the site to exactly how it was.

* **One switch per feature.** Each one is a toggle under Settings → Mothball, and nothing changes until you flip it.
* **Settings in code.** Set any of them in `wp-config.php` to keep every copy of a site the same.
* **WP-CLI.** Check and change everything from the command line.

## Requirements

* WordPress 6.5 or later
* PHP 7.4 or later
* [WP-CLI](https://github.com/wp-cli/wp-cli) &mdash; Optional, for the `wp mothball` command

## Installation

Clone the plugin into your plugins folder and activate it:

```sh
cd wp-content/plugins
git clone https://github.com/jonbp/mothball.git
wp plugin activate mothball
```

Then head to **Settings → Mothball** and switch off whatever the site doesn't need.

## Features

### Comments

* Closes comments, pingbacks and trackbacks on every post type, and hides existing comments
* Removes the Comments screens, menu, admin bar bubble and dashboard activity
* Removes the comment blocks, the Recent Comments widget, the reply script and the comment feeds
* Limits the comments REST route to signed-in users, so notes in the block editor keep working

### Search

* Front end searches, `/search/` URLs and search feeds return a 404
* Removes search forms, the Search block and widget, and the admin bar search box
* Limits the search REST route to signed-in users, so link search in the editor keeps working
* Searching inside the admin is untouched

### Blog

* Hides Posts, Categories and Tags from the admin, menus, sitemaps and the REST API
* Single posts, the posts page, category, tag, date and author archives and blog feeds return a 404
* Removes the blog blocks and widgets, Quick Draft and the users sitemap
* Categories and tags that a custom post type also uses are left alone
* A homepage that shows your latest posts keeps working

### XML-RPC

* Answers every request to `xmlrpc.php`, including its RSD document, with a 403 fault before any method runs
* Removes the RSD link and the `X-Pingback` header

### Clean-up

Each of these is a separate switch.

* **Page head** &mdash; Removes the emoji script and styles, the generator tag, the RSD and shortlink tags and the oEmbed discovery links
* **Events and News** &mdash; Removes the dashboard widget that fetches from WordPress.org
* **Directories** &mdash; Stops the editor suggesting blocks from the block directory and loading remote patterns, and switches off both REST routes
* **Heartbeat** &mdash; Keeps Heartbeat in the editor and post lists, where it autosaves and shows who's editing what, and stops it everywhere else

### REST API

Every group of REST routes on the site is listed, split by resource for WordPress's own routes (`/wp/v2/users`, `/wp/v2/media` and so on) and by namespace for plugins. Each group can be:

* **Open** &mdash; As WordPress ships it
* **Signed-in only** &mdash; Removed for visitors, and still there for you and the block editor
* **Off** &mdash; Removed for everyone

Signed-in only is the safe choice for anything the editor uses. Off is for routes nothing on the site calls. Some plugins call their own routes from the front end for visitors, for analytics or forms, so leave those open.

You can also remove the REST API discovery links from the page head, the `Link` header and the RSD document.

## Settings in Code

Settings saved on the settings screen live in the database, so they can differ between your local and live sites. To keep them the same, set them in `wp-config.php` with the `MOTHBALL_SETTINGS` constant, above the `wp-settings.php` require:

```php
/* Mothball Settings */
define( 'MOTHBALL_SETTINGS', array(
	'comments' => true,
	'search'   => true,
	'blog'     => true,
	'xmlrpc'   => true,
	'rest'     => array(
		'wp/v2/users' => 'private',
	),
) );
```

Anything the constant sets is locked on the settings screen, and `wp mothball` won't change it either. Anything you leave out can still be changed as normal. If you later remove a setting from the constant, the value chosen on the screen comes back.

`true` mothballs a feature and `false` keeps it on. The available keys are:

| Key | What it switches off |
| --- | --- |
| `comments` | The comment system |
| `search` | Front end search |
| `blog` | Posts, categories and tags |
| `xmlrpc` | XML-RPC |
| `head` | The page head clean-up |
| `dashboard_news` | The Events and News dashboard widget |
| `directories` | The block and pattern directories |
| `heartbeat` | Heartbeat outside the editor and post lists |
| `rest_links` | The REST API discovery links |
| `rest` | REST route groups, each set to `open`, `private` or `off` |

## WP-CLI

```sh
wp mothball status                      # every switch, restricted route group, and where each setting comes from
wp mothball off comments search         # mothball features
wp mothball on comments                 # bring them back
wp mothball rest wp/v2/users private    # set a route group to open, private or off
```

`wp mothball status` takes `--format` as `table`, `csv`, `json` or `yaml`.

## Filters

| Filter | Description |
| --- | --- |
| `mothball_hidden_blocks` | The blocks hidden from the inserter and the front end. A name ending in `*` matches a prefix. |
| `mothball_blog_taxonomies` | The taxonomies hidden along with the blog. |
| `mothball_rest_restrictions` | The REST route groups that are restricted, and how. |
| `mothball_heartbeat_screens` | The admin screens where Heartbeat keeps running. Defaults to `post` and `edit`. |

## Good to Know

* **Deactivating.** Everything comes back straight away. Mothball keeps its settings in a single option, so reactivating it picks up where you left off. Deleting the plugin removes that option.
* **Page caches.** If the site uses a page cache, clear it after changing a setting.
* **Signed-in REST requests.** The block editor sends authenticated requests, so it still sees every signed-in only route. A request from a logged-in browser without a REST nonce counts as a visitor, which is how WordPress treats it anyway.
