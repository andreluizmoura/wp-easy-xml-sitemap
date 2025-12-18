<?php
/**
 * Sitemap controller - handles requests and routing
 *
 * @package EasyXMLSitemap
 */

namespace EasyXMLSitemap;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sitemap_Controller class - manages sitemap endpoints and output
 */
class Sitemap_Controller {

    /**
     * Single instance of the controller
     *
     * @var Sitemap_Controller
     */
    private static $instance = null;

    /**
     * Sitemap slug/prefix for URLs
     */
    const SITEMAP_SLUG = 'easy-sitemap';

    /**
     * Valid sitemap types
     *
     * @var array
     */
    private $valid_types = array( 
        'posts',
        'posts-index',
        'posts-date',
        'posts-category',
        'pages', 
        'tags', 
        'categories', 
        'general', 
        'news', 
        'sitemap-index' 
    );

    /**
     * Get singleton instance
     *
     * @return Sitemap_Controller
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - initialize hooks
     */
    private function __construct() {
        add_action( 'init', array( $this, 'register_rewrite_rules' ) );
        add_action( 'template_redirect', array( $this, 'handle_sitemap_request' ), 1 );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
    }

    /**
     * Register rewrite rules for sitemap URLs
     */
    public function register_rewrite_rules() {
        $slug = self::SITEMAP_SLUG;
        
        // Main sitemap: /easy-sitemap/sitemap.xml
        add_rewrite_rule(
            '^' . $slug . '/sitemap\.xml$',
            'index.php?easy_sitemap_type=sitemap-index',
            'top'
        );
        
        // Posts index: /easy-sitemap/posts-index.xml
        add_rewrite_rule(
            '^' . $slug . '/posts-index\.xml$',
            'index.php?easy_sitemap_type=posts-index',
            'top'
        );
        
        // Posts by date: /easy-sitemap/posts-2024-12.xml
        add_rewrite_rule(
            '^' . $slug . '/posts-([0-9]{4})-([0-9]{2})\.xml$',
            'index.php?easy_sitemap_type=posts-date&easy_sitemap_year=$matches[1]&easy_sitemap_month=$matches[2]',
            'top'
        );
        
        // Posts by category: /easy-sitemap/posts-category-slug.xml
        add_rewrite_rule(
            '^' . $slug . '/posts-category-([a-z0-9-]+)\.xml$',
            'index.php?easy_sitemap_type=posts-category&easy_sitemap_cat=$matches[1]',
            'top'
        );
        
        // Other sitemaps: /easy-sitemap/{type}.xml
        add_rewrite_rule(
            '^' . $slug . '/([a-z-]+)\.xml$',
            'index.php?easy_sitemap_type=$matches[1]',
            'top'
        );
    }

    /**
     * Add custom query vars
     *
     * @param array $vars Existing query vars
     * @return array Modified query vars
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'easy_sitemap_type';
        $vars[] = 'easy_sitemap_year';
        $vars[] = 'easy_sitemap_month';
        $vars[] = 'easy_sitemap_cat';
        return $vars;
    }

    /**
     * Handle sitemap requests
     */
    public function handle_sitemap_request() {
        $sitemap_type = get_query_var( 'easy_sitemap_type', false );
        
        // Not a sitemap request
        if ( false === $sitemap_type ) {
            return;
        }
        
        // Validate sitemap type
        if ( ! in_array( $sitemap_type, $this->valid_types, true ) ) {
            $this->send_404();
            return;
        }
        
        // Special handling for posts-date
        if ( 'posts-date' === $sitemap_type ) {
            $year = get_query_var( 'easy_sitemap_year', false );
            $month = get_query_var( 'easy_sitemap_month', false );
            
            if ( ! $year || ! $month ) {
                $this->send_404();
                return;
            }
        }
        
        // Special handling for posts-category
        if ( 'posts-category' === $sitemap_type ) {
            $cat_slug = get_query_var( 'easy_sitemap_cat', false );
            
            if ( ! $cat_slug ) {
                $this->send_404();
                return;
            }
        }
        
        // Check if sitemap is enabled (except for index and dynamic types)
        $always_available = array( 'sitemap-index', 'posts-index', 'posts-date', 'posts-category' );
        if ( ! in_array( $sitemap_type, $always_available, true ) ) {
            if ( ! $this->is_sitemap_enabled( $sitemap_type ) ) {
                $this->send_404();
                return;
            }
        }
        
        // Serve the sitemap
        $this->serve_sitemap( $sitemap_type );
    }

    /**
     * Serve a sitemap (from cache or generate fresh)
     *
     * @param string $sitemap_type Type of sitemap to serve
     */
    private function serve_sitemap( $sitemap_type ) {
        // Build cache key
        $cache_key = $sitemap_type;
        
        // Add dynamic parameters to cache key
        if ( 'posts-date' === $sitemap_type ) {
            $year = get_query_var( 'easy_sitemap_year' );
            $month = get_query_var( 'easy_sitemap_month' );
            $cache_key .= '-' . $year . '-' . $month;
        } elseif ( 'posts-category' === $sitemap_type ) {
            $cat_slug = get_query_var( 'easy_sitemap_cat' );
            $cache_key .= '-' . $cat_slug;
        }
        
        // Try to get from cache
        $xml = Cache::get( $cache_key );
        
        // If not cached, generate fresh
        if ( false === $xml ) {
            $xml = $this->generate_sitemap( $sitemap_type );
            
            // Store in cache
            if ( ! empty( $xml ) ) {
                Cache::set( $cache_key, $xml );
            }
        }
        
        // Send headers and output
        $this->send_xml_headers();
        echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    /**
     * Generate a sitemap based on type
     *
     * @param string $sitemap_type Type of sitemap
     * @return string XML content
     */
    private function generate_sitemap( $sitemap_type ) {
        switch ( $sitemap_type ) {
            case 'sitemap-index':
                return XML_Renderer::generate_sitemap_index();
                
            case 'posts':
                // Legacy: redirect to posts-index logic
                return XML_Renderer::generate_posts_index();
                
            case 'posts-index':
                return XML_Renderer::generate_posts_index();
                
            case 'posts-date':
                $year = get_query_var( 'easy_sitemap_year' );
                $month = get_query_var( 'easy_sitemap_month' );
                return XML_Renderer::generate_posts_by_date( $year, $month );
                
            case 'posts-category':
                $cat_slug = get_query_var( 'easy_sitemap_cat' );
                return XML_Renderer::generate_posts_by_category( $cat_slug );
                
            case 'pages':
                return XML_Renderer::generate_pages_sitemap();
                
            case 'tags':
                return XML_Renderer::generate_tags_sitemap();
                
            case 'categories':
                return XML_Renderer::generate_categories_sitemap();
                
            case 'general':
                return XML_Renderer::generate_general_sitemap();
                
            case 'news':
                return XML_Renderer::generate_news_sitemap();
                
            default:
                return '';
        }
    }

    /**
     * Check if a sitemap type is enabled in settings
     *
     * @param string $sitemap_type Type of sitemap
     * @return bool True if enabled
     */
    private function is_sitemap_enabled( $sitemap_type ) {
        $settings = get_option( 'easy_xml_sitemap_settings', array() );
        $key      = 'enable_' . $sitemap_type;
        
        // Default to true if not set (backwards compatibility)
        return isset( $settings[ $key ] ) ? (bool) $settings[ $key ] : true;
    }

    /**
     * Send XML headers
     */
    private function send_xml_headers() {
        if ( ! headers_sent() ) {
            status_header( 200 );
            header( 'Content-Type: application/xml; charset=UTF-8' );
            header( 'X-Robots-Tag: noindex, follow', true );
            
            // Cache control headers
            $cache_duration = $this->get_cache_duration();
            header( 'Cache-Control: max-age=' . $cache_duration );
            header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $cache_duration ) . ' GMT' );
        }
    }

    /**
     * Send 404 response
     */
    private function send_404() {
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        nocache_headers();
        include( get_query_template( '404' ) );
        exit;
    }

    /**
     * Get cache duration from settings
     *
     * @return int Cache duration in seconds
     */
    private function get_cache_duration() {
        $settings = get_option( 'easy_xml_sitemap_settings', array() );
        return isset( $settings['cache_duration'] ) ? absint( $settings['cache_duration'] ) : 3600;
    }

    /**
     * Get sitemap URL for a specific type
     *
     * @param string $type Sitemap type
     * @return string Full URL to the sitemap
     */
    public static function get_sitemap_url( $type ) {
        return home_url( '/' . self::SITEMAP_SLUG . '/' . $type . '.xml' );
    }

    /**
     * Get all enabled sitemap URLs
     *
     * @return array Array of sitemap URLs with type as key
     */
    public static function get_all_sitemap_urls() {
        $controller = self::get_instance();
        $settings   = get_option( 'easy_xml_sitemap_settings', array() );
        $urls       = array();
        
        // Add sitemap index first
        $urls['sitemap-index'] = self::get_sitemap_url( 'sitemap-index' );
        
        // Add other sitemaps
        $sitemap_map = array(
            'posts-index'  => 'enable_posts',
            'pages'        => 'enable_pages',
            'tags'         => 'enable_tags',
            'categories'   => 'enable_categories',
            'general'      => 'enable_general',
            'news'         => 'enable_news',
        );
        
        foreach ( $sitemap_map as $type => $setting_key ) {
            if ( isset( $settings[ $setting_key ] ) && $settings[ $setting_key ] ) {
                $urls[ $type ] = self::get_sitemap_url( $type );
            }
        }
        
        return $urls;
    }

    /**
     * Regenerate all sitemaps (clear cache)
     *
     * @return bool True on success
     */
    public static function regenerate_all_sitemaps() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }
        
        Cache::clear_all();
        
        return true;
    }
}
