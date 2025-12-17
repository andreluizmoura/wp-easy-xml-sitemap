<?php
/**
 * Uninstall script for Easy XML Sitemap
 *
 * This file is executed when the plugin is deleted via WordPress admin.
 * It removes all plugin data from the database.
 *
 * @package EasyXMLSitemap
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Run uninstall for a single site.
 */
function easy_xml_sitemap_uninstall_single_site() {
    // Remove plugin settings option.
    delete_option( 'easy_xml_sitemap_settings' );

    // Remove post meta (exclusion flags) from all posts and pages.
    if ( function_exists( 'delete_post_meta_by_key' ) ) {
        delete_post_meta_by_key( '_easy_xml_sitemap_exclude' );
    }

    // Best effort: remove any scheduled events related to the plugin.
    wp_clear_scheduled_hook( 'easy_xml_sitemap_cleanup' );
}

// Multisite handling.
if ( is_multisite() ) {
    $easy_xml_sitemap_sites = get_sites(
        array(
            'fields' => 'ids',
        )
    );

    foreach ( $easy_xml_sitemap_sites as $easy_xml_sitemap_site_id ) {
        switch_to_blog( $easy_xml_sitemap_site_id );
        easy_xml_sitemap_uninstall_single_site();
        restore_current_blog();
    }
} else {
    easy_xml_sitemap_uninstall_single_site();
}

// Flush rewrite rules to clean up endpoints.
flush_rewrite_rules();
