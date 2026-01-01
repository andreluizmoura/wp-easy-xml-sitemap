<?php
/**
 * Plugin Name:       Easy XML Sitemap
 * Plugin URI:        https://wordpress.andremoura.com
 * Description:       Lightweight, modular XML sitemap generator with custom post type support, image/video sitemaps, sitemap index, stats, WP-CLI, and robots.txt integration.
 * Version:           2.1.1
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

    /**
     * @var Easy_XML_Sitemap|null
     */
    private static $instance = null;

    const VERSION     = '2.0.0';
    const OPTION_NAME = 'easy_xml_sitemap_settings';
    const STATS_NAME  = 'easy_xml_sitemap_stats';

    const PLUGIN_FILE = __FILE__;

    /**
     * Get singleton
     *
     * @return Easy_XML_Sitemap
     */
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
            define( 'EASY_XML_SITEMAP_FILE', self::PLUGIN_FILE );
        }
        if ( ! defined( 'EASY_XML_SITEMAP_PATH' ) ) {
            define( 'EASY_XML_SITEMAP_PATH', plugin_dir_path( self::PLUGIN_FILE ) );
        }
        if ( ! defined( 'EASY_XML_SITEMAP_URL' ) ) {
            define( 'EASY_XML_SITEMAP_URL', plugin_dir_url( self::PLUGIN_FILE ) );
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
        add_action( 'easy_xml_sitemap_regenerate', array( $this, 'regenerate' ) );
        add_action( 'easy_xml_sitemap_clear_cache', array( $this, 'clear_cache' ) );
    }

    public function init_components() {
        Sitemap_Controller::get_instance();
        Post_Meta::get_instance();
        Admin_Settings::get_instance();
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

    public function activate() {
        Sitemap_Controller::get_instance()->register_rewrite_rules();
        flush_rewrite_rules();

        $defaults = array(
            // Legacy toggles still exist for backward compatibility; v2 uses post_types[] primarily.
            'enable_posts'       => true,
            'posts_organization' => 'single',
            'enable_pages'       => true,
            'enable_categories'  => true,
            'enable_tags'        => true,
            'enable_news'        => false,
            'enable_general'     => true,

            'add_to_robots'      => true,
            'cache_duration'     => 3600,

            // v2 features
            'include_images'     => true,
            'include_videos'     => false,

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
            update_option( self::OPTION_NAME, $defaults );
        } else {
            // Merge new defaults without overwriting existing.
            update_option( self::OPTION_NAME, array_merge( $defaults, $existing ) );
        }

        // Initialize stats storage if missing.
        if ( ! get_option( self::STATS_NAME, false ) ) {
            update_option(
                self::STATS_NAME,
                array(
                    'hits_total'      => 0,
                    'hits_by_type'    => array(),
                    'last_generated'  => '',
                    'last_gen_time'   => 0,
                    'last_total_urls' => 0,
                    'last_ping'       => '',
                    'last_ping_engine'=> '',
                    'last_ping_status'=> '',
                )
            );
        }

        Cache::clear_all();
    }

    public function deactivate() {
        flush_rewrite_rules();
        Cache::clear_all();
    }

    public function add_robots_sitemap( $output, $public ) {
        $settings = get_option( self::OPTION_NAME, array() );

        if ( empty( $settings['add_to_robots'] ) ) {
            return $output;
        }

        $sitemap_url = home_url( '/sitemap.xml' );

        if ( false !== strpos( $output, $sitemap_url ) ) {
            return $output;
        }

        $output .= "\nSitemap: " . esc_url( $sitemap_url ) . "\n";
        return $output;
    }

    /**
     * Debounced ping scheduler on publish/update.
     */
    public function maybe_schedule_ping( $new_status, $old_status, $post ) {
        if ( ! $post || empty( $post->ID ) ) {
            return;
        }

        // Only for public post types.
        $post_type_obj = get_post_type_object( $post->post_type );
        if ( ! $post_type_obj || empty( $post_type_obj->public ) ) {
            return;
        }

        $settings = get_option( self::OPTION_NAME, array() );
        if ( empty( $settings['auto_ping'] ) ) {
            return;
        }

        // Trigger on publish or update of published content.
        $should = false;
        if ( 'publish' === $new_status && 'publish' !== $old_status ) {
            $should = true;
        }
        if ( 'publish' === $new_status && 'publish' === $old_status ) {
            $should = true;
        }
        if ( ! $should ) {
            return;
        }

        $min = isset( $settings['ping_debounce_min'] ) ? absint( $settings['ping_debounce_min'] ) : 5;
        if ( $min < 1 ) {
            $min = 1;
        }

        // One scheduled event at a time.
        if ( ! wp_next_scheduled( 'easy_xml_sitemap_do_ping' ) ) {
            wp_schedule_single_event( time() + ( $min * MINUTE_IN_SECONDS ), 'easy_xml_sitemap_do_ping' );
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
            $targets['Google'] = 'https://www.google.com/ping?sitemap=' . $sitemap_url;
        }
        if ( ! isset( $settings['ping_bing'] ) || $settings['ping_bing'] ) {
            $targets['Bing'] = 'https://www.bing.com/ping?sitemap=' . $sitemap_url;
        }

        foreach ( $targets as $engine => $url ) {
            $res = wp_remote_get(
                $url,
                array(
                    'timeout'   => 5,
                    'sslverify' => true,
                )
            );

            $status = 'error';
            if ( ! is_wp_error( $res ) ) {
                $code = wp_remote_retrieve_response_code( $res );
                $status = ( $code >= 200 && $code < 300 ) ? 'ok' : (string) $code;
            } else {
                $status = $res->get_error_message();
            }

            Sitemap_Controller::record_ping_stat( $engine, $status );
        }
    }

    public function regenerate() {
        Cache::clear_all();
    }

    public function clear_cache() {
        Cache::clear_all();
    }

    private function register_wp_cli() {
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            \WP_CLI::add_command(
                'easy-sitemap',
                function( $args, $assoc_args ) {
                    $sub = isset( $args[0] ) ? $args[0] : 'status';

                    if ( 'status' === $sub ) {
                        $stats = get_option( self::STATS_NAME, array() );
                        \WP_CLI::line( 'Easy XML Sitemap' );
                        \WP_CLI::line( 'Version: ' . self::VERSION );
                        \WP_CLI::line( 'Last generation: ' . ( $stats['last_generated'] ? $stats['last_generated'] : 'N/A' ) );
                        \WP_CLI::line( 'Total URLs (last gen): ' . ( isset( $stats['last_total_urls'] ) ? (int) $stats['last_total_urls'] : 0 ) );
                        \WP_CLI::line( 'Hits total: ' . ( isset( $stats['hits_total'] ) ? (int) $stats['hits_total'] : 0 ) );
                        return;
                    }

                    if ( 'regenerate' === $sub ) {
                        do_action( 'easy_xml_sitemap_regenerate' );
                        \WP_CLI::success( 'Sitemap cache cleared (regeneration will happen on next request).' );
                        return;
                    }

                    if ( 'clear-cache' === $sub ) {
                        do_action( 'easy_xml_sitemap_clear_cache' );
                        \WP_CLI::success( 'Sitemap cache cleared.' );
                        return;
                    }

                    \WP_CLI::error( "Unknown subcommand: {$sub}. Use: status | regenerate | clear-cache" );
                }
            );
        }
    }

    /**
     * Very safe conflict UX: only warn if Yoast/RankMath is active.
     * No disabling, no interception, no redirects.
     */
    public function maybe_show_seo_conflict_notice() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $yoast    = defined( 'WPSEO_VERSION' );
        $rankmath = defined( 'RANK_MATH_VERSION' );

        if ( ! $yoast && ! $rankmath ) {
            return;
        }

        echo '<div class="notice notice-warning"><p>';
        echo '<strong>Easy XML Sitemap:</strong> ';
        echo esc_html__( 'Detectamos outro plugin de SEO ativo (Yoast SEO ou Rank Math). Para evitar confusão nos motores de busca, recomendamos manter apenas um sitemap ativo. Este plugin publica sitemaps em /sitemap.xml e não altera automaticamente as configurações do seu plugin de SEO.', 'easy-xml-sitemap' );
        echo '</p></div>';
    }
}

Easy_XML_Sitemap::get_instance();
