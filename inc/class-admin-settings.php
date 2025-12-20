<?php
/**
 * Admin settings page
 *
 * @package EasyXMLSitemap
 */

namespace EasyXMLSitemap;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Settings {

    private static $instance = null;

    const PAGE_SLUG     = 'easy-xml-sitemap';
    const OPTION_NAME   = 'easy_xml_sitemap_settings';
    const STATS_NAME    = 'easy_xml_sitemap_stats';
    const NONCE_ACTION  = 'easy_xml_sitemap_regenerate';
    const NONCE_FIELD   = 'easy_xml_sitemap_nonce';

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_post_easy_xml_sitemap_regenerate', array( $this, 'handle_regenerate' ) );
        add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );

        // Ensure v2 migration once in admin context.
        add_action( 'admin_init', array( $this, 'maybe_migrate_settings_v2' ), 5 );
    }

    public function add_settings_page() {
        add_options_page(
            __( 'Easy XML Sitemap', 'easy-xml-sitemap' ),
            __( 'Easy XML Sitemap', 'easy-xml-sitemap' ),
            'manage_options',
            self::PAGE_SLUG,
            array( $this, 'render_settings_page' )
        );
    }

    public function register_settings() {
        register_setting(
            'easy_xml_sitemap_settings',
            self::OPTION_NAME,
            array( $this, 'sanitize_settings' )
        );
    }

    /**
     * One-time migration to v2 defaults (non-destructive).
     */
    public function maybe_migrate_settings_v2() {
        $settings = get_option( self::OPTION_NAME, array() );

        // If post_types isn't set, initialize it from legacy post/page toggles.
        if ( ! isset( $settings['post_types'] ) || ! is_array( $settings['post_types'] ) ) {
            $settings['post_types'] = array();

            $pts = get_post_types( array( 'public' => true ), 'objects' );
            foreach ( $pts as $pt => $obj ) {
                if ( 'attachment' === $pt ) {
                    continue;
                }

                if ( 'post' === $pt ) {
                    $settings['post_types'][ $pt ] = isset( $settings['enable_posts'] ) ? (bool) $settings['enable_posts'] : true;
                } elseif ( 'page' === $pt ) {
                    $settings['post_types'][ $pt ] = isset( $settings['enable_pages'] ) ? (bool) $settings['enable_pages'] : true;
                } else {
                    // Default: disabled for other CPTs until user enables.
                    $settings['post_types'][ $pt ] = false;
                }
            }
        }

        // Media toggles defaults.
        if ( ! isset( $settings['include_images'] ) ) {
            $settings['include_images'] = false;
        }
        if ( ! isset( $settings['include_videos'] ) ) {
            $settings['include_videos'] = false;
        }

        // Ping defaults.
        if ( ! isset( $settings['auto_ping'] ) ) {
            $settings['auto_ping'] = false;
        }
        if ( ! isset( $settings['ping_google'] ) ) {
            $settings['ping_google'] = true;
        }
        if ( ! isset( $settings['ping_bing'] ) ) {
            $settings['ping_bing'] = true;
        }
        if ( ! isset( $settings['ping_debounce_min'] ) ) {
            $settings['ping_debounce_min'] = 5;
        }

        update_option( self::OPTION_NAME, $settings );
    }

    public function sanitize_settings( $input ) {
        $old = get_option( self::OPTION_NAME, array() );

        // MERGE: keeps values from other tabs when the current tab doesn't submit them.
        $merged = is_array( $input ) ? array_merge( $old, $input ) : $old;
        $out    = $old;

        // Legacy toggles
        if ( array_key_exists( 'enable_posts', $merged ) ) {
            $out['enable_posts'] = (bool) $merged['enable_posts'];
        }
        if ( array_key_exists( 'enable_pages', $merged ) ) {
            $out['enable_pages'] = (bool) $merged['enable_pages'];
        }
        if ( array_key_exists( 'enable_categories', $merged ) ) {
            $out['enable_categories'] = (bool) $merged['enable_categories'];
        }
        if ( array_key_exists( 'enable_tags', $merged ) ) {
            $out['enable_tags'] = (bool) $merged['enable_tags'];
        }
        if ( array_key_exists( 'enable_general', $merged ) ) {
            $out['enable_general'] = (bool) $merged['enable_general'];
        }
        if ( array_key_exists( 'enable_news', $merged ) ) {
            $out['enable_news'] = (bool) $merged['enable_news'];
        }

        // Posts organization
        if ( array_key_exists( 'posts_organization', $merged ) ) {
            $org = sanitize_key( $merged['posts_organization'] );
            $out['posts_organization'] = in_array( $org, array( 'single', 'date', 'category' ), true ) ? $org : 'single';
        }

        // robots + cache
        if ( array_key_exists( 'add_to_robots', $merged ) ) {
            $out['add_to_robots'] = (bool) $merged['add_to_robots'];
        }
        if ( array_key_exists( 'cache_duration', $merged ) ) {
            $cd = absint( $merged['cache_duration'] );
            $out['cache_duration'] = max( 60, min( 604800, $cd ) );
        }

        // v2 media
        if ( array_key_exists( 'include_images', $merged ) ) {
            $out['include_images'] = (bool) $merged['include_images'];
        }
        if ( array_key_exists( 'include_videos', $merged ) ) {
            $out['include_videos'] = (bool) $merged['include_videos'];
        }

        // ping
        foreach ( array( 'auto_ping', 'ping_google', 'ping_bing' ) as $k ) {
            if ( array_key_exists( $k, $merged ) ) {
                $out[ $k ] = (bool) $merged[ $k ];
            }
        }
        if ( array_key_exists( 'ping_debounce_min', $merged ) ) {
            $d = absint( $merged['ping_debounce_min'] );
            $out['ping_debounce_min'] = max( 1, min( 60, $d ) );
        }

        // post_types map (only update if it came in POST)
        if ( array_key_exists( 'post_types', $merged ) && is_array( $merged['post_types'] ) ) {
            $out['post_types'] = array();
            $pts = get_post_types( array( 'public' => true ), 'objects' );
            foreach ( $pts as $pt => $obj ) {
                if ( 'attachment' === $pt ) {
                    continue;
                }
                $out['post_types'][ $pt ] = ! empty( $merged['post_types'][ $pt ] );
            }
            // Keep legacy coherent
            $out['enable_posts'] = ! empty( $out['post_types']['post'] );
            $out['enable_pages'] = ! empty( $out['post_types']['page'] );
        }

        // Clear cache & flush rewrites on change
        if ( $old !== $out ) {
            if ( class_exists( '\\EasyXMLSitemap\\Cache' ) ) {
                Cache::clear_all();
            }
            flush_rewrite_rules();
        }

        return $out;
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'sitemaps'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( empty( $tab ) ) {
            $tab = 'sitemaps';
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Easy XML Sitemap', 'easy-xml-sitemap' ) . '</h1>';

        $this->render_tabs( $tab );

        echo '<form method="post" action="options.php">';
        settings_fields( 'easy_xml_sitemap_settings' );

        $settings = get_option( self::OPTION_NAME, array() );

        if ( 'sitemaps' === $tab ) {
            $this->render_tab_sitemaps( $settings );
        } elseif ( 'posttypes' === $tab ) {
            $this->render_tab_post_types( $settings );
        } elseif ( 'media' === $tab ) {
            $this->render_tab_media( $settings );
        } elseif ( 'quicklinks' === $tab ) {
            $this->render_tab_quicklinks( $settings );
        } elseif ( 'status' === $tab ) {
            $this->render_tab_status();
        } elseif ( 'advanced' === $tab ) {
            $this->render_tab_advanced( $settings );
        }

        if ( ! in_array( $tab, array( 'status', 'quicklinks' ), true ) ) {
            submit_button();
        }

        echo '</form>';

        // Regenerate button (separate form)
        echo '<hr />';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="easy_xml_sitemap_regenerate" />';
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
        submit_button( __( 'Regenerate All Sitemaps', 'easy-xml-sitemap' ), 'secondary' );
        echo '</form>';

        echo '</div>';
    }

    private function render_tabs( $active ) {
        $tabs = array(
            'sitemaps'    => __( 'Sitemaps', 'easy-xml-sitemap' ),
            'posttypes'   => __( 'Post Types', 'easy-xml-sitemap' ),
            'media'       => __( 'Media', 'easy-xml-sitemap' ),
            'quicklinks'  => __( 'Quick Links', 'easy-xml-sitemap' ),
            'status'      => __( 'Status', 'easy-xml-sitemap' ),
            'advanced'    => __( 'Advanced', 'easy-xml-sitemap' ),
        );

        echo '<h2 class="nav-tab-wrapper">';
        foreach ( $tabs as $key => $label ) {
            $url = add_query_arg(
                array(
                    'page' => self::PAGE_SLUG,
                    'tab'  => $key,
                ),
                admin_url( 'options-general.php' )
            );

            $class = ( $active === $key ) ? 'nav-tab nav-tab-active' : 'nav-tab';
            echo '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</a>';
        }
        echo '</h2>';
    }

    private function render_tab_sitemaps( $settings ) {
        echo '<h2>' . esc_html__( 'Sitemap Types', 'easy-xml-sitemap' ) . '</h2>';

        echo '<p class="description">' . esc_html__(
            'Select which sitemap types should be available. The sitemap index will list the enabled ones.',
            'easy-xml-sitemap'
        ) . '</p>';

        echo '<div style="margin-top:12px;">';
        $this->checkbox_row(
            'enable_general',
            __( 'General', 'easy-xml-sitemap' ),
            __( 'Basic sitemap with homepage and core URLs.', 'easy-xml-sitemap' ),
            $settings
        );
        $this->checkbox_row(
            'enable_categories',
            __( 'Categories', 'easy-xml-sitemap' ),
            __( 'Include categories sitemap.', 'easy-xml-sitemap' ),
            $settings
        );
        $this->checkbox_row(
            'enable_tags',
            __( 'Tags', 'easy-xml-sitemap' ),
            __( 'Include tags sitemap.', 'easy-xml-sitemap' ),
            $settings
        );
        $this->checkbox_row(
            'enable_news',
            __( 'News', 'easy-xml-sitemap' ),
            __( 'Include Google News sitemap (if you publish news-style content).', 'easy-xml-sitemap' ),
            $settings
        );
        echo '</div>';

        echo '<hr />';

        echo '<h3>' . esc_html__( 'Posts organization', 'easy-xml-sitemap' ) . '</h3>';

        $current = isset( $settings['posts_organization'] ) ? $settings['posts_organization'] : 'single';

        $this->radio_row( 'posts_organization', 'single', __( 'Single sitemap for all posts', 'easy-xml-sitemap' ), $current );
        $this->radio_row( 'posts_organization', 'date', __( 'Split by month (YYYY-MM)', 'easy-xml-sitemap' ), $current );
        $this->radio_row( 'posts_organization', 'category', __( 'Split by category', 'easy-xml-sitemap' ), $current );

        echo '<p class="description">' . esc_html__(
            'This affects how post sitemaps are organized under the posts index.',
            'easy-xml-sitemap'
        ) . '</p>';
    }

    private function render_tab_post_types( $settings ) {
        echo '<h2>' . esc_html__( 'Post Types', 'easy-xml-sitemap' ) . '</h2>';

        echo '<p class="description">' . esc_html__(
            'Choose which public post types should have a sitemap endpoint.',
            'easy-xml-sitemap'
        ) . '</p>';

        $pts = get_post_types( array( 'public' => true ), 'objects' );

        echo '<table class="widefat striped" style="max-width:900px;margin-top:12px;">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Enable', 'easy-xml-sitemap' ) . '</th>';
        echo '<th>' . esc_html__( 'Post Type', 'easy-xml-sitemap' ) . '</th>';
        echo '<th>' . esc_html__( 'Slug', 'easy-xml-sitemap' ) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ( $pts as $pt => $obj ) {
            if ( 'attachment' === $pt ) {
                continue;
            }

            $enabled = false;
            if ( isset( $settings['post_types'] ) && is_array( $settings['post_types'] ) ) {
                $enabled = ! empty( $settings['post_types'][ $pt ] );
            } else {
                // fallback for old installs
                if ( 'post' === $pt ) {
                    $enabled = isset( $settings['enable_posts'] ) ? (bool) $settings['enable_posts'] : true;
                } elseif ( 'page' === $pt ) {
                    $enabled = isset( $settings['enable_pages'] ) ? (bool) $settings['enable_pages'] : true;
                }
            }

            echo '<tr>';
            echo '<td><label><input type="checkbox" name="' . esc_attr( self::OPTION_NAME ) . '[post_types][' . esc_attr( $pt ) . ']" value="1" ' . checked( $enabled, true, false ) . ' /></label></td>';
            echo '<td><strong>' . esc_html( $obj->labels->name ) . '</strong></td>';
            echo '<td><code>' . esc_html( $pt ) . '</code></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        echo '<p class="description" style="max-width:900px;margin-top:10px;">' . esc_html__(
            'Each enabled post type is available at /easy-sitemap/{posttype}.xml',
            'easy-xml-sitemap'
        ) . '</p>';
    }

    private function render_tab_media( $settings ) {
        echo '<h2>' . esc_html__( 'Media', 'easy-xml-sitemap' ) . '</h2>';

        echo '<p class="description">' . esc_html__(
            'Control whether images and videos should be included as extensions in sitemap entries.',
            'easy-xml-sitemap'
        ) . '</p>';

        $this->checkbox_row(
            'include_images',
            __( 'Include Images', 'easy-xml-sitemap' ),
            __( 'Add image sitemap tags for featured images and images inside post content.', 'easy-xml-sitemap' ),
            $settings
        );

        $this->checkbox_row(
            'include_videos',
            __( 'Include Videos', 'easy-xml-sitemap' ),
            __( 'Add video sitemap tags when a reliable video and thumbnail can be detected.', 'easy-xml-sitemap' ),
            $settings
        );
    }

    private function render_tab_quicklinks( $settings ) {
        echo '<h2>' . esc_html__( 'Quick Links', 'easy-xml-sitemap' ) . '</h2>';

        $sitemap_index_url = home_url( '/easy-sitemap/sitemap.xml' );
        $robots_url        = home_url( '/robots.txt' );

        echo '<div style="max-width:1000px;margin:12px 0;padding:12px;border:1px solid #ccd0d4;background:#fff;border-radius:6px;">';
        echo '<p style="margin:6px 0;">';
        echo '<strong>' . esc_html__( 'Sitemap index:', 'easy-xml-sitemap' ) . '</strong> ';
        echo '<a href="' . esc_url( $sitemap_index_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $sitemap_index_url ) . '</a>';
        echo '</p>';

        echo '<p style="margin:6px 0;">';
        echo '<strong>' . esc_html__( 'robots.txt:', 'easy-xml-sitemap' ) . '</strong> ';
        echo '<a href="' . esc_url( $robots_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $robots_url ) . '</a>';
        echo '</p>';

        echo '<p class="description" style="margin:10px 0 0 0;">' . esc_html__(
            'Use these links to confirm the sitemap is accessible and the correct "Sitemap:" line is present in robots.txt.',
            'easy-xml-sitemap'
        ) . '</p>';
        echo '</div>';

        // Build table rows.
        $rows = array();

        // Sitemap Index (always).
        $rows[] = array(
            'label'       => __( 'Sitemap Index', 'easy-xml-sitemap' ),
            'url'         => $sitemap_index_url,
            'enabled'     => true,
            'description' => __( 'Main index that lists all sitemap endpoints.', 'easy-xml-sitemap' ),
        );

        // Legacy / built-in sitemaps.
        $legacy = array(
            'general'    => array( __( 'General Sitemap', 'easy-xml-sitemap' ), __( 'Homepage and base site URLs.', 'easy-xml-sitemap' ) ),
            'categories' => array( __( 'Categories Sitemap', 'easy-xml-sitemap' ), __( 'Sitemap for post categories.', 'easy-xml-sitemap' ) ),
            'tags'       => array( __( 'Tags Sitemap', 'easy-xml-sitemap' ), __( 'Sitemap for post tags.', 'easy-xml-sitemap' ) ),
            'news'       => array( __( 'Google News Sitemap', 'easy-xml-sitemap' ), __( 'News sitemap for recent posts.', 'easy-xml-sitemap' ) ),
        );

        foreach ( $legacy as $slug => $meta ) {
            $key     = 'enable_' . $slug;
            $enabled = isset( $settings[ $key ] ) ? (bool) $settings[ $key ] : true;

            $rows[] = array(
                'label'       => $meta[0],
                'url'         => Sitemap_Controller::get_sitemap_url( $slug ),
                'enabled'     => $enabled,
                'description' => $meta[1],
            );
        }

        // Posts Index (entry point).
        $org = isset( $settings['posts_organization'] ) ? (string) $settings['posts_organization'] : 'single';
        $posts_index_desc = ( 'single' === $org )
            ? __( 'Single sitemap containing all posts (when posts organization is set to single).', 'easy-xml-sitemap' )
            : __( 'Sitemap index that lists the post sitemaps generated by your organization setting (by date or by category).', 'easy-xml-sitemap' );

        $rows[] = array(
            'label'       => __( 'Posts Index', 'easy-xml-sitemap' ),
            'url'         => Sitemap_Controller::get_sitemap_url( 'posts-index' ),
            'enabled'     => true,
            'description' => $posts_index_desc,
        );

        // CPT endpoints (enabled only).
        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        foreach ( $post_types as $pt => $obj ) {
            if ( 'attachment' === $pt ) {
                continue;
            }

            // Keep the Quick Links list focused: "Posts" should be accessed via the Posts Index.
            if ( 'post' === $pt ) {
                continue;
            }

            $enabled = false;
            if ( isset( $settings['post_types'] ) && is_array( $settings['post_types'] ) ) {
                $enabled = ! empty( $settings['post_types'][ $pt ] );
            } else {
                // Legacy fallback for post/page only.
                if ( 'post' === $pt ) {
                    $enabled = isset( $settings['enable_posts'] ) ? (bool) $settings['enable_posts'] : true;
                } elseif ( 'page' === $pt ) {
                    $enabled = isset( $settings['enable_pages'] ) ? (bool) $settings['enable_pages'] : true;
                }
            }

            if ( ! $enabled ) {
                continue;
            }

            $rows[] = array(
                'label'       => sprintf( __( 'Post Type: %s', 'easy-xml-sitemap' ), $obj->labels->name ),
                'url'         => Sitemap_Controller::get_sitemap_url( $pt ),
                'enabled'     => true,
                'description' => sprintf( __( 'Sitemap for "%s".', 'easy-xml-sitemap' ), $pt ),
            );
        }

        echo '<h3 style="margin-top:18px;">' . esc_html__( 'Sitemap Endpoints', 'easy-xml-sitemap' ) . '</h3>';
        echo '<table class="widefat striped" style="max-width:1000px;"><thead><tr>';
        echo '<th>' . esc_html__( 'Sitemap', 'easy-xml-sitemap' ) . '</th>';
        echo '<th>' . esc_html__( 'URL', 'easy-xml-sitemap' ) . '</th>';
        echo '<th>' . esc_html__( 'Enabled', 'easy-xml-sitemap' ) . '</th>';
        echo '<th>' . esc_html__( 'Notes', 'easy-xml-sitemap' ) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ( $rows as $r ) {
            $enabled_txt = ! empty( $r['enabled'] ) ? __( 'Yes', 'easy-xml-sitemap' ) : __( 'No', 'easy-xml-sitemap' );

            echo '<tr>';
            echo '<td><strong>' . esc_html( $r['label'] ) . '</strong></td>';
            echo '<td><a href="' . esc_url( $r['url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $r['url'] ) . '</a></td>';
            echo '<td>' . esc_html( $enabled_txt ) . '</td>';
            echo '<td>' . esc_html( $r['description'] ) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private function render_tab_status() {
        echo '<h2>' . esc_html__( 'Sitemap Status', 'easy-xml-sitemap' ) . '</h2>';

        $stats = get_option( self::STATS_NAME, array() );

        $last_generated = isset( $stats['last_generated'] ) ? $stats['last_generated'] : '';
        $last_total     = isset( $stats['last_total_urls'] ) ? (int) $stats['last_total_urls'] : 0;
        $last_time      = isset( $stats['last_gen_time'] ) ? (int) $stats['last_gen_time'] : 0;

        $hits_total     = isset( $stats['hits_total'] ) ? (int) $stats['hits_total'] : 0;

        $last_ping      = isset( $stats['last_ping'] ) ? $stats['last_ping'] : '';
        $last_engine    = isset( $stats['last_ping_engine'] ) ? $stats['last_ping_engine'] : '';
        $last_ping_stat = isset( $stats['last_ping_status'] ) ? $stats['last_ping_status'] : '';

        echo '<table class="widefat striped" style="max-width:900px;"><tbody>';
        echo '<tr><th>' . esc_html__( 'Last generation', 'easy-xml-sitemap' ) . '</th><td>' . esc_html( $last_generated ? $last_generated : '—' ) . '</td></tr>';
        echo '<tr><th>' . esc_html__( 'URLs in last generation', 'easy-xml-sitemap' ) . '</th><td>' . esc_html( (string) $last_total ) . '</td></tr>';
        echo '<tr><th>' . esc_html__( 'Generation time (ms)', 'easy-xml-sitemap' ) . '</th><td>' . esc_html( (string) $last_time ) . '</td></tr>';
        echo '<tr><th>' . esc_html__( 'Total hits (all sitemap endpoints)', 'easy-xml-sitemap' ) . '</th><td>' . esc_html( (string) $hits_total ) . '</td></tr>';
        echo '<tr><th>' . esc_html__( 'Last ping', 'easy-xml-sitemap' ) . '</th><td>' . esc_html( $last_ping ? $last_ping : '—' ) . '</td></tr>';
        echo '<tr><th>' . esc_html__( 'Last ping engine', 'easy-xml-sitemap' ) . '</th><td>' . esc_html( $last_engine ? $last_engine : '—' ) . '</td></tr>';
        echo '<tr><th>' . esc_html__( 'Last ping status', 'easy-xml-sitemap' ) . '</th><td>' . esc_html( $last_ping_stat ? $last_ping_stat : '—' ) . '</td></tr>';
        echo '</tbody></table>';

        if ( isset( $stats['hits_by_type'] ) && is_array( $stats['hits_by_type'] ) && ! empty( $stats['hits_by_type'] ) ) {
            echo '<h3 style="margin-top:18px;">' . esc_html__( 'Hits by endpoint', 'easy-xml-sitemap' ) . '</h3>';
            echo '<table class="widefat striped" style="max-width:900px;"><thead><tr><th>' . esc_html__( 'Endpoint type', 'easy-xml-sitemap' ) . '</th><th>' . esc_html__( 'Hits', 'easy-xml-sitemap' ) . '</th></tr></thead><tbody>';
            foreach ( $stats['hits_by_type'] as $type => $hits ) {
                echo '<tr><td><code>' . esc_html( $type ) . '</code></td><td>' . esc_html( (string) (int) $hits ) . '</td></tr>';
            }
            echo '</tbody></table>';
        }
    }

    private function render_tab_advanced( $settings ) {
        echo '<h2>' . esc_html__( 'Advanced', 'easy-xml-sitemap' ) . '</h2>';

        $this->checkbox_row(
            'add_to_robots',
            __( 'Add sitemap to robots.txt', 'easy-xml-sitemap' ),
            __( 'Adds the sitemap URL to the virtual robots.txt output.', 'easy-xml-sitemap' ),
            $settings
        );

        echo '<p><strong>' . esc_html__( 'Cache duration (seconds)', 'easy-xml-sitemap' ) . '</strong><br />';
        $cache = isset( $settings['cache_duration'] ) ? absint( $settings['cache_duration'] ) : 3600;
        echo '<input type="number" min="60" max="604800" name="' . esc_attr( self::OPTION_NAME ) . '[cache_duration]" value="' . esc_attr( $cache ) . '" />';
        echo '<br /><span class="description">' . esc_html__( 'Recommended: 3600 (1 hour).', 'easy-xml-sitemap' ) . '</span></p>';

        echo '<hr />';
        echo '<h3>' . esc_html__( 'Automatic Ping', 'easy-xml-sitemap' ) . '</h3>';

        $this->checkbox_row(
            'auto_ping',
            __( 'Enable automatic ping on updates', 'easy-xml-sitemap' ),
            __( 'Pings search engines after content updates (debounced).', 'easy-xml-sitemap' ),
            $settings
        );

        $this->checkbox_row(
            'ping_google',
            __( 'Ping Google', 'easy-xml-sitemap' ),
            __( 'Ping Google on sitemap updates.', 'easy-xml-sitemap' ),
            $settings
        );

        $this->checkbox_row(
            'ping_bing',
            __( 'Ping Bing', 'easy-xml-sitemap' ),
            __( 'Ping Bing on sitemap updates.', 'easy-xml-sitemap' ),
            $settings
        );

        $debounce = isset( $settings['ping_debounce_min'] ) ? absint( $settings['ping_debounce_min'] ) : 5;
        echo '<p><strong>' . esc_html__( 'Debounce window (minutes)', 'easy-xml-sitemap' ) . '</strong><br />';
        echo '<input type="number" min="1" max="60" name="' . esc_attr( self::OPTION_NAME ) . '[ping_debounce_min]" value="' . esc_attr( $debounce ) . '" />';
        echo '<br /><span class="description">' . esc_html__( 'Prevents too many pings during frequent updates. Recommended: 5.', 'easy-xml-sitemap' ) . '</span></p>';
    }

    public function handle_regenerate() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'easy-xml-sitemap' ) );
        }

        check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD );

        if ( class_exists( '\\EasyXMLSitemap\\Cache' ) ) {
            Cache::clear_all();
        }

        flush_rewrite_rules();

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page'    => self::PAGE_SLUG,
                    'tab'     => 'status',
                    'ezs_msg' => 'regenerated',
                ),
                admin_url( 'options-general.php' )
            )
        );
        exit;
    }

    public function show_admin_notices() {
        if ( ! isset( $_GET['page'] ) || self::PAGE_SLUG !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        if ( isset( $_GET['ezs_msg'] ) && 'regenerated' === $_GET['ezs_msg'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo esc_html__( 'Sitemaps cache cleared. Sitemaps will be regenerated on next request.', 'easy-xml-sitemap' );
            echo '</p></div>';
        }
    }

    private function checkbox_row( $key, $title, $desc, $settings ) {
        $checked = ! empty( $settings[ $key ] );
        echo '<p><label><input type="checkbox" name="' . esc_attr( self::OPTION_NAME ) . '[' . esc_attr( $key ) . ']" value="1" ' . checked( $checked, true, false ) . ' /> ';
        echo '<strong>' . esc_html( $title ) . '</strong></label><br />';
        echo '<span class="description">' . esc_html( $desc ) . '</span></p>';
    }

    private function radio_row( $name, $value, $label, $current ) {
        echo '<label style="display:block;margin:6px 0;">';
        echo '<input type="radio" name="' . esc_attr( self::OPTION_NAME ) . '[' . esc_attr( $name ) . ']" value="' . esc_attr( $value ) . '" ' . checked( $current, $value, false ) . ' /> ';
        echo esc_html( $label );
        echo '</label>';
    }
}
