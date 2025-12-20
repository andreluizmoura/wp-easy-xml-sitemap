<?php

class Easy_XML_Sitemap_Stats {

    public static function record_generation( $total_urls, $duration ) {
        update_option( 'easy_xml_sitemap_stats', [
            'last_generated' => current_time( 'mysql' ),
            'total_urls'     => $total_urls,
            'generation_time'=> $duration,
        ] );
    }

    public static function record_hit() {
        $stats = get_option( 'easy_xml_sitemap_stats', [] );
        $stats['hits'] = ( $stats['hits'] ?? 0 ) + 1;
        update_option( 'easy_xml_sitemap_stats', $stats );
    }

    public static function record_ping( $engine, $status ) {
        $stats = get_option( 'easy_xml_sitemap_stats', [] );
        $stats['last_ping'] = current_time( 'mysql' );
        $stats['ping_engine'] = $engine;
        $stats['ping_status'] = $status;
        update_option( 'easy_xml_sitemap_stats', $stats );
    }
}
