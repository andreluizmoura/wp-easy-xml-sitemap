# Changelog

All notable changes to Easy XML Sitemap will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned / Future Development
- Multisite network-wide settings
- REST API endpoints for programmatic access
- Advanced sitemap analytics (beyond technical stats)
- Deeper integration with SEO plugins metadata (noindex/canonical rules, etc.)

---

## [2.0.0] - 2025-12-19

### Added
- **Custom Post Type support with UI controls**
  - Enable/disable all public CPTs (attachments excluded)
  - CPT sitemaps at `/easy-sitemap/{posttype}.xml`
  - CPT sitemaps listed in `/easy-sitemap/sitemap.xml`
- **Image sitemap support**
  - Featured image + images found in post content
  - Image entries included inside each `<url>` when enabled
- **Video sitemap support (conservative)**
  - YouTube thumbnails derived safely
  - Vimeo supported only when a reliable thumbnail is available (fallback to featured image)
  - Self-hosted `<video src>` supported with featured image thumbnail fallback
- **Sitemap statistics / status page**
  - Last generation timestamp, URL count and generation time
  - Total hits + hits by endpoint type
  - Last ping status (engine and result)
- **WP-CLI commands**
  - `wp easy-sitemap status`
  - `wp easy-sitemap regenerate`
  - `wp easy-sitemap clear-cache`
- **Automatic ping to search engines on content update**
  - Debounced scheduling to prevent excessive ping during bulk updates/imports
- **Yoast SEO / Rank Math compatibility UX**
  - Admin notice when another SEO plugin is active
  - No automatic overrides or disabling (user stays in control)

### Changed
- Refactored rewrite rules and routing to support CPT endpoints safely
- Settings updated with tabs (Sitemaps, Post Types, Media, Status, Advanced)
- Automatic settings migration from v1.x to v2 map (`post_types`)

### Fixed
- Improved cache clearing behavior when settings change (cache + rewrite flush)
- Safer video sitemap generation by skipping entries without required fields

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
- Smart caching system with automatic invalidation
- Admin settings page with enable/disable and regeneration
- Clean, SEO-friendly URLs
- Security hardening and documentation
