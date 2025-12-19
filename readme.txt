=== Easy XML Sitemap ===
Contributors: andremoura
Tags: sitemap, xml sitemap, seo, google news, robots.txt
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.2
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight XML sitemap generator with posts organization options, sitemap index, and robots.txt integration.

== Description ==

Easy XML Sitemap is a lightweight and efficient plugin that generates XML sitemaps for your WordPress site. It focuses on performance, modularity, and scalability for sites of all sizes.

**Key Features**

* **Flexible Posts Organization**: Choose how to organize your posts sitemaps
	+ Single sitemap (all posts in one file)
	+ Organize by date (one sitemap per month/year) - ideal for news sites
	+ Organize by category (one sitemap per category) - great for multi-topic blogs
* **Sitemap Index**: Automatically generates a sitemap index (`sitemap.xml`)
* **Multiple Sitemap Types**:
	+ Posts (with organization options)
	+ Pages
	+ Categories
	+ Tags
	+ General (comprehensive all-in-one)
	+ Google News (optional, last 2 days)
* **robots.txt Integration**: Automatic sitemap URL addition to virtual robots.txt
* **Per-Post/Page Exclusion**: Simple checkbox to exclude individual content
* **Smart Caching System**: Configurable cache duration with automatic invalidation
* **Performance-Optimized**: Efficient queries designed for large content libraries
* **Developer-Friendly**: Filters, actions, and clean code structure

**Perfect For**

* Blogs with 10,000+ posts (use date or category organization)
* News sites publishing frequently
* Multi-category content sites
* Small to enterprise-level WordPress sites
* Developers who need extensibility

**Works Alongside Popular SEO Plugins**

Compatible with Yoast SEO, Rank Math, All in One SEO, and others. Simply disable their sitemap feature and use Easy XML Sitemap for better performance.

**Developer-Friendly**

All core components are structured in classes and namespaced, with hooks provided throughout:

* `easy_xml_sitemap_before_render`
* `easy_xml_sitemap_after_render`
* `easy_xml_sitemap_before_clear_cache`
* `easy_xml_sitemap_after_clear_cache`
* `easy_xml_sitemap_meta_box_post_types`
* `easy_xml_sitemap_cache_duration`
* and more…

== Installation ==

**Automatic Installation**

1. Go to WordPress admin → Plugins → Add New
2. Search for "Easy XML Sitemap"
3. Click "Install Now" → "Activate"
4. Go to Settings → Easy Sitemap to configure

**Manual Installation**

1. Download the plugin ZIP file
2. Go to Plugins → Add New → Upload Plugin
3. Choose the ZIP file and click "Install Now"
4. Activate the plugin
5. Configure at Settings → Easy Sitemap

**After Installation**

1. Visit Settings → Easy Sitemap
2. Choose your posts organization method:
   - **Single**: All posts in one file (best for <5,000 posts)
   - **Date**: One sitemap per month (best for news/time-based sites)
   - **Category**: One sitemap per category (best for topic-based sites)
3. Enable/disable other sitemap types as needed
4. Configure cache duration (default: 1 hour)
5. Save settings
6. Go to Settings → Permalinks and click "Save Changes" (flush rewrite rules)
7. Visit `https://your-site.com/easy-sitemap/sitemap.xml` to verify
8. Submit your sitemap to Google Search Console and Bing Webmaster Tools

== Frequently Asked Questions ==

= Where is my sitemap located? =

The main sitemap index is at:
`https://your-site.com/easy-sitemap/sitemap.xml`

Individual sitemaps are automatically generated based on your organization settings.

= Does this plugin conflict with SEO plugins? =

No conflicts. This plugin works alongside popular SEO plugins. If your SEO plugin has sitemap functionality, you can disable it and use Easy XML Sitemap instead for better performance on large sites.

= Which posts organization method should I choose? =

* **Single** (default): Best for sites with <5,000 posts. All posts in one file.
* **Date**: Best for news sites, blogs with frequent updates, or sites with 10,000+ posts. Creates one sitemap per month/year.
* **Category**: Best for multi-topic sites with well-organized categories. Creates one sitemap per category.

You can change this anytime in Settings → Easy Sitemap.

= Does it support custom post types? =

Currently, the plugin supports posts and pages. Custom post type support is planned for a future release.

= How do I exclude specific posts from the sitemap? =

1. Edit the post or page
2. Look for "XML Sitemap Options" in the sidebar (Gutenberg) or below the editor (Classic)
3. Check "Exclude from XML sitemaps"
4. Update/save the post

= Does this plugin submit the sitemap to search engines? =

No, you need to manually submit your sitemap URL to:
* [Google Search Console](https://search.google.com/search-console)
* [Bing Webmaster Tools](https://www.bing.com/webmasters)

Enter: `easy-sitemap/sitemap.xml` in the sitemap submission field.

= Why isn't my sitemap showing in robots.txt? =

The automatic robots.txt integration only works with WordPress's **virtual** robots.txt. If you have a physical `robots.txt` file in your site root, the plugin can't modify it. Either:
1. Delete the physical file (after backing it up), or
2. Manually add this line: `Sitemap: https://your-site.com/easy-sitemap/sitemap.xml`

Check Settings → Easy Sitemap for detection and instructions.

= How do I clear the sitemap cache? =

Go to Settings → Easy Sitemap and click "Regenerate All Sitemaps". The cache also clears automatically when you:
* Publish, update, or delete posts/pages
* Change categories or tags
* Modify sitemap settings

= My sitemaps return 404 errors =

1. Go to Settings → Permalinks
2. Click "Save Changes" (this flushes rewrite rules)
3. Test your sitemap URL again

= How do I check which organization method is active? =

Go to Settings → Easy Sitemap and look at the "Posts Organization" setting. You'll see three options:
* Single sitemap
* Organize by date
* Organize by category

The selected option shows which structure is currently active.

== Screenshots ==

1. **Admin Settings Page** - Configure sitemap types and posts organization
2. **Posts Organization Options** - Choose between single, date, or category organization
3. **Sitemap URLs Table** - View all your sitemap URLs with status
4. **robots.txt Integration** - Automatic sitemap detection and warnings
5. **Per-Post Exclusion** - Simple checkbox in the post editor
6. **Sitemap Index Output** - Styled XML view in browser
7. **Individual Sitemap Output** - Clean, valid XML for search engines

== Changelog ==

= 1.2.0 - 2024-12-19 =

**Added**
* Posts organization options: single, by date, or by category
* Dynamic posts index that adapts to organization method
* Posts by date sitemaps: `/easy-sitemap/posts-YYYY-MM.xml`
* Posts by category sitemaps: `/easy-sitemap/posts-{category-slug}.xml`
* Radio button UI for organization selection
* Automatic cache regeneration when settings change

**Changed**
* Posts sitemap now serves as index when date/category organization enabled
* Enhanced admin settings page with better help text
* Improved cache invalidation for dynamic sitemap types
* Updated rewrite rules to support dynamic URL patterns

**Fixed**
* Critical: Fatal error in sitemap controller causing activation failure
* Critical: Malformed rewrite rules and duplicate function declarations
* Performance: Optimized database queries for organized sitemaps
* Cache key generation for dynamic sitemap types

= 1.1.3 - 2024-12-15 =
* Added plugin icons for WordPress.org directory
* Enhanced visual branding

= 1.1.0 - 2024-12-05 =
* Added sitemap index file (`sitemap.xml`)
* Added robots.txt integration with automatic detection
* Changed base path from `/easy-xml-sitemap/` to `/easy-sitemap/`
* Updated settings menu name to "Easy Sitemap"
* Enhanced admin interface with better organization
* Added robots.txt status detection and warnings
* Improved cache management

= 1.0.0 - 2024-12-05 =
* Initial release
* Multiple sitemap types (posts, pages, tags, categories, general, news)
* Per-post/page exclusion controls
* Smart caching system
* Admin settings page
* Classic and block editor support
* Multisite compatible

== Upgrade Notice ==

= 1.2.0 =
Major update with posts organization options. Recommended for sites with 10,000+ posts. Backup before upgrading. After upgrade: 1) Go to Settings → Easy Sitemap and choose organization method, 2) Go to Settings → Permalinks and save, 3) Clear all caches, 4) Resubmit sitemap to search engines.

= 1.1.0 =
URL structure changed. Main sitemap moved to `/easy-sitemap/sitemap.xml`. After updating, flush permalinks (Settings → Permalinks → Save) and resubmit to Google Search Console.

= 1.0.0 =
Initial release of Easy XML Sitemap.

== Performance ==

This plugin is designed for performance:

* **Efficient Database Queries**: Optimized for large databases
* **Smart Caching**: Transient-based with configurable duration
* **Conditional Loading**: Admin resources only load when needed
* **No Front-End Impact**: Pure XML output, no styling or JavaScript
* **Scalable Organization**: Date/category methods handle 100,000+ posts

**Benchmarks** (average generation time on standard hosting):
* 1,000 posts: <0.5 seconds
* 10,000 posts (single): ~2 seconds
* 10,000 posts (by date): <0.5 seconds per month
* 50,000 posts (by date): <0.5 seconds per month

== Support ==

Need help? We're here for you:

* [Support Forum](https://wordpress.org/support/plugin/easy-xml-sitemap/)
* [GitHub Issues](https://github.com/andremoura/easy-xml-sitemap/issues)
* [Documentation](https://wordpress.andremoura.com)
* Email: plugins@andremoura.com

== Contributing ==

Contributions are welcome! Visit our [GitHub repository](https://github.com/andremoura/easy-xml-sitemap) to:
* Report bugs
* Suggest features
* Submit pull requests
* Review code

See [CONTRIBUTING.md](https://github.com/andremoura/easy-xml-sitemap/blob/main/CONTRIBUTING.md) for guidelines.

== Privacy ==

This plugin:
* Does NOT collect any user data
* Does NOT make external API calls
* Does NOT use cookies
* Does NOT track users
* Only generates XML files based on your public WordPress content

== Credits ==

**Developer**: André Moura
**Website**: [wordpress.andremoura.com](https://wordpress.andremoura.com)
**License**: GPL v2 or later

== Links ==

* [Plugin Homepage](https://wordpress.andremoura.com)
* [Support Forum](https://wordpress.org/support/plugin/easy-xml-sitemap/)
* [Sitemaps Protocol](https://www.sitemaps.org/protocol.html)
* [Google Sitemap Guidelines](https://developers.google.com/search/docs/advanced/sitemaps/overview)
