<?php
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

class Easy_XML_Sitemap_CLI {

    public function status() {
        $stats = get_option( 'easy_xml_sitemap_stats', [] );

        WP_CLI::line( 'Easy XML Sitemap Status' );
        WP_CLI::line( '----------------------' );
        WP_CLI::line( 'Last generation: ' . ( $stats['last_generated'] ?? 'N/A' ) );
        WP_CLI::line( 'Total URLs: ' . ( $stats['total_urls'] ?? 'N/A' ) );
        WP_CLI::line( 'Last ping: ' . ( $stats['last_ping'] ?? 'N/A' ) );
    }

    public function regenerate() {
        do_action( 'easy_xml_sitemap_regenerate' );
        WP_CLI::success( 'Sitemap regenerated.' );
    }

    public function clear_cache() {
        do_action( 'easy_xml_sitemap_clear_cache' );
        WP_CLI::success( 'Sitemap cache cleared.' );
    }
}

WP_CLI::add_command( 'easy-sitemap', 'Easy_XML_Sitemap_CLI' );
