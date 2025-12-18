<?php
/**
 * Admin settings page
 *
 * @package EasyXMLSitemap
 */

namespace EasyXMLSitemap;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin_Settings class - manages plugin settings interface.
 */
class Admin_Settings {

    /**
     * Single instance of the class.
     *
     * @var Admin_Settings
     */
    private static $instance = null;

    /**
     * Settings page slug.
     */
    const PAGE_SLUG = 'easy-xml-sitemap';

    /**
     * Settings option name.
     */
    const OPTION_NAME = 'easy_xml_sitemap_settings';

    /**
     * Nonce action for regeneration.
     */
    const NONCE_ACTION = 'easy_xml_sitemap_regenerate';

    /**
     * Nonce field name.
     */
    const NONCE_FIELD = 'easy_xml_sitemap_regenerate_nonce';

    /**
     * Get singleton instance.
     *
     * @return Admin_Settings
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - initialize hooks.
     */
    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_post_easy_xml_sitemap_regenerate', array( $this, 'handle_regenerate' ) );
        add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
    }

    /**
     * Add settings page under Settings menu.
     */
    public function add_settings_page() {
        add_options_page(
            __( 'Easy Sitemap', 'easy-xml-sitemap' ),
            __( 'Easy Sitemap', 'easy-xml-sitemap' ),
            'manage_options',
            self::PAGE_SLUG,
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings and fields.
     */
    public function register_settings() {
        register_setting(
            'easy_xml_sitemap_settings',
            self::OPTION_NAME,
            array( $this, 'sanitize_settings' )
        );

        add_settings_section(
            'easy_xml_sitemap_general_section',
            __( 'Sitemap Configuration', 'easy-xml-sitemap' ),
            array( $this, 'render_general_section' ),
            self::PAGE_SLUG
        );

        add_settings_field(
            'enable_posts',
            __( 'Posts Sitemap', 'easy-xml-sitemap' ),
            array( $this, 'render_checkbox_field' ),
            self::PAGE_SLUG,
            'easy_xml_sitemap_general_section',
            array(
                'label_for'   => 'enable_posts',
                'description' => __( 'Generate sitemap for all published posts', 'easy-xml-sitemap' ),
            )
        );

        add_settings_field(
            'posts_organization',
            __( 'Posts Organization', 'easy-xml-sitemap' ),
            array( $this, 'render_radio_field' ),
            self::PAGE_SLUG,
            'easy_xml_sitemap_general_section',
            array(
                'label_for'   => 'posts_organization',
                'options'     => array(
                    'single'   => __( 'Single sitemap (all posts in one file)', 'easy-xml-sitemap' ),
                    'date'     => __( 'Organize by date (one sitemap per month/year)', 'easy-xml-sitemap' ),
                    'category' => __( 'Organize by category (one sitemap per category)', 'easy-xml-sitemap' ),
                ),
                'default'     => 'single',
                'description' => __( 'How to organize posts in the sitemap. For large sites, organizing by date or category improves performance.', 'easy-xml-sitemap' ),
            )
        );

        add_settings_field(
            'enable_pages',
            __( 'Pages Sitemap', 'easy-xml-sitemap' ),
            array( $this, 'render_checkbox_field' ),
            self::PAGE_SLUG,
            'easy_xml_sitemap_general_section',
            array(
                'label_for'   => 'enable_pages',
                'description' => __( 'Generate sitemap for all published pages', 'easy-xml-sitemap' ),
            )
        );

        add_settings_field(
            'enable_tags',
            __( 'Tags Sitemap', 'easy-xml-sitemap' ),
            array( $this, 'render_checkbox_field' ),
            self::PAGE_SLUG,
            'easy_xml_sitemap_general_section',
            array(
                'label_for'   => 'enable_tags',
                'description' => __( 'Generate sitemap for post tags', 'easy-xml-sitemap' ),
            )
        );

        add_settings_field(
            'enable_categories',
            __( 'Categories Sitemap', 'easy-xml-sitemap' ),
            array( $this, 'render_checkbox_field' ),
            self::PAGE_SLUG,
            'easy_xml_sitemap_general_section',
            array(
                'label_for'   => 'enable_categories',
                'description' => __( 'Generate sitemap for post categories', 'easy-xml-sitemap' ),
            )
        );

        add_settings_field(
            'enable_general',
            __( 'General Sitemap', 'easy-xml-sitemap' ),
            array( $this, 'render_checkbox_field' ),
            self::PAGE_SLUG,
            'easy_xml_sitemap_general_section',
            array(
                'label_for'   => 'enable_general',
                'description' => __( 'Generate a comprehensive sitemap with all URLs (homepage, posts, pages, categories, tags)', 'easy-xml-sitemap' ),
            )
        );

        add_settings_field(
            'enable_news',
            __( 'Google News Sitemap', 'easy-xml-sitemap' ),
            array( $this, 'render_checkbox_field' ),
            self::PAGE_SLUG,
            'easy_xml_sitemap_general_section',
            array(
                'label_for'   => 'enable_news',
                'description' => __( 'Enable Google News compatible sitemap for recent posts (last 2 days)', 'easy-xml-sitemap' ),
            )
        );

        add_settings_field(
            'add_to_robots',
            __( 'Add to robots.txt', 'easy-xml-sitemap' ),
            array( $this, 'render_checkbox_field' ),
            self::PAGE_SLUG,
            'easy_xml_sitemap_general_section',
            array(
                'label_for'   => 'add_to_robots',
                'description' => __( 'Automatically add sitemap URL to virtual robots.txt', 'easy-xml-sitemap' ),
            )
        );

        add_settings_field(
            'cache_duration',
            __( 'Cache Duration (seconds)', 'easy-xml-sitemap' ),
            array( $this, 'render_number_field' ),
            self::PAGE_SLUG,
            'easy_xml_sitemap_general_section',
            array(
                'label_for'   => 'cache_duration',
                'description' => __( 'How long to cache sitemap output (60 seconds to 1 week, default: 3600)', 'easy-xml-sitemap' ),
                'min'         => 60,
                'max'         => 604800,
            )
        );
    }

    /**
     * Sanitize settings input.
     *
     * @param array $input Raw input.
     * @return array Sanitized input.
     */
    public function sanitize_settings( $input ) {
        $output = array();

        $output['enable_posts']        = isset( $input['enable_posts'] ) ? (bool) $input['enable_posts'] : false;
        $output['posts_organization']  = isset( $input['posts_organization'] ) ? sanitize_key( $input['posts_organization'] ) : 'single';
        $output['enable_pages']        = isset( $input['enable_pages'] ) ? (bool) $input['enable_pages'] : false;
        $output['enable_categories']   = isset( $input['enable_categories'] ) ? (bool) $input['enable_categories'] : false;
        $output['enable_tags']         = isset( $input['enable_tags'] ) ? (bool) $input['enable_tags'] : false;
        $output['enable_general']      = isset( $input['enable_general'] ) ? (bool) $input['enable_general'] : false;
        $output['enable_news']         = isset( $input['enable_news'] ) ? (bool) $input['enable_news'] : false;
        $output['add_to_robots']       = isset( $input['add_to_robots'] ) ? (bool) $input['add_to_robots'] : false;

        $output['cache_duration'] = isset( $input['cache_duration'] ) ? absint( $input['cache_duration'] ) : 3600;

        if ( $output['cache_duration'] < 60 ) {
            $output['cache_duration'] = 60;
        }

        if ( $output['cache_duration'] > 604800 ) {
            $output['cache_duration'] = 604800;
        }
        
        // Validate posts_organization
        if ( ! in_array( $output['posts_organization'], array( 'single', 'date', 'category' ), true ) ) {
            $output['posts_organization'] = 'single';
        }

        return $output;
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $options = get_option( self::OPTION_NAME, array() );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Easy XML Sitemap Settings', 'easy-xml-sitemap' ); ?></h1>
            <p><?php esc_html_e( 'Configure XML sitemap generation for your WordPress site.', 'easy-xml-sitemap' ); ?></p>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'easy_xml_sitemap_settings' );
                do_settings_sections( self::PAGE_SLUG );
                submit_button();
                ?>
            </form>

            <hr />

            <h2><?php esc_html_e( 'robots.txt Configuration', 'easy-xml-sitemap' ); ?></h2>
            
            <?php
            // Check if physical robots.txt exists
            $robots_file = ABSPATH . 'robots.txt';
            if ( file_exists( $robots_file ) ) :
            ?>
                <div class="notice notice-warning inline">
                    <p>
                        <strong><?php esc_html_e( '⚠️ Physical robots.txt detected', 'easy-xml-sitemap' ); ?></strong><br />
                        <?php esc_html_e( 'A physical robots.txt file exists in your site root. The "Add to robots.txt" option will not work automatically.', 'easy-xml-sitemap' ); ?>
                    </p>
                    <p>
                        <?php esc_html_e( 'To use automatic robots.txt integration, please delete or rename the physical robots.txt file.', 'easy-xml-sitemap' ); ?><br />
                        <?php esc_html_e( 'Alternatively, manually add this line to your robots.txt:', 'easy-xml-sitemap' ); ?>
                    </p>
                    <p>
                        <code>Sitemap: <?php echo esc_html( home_url( '/easy-sitemap/sitemap.xml' ) ); ?></code>
                    </p>
                </div>
            <?php else : ?>
                <div class="notice notice-success inline">
                    <p>
                        <strong><?php esc_html_e( '✓ No physical robots.txt found', 'easy-xml-sitemap' ); ?></strong><br />
                        <?php esc_html_e( 'Virtual robots.txt integration will work correctly if enabled above.', 'easy-xml-sitemap' ); ?>
                    </p>
                    <p>
                        <a href="<?php echo esc_url( home_url( '/robots.txt' ) ); ?>" target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e( 'View your robots.txt', 'easy-xml-sitemap' ); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <hr />

            <h2><?php esc_html_e( 'Cache Management', 'easy-xml-sitemap' ); ?></h2>
            <p><?php esc_html_e( 'Manually regenerate all sitemap files to clear the cache and rebuild from current content.', 'easy-xml-sitemap' ); ?></p>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>
                <input type="hidden" name="action" value="easy_xml_sitemap_regenerate" />
                <?php submit_button( __( 'Regenerate All Sitemaps', 'easy-xml-sitemap' ), 'secondary' ); ?>
            </form>

            <hr />

            <h2><?php esc_html_e( 'Sitemap URLs', 'easy-xml-sitemap' ); ?></h2>
            <p><?php esc_html_e( 'These are the sitemap URLs generated by this plugin. Submit the main sitemap to search engines.', 'easy-xml-sitemap' ); ?></p>

            <?php
            $sitemap_types = array(
                'sitemap-index' => array(
                    'label'   => __( 'Main Sitemap (submit this to search engines)', 'easy-xml-sitemap' ),
                    'enabled' => true,
                ),
                'posts-index'   => array(
                    'label'   => __( 'Posts Sitemap', 'easy-xml-sitemap' ),
                    'enabled' => ! empty( $options['enable_posts'] ),
                ),
                'pages'         => array(
                    'label'   => __( 'Pages Sitemap', 'easy-xml-sitemap' ),
                    'enabled' => ! empty( $options['enable_pages'] ),
                ),
                'categories'    => array(
                    'label'   => __( 'Categories Sitemap', 'easy-xml-sitemap' ),
                    'enabled' => ! empty( $options['enable_categories'] ),
                ),
                'tags'          => array(
                    'label'   => __( 'Tags Sitemap', 'easy-xml-sitemap' ),
                    'enabled' => ! empty( $options['enable_tags'] ),
                ),
                'general'       => array(
                    'label'   => __( 'General Sitemap', 'easy-xml-sitemap' ),
                    'enabled' => ! empty( $options['enable_general'] ),
                ),
                'news'          => array(
                    'label'   => __( 'Google News Sitemap', 'easy-xml-sitemap' ),
                    'enabled' => ! empty( $options['enable_news'] ),
                ),
            );
            ?>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th style="width: 40%;"><?php esc_html_e( 'Sitemap Type', 'easy-xml-sitemap' ); ?></th>
                        <th><?php esc_html_e( 'URL', 'easy-xml-sitemap' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $sitemap_types as $type => $data ) : ?>
                        <?php
                        $url = Sitemap_Controller::get_sitemap_url( $type );
                        $highlight_style = ( 'sitemap-index' === $type ) ? 'background-color: #f0f6fc; font-weight: 600;' : '';
                        ?>
                        <tr<?php if ( $highlight_style ) : ?> style="<?php echo esc_attr( $highlight_style ); ?>"<?php endif; ?>>
                            <td>
                                <?php echo esc_html( $data['label'] ); ?>
                                <?php if ( 'sitemap-index' === $type ) : ?>
                                    <br /><small style="color: #2271b1;"><?php esc_html_e( '← Submit this URL to Google Search Console and Bing Webmaster Tools', 'easy-xml-sitemap' ); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ( $data['enabled'] ) : ?>
                                    <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php echo esc_html( $url ); ?>
                                    </a>
                                <?php else : ?>
                                    <span style="color: #999;">
                                        <?php esc_html_e( 'Disabled', 'easy-xml-sitemap' ); ?>
                                        <small>(<?php echo esc_html( $url ); ?>)</small>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <hr />

            <h2><?php esc_html_e( 'Search Engine Submission', 'easy-xml-sitemap' ); ?></h2>
            <p><?php esc_html_e( 'Submit your main sitemap to search engines for better crawling and indexing:', 'easy-xml-sitemap' ); ?></p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li>
                    <strong>Google Search Console:</strong> 
                    <a href="https://search.google.com/search-console" target="_blank" rel="noopener noreferrer">
                        <?php esc_html_e( 'Submit Sitemap', 'easy-xml-sitemap' ); ?>
                    </a>
                </li>
                <li>
                    <strong>Bing Webmaster Tools:</strong> 
                    <a href="https://www.bing.com/webmasters" target="_blank" rel="noopener noreferrer">
                        <?php esc_html_e( 'Submit Sitemap', 'easy-xml-sitemap' ); ?>
                    </a>
                </li>
            </ul>
        </div>
        <?php
    }

    /**
     * Render general section description.
     */
    public function render_general_section() {
        ?>
        <p><?php esc_html_e( 'Enable or disable different sitemap types and configure caching behavior.', 'easy-xml-sitemap' ); ?></p>
        <?php
    }

    /**
     * Render checkbox field.
     *
     * @param array $args Field arguments.
     */
    public function render_checkbox_field( $args ) {
        $options = get_option( self::OPTION_NAME, array() );
        $id      = isset( $args['label_for'] ) ? $args['label_for'] : '';
        $checked = ! empty( $options[ $id ] );
        ?>
        <label for="<?php echo esc_attr( $id ); ?>">
            <input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( self::OPTION_NAME . '[' . $id . ']' ); ?>" value="1" <?php checked( $checked ); ?> />
            <?php if ( ! empty( $args['description'] ) ) : ?>
                <?php echo esc_html( $args['description'] ); ?>
            <?php endif; ?>
        </label>
        <?php
    }

    /**
     * Render radio field.
     *
     * @param array $args Field arguments.
     */
    public function render_radio_field( $args ) {
        $options = get_option( self::OPTION_NAME, array() );
        $id      = isset( $args['label_for'] ) ? $args['label_for'] : '';
        $choices = isset( $args['options'] ) ? $args['options'] : array();
        $default = isset( $args['default'] ) ? $args['default'] : '';
        $value   = isset( $options[ $id ] ) ? $options[ $id ] : $default;
        
        foreach ( $choices as $choice_value => $choice_label ) {
            ?>
            <label style="display: block; margin-bottom: 8px;">
                <input 
                    type="radio" 
                    name="<?php echo esc_attr( self::OPTION_NAME . '[' . $id . ']' ); ?>" 
                    value="<?php echo esc_attr( $choice_value ); ?>" 
                    <?php checked( $value, $choice_value ); ?> 
                />
                <?php echo esc_html( $choice_label ); ?>
            </label>
            <?php
        }
        
        if ( ! empty( $args['description'] ) ) {
            ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
            <?php
        }
    }

    /**
     * Render number field.
     *
     * @param array $args Field arguments.
     */
    public function render_number_field( $args ) {
        $options   = get_option( self::OPTION_NAME, array() );
        $id        = isset( $args['label_for'] ) ? $args['label_for'] : '';
        $value     = isset( $options[ $id ] ) ? absint( $options[ $id ] ) : 3600;
        $min       = isset( $args['min'] ) ? absint( $args['min'] ) : 60;
        $max       = isset( $args['max'] ) ? absint( $args['max'] ) : 604800;
        ?>
        <input type="number"
            id="<?php echo esc_attr( $id ); ?>"
            name="<?php echo esc_attr( self::OPTION_NAME . '[' . $id . ']' ); ?>"
            value="<?php echo esc_attr( $value ); ?>"
            min="<?php echo esc_attr( $min ); ?>"
            max="<?php echo esc_attr( $max ); ?>"
            style="width: 150px;"
        />
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif; ?>
        <?php
    }

    /**
     * Handle sitemap regeneration request.
     */
    public function handle_regenerate() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to perform this action.', 'easy-xml-sitemap' ) );
        }

        if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
            wp_die( esc_html__( 'Security check failed. Please try again.', 'easy-xml-sitemap' ) );
        }

        // Clear all sitemap caches.
        Cache::clear_all();

        // Redirect back with success message.
        wp_safe_redirect(
            add_query_arg(
                array(
                    'page'        => self::PAGE_SLUG,
                    'regenerated' => '1',
                ),
                admin_url( 'options-general.php' )
            )
        );
        exit;
    }

    /**
     * Show admin notices.
     */
    public function show_admin_notices() {
        if ( ! isset( $_GET['page'] ) || self::PAGE_SLUG !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        if ( isset( $_GET['regenerated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['regenerated'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( '✓ All sitemaps have been regenerated successfully.', 'easy-xml-sitemap' ); ?></p>
            </div>
            <?php
        }
    }
}
