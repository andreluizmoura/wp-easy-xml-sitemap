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
            __( 'Easy XML Sitemap', 'easy-xml-sitemap' ),
            __( 'Easy XML Sitemap', 'easy-xml-sitemap' ),
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
            __( 'General Settings', 'easy-xml-sitemap' ),
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
            'enable_news',
            __( 'Google News Sitemap', 'easy-xml-sitemap' ),
            array( $this, 'render_checkbox_field' ),
            self::PAGE_SLUG,
            'easy_xml_sitemap_general_section',
            array(
                'label_for'   => 'enable_news',
                'description' => __( 'Enable Google News compatible sitemap for recent posts', 'easy-xml-sitemap' ),
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
                'description' => __( 'Automatically add sitemap index URL to robots.txt', 'easy-xml-sitemap' ),
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
                'description' => __( 'How long to cache sitemap output (default: 3600 seconds)', 'easy-xml-sitemap' ),
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

        $output['enable_posts']      = isset( $input['enable_posts'] ) ? (bool) $input['enable_posts'] : false;
        $output['enable_pages']      = isset( $input['enable_pages'] ) ? (bool) $input['enable_pages'] : false;
        $output['enable_categories'] = isset( $input['enable_categories'] ) ? (bool) $input['enable_categories'] : false;
        $output['enable_tags']       = isset( $input['enable_tags'] ) ? (bool) $input['enable_tags'] : false;
        $output['enable_news']       = isset( $input['enable_news'] ) ? (bool) $input['enable_news'] : false;
        $output['add_to_robots']     = isset( $input['add_to_robots'] ) ? (bool) $input['add_to_robots'] : false;

        $output['cache_duration'] = isset( $input['cache_duration'] ) ? absint( $input['cache_duration'] ) : 3600;

        if ( $output['cache_duration'] < 60 ) {
            $output['cache_duration'] = 60;
        }

        if ( $output['cache_duration'] > 604800 ) {
            $output['cache_duration'] = 604800;
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
            <h1><?php esc_html_e( 'Easy XML Sitemap', 'easy-xml-sitemap' ); ?></h1>
            <p><?php esc_html_e( 'Configure the XML sitemap settings for your site.', 'easy-xml-sitemap' ); ?></p>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'easy_xml_sitemap_settings' );
                do_settings_sections( self::PAGE_SLUG );
                submit_button();
                ?>
            </form>

            <hr />

            <h2><?php esc_html_e( 'Sitemap Tools', 'easy-xml-sitemap' ); ?></h2>
            <p><?php esc_html_e( 'Use the tools below to manually regenerate XML sitemaps.', 'easy-xml-sitemap' ); ?></p>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>
                <input type="hidden" name="action" value="easy_xml_sitemap_regenerate" />
                <?php submit_button( __( 'Regenerate Sitemaps', 'easy-xml-sitemap' ), 'secondary' ); ?>
            </form>

            <h2><?php esc_html_e( 'Sitemap URLs', 'easy-xml-sitemap' ); ?></h2>
            <p><?php esc_html_e( 'Below are the main sitemap URLs generated by this plugin.', 'easy-xml-sitemap' ); ?></p>

            <?php
            $sitemap_types = array(
                'sitemap-index' => __( 'Sitemap Index', 'easy-xml-sitemap' ),
                'posts'         => __( 'Posts Sitemap', 'easy-xml-sitemap' ),
                'pages'         => __( 'Pages Sitemap', 'easy-xml-sitemap' ),
                'categories'    => __( 'Categories Sitemap', 'easy-xml-sitemap' ),
                'tags'          => __( 'Tags Sitemap', 'easy-xml-sitemap' ),
                'news'          => __( 'Google News Sitemap', 'easy-xml-sitemap' ),
            );
            ?>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Sitemap Type', 'easy-xml-sitemap' ); ?></th>
                        <th><?php esc_html_e( 'URL', 'easy-xml-sitemap' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $sitemap_types as $type => $label ) : ?>
                        <?php
                        $enabled = true;
                        if ( 'sitemap-index' !== $type ) {
                            if ( 'news' === $type ) {
                                $enabled = ! empty( $options['enable_news'] );
                            } elseif ( 'posts' === $type ) {
                                $enabled = ! empty( $options['enable_posts'] );
                            } elseif ( 'pages' === $type ) {
                                $enabled = ! empty( $options['enable_pages'] );
                            } elseif ( 'categories' === $type ) {
                                $enabled = ! empty( $options['enable_categories'] );
                            } elseif ( 'tags' === $type ) {
                                $enabled = ! empty( $options['enable_tags'] );
                            }
                        }

                        $url = Sitemap_Controller::get_sitemap_url( $type );

                        $highlight_style = ( 'sitemap-index' === $type ) ? 'background-color: #f0f6fc;' : '';
                        ?>
                        <tr<?php if ( $highlight_style ) : ?> style="<?php echo esc_attr( $highlight_style ); ?>"<?php endif; ?>>
                            <td><strong><?php echo esc_html( $label ); ?></strong></td>
                            <td>
                                <?php if ( $enabled ) : ?>
                                    <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php echo esc_html( $url ); ?>
                                    </a>
                                <?php else : ?>
                                    <em><?php esc_html_e( 'Disabled', 'easy-xml-sitemap' ); ?></em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render general section description.
     */
    public function render_general_section() {
        ?>
        <p><?php esc_html_e( 'Configure which content types should be included in XML sitemaps and how caching should behave.', 'easy-xml-sitemap' ); ?></p>
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
                <p><?php esc_html_e( 'All sitemaps have been regenerated successfully.', 'easy-xml-sitemap' ); ?></p>
            </div>
            <?php
        }
    }
}
