# Easy XML Sitemap - Installation Guide

## Version 1.1.0

Complete installation and configuration guide for Easy XML Sitemap WordPress plugin.

---

## Table of Contents

1. [Requirements](#requirements)
2. [Installation Methods](#installation-methods)
3. [Initial Configuration](#initial-configuration)
4. [Sitemap Index Setup](#sitemap-index-setup)
5. [robots.txt Integration](#robotstxt-integration)
6. [Submitting to Search Engines](#submitting-to-search-engines)
7. [Troubleshooting](#troubleshooting)
8. [Advanced Configuration](#advanced-configuration)

---

## Requirements

### Minimum Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.2 or higher
- **Server**: Apache or Nginx with mod_rewrite enabled
- **Permalinks**: Pretty permalinks recommended (Settings > Permalinks > anything except "Plain")

### Recommended

- WordPress 6.0+
- PHP 8.0+
- SSL certificate (HTTPS)
- WP_DEBUG disabled in production

---

## Installation Methods

### Method 1: WordPress Dashboard (Recommended)

1. **Login** to your WordPress admin panel
2. Navigate to **Plugins > Add New**
3. Search for **"Easy XML Sitemap"**
4. Click **"Install Now"** next to Easy XML Sitemap by André Moura
5. Click **"Activate"** once installation completes
6. You'll see a success message confirming activation

### Method 2: Upload via Dashboard

1. Download the plugin ZIP file from WordPress.org or the plugin website
2. Login to your WordPress admin panel
3. Navigate to **Plugins > Add New**
4. Click **"Upload Plugin"** at the top
5. Click **"Choose File"** and select the downloaded ZIP
6. Click **"Install Now"**
7. Click **"Activate Plugin"**

### Method 3: Manual FTP Installation

1. Download and extract the plugin ZIP file
2. Connect to your server via FTP/SFTP
3. Navigate to `/wp-content/plugins/`
4. Upload the entire `easy-xml-sitemap` folder
5. Login to WordPress admin
6. Navigate to **Plugins > Installed Plugins**
7. Find "Easy XML Sitemap" and click **"Activate"**

### Method 4: WP-CLI Installation

```bash
# Install from WordPress.org
wp plugin install easy-xml-sitemap --activate

# Or install from ZIP file
wp plugin install /path/to/easy-xml-sitemap.zip --activate
```

---

## Initial Configuration

### Step 1: Access Settings

After activation, navigate to:
**WordPress Dashboard > Settings > Easy Sitemap**

### Step 2: Configure Sitemap Types

The settings page shows several options:

#### Sitemap Configuration

- **☑ Sitemap Index** (Recommended: Enabled)
  - Main sitemap that lists all other sitemaps
  - This is what you submit to search engines

- **☑ Posts Sitemap**
  - Includes all published posts
  - Default: Enabled

- **☑ Pages Sitemap**
  - Includes all published pages
  - Default: Enabled

- **☑ Tags Sitemap**
  - Includes all post tags with content
  - Default: Enabled

- **☑ Categories Sitemap**
  - Includes all categories with content
  - Default: Enabled

- **☑ General Sitemap**
  - Comprehensive sitemap with all URLs
  - Includes: homepage, posts, pages, categories, tags
  - Default: Enabled

- **☐ Google News Sitemap**
  - Posts from last 2 days in Google News format
  - Default: Disabled (enable only if you publish news)
  - Requires frequent updates

#### robots.txt Integration

- **☑ Add to robots.txt** (Recommended: Enabled)
  - Automatically adds sitemap index URL to virtual robots.txt
  - Helps search engines discover your sitemaps
  - **Note**: Only works if NO physical robots.txt file exists in your site root

#### Cache Duration

- **Default**: 3600 seconds (1 hour)
- **Range**: 60 seconds (1 minute) to 604800 seconds (1 week)
- **Recommendation**: 
  - High-traffic sites: 3600-7200 seconds
  - Low-traffic sites: 21600-86400 seconds
  - News sites: 300-900 seconds

### Step 3: Save Settings

Click **"Save Settings"** at the bottom of the page.

---

## Sitemap Index Setup

### What is a Sitemap Index?

A sitemap index is the **master sitemap** that lists all your other sitemaps. This is the recommended approach for submitting sitemaps to search engines.

### Your Sitemap Index URL

After enabling the sitemap index, you'll find it at:
```
https://yourdomain.com/easy-sitemap/sitemap-index.xml
```

### Verification

1. **Navigate** to Settings > Easy Sitemap
2. Look for the **Sitemap URLs** table
3. The **Sitemap Index (Main)** should appear **first** with a blue background
4. Click the URL to view it in your browser
5. You should see XML code listing all enabled sitemaps

**Example sitemap index output:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>https://yourdomain.com/easy-sitemap/posts.xml</loc>
        <lastmod>2024-12-05T12:00:00+00:00</lastmod>
    </sitemap>
    <sitemap>
        <loc>https://yourdomain.com/easy-sitemap/pages.xml</loc>
        <lastmod>2024-12-05T12:00:00+00:00</lastmod>
    </sitemap>
    <!-- Additional sitemaps... -->
</sitemapindex>
```

---

## robots.txt Integration

### Understanding robots.txt

The `robots.txt` file tells search engines which pages to crawl. Adding your sitemap helps search engines discover your content faster.

### How It Works

When **"Add to robots.txt"** is enabled, the plugin automatically adds:
```
Sitemap: https://yourdomain.com/easy-sitemap/sitemap-index.xml
```

### Checking Your robots.txt

Visit: `https://yourdomain.com/robots.txt`

You should see something like:
```
User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php

Sitemap: https://yourdomain.com/easy-sitemap/sitemap-index.xml
```

### Troubleshooting robots.txt

**❌ Problem**: Sitemap not appearing in robots.txt

**✅ Solutions**:

1. **Check for physical robots.txt file**
   - Connect via FTP
   - Look in your site's root directory
   - If `robots.txt` exists, delete it (after backing up)
   - WordPress will then use the virtual robots.txt

2. **Verify settings**
   - Go to Settings > Easy Sitemap
   - Ensure "Add to robots.txt" is checked
   - Ensure "Sitemap Index" is enabled
   - Save settings

3. **Clear permalinks**
   - Go to Settings > Permalinks
   - Click "Save Changes" (no changes needed)
   - This refreshes rewrite rules

4. **Check file permissions**
   - If you must use a physical robots.txt:
   - Add this line manually:
     ```
     Sitemap: https://yourdomain.com/easy-sitemap/sitemap-index.xml
     ```

---

## Submitting to Search Engines

### Google Search Console

**Most Important**: This is the primary way Google discovers your content.

1. **Login** to [Google Search Console](https://search.google.com/search-console)
2. **Select** your property (website)
3. Navigate to **Sitemaps** in the left menu
4. In the "Add a new sitemap" field, enter:
   ```
   easy-sitemap/sitemap-index.xml
   ```
5. Click **"Submit"**
6. Status should show "Success" after a few moments

**Expected Result**: Google will regularly check your sitemap index and crawl the individual sitemaps it references.

### Bing Webmaster Tools

1. **Login** to [Bing Webmaster Tools](https://www.bing.com/webmasters)
2. **Select** your site
3. Navigate to **Sitemaps**
4. Click **"Submit Sitemap"**
5. Enter:
   ```
   https://yourdomain.com/easy-sitemap/sitemap-index.xml
   ```
6. Click **"Submit"**

### Other Search Engines

Most other search engines (Yahoo, DuckDuckGo, etc.) use Google or Bing data, so submitting to those two covers most bases.

### Verification Timeline

- **Google**: Usually processes within 24-48 hours
- **Bing**: May take 3-7 days
- **Full indexing**: Can take 2-4 weeks for all URLs

---

## Troubleshooting

### Permalinks Issues

**Symptom**: Sitemaps return 404 errors

**Solution**:
1. Go to **Settings > Permalinks**
2. Click **"Save Changes"** (even if you didn't change anything)
3. This flushes and regenerates WordPress rewrite rules
4. Test your sitemap URLs again

### Cache Not Clearing

**Symptom**: Updates not appearing in sitemaps

**Solution**:
1. Go to **Settings > Easy Sitemap**
2. Click **"Regenerate All Sitemaps"** button
3. Check if you're using a caching plugin (W3 Total Cache, WP Super Cache, etc.)
4. Clear caching plugin cache
5. Clear CDN cache if using Cloudflare, etc.

### Empty Sitemaps

**Symptom**: Sitemap loads but has no URLs

**Possible Causes**:
1. **No published content** - Create and publish posts/pages
2. **All content excluded** - Check exclusion settings on posts/pages
3. **Permalink issue** - Reset permalinks (see above)

**Debug Steps**:
```
1. Check if you have published posts/pages
2. Edit a post and look for "XML Sitemap Settings" panel
3. Ensure "Exclude from XML sitemaps" is NOT checked
4. Save/update the post
5. Manually regenerate sitemaps
6. Check sitemap URL again
```

### Wrong Content-Type

**Symptom**: Browser shows raw text instead of XML

**Solution**:
- This is usually a server configuration issue
- The plugin sends correct headers
- Try viewing in different browsers
- Check if .htaccess has XML MIME type configured

### Performance Issues

**Symptom**: Slow admin or front-end

**Solutions**:
1. **Increase cache duration**
   - Go to Settings > Easy Sitemap
   - Set cache duration to 21600 (6 hours) or higher

2. **Disable unused sitemaps**
   - If you don't need the general sitemap, disable it
   - If not a news site, disable Google News sitemap

3. **Check query performance**
   - Very large sites (10,000+ posts) may need optimization
   - Consider enabling object caching on your server

### robots.txt Not Working

See the [robots.txt Integration](#robotstxt-integration) section above for detailed troubleshooting.

---

## Advanced Configuration

### Per-Post/Page Exclusion

#### Block Editor (Gutenberg)

1. **Edit** any post or page
2. Look for **"XML Sitemap"** panel in the right sidebar
3. Check **"Exclude from XML sitemaps"**
4. **Update** the post

#### Classic Editor

1. **Edit** any post or page
2. Scroll down to **"XML Sitemap Settings"** meta box
3. Check **"Exclude from XML sitemaps"**
4. **Update** the post

**Result**: This content won't appear in ANY sitemap (posts, pages, general, or news).

### Customizing Cache Duration

**For Different Site Types**:

| Site Type | Recommended Duration | Reason |
|-----------|---------------------|---------|
| Blog (1-2 posts/week) | 86400 (1 day) | Content rarely changes |
| News Site | 300-900 (5-15 min) | Frequent updates |
| E-commerce | 3600-7200 (1-2 hrs) | Products change regularly |
| Static Site | 604800 (1 week) | Content very stable |
| Corporate Site | 21600-43200 (6-12 hrs) | Occasional updates |

### Manual Cache Control

**When to regenerate manually**:
- After bulk importing content
- After significant site restructuring
- After theme change
- Before submitting to search engines
- After migration

**How to regenerate**:
1. Go to **Settings > Easy Sitemap**
2. Scroll to **"Cache Management"** section
3. Click **"Regenerate All Sitemaps"** button
4. Wait for success message

### File Structure

Understanding the plugin structure:

```
easy-xml-sitemap/
├── easy-xml-sitemap.php          # Main plugin file
├── uninstall.php                 # Cleanup script
├── readme.txt                    # WordPress.org readme
├── changelog.md                  # Version history
├── CONTRIBUTING.md               # Contribution guide
├── Install.md                    # This file
├── composer.json                 # PHP dependencies
├── package.json                  # Node.js dependencies
└── inc/                          # Core classes
    ├── class-cache.php           # Cache management
    ├── class-xml-renderer.php    # XML generation
    ├── class-sitemap-controller.php  # Request handling
    ├── class-post-meta.php       # Exclusion controls
    └── class-admin-settings.php  # Admin interface
```

### Developer Hooks

The plugin provides several filters for developers:

```php
// Modify cache duration programmatically
add_filter('easy_xml_sitemap_cache_duration', function($duration) {
    return 7200; // 2 hours
});
```

### Multisite Compatibility

The plugin works on WordPress Multisite:
- Each site has independent settings
- Sitemaps are site-specific
- Network-wide activation is supported

---

## Updating the Plugin

### Automatic Updates

WordPress will notify you of updates in the dashboard:
1. Go to **Dashboard > Updates**
2. Check the box next to "Easy XML Sitemap"
3. Click **"Update Plugins"**

### Manual Update

1. **Deactivate** (but don't delete) the current version
2. **Delete** the plugin files via FTP or dashboard
3. **Install** the new version using any installation method
4. **Activate** the plugin
5. Your settings are preserved

### After Updating

1. Go to **Settings > Permalinks**
2. Click **"Save Changes"** to refresh rewrite rules
3. Go to **Settings > Easy Sitemap**
4. Click **"Regenerate All Sitemaps"**
5. Verify your sitemap URLs still work

---

## Uninstalling

### Complete Removal

**Option 1: Via Dashboard**
1. **Deactivate** the plugin
2. Click **"Delete"**
3. Confirm deletion

**Option 2: Via WP-CLI**
```bash
wp plugin deactivate easy-xml-sitemap
wp plugin uninstall easy-xml-sitemap
```

### What Gets Removed

The uninstall script removes:
- All plugin settings from options table
- All cached sitemap transients
- Post meta exclusion flags
- Custom rewrite rules

### What Stays

- Your WordPress content (posts, pages, etc.) remains untouched
- Server log files (if any)

---

## Getting Help

### Support Channels

1. **WordPress.org Forums**: [Plugin Support Forum](https://wordpress.org/support/plugin/easy-xml-sitemap/)
2. **Plugin Website**: [https://wordpress.andremoura.com](https://wordpress.andremoura.com)
3. **GitHub Issues**: [Report bugs or request features](https://github.com/andremoura/easy-xml-sitemap/issues)

### Before Asking for Help

Please provide:
- WordPress version
- PHP version
- Plugin version
- Active theme name
- Other active plugins
- Error messages (if any)
- Steps to reproduce the issue
- Screenshot (if visual issue)

### Common Error Messages

| Error | Meaning | Solution |
|-------|---------|----------|
| 404 Not Found | Rewrite rules issue | Flush permalinks |
| 500 Internal Server Error | PHP error | Check error logs |
| Empty sitemap | No content or all excluded | Check content/exclusions |
| Cache not clearing | Hook issue | Manual regeneration |

---

## Best Practices

### SEO Best Practices

1. **Submit sitemap index only** - Don't submit individual sitemaps
2. **Monitor in Search Console** - Check regularly for errors
3. **Keep sitemaps clean** - Exclude draft, private, or low-quality content
4. **Update regularly** - But don't over-optimize; automated updates are fine
5. **Use HTTPS URLs** - If your site has SSL

### Performance Best Practices

1. **Optimize cache duration** - Balance freshness with server load
2. **Disable unused sitemaps** - No need for all types
3. **Use object caching** - Redis, Memcached on large sites
4. **Exclude appropriately** - Don't include duplicate or thin content
5. **Monitor size** - Google recommends max 50,000 URLs per sitemap

### Maintenance Schedule

**Weekly**:
- Check Search Console for errors
- Review new content for exclusion needs

**Monthly**:
- Verify all sitemap URLs load correctly
- Check robots.txt includes sitemap
- Review cache performance

**Quarterly**:
- Update plugin to latest version
- Review and optimize settings
- Audit excluded content

---

## Changelog

### Version 1.1.0 - 2024-12-05

**Added:**
- Sitemap index (`sitemap-index.xml`) functionality
- robots.txt integration option
- Sitemap index appears first in URLs table

**Changed:**
- Sitemap base path: `/easy-xml-sitemap/` → `/easy-sitemap/`
- Settings menu name: "XML Sitemap" → "Easy Sitemap"

**Updated:**
- All rewrite rules for new path
- Documentation for new features

### Version 1.0.0 - 2024-12-05

- Initial release

---

## Credits

**Developer**: André Moura  
**Website**: [https://wordpress.andremoura.com](https://wordpress.andremoura.com)  
**Support**: [plugins@andremoura.com](mailto:plugins@andremoura.com)  
**License**: GPL v2 or later

---

## Additional Resources

- [Sitemaps.org Protocol](https://www.sitemaps.org/protocol.html)
- [Google Sitemap Guidelines](https://developers.google.com/search/docs/advanced/sitemaps/overview)
- [Google News Sitemap Guidelines](https://developers.google.com/search/docs/advanced/sitemaps/news-sitemap)
- [WordPress Rewrite API](https://codex.wordpress.org/Rewrite_API)

---

**Last Updated**: December 5, 2024  
**Document Version**: 1.1.0