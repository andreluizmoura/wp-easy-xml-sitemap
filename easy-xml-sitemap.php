<?php
/**
 * Plugin Name:       Easy XML Sitemap
 * Plugin URI:        https://wordpress.org/plugins/easy-xml-sitemap/
 * Description:       Fast XML sitemap with caching and multiple sitemap types.
 * Version:           2.0.1
 * Author:            André Moura
 * Author URI:        https://www.andremoura.com
 * Text Domain:       easy-xml-sitemap
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Tested up to:      6.9
 * Requires PHP:      7.2
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace EasyXMLSitemap;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Easy_XML_Sitemap {

    const VERSION = '2.0.0';

    const OPTION_NAME = 'easy_xml_sitemap_settings';

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->define_constants();
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function define_constants() {
        if ( ! defined( 'EASY_XML_SITEMAP_VERSION' ) ) {
            define( 'EASY_XML_SITEMAP_VERSION', self::VERSION );
        }
        if ( ! defined( 'EASY_XML_SITEMAP_FILE' ) ) {
            define( 'EASY_XML_SITEMAP_FILE', __FILE__ );
        }
        if ( ! defined( 'EASY_XML_SITEMAP_PATH' ) ) {
            define( 'EASY_XML_SITEMAP_PATH', plugin_dir_path( __FILE__ ) );
        }
        if ( ! defined( 'EASY_XML_SITEMAP_URL' ) ) {
            define( 'EASY_XML_SITEMAP_URL', plugin_dir_url( __FILE__ ) );
        }
    }

    private function load_dependencies() {
        require_once EASY_XML_SITEMAP_PATH . 'inc/class-cache.php';
        require_once EASY_XML_SITEMAP_PATH . 'inc/class-post-meta.php';
        require_once EASY_XML_SITEMAP_PATH . 'inc/class-sitemap-controller.php';
        require_once EASY_XML_SITEMAP_PATH . 'inc/class-xml-renderer.php';
        require_once EASY_XML_SITEMAP_PATH . 'inc/class-admin-settings.php';
    }

    private function init_hooks() {
        // Disable WP native sitemap (kept as legacy behavior).
        add_filter( 'wp_sitemaps_enabled', '__return_false', 9999 );

        // Redirect /wp-sitemap.xml to our sitemap (safe, since our URL is distinct).
        add_action( 'template_redirect', array( $this, 'redirect_native_sitemap' ), 0 );

        add_action( 'init', array( $this, 'init_components' ) );

        register_activation_hook( EASY_XML_SITEMAP_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( EASY_XML_SITEMAP_FILE, array( $this, 'deactivate' ) );

        add_filter( 'robots_txt', array( $this, 'add_robots_sitemap' ), 10, 2 );

        // Ping search engines (debounced).
        add_action( 'transition_post_status', array( $this, 'maybe_schedule_ping' ), 10, 3 );
        add_action( 'easy_xml_sitemap_do_ping', array( $this, 'do_ping' ) );

        // WP-CLI (minimal).
        $this->register_wp_cli();

        // Admin notice for SEO plugin conflicts (UX-only; no overrides).
        add_action( 'admin_notices', array( $this, 'maybe_show_seo_conflict_notice' ) );

        // Internal actions for CLI / admin triggers.
        add_action( 'easy_xml_sitemap_regenerate', array( $this, 'regenerate_all_cache' ) );
    }

    public function init_components() {
        Sitemap_Controller::get_instance();
        Admin_Settings::get_instance();
        Cache::get_instance();
        Post_Meta::get_instance();
    }

    public function activate() {
        Sitemap_Controller::get_instance()->register_rewrite_rules();
        flush_rewrite_rules();

        $defaults = array(
            'enabled'            => true,
            'add_to_robots'      => true,
            'disable_native'     => true,
            'cache_ttl_minutes'  => 60,

            // media
            'include_images'     => true,
            'include_videos'     => false,

            // post meta keys (autodetected)
            'lastmod_key'        => '',
            'changefreq_key'     => '',
            'priority_key'       => '',

            // post types enabled map (created/migrated in Admin_Settings too)
            'post_types'         => array(
                'post' => true,
                'page' => true,
            ),

            // Ping settings
            'auto_ping'          => true,
            'ping_google'        => true,
            'ping_bing'          => true,
            'ping_debounce_min'  => 5,
        );

        $existing = get_option( self::OPTION_NAME, array() );
        if ( empty( $existing ) ) {
            add_option( self::OPTION_NAME, $defaults );
        } else {
            update_option( self::OPTION_NAME, wp_parse_args( $existing, $defaults ) );
        }
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function redirect_native_sitemap() {
        if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
            return;
        }
        $request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

        if ( false !== strpos( $request_uri, 'wp-sitemap.xml' ) ) {
            wp_redirect( home_url( '/sitemap.xml' ), 301 );
            exit;
        }
    }

    function add_robots_sitemap( $output, $public ) {
        $settings = get_option( self::OPTION_NAME, array() );

        if ( empty( $settings['add_to_robots'] ) ) {
            return $output;
        }

        $sitemap_url = home_url( '/sitemap.xml' );

        if ( false !== strpos( $output, $sitemap_url ) ) {
            return $output;
        }

        $output .= "\nSitemap: " . esc_url_raw( $sitemap_url ) . "\n";
        return $output;
    }

    public function maybe_schedule_ping( $new_status, $old_status, $post ) {
        if ( 'publish' !== $new_status ) {
            return;
        }

        $settings = get_option( self::OPTION_NAME, array() );
        if ( empty( $settings['auto_ping'] ) ) {
            return;
        }

        $debounce_min = isset( $settings['ping_debounce_min'] ) ? absint( $settings['ping_debounce_min'] ) : 5;
        if ( $debounce_min < 1 ) {
            $debounce_min = 1;
        }

        if ( ! wp_next_scheduled( 'easy_xml_sitemap_do_ping' ) ) {
            wp_schedule_single_event( time() + ( $debounce_min * 60 ), 'easy_xml_sitemap_do_ping' );
        }
    }

    public function do_ping() {
        $settings = get_option( self::OPTION_NAME, array() );
        if ( empty( $settings['auto_ping'] ) ) {
            return;
        }

        $sitemap_url = rawurlencode( home_url( '/sitemap.xml' ) );

        $targets = array();

        if ( ! isset( $settings['ping_google'] ) || $settings['ping_google'] ) {
            $targets[] = 'https://www.google.com/ping?sitemap=' . $sitemap_url;
        }
        if ( ! isset( $settings['ping_bing'] ) || $settings['ping_bing'] ) {
            $targets[] = 'https://www.bing.com/ping?sitemap=' . $sitemap_url;
        }

        foreach ( $targets as $target ) {
            wp_remote_get(
                esc_url_raw( $target ),
                array(
                    'timeout'     => 5,
                    'redirection' => 2,
                    'blocking'    => false,
                )
            );
        }
    }

    private function register_wp_cli() {
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            \WP_CLI::add_command(
                'easy-xml-sitemap regenerate',
                function () {
                    do_action( 'easy_xml_sitemap_regenerate' );
                    \WP_CLI::success( 'Easy XML Sitemap cache regenerated.' );
                }
            );
        }
    }

    public function regenerate_all_cache() {
        // placeholder for future - currently controller/cache handles by request
        Cache::get_instance()->purge_all();
    }

    public function maybe_show_seo_conflict_notice() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Check typical SEO plugins
        $has_yoast = defined( 'WPSEO_VERSION' );
        $has_rank  = defined( 'RANK_MATH_VERSION' );

        if ( ! $has_yoast && ! $has_rank ) {
            return;
        }

        echo '<div class="notice notice-warning"><p>';
        echo esc_html__( 'Detectamos outro plugin de SEO ativo (Yoast SEO ou Rank Math). Para evitar confusão nos motores de busca, recomendamos manter apenas um sitemap ativo. Este plugin publica o sitemap index em /sitemap.xml e não altera automaticamente as configurações do seu plugin de SEO.', 'easy-xml-sitemap' );
        echo '</p></div>';
    }
}

Easy_XML_Sitemap::get_instance();