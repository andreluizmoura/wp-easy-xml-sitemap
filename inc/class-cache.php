<?php
/**
 * Cache management for XML sitemaps
 *
 * @package EasyXMLSitemap
 */

namespace EasyXMLSitemap;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cache class - handles storing and retrieving generated sitemap XML
 */
class Cache {

    /**
     * Cache key prefix to avoid conflicts
     */
    const CACHE_PREFIX = 'easy_xml_sitemap_';

    /**
     * Get cached sitemap XML
     *
     * @param string $sitemap_type Type of sitemap (posts, pages, tags, categories, general, news, sitemap-index)
     * @return string|false Returns cached XML string or false if not found/expired
     */
    public static function get( $sitemap_type ) {
        $cache_key = self::get_cache_key( $sitemap_type );
        return get_transient( $cache_key );
    }

    /**
     * Store sitemap XML in cache
     *
     * @param string $sitemap_type Type of sitemap
     * @param string $xml_content  The XML content to cache
     * @return bool True on success, false on failure
     */
    public static function set( $sitemap_type, $xml_content ) {
        $cache_key = self::get_cache_key( $sitemap_type );
        $duration  = self::get_cache_duration();
        
        return set_transient( $cache_key, $xml_content, $duration );
    }

    /**
     * Clear cache for a specific sitemap type
     *
     * @param string $sitemap_type Type of sitemap
     * @return bool True on success
     */
    public static function clear( $sitemap_type ) {
        $cache_key = self::get_cache_key( $sitemap_type );
        return delete_transient( $cache_key );
    }

    /**
     * Clear all sitemap caches
     *
     * @return void
     */
    public static function clear_all() {
        $sitemap_types = array( 
            'posts', 
            'posts-index',
            'pages', 
            'tags', 
            'categories', 
            'general', 
            'news', 
            'sitemap-index' 
        );
        
        foreach ( $sitemap_types as $type ) {
            self::clear( $type );
        }
        
        // Clear dynamic caches (posts-date-*, posts-category-*)
        global $wpdb;
        
        $pattern_date = $wpdb->esc_like( '_transient_' . self::CACHE_PREFIX . 'posts-date-' ) . '%';
        $pattern_cat = $wpdb->esc_like( '_transient_' . self::CACHE_PREFIX . 'posts-category-' ) . '%';
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                OR option_name LIKE %s",
                $pattern_date,
                $pattern_cat
            )
        );
        
        // Clear timeout transients too
        $pattern_date_timeout = $wpdb->esc_like( '_transient_timeout_' . self::CACHE_PREFIX . 'posts-date-' ) . '%';
        $pattern_cat_timeout = $wpdb->esc_like( '_transient_timeout_' . self::CACHE_PREFIX . 'posts-category-' ) . '%';
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                OR option_name LIKE %s",
                $pattern_date_timeout,
                $pattern_cat_timeout
            )
        );
    }

    /**
     * Get cache duration from settings
     *
     * @return int Cache duration in seconds
     */
    private static function get_cache_duration() {
        $settings = get_option( 'easy_xml_sitemap_settings', array() );
        $duration = isset( $settings['cache_duration'] ) ? absint( $settings['cache_duration'] ) : 3600;
        
        // Ensure minimum 60 seconds, maximum 1 week
        $duration = max( 60, min( $duration, 604800 ) );
        
        /**
         * Filter cache duration
         *
         * @param int $duration Cache duration in seconds
         */
        return apply_filters( 'easy_xml_sitemap_cache_duration', $duration );
    }

    /**
     * Generate cache key for a sitemap type
     *
     * @param string $sitemap_type Type of sitemap
     * @return string Cache key
     */
    private static function get_cache_key( $sitemap_type ) {
        return self::CACHE_PREFIX . sanitize_key( $sitemap_type );
    }

    /**
     * Initialize cache invalidation hooks
     * Called automatically when the plugin loads
     */
    public static function init_invalidation_hooks() {
        // Invalidate on post/page save or delete
        add_action( 'save_post', array( __CLASS__, 'invalidate_on_post_change' ), 10, 1 );
        add_action( 'delete_post', array( __CLASS__, 'invalidate_on_post_change' ), 10, 1 );
        add_action( 'wp_trash_post', array( __CLASS__, 'invalidate_on_post_change' ), 10, 1 );
        add_action( 'untrash_post', array( __CLASS__, 'invalidate_on_post_change' ), 10, 1 );
        
        // Invalidate on term (category/tag) changes
        add_action( 'created_term', array( __CLASS__, 'invalidate_on_term_change' ), 10, 3 );
        add_action( 'edited_term', array( __CLASS__, 'invalidate_on_term_change' ), 10, 3 );
        add_action( 'delete_term', array( __CLASS__, 'invalidate_on_term_change' ), 10, 3 );
        
        // Invalidate when post meta changes (for index control)
        add_action( 'updated_post_meta', array( __CLASS__, 'invalidate_on_meta_change' ), 10, 4 );
        add_action( 'deleted_post_meta', array( __CLASS__, 'invalidate_on_meta_change' ), 10, 4 );
    }

    /**
     * Invalidate cache when a post changes
     *
     * @param int $post_id Post ID
     */
    public static function invalidate_on_post_change( $post_id ) {
        // Skip autosaves and revisions
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
            return;
        }

        $post_type = get_post_type( $post_id );
        
        // Clear relevant caches based on post type
        if ( 'post' === $post_type ) {
            self::clear( 'posts' );
            self::clear( 'posts-index' );
            self::clear( 'tags' );
            self::clear( 'categories' );
            self::clear( 'news' );
            
            // Clear date-specific cache
            $post = get_post( $post_id );
            if ( $post ) {
                $year = gmdate( 'Y', strtotime( $post->post_date ) );
                $month = gmdate( 'm', strtotime( $post->post_date ) );
                self::clear( 'posts-date-' . $year . '-' . $month );
            }
            
            // Clear category-specific caches
            $categories = get_the_category( $post_id );
            if ( ! empty( $categories ) ) {
                foreach ( $categories as $category ) {
                    self::clear( 'posts-category-' . $category->slug );
                }
            }
            
        } elseif ( 'page' === $post_type ) {
            self::clear( 'pages' );
        }
        
        // Always clear general sitemap and sitemap index
        self::clear( 'general' );
        self::clear( 'sitemap-index' );
    }

    /**
     * Invalidate cache when a term (category/tag) changes
     *
     * @param int    $term_id  Term ID
     * @param int    $tt_id    Term taxonomy ID
     * @param string $taxonomy Taxonomy slug
     */
    public static function invalidate_on_term_change( $term_id, $tt_id, $taxonomy ) {
        if ( 'category' === $taxonomy ) {
            self::clear( 'categories' );
            self::clear( 'posts-index' );
            
            // Clear category-specific posts cache
            $term = get_term( $term_id, $taxonomy );
            if ( $term && ! is_wp_error( $term ) ) {
                self::clear( 'posts-category-' . $term->slug );
            }
            
        } elseif ( 'post_tag' === $taxonomy ) {
            self::clear( 'tags' );
        }
        
        // Clear general sitemap and sitemap index
        self::clear( 'general' );
        self::clear( 'sitemap-index' );
    }

    /**
     * Invalidate cache when post meta changes (especially indexing control)
     *
     * @param int    $meta_id    Meta ID
     * @param int    $post_id    Post ID
     * @param string $meta_key   Meta key
     * @param mixed  $meta_value Meta value
     */
    public static function invalidate_on_meta_change( $meta_id, $post_id, $meta_key, $meta_value ) {
        // Only invalidate if the index control meta key changed
        if ( '_easy_xml_sitemap_exclude' === $meta_key ) {
            self::invalidate_on_post_change( $post_id );
        }
    }
}

// Initialize invalidation hooks
Cache::init_invalidation_hooks();
