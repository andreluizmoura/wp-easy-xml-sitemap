# Changelog

All notable changes to Easy XML Sitemap will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned Features
- Custom post type support
- Sitemap index file generation
- Image sitemap support
- Video sitemap support
- Multisite network-wide settings
- WP-CLI commands for sitemap management
- REST API endpoints for programmatic access
- Sitemap statistics and analytics
- Integration with popular SEO plugins

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
  - Pattern: `/easy-xml-sitemap/{type}.xml`
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
  - This `CHANGELOG.md` file
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
  - Main plugin file: `easy-xml-sitemap.php` (< 200 lines)
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
- No sitemap index file (uses individual sitemaps)
- No image or video sitemap support
- No automatic ping to search engines
- Post priority is static (not calculated based on factors)

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

### From Version X.X to 1.0

Not applicable for initial release.

### Future Upgrades

When upgrading to future versions:
1. Backup your database before upgrading
2. Check the changelog for breaking changes
3. Test on a staging site first
4. Clear all caches after upgrade
5. Regenerate sitemaps from Settings > XML Sitemap
6. Resubmit sitemaps to search engines if structure changes

---

## Support and Feedback

### Reporting Issues
If you encounter bugs or have feature requests:
1. Check existing issues on the plugin repository
2. Search the WordPress.org support forum
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
- [GitHub Repository](https://github.com/andremoura/easy-xml-sitemap)
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