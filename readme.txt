=== Easy XML Sitemap ===
Contributors: andremoura
Author URI: https://www.andremoura.com
Plugin URI:  https://wordpress.andremoura.com
Donate link: https://ko-fi.com/andremouradev
Tags: sitemap, xml sitemap, seo, image sitemap, video sitemap
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 2.0.3
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easy XML Sitemap is a lightweight XML sitemap generator with custom post type support, image and video extensions, WP-CLI, and automatic ping.

== Description ==

Easy XML Sitemap is a lightweight and extensible XML sitemap plugin for WordPress.

It generates a sitemap index and multiple sitemap endpoints, supports all public custom post types, and can include images and videos inside each URL entry (Google extensions). It also provides a Status page with technical statistics (generation time, URL counts, hits, and ping results) so you can quickly confirm that everything is working.

The plugin is designed to be safe in real-world WordPress setups:
- It uses caching for performance
- It supports large sites via sitemap index and posts organization options
- It includes WP-CLI commands for maintenance
- It pings search engines on updates with debounce to avoid excessive requests
- It detects when Yoast SEO or Rank Math are active and warns you clearly, without automatically overriding other plugins

Main sitemap index URL:
- /easy-sitemap/sitemap.xml

== Features ==

* Sitemap Index at `/easy-sitemap/sitemap.xml`
* Supports all public Custom Post Types (CPTs) with UI controls
* Posts can be organized as:
  - Single sitemap (all posts in one file)
  - By date (one sitemap per month/year)
  - By category (one sitemap per category)
* Image sitemap extension:
  - Featured images
  - Images found in post content
* Video sitemap extension (conservative and safe):
  - YouTube embeds supported with reliable thumbnails
  - Self-hosted video supported when thumbnail is available
  - Vimeo supported only when a reliable thumbnail is available (fallback strategy)
* Automatic ping to search engines on content update (debounced)
* Status page with technical sitemap statistics and ping results
* WP-CLI commands: status, regenerate, clear-cache
* robots.txt integration (adds sitemap URL to the virtual robots.txt)
* Per-post exclusion controls (existing feature preserved)

== Sitemap Endpoints ==

Sitemap index:
- `/easy-sitemap/sitemap.xml`

Legacy endpoints (still supported):
- `/easy-sitemap/posts-index.xml`
- `/easy-sitemap/posts-YYYY-MM.xml` (when organizing posts by date)
- `/easy-sitemap/posts-{category-slug}.xml` (when organizing posts by category)
- `/easy-sitemap/pages.xml`
- `/easy-sitemap/tags.xml`
- `/easy-sitemap/categories.xml`
- `/easy-sitemap/general.xml`
- `/easy-sitemap/news.xml`

Custom post type sitemaps (v2.0.0):
- `/easy-sitemap/{posttype}.xml` (example: `/easy-sitemap/product.xml`)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/easy-xml-sitemap/`, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Settings → Easy Sitemap.
4. Configure which post types should be included.
5. Visit `/easy-sitemap/sitemap.xml` to confirm output.

If you change permalink structure or install the plugin on an existing site, you may need to:
- click "Regenerate All Sitemaps" in the settings page, or
- run WP-CLI commands (see FAQ)

== Frequently Asked Questions ==

= Where is the sitemap? =
The main sitemap index is:
- `/easy-sitemap/sitemap.xml`

This index lists all enabled sitemap endpoints.

= Does it support Custom Post Types? =
Yes. In v2.0.0 you can enable/disable all public custom post types in Settings → Easy Sitemap → Post Types.
Each enabled post type is published at:
- `/easy-sitemap/{posttype}.xml`

= Can it include images in the sitemap? =
Yes. Enable "Include Images" in Settings → Easy Sitemap → Media.
The plugin includes:
- Featured images
- Images found in post content (`<img src="...">`)

= Can it include videos in the sitemap? =
Yes, but this is intentionally conservative to avoid generating invalid video sitemap entries.
Enable "Include Videos" in Settings → Easy Sitemap → Media.

Notes:
- YouTube embeds are supported with reliable thumbnails.
- Self-hosted video can be included when a thumbnail is available.
- Vimeo thumbnails require API access; this plugin avoids external dependencies and only includes Vimeo when a reliable thumbnail is available (fallback uses featured image when possible).

= Why does it say it detected Yoast SEO or Rank Math? Will there be conflicts? =
Yoast SEO and Rank Math often generate their own sitemaps.
Running multiple sitemaps can confuse site owners and crawlers.

Easy XML Sitemap:
- does NOT disable Yoast/Rank Math automatically
- does NOT override their settings
- does NOT intercept their sitemap URLs
- simply warns you clearly so you can choose which sitemap to use

Recommendation:
Use only one sitemap solution and submit the chosen sitemap in Google Search Console.

= Does the plugin ping search engines automatically? =
Yes. When enabled (Advanced tab), the plugin pings search engines after content updates.
It uses a debounce delay so that multiple updates in a short time trigger only one ping.

You can configure:
- enable/disable auto ping
- choose engines (Google/Bing)
- debounce delay (minutes)

= What does the Status page show? =
Settings → Easy Sitemap → Status shows technical diagnostics:
- last generation timestamp
- URL count from last generation
- generation time
- total hits and hits by endpoint type
- last ping time, engine and status

This is not SEO analytics — it is technical health information.

= What WP-CLI commands are available? =
The plugin provides a minimal set of WP-CLI commands:

- `wp easy-sitemap status`
  Shows last generation stats and hit counters.

- `wp easy-sitemap regenerate`
  Clears cache so the sitemap is regenerated on next request.

- `wp easy-sitemap clear-cache`
  Clears cache immediately.

These are useful after large imports, deployments, or troubleshooting.

= Does it affect robots.txt? =
If enabled, the plugin adds the sitemap index URL to WordPress' virtual robots.txt output.
This does not modify a physical robots.txt file on disk.

= My sitemap returns 404. What should I do? =
Most common causes:
1) Permalink rules not flushed
2) Cached rewrite rules on the site

Fix:
- Go to Settings → Permalinks and click "Save" (without changes), or
- Use the "Regenerate All Sitemaps" button, or
- Run `wp rewrite flush --hard` and `wp easy-sitemap regenerate`

== Screenshots ==

1. Main Settings tab (sitemap selection and posts organization)
2. Post Types tab (enable/disable CPTs)
3. Media tab (image and video options)
4. Status tab (technical diagnostics and ping results)
5. Advanced tab (robots.txt, cache duration, automatic ping)

== Changelog ==

= 2.0.0 =
- Added Custom Post Type support with UI controls
- Added Image sitemap support (featured + content images)
- Added Video sitemap support (conservative and safe)
- Added Status page with sitemap technical statistics
- Added WP-CLI commands (status, regenerate, clear-cache)
- Added automatic ping to search engines on content updates (debounced)
- Added safe compatibility UX for Yoast SEO and Rank Math (notice only; no overrides)
- Refactored rewrite rules and routing for CPT endpoints
- Settings UI reorganized into tabs for clarity

= 1.2.0 =
- Posts organization options: single/date/category
- Dynamic posts index with date and category sitemap endpoints
- Settings UI improvements
- Cache invalidation improvements and rewrite rules fixes

= 1.1.3 =
- Plugin icons for WordPress.org directory
- Enhanced visual branding

= 1.1.0 =
- Sitemap index at /easy-sitemap/sitemap.xml
- robots.txt integration and admin enhancements
- Base path changed to /easy-sitemap/

= 1.0.0 =
- Initial release
- Multiple sitemap types (posts, pages, tags, categories, general, news)
- Exclusion controls and caching
