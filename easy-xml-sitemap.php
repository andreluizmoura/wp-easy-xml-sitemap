<?php
/**
 * Plugin Name: Easy XML Sitemap
 * Description: Lightweight, modular XML sitemap generator for posts, pages, taxonomies, and Google News.
 * Version: 1.1.0
 * Author: André Moura
 * Author URI: https://www.andremoura.com
 * Plugin URI:  https://wordpress.andremoura.com
 * Text Domain: easy-xml-sitemap
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace EasyXMLSitemap;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class
 */
class Easy_XML_Sitemap {

    /**
     * Single instance of the class
     *
     * @var Easy_XML_Sitemap
     */
    private static $instance = null;

    /**
     * Plugin version
     *
     * @var string
     */
    const VERSION = '1.1.0';

    /**
     * Settings option name
     *
     * @var string
     */
    const OPTION_NAME = 'easy_xml_sitemap_settings';

    /**
     * Plugin file constant
     */
    const PLUGIN_FILE = __FILE__;

    /**
     * Get singleton instance
     *
     * @return Easy_XML_Sitemap
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->define_constants();
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Define plugin constants
     */
    private function define_constants() {
        if ( ! defined( 'EASY_XML_SITEMAP_VERSION' ) ) {
            define( 'EASY_XML_SITEMAP_VERSION', self::VERSION );
        }

        if ( ! defined( 'EASY_XML_SITEMAP_FILE' ) ) {
            define( 'EASY_XML_SITEMAP_FILE', self::PLUGIN_FILE );
        }

        if ( ! defined( 'EASY_XML_SITEMAP_PATH' ) ) {
            define( 'EASY_XML_SITEMAP_PATH', plugin_dir_path( self::PLUGIN_FILE ) );
        }

        if ( ! defined( 'EASY_XML_SITEMAP_URL' ) ) {
            define( 'EASY_XML_SITEMAP_URL', plugin_dir_url( self::PLUGIN_FILE ) );
        }
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once EASY_XML_SITEMAP_PATH . 'inc/class-sitemap-controller.php';
        require_once EASY_XML_SITEMAP_PATH . 'inc/class-xml-renderer.php';
        require_once EASY_XML_SITEMAP_PATH . 'inc/class-post-meta.php';
        require_once EASY_XML_SITEMAP_PATH . 'inc/class-cache.php';
        require_once EASY_XML_SITEMAP_PATH . 'inc/class-admin-settings.php';
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Initialize core components
        add_action( 'init', array( $this, 'init_components' ) );
        
        // Plugin activation and deactivation
        register_activation_hook( EASY_XML_SITEMAP_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( EASY_XML_SITEMAP_FILE, array( $this, 'deactivate' ) );
        
        // Add sitemap to robots.txt if enabled
        add_action( 'do_robots', array( $this, 'add_robots_sitemap' ), 0 );
    }

    /**
     * Initialize plugin components
     */
    public function init_components() {
        Sitemap_Controller::get_instance();
        Post_Meta::get_instance();
        Admin_Settings::get_instance();
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Ensure rewrite rules are registered
        Sitemap_Controller::get_instance()->register_rewrite_rules();
        
        // Flush rewrite rules to add custom endpoints
        flush_rewrite_rules();
        
        // Optionally, initialize default settings
        $defaults = array(
            'enable_posts'      => true,
            'enable_pages'      => true,
            'enable_categories' => true,
            'enable_tags'       => true,
            'enable_news'       => false,
            'add_to_robots'     => true,
            'cache_duration'    => 3600,
        );

        $existing = get_option( self::OPTION_NAME, array() );
        if ( empty( $existing ) ) {
            update_option( self::OPTION_NAME, $defaults );
        }

        // Clear cache
        Cache::clear_all();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules to remove custom endpoints
        flush_rewrite_rules();
        
        // Clear cache
        Cache::clear_all();
    }

    /**
     * Add sitemap index to robots.txt
     */
    public function add_robots_sitemap() {
        $settings = get_option( 'easy_xml_sitemap_settings', array() );
        
        // Check if option is enabled
        if ( empty( $settings['add_to_robots'] ) ) {
            return;
        }
        
        // Get sitemap index URL
        $sitemap_url = Sitemap_Controller::get_sitemap_url( 'sitemap-index' );
        
        echo "Sitemap: " . esc_url( $sitemap_url ) . "\n";
    }
}

// Initialize the plugin
Easy_XML_Sitemap::get_instance();
