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
    private $valid_types = array( 'posts', 'pages', 'tags', 'categories', 'general', 'news', 'sitemap-index' );

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
    public static function register_rewrite_rules() {
        $slug = self::SITEMAP_SLUG;
        
        // Pattern: /easy-sitemap/posts.xml or /easy-sitemap/sitemap-index.xml
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
        
        // Check if this sitemap is enabled (except for sitemap-index which is always available if enabled)
        if ( 'sitemap-index' !== $sitemap_type && ! $this->is_sitemap_enabled( $sitemap_type ) ) {
            $this->send_404();
            return;
        }
        
        // Check if sitemap index is enabled
        if ( 'sitemap-index' === $sitemap_type ) {
            $settings = get_option( 'easy_xml_sitemap_settings', array() );
            $index_enabled = isset( $settings['enable_index'] ) ? $settings['enable_index'] : true;
            
            if ( ! $index_enabled ) {
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
        // Try to get from cache
        $xml = Cache::get( $sitemap_type );
        
        // If not cached, generate fresh
        if ( false === $xml ) {
            $xml = $this->generate_sitemap( $sitemap_type );
            
            // Store in cache
            if ( ! empty( $xml ) ) {
                Cache::set( $sitemap_type, $xml );
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
                return XML_Renderer::generate_posts_sitemap();
                
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
        // Prevent caching by some plugins/servers
        if ( ! headers_sent() ) {
            status_header( 200 );
            header( 'Content-Type: application/xml; charset=UTF-8' );
            header( 'X-Robots-Tag: noindex, follow', true );
            
            // Optional: Add cache control headers
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
     * @param string $type Sitemap type (posts, pages, sitemap-index, etc.)
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
        
        // Add sitemap index first if enabled
        $index_enabled = isset( $settings['enable_index'] ) ? $settings['enable_index'] : true;
        if ( $index_enabled ) {
            $urls['sitemap-index'] = self::get_sitemap_url( 'sitemap-index' );
        }
        
        // Add other sitemaps
        foreach ( $controller->valid_types as $type ) {
            // Skip sitemap-index as we already added it
            if ( 'sitemap-index' === $type ) {
                continue;
            }
            
            $key = 'enable_' . $type;
            if ( isset( $settings[ $key ] ) && $settings[ $key ] ) {
                $urls[ $type ] = self::get_sitemap_url( $type );
            }
        }
        
        return $urls;
    }

    /**
     * Regenerate all sitemaps (clear cache)
     * Used by admin settings page
     *
     * @return bool True on success
     */
    public static function regenerate_all_sitemaps() {
        // Check capability
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }
        
        Cache::clear_all();
        
        return true;
    }

    /**
     * Regenerate a specific sitemap (clear its cache)
     *
     * @param string $sitemap_type Type of sitemap
     * @return bool True on success
     */
    public static function regenerate_sitemap( $sitemap_type ) {
        // Check capability
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }
        
        $controller = self::get_instance();
        
        if ( ! in_array( $sitemap_type, $controller->valid_types, true ) ) {
            return false;
        }
        
        Cache::clear( $sitemap_type );
        
        return true;
    }
}