=== ShortCache ===
Contributors: ideag
Tags: shortcode, cache, shortcode caching, caching
Donate link: http://arunas.co#coffee
Requires at least: 4.7
Tested up to: 4.7
Stable tag: 0.2.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows user to cache output of any shortcode by adding a `cache` attribute to it.

== Description ==

Allows user to cache output of any shortcode by adding a `cache` attribute to it. For example, cache output of `[gallery]` shortcode like this: `[gallery cache]`.

Also try out my other plugins:

* [Content Cards](http://arunas.co/cc) - a plugin that makes ordinary web links great by making it possible to embed a beautiful Content Card to link to any web site.
* [Gust](http://arunas.co/gust) - a Ghost-like admin panel for WordPress, featuring Markdown based split-view editor.
* [tinyRatings](http://arunas.co/tinyratings) - a simple rating system for WordPress. Allow your users to like, up/down vote or 5-star your posts, pages, taxonomies or even custom things.
* [tinyCoffee](http://arunas.co/tinycoffee) - a PayPal donations button with a twist. Ask people to treat you to a coffee/beer/etc.
* [tinySocial](http://arunas.co/tinysocial) - a plugin to display social sharing links to Facebook/Twitter/etc. via shortcodes
* [tinyTOC](http://arunas.co/tinytoc) - a plugin auto-generate tables of content for posts with many chapter headlines.
* [tinyIP](http://arunas.co/tinyip) - *Premium* - stop WordPress users from sharing login information, force users to be logged in only from one device at a time.

An enormous amount of coffee was consumed while developing these plugins, so if you like what you get, please consider supporting me [on Patreon](https://patreon.com/arunas).

== Installation ==

1. Install via `WP Admin > Plugins > Add New` or download a .zip file and upload via FTP to `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. (optional) Modify options via `WP Admin > Settings > ShortCache`, if needed.
1. In the shortcode You want to cache, add a `cache` attribute: `[gallery cache]`.
1. (optional) You can define a custom cache interval by passing that to the attribute like this: `[gallery cache="5"]` - this will be cached for 5 hours.
1. (optional) You can also define a custom cache scope by passing a `cache-scope` attribue: `[gallery cache cache-scope="user_id,post_id"]`. This will create different caches for every user and every post.

== Frequently Asked Questions ==

Send them to ask@arunas.co

== Screenshots ==

1. Settings screen.

== Changelog ==

= 0.2.0 =
* Initial release on wp.org

= 0.1.0 =
* Initial release
