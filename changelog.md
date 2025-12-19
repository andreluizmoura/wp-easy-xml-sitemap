# Changelog

All notable changes to Easy XML Sitemap will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned Features
- Custom post type support with UI controls
- Image sitemap support
- Video sitemap support
- Multisite network-wide settings
- WP-CLI commands for sitemap management
- REST API endpoints for programmatic access
- Sitemap statistics and analytics
- Integration with popular SEO plugins (Yoast, Rank Math)
- Automatic ping to search engines on content update

---

## [1.2.0] - 2024-12-19

### Added
- **Posts Organization Options**: Choose how to organize posts in sitemaps
  - Single sitemap (all posts in one file)
  - Organize by date (one sitemap per month/year)
  - Organize by category (one sitemap per category)
- **Dynamic Posts Index**: Automatically generates index based on organization method
- **Posts by Date Sitemaps**: `/easy-sitemap/posts-YYYY-MM.xml` for date-based organization
- **Posts by Category Sitemaps**: `/easy-sitemap/posts-{category-slug}.xml` for category-based organization
- **Radio Button UI**: New settings interface for posts organization selection
- **Automatic Cache Regeneration**: Cache clears automatically when organization settings change

### Changed
- **Posts Sitemap Endpoint**: Now serves as an index when date/category organization is enabled
- **Admin Settings Page**: Enhanced UI with organization options and better help text
- **Cache Invalidation**: Improved logic to handle dynamic sitemap types
- **Rewrite Rules**: Added support for dynamic URL patterns (date and category slugs)

### Fixed
- **Critical**: Fixed fatal error in `class-sitemap-controller.php` caused by malformed rewrite rules
- **Critical**: Resolved duplicate function declarations and incomplete code blocks
- **Performance**: Optimized database queries for date-based and category-based sitemaps
- **Cache Keys**: Fixed cache key generation for dynamic sitemap types

### Technical Details
- Added validation for year/month parameters in date-based sitemaps
- Added category existence validation for category-based sitemaps
- Enhanced `XML_Renderer` with new methods:
  - `generate_posts_by_date($year, $month)`
  - `generate_posts_by_category($cat_slug)`
- Updated cache clearing to handle dynamic cache keys with wildcards
- Improved SQL query performance with direct database cleanup for dynamic caches

---

## [1.1.3] - 2024-12-15

### Added
- Plugin icons for WordPress.org directory
- Enhanced visual branding

---

## [1.1.0] - 2024-12-05

### Added
- **Sitemap Index**: Main sitemap index file at `/easy-sitemap/sitemap.xml`
- **robots.txt Integration**: Automatic sitemap URL addition to virtual robots.txt
- **Sitemap Index Priority**: Index now appears first in the admin URLs table with visual highlighting
- **Physical robots.txt Detection**: Warning in admin when physical robots.txt file exists
- **robots.txt Viewer**: Direct link to view current robots.txt from settings page
- **Enhanced Admin Interface**: Improved settings page with better organization and help text

### Changed
- **Sitemap Base Path**: Changed from `/easy-xml-sitemap/` to `/easy-sitemap/` for cleaner URLs
- **Settings Menu Name**: Changed from "XML Sitemap" to "Easy Sitemap"
- **Main Sitemap URL**: Now `/easy-sitemap/sitemap.xml` (sitemap index)
- **Individual Sitemaps**: All other sitemaps listed in the index
- **Admin Table Layout**: Sitemap index highlighted with blue background
- **robots.txt Implementation**: Uses WordPress filter instead of manual file manipulation

### Updated
- All rewrite rules updated for new base path
- Cache system updated to handle sitemap index
- Documentation updated with new URLs and features
- Install.md with comprehensive robots.txt troubleshooting

### Fixed
- robots.txt integration now uses proper WordPress filters
- Duplicate sitemap entries prevention in robots.txt
- Permalink flushing on activation and settings changes

---

## [1.0.0] - 2024-12-05

### Added
- Initial release of Easy XML Sitemap
- Multiple sitemap types:
  - Posts sitemap (`posts.xml`)
  - Pages sitemap (`pages.xml`)
  - Tags sitemap (`tags.xml`)
  - Categories sitemap (`categories.xml`)
  - General sitemap (`general.xml`) - comprehensive sitemap with all URLs
  - Google News sitemap (`news.xml`) - compliant with Google News schema
- Per-post/page exclusion control
  - Meta box for classic editor
  - Sidebar panel for block editor (Gutenberg)
  - Post meta storage with `_easy_xml_sitemap_exclude` key
- Smart caching system
  - Configurable cache duration (60 seconds to 1 week)
  - Automatic cache invalidation on content changes
  - Manual regeneration option
- Admin settings page
  - Enable/disable individual sitemaps
  - Cache duration configuration
  - Sitemap URLs display with status
  - Manual regeneration button
- Clean, SEO-friendly URLs
  - Pattern: `/easy-sitemap/{type}.xml`
  - Custom rewrite rules
  - Proper XML content-type headers
- Automatic updates
  - Cache clears on post/page publish, update, delete
  - Cache clears on taxonomy (category/tag) changes
  - Cache clears on post meta updates
- WordPress compatibility
  - Classic editor support
  - Block editor (Gutenberg) support
  - Multisite compatible
  - REST API integration for block editor
- Performance optimizations
  - Transient-based caching
  - Efficient database queries
  - Conditional loading of admin resources
- Security features
  - Capability checks (`manage_options`, `edit_posts`, `edit_pages`)
  - Nonce verification for all forms
  - Input sanitization and output escaping
  - SQL injection prevention with prepared statements
- Developer features
  - Clean, modular code structure
  - PHP namespacing (`EasyXMLSitemap`)
  - PSR-4 compatible autoloading
  - WordPress Coding Standards compliant
  - Inline documentation and DocBlocks
- Documentation
  - Comprehensive `readme.txt` for WordPress.org
  - Detailed `Install.md` with installation and troubleshooting
  - `CHANGELOG.md` file
  - `CONTRIBUTING.md` guidelines
- Proper plugin lifecycle
  - Activation hook (registers rewrite rules, sets defaults)
  - Deactivation hook (flushes rewrite rules, clears cache)
  - Uninstall script (removes all plugin data)

### Technical Details
- **Minimum Requirements**:
  - WordPress 5.0+
  - PHP 7.2+
  - Pretty permalinks recommended
- **File Structure**:
  - Main plugin file: `easy-xml-sitemap.php`
  - Core classes in `inc/` directory
  - Modular architecture for easy maintenance
- **Classes**:
  - `Easy_XML_Sitemap` - Main plugin bootstrap
  - `Cache` - Cache management and invalidation
  - `XML_Renderer` - XML generation for all sitemap types
  - `Sitemap_Controller` - Request handling and routing
  - `Post_Meta` - Per-post/page exclusion controls
  - `Admin_Settings` - Admin interface and settings management

### XML Standards Compliance
- Sitemaps.org protocol compliance
- Google News sitemap specification compliance
- Valid XML output with proper encoding (UTF-8)
- ISO 8601 date format for timestamps
- Proper URL escaping and entity encoding

### Google News Sitemap Specifics
- Includes posts from last 2 days only
- Required fields implemented:
  - `<news:publication><news:name>` - Site name
  - `<news:language>` - Site language code
  - `<news:publication_date>` - Post publication date
  - `<news:title>` - Post title
- Optional fields implemented:
  - `<news:genres>` - Hardcoded to "Blog"
  - `<news:keywords>` - Up to 10 post tags
- Complies with Google News content policies

### Known Limitations
- No custom post type support (planned for future release)
- No image or video sitemap support (planned)
- No automatic ping to search engines (planned)

---

## Version History

### Versioning Scheme

This plugin uses Semantic Versioning (SemVer):
- **MAJOR** version for incompatible API changes
- **MINOR** version for new functionality in a backwards compatible manner
- **PATCH** version for backwards compatible bug fixes

### Release Types

- **Alpha** - Early development, unstable, not recommended for production
- **Beta** - Feature complete, testing phase, may contain bugs
- **RC (Release Candidate)** - Final testing before stable release
- **Stable** - Production-ready, fully tested

---

## Migration Guide

### From Version 1.1.x to 1.2.0

1. **Backup your database** before upgrading
2. The plugin will automatically migrate to the new structure
3. **Default behavior**: Posts organization defaults to "single" (all posts in one file)
4. If you have a large site (10,000+ posts), consider:
   - Changing to "date" organization for better performance
   - Changing to "category" organization if content is well-categorized
5. **Clear all caches** after upgrade:
   - Plugin cache (automatic)
   - WordPress object cache
   - Page cache (WP Super Cache, W3 Total Cache, etc.)
   - CDN cache (Cloudflare, etc.)
6. **Flush permalinks**: Go to Settings > Permalinks and click "Save Changes"
7. **Regenerate sitemaps**: Go to Settings > Easy Sitemap and click "Regenerate All Sitemaps"
8. **Resubmit to search engines**: Update your sitemap in Google Search Console and Bing Webmaster Tools
9. The main sitemap URL remains the same: `/easy-sitemap/sitemap.xml`

### From Version 1.0.x to 1.1.0

1. **Main sitemap URL changed**: Update from `/easy-xml-sitemap/sitemap-index.xml` to `/easy-sitemap/sitemap.xml`
2. **Resubmit to search engines**: Update sitemap URL in Google Search Console and Bing
3. All individual sitemap URLs changed from `/easy-xml-sitemap/` to `/easy-sitemap/`
4. robots.txt integration now automatic (if no physical robots.txt exists)
5. Check Settings > Easy Sitemap for robots.txt status

---

## Support and Feedback

### Reporting Issues
If you encounter bugs or have feature requests:
1. Check existing issues on the [GitHub repository](https://github.com/andremoura/easy-xml-sitemap/issues)
2. Search the [WordPress.org support forum](https://wordpress.org/support/plugin/easy-xml-sitemap/)
3. Create a new issue with detailed information:
   - Plugin version
   - WordPress version
   - PHP version
   - Steps to reproduce
   - Expected vs actual behavior
   - Error messages or logs

### Feature Requests
We welcome feature suggestions! Please:
1. Check if the feature is already planned (see Unreleased section)
2. Describe the use case and benefit
3. Provide examples or mockups if applicable
4. Submit via GitHub Issues or WordPress.org support forum

---

## Contributors

### Core Team
- **André Moura** - Initial development and maintenance

### Special Thanks
- WordPress community for support and feedback
- Early testers and beta users
- Contributors (see CONTRIBUTING.md)

---

## Links

- [Plugin Homepage](https://wordpress.andremoura.com)
- [WordPress.org Plugin Page](https://wordpress.org/plugins/easy-xml-sitemap/)
- [Support Forum](https://wordpress.org/support/plugin/easy-xml-sitemap/)
- [Documentation](https://wordpress.andremoura.com/easy-xml-sitemap/docs/)

---

**Legend:**
- `Added` - New features
- `Changed` - Changes to existing functionality
- `Deprecated` - Soon-to-be removed features
- `Removed` - Removed features
- `Fixed` - Bug fixes
- `Security` - Security improvements
