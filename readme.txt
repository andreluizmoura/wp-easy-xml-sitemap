=== Easy XML Sitemap ===
Contributors: andremoura
Tags: sitemap, xml sitemap, seo, google news, robots.txt
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.2
Stable tag: 1.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight XML sitemap generator for posts, pages, taxonomies and Google News, with sitemap index and robots.txt integration.

== Description ==

Easy XML Sitemap is a lightweight and efficient plugin that generates XML sitemaps for your WordPress site. It focuses on performance, modularity, and compatibility with custom setups.

**Key Features**

* Automatically generates XML sitemaps for:
	+ Posts
	+ Pages
	+ Custom post types
	+ Taxonomies
	+ Google News (optional)
* Sitemap index file (`sitemap-index.xml`) for improved scalability
* Support for large sites with pagination and resource-friendly queries
* Simple per-post exclusion option
* Robots.txt integration (optional)
* Works alongside popular SEO plugins (Yoast, Rank Math, All in One SEO, etc.)
* Filters and actions for developers to extend and customize behavior
* No front-end bloat – all output is XML

**Performance-focused**

This plugin was built to use efficient database queries and optional caching to keep your site fast, even with large content libraries.

**Developer-friendly**

All core components are structured in classes and namespaced, with hooks provided throughout:

* `easy_xml_sitemap_before_render`
* `easy_xml_sitemap_after_render`
* `easy_xml_sitemap_before_clear_cache`
* `easy_xml_sitemap_after_clear_cache`
* `easy_xml_sitemap_meta_box_post_types`
* and more…

== Installation ==

1. Upload the `easy-xml-sitemap` folder to the `/wp-content/plugins/` directory, or install the plugin from the WordPress.org plugin repository.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings → Reading** or the plugin settings page (if available) to configure any additional options.
4. Visit `https://your-site.com/sitemap-index.xml` to view your sitemap index.

== Frequently Asked Questions ==

= Where is my sitemap located? =

By default, the sitemap index is available at:

`https://your-site.com/sitemap-index.xml`

Individual sitemaps for content types will be available under URLs like:

`https://your-site.com/sitemap-post-1.xml`  
`https://your-site.com/sitemap-page-1.xml`

The exact URLs may vary depending on your permalink structure.

= Does this plugin conflict with SEO plugins like Yoast SEO or Rank Math? =

The plugin is designed to be compatible with common SEO plugins. If another plugin already provides XML sitemaps, you may choose to disable that feature in the SEO plugin settings, or configure Easy XML Sitemap to avoid overlapping functionality.

= Does it support custom post types and taxonomies? =

Yes. Custom post types and taxonomies that are set to be public can be included in the sitemap. The plugin uses WordPress APIs to detect and handle them.

= How can I exclude specific posts or pages from the sitemap? =

You can exclude individual posts or pages directly from the edit screen by using the **XML Sitemap Options** meta box and checking the option to exclude the content from sitemaps.

= Can I customize which post types or taxonomies are included? =

Yes. The plugin provides filters that allow developers to customize which post types and taxonomies are included in the sitemap. Please refer to the developer documentation or source code comments for examples.

= Does this plugin submit the sitemap to Google or other search engines? =

No. This plugin generates and serves the XML sitemap files. You can manually submit your sitemap URL in Google Search Console, Bing Webmaster Tools, and similar services, or rely on search engines to discover it via `robots.txt`.

== Screenshots ==

1. Example of XML sitemap index output in the browser.
2. Example of an individual sitemap for posts.
3. Meta box for excluding individual posts from the sitemap.

== Changelog ==

= 1.1.1 =
* Add plugin icons.

= 1.1.0 =
* Added support for per-post exclusion via meta box.
* Introduced caching layer for sitemap queries to improve performance.
* Improved compatibility with custom post types and taxonomies.
* Enhanced robots.txt integration for sitemap index.
* Refactored internal classes to be more modular and extensible.

= 1.0.1 =
* Fixed minor issue with sitemap index URLs in certain permalink configurations.
* Improved handling of empty or non-public post types.

= 1.0.0 =
* Initial release of Easy XML Sitemap.
* XML sitemap index for posts, pages, and taxonomies.
* Basic support for large sites with pagination.

== Upgrade Notice ==

= 1.1.0 =
This release introduces per-post exclusion and an internal caching layer for improved performance. It is recommended to clear any external caches (page cache, object cache) after upgrading.
