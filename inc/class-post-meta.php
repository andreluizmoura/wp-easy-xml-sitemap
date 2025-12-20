<?php
/**
 * Post meta controls for sitemap exclusion
 *
 * @package EasyXMLSitemap
 */

namespace EasyXMLSitemap;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Post_Meta class - handles per-post/page sitemap exclusion controls.
 */
class Post_Meta {

    /**
     * Single instance of the class.
     *
     * @var Post_Meta
     */
    private static $instance = null;

    /**
     * Meta key for exclusion flag.
     */
    const META_KEY = '_easy_xml_sitemap_exclude';

    /**
     * Get singleton instance.
     *
     * @return Post_Meta
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
        add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_post_meta' ) );

        // Block editor integration.
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );

        // Register REST field for exclusion flag.
        add_action( 'init', array( $this, 'register_rest_field' ) );
    }

    /**
     * Register meta box for post/page edit screens.
     */
    public function register_meta_box() {
        $post_types = apply_filters(
            'easy_xml_sitemap_meta_box_post_types',
            array( 'post', 'page' )
        );

        foreach ( $post_types as $post_type ) {
            add_meta_box(
                'easy-xml-sitemap-meta',
                __( 'XML Sitemap Options', 'easy-xml-sitemap' ),
                array( $this, 'render_meta_box' ),
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * Render meta box content.
     *
     * @param \WP_Post $post Post object.
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'easy_xml_sitemap_meta_box', 'easy_xml_sitemap_meta_box_nonce' );

        $excluded = get_post_meta( $post->ID, self::META_KEY, true );
        ?>
        <p>
            <label for="easy-xml-sitemap-exclude">
                <input type="checkbox" name="easy_xml_sitemap_exclude" id="easy-xml-sitemap-exclude" value="1" <?php checked( $excluded, '1' ); ?> />
                <?php esc_html_e( 'Exclude this content from XML sitemaps', 'easy-xml-sitemap' ); ?>
            </label>
        </p>
        <?php
    }

    /**
     * Save post meta when the post is saved.
     *
     * @param int $post_id Post ID.
     */
    public function save_post_meta( $post_id ) {
        if ( ! isset( $_POST['easy_xml_sitemap_meta_box_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['easy_xml_sitemap_meta_box_nonce'] ) ), 'easy_xml_sitemap_meta_box' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( isset( $_POST['post_type'] ) && 'page' === $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return;
            }
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
        }

        $exclude = isset( $_POST['easy_xml_sitemap_exclude'] ) ? '1' : '';

        if ( $exclude ) {
            update_post_meta( $post_id, self::META_KEY, true );
        } else {
            delete_post_meta( $post_id, self::META_KEY );
        }
    }

    /**
     * Enqueue block editor assets.
     */
    public function enqueue_block_editor_assets() {
        $handle = 'easy-xml-sitemap-editor';

        wp_register_script(
            $handle,
            EASY_XML_SITEMAP_URL . 'assets/js/editor.js',
            array( 'wp-edit-post', 'wp-plugins', 'wp-element', 'wp-components', 'wp-data', 'wp-compose' ),
            EASY_XML_SITEMAP_VERSION,
            true
        );

        wp_enqueue_script( $handle );

        $script = "
        (function(wp) {
            var registerPlugin = wp.plugins.registerPlugin;
            var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
            var CheckboxControl = wp.components.CheckboxControl;
            var withSelect = wp.data.withSelect;
            var withDispatch = wp.data.withDispatch;
            var compose = wp.compose.compose;

            var EasyXMLSitemapPanel = compose(
                withSelect(function(select) {
                    return {
                        meta: select('core/editor').getEditedPostAttribute('meta')
                    };
                }),
                withDispatch(function(dispatch) {
                    return {
                        setMeta: function(newMeta) {
                            dispatch('core/editor').editPost({
                                meta: newMeta
                            });
                        }
                    };
                })
            )(function(props) {
                var meta = props.meta || {};
                var excluded = !!meta._easy_xml_sitemap_exclude;

                return wp.element.createElement(
                    PluginDocumentSettingPanel,
                    {
                        name: 'easy-xml-sitemap-panel',
                        title: 'XML Sitemap',
                        className: 'easy-xml-sitemap-panel'
                    },
                    wp.element.createElement(CheckboxControl, {
                        label: 'Exclude from XML sitemaps',
                        checked: excluded,
                        onChange: function(checked) {
                            var newMeta = Object.assign({}, meta, {
                                _easy_xml_sitemap_exclude: checked ? '1' : ''
                            });
                            props.setMeta(newMeta);
                        }
                    })
                );
            });

            registerPlugin('easy-xml-sitemap', {
                render: EasyXMLSitemapPanel,
                icon: 'location-alt'
            });
        })(window.wp);
        ";

        wp_add_inline_script( 'wp-plugins', $script );
    }

    /**
     * Register REST field for exclusion meta.
     */
    public function register_rest_field() {
        register_post_meta(
            'post',
            self::META_KEY,
            array(
                'type'          => 'boolean',
                'single'        => true,
                'show_in_rest'  => true,
                'default'       => false,
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_post_meta(
            'page',
            self::META_KEY,
            array(
                'type'          => 'boolean',
                'single'        => true,
                'show_in_rest'  => true,
                'default'       => false,
                'auth_callback' => function() {
                    return current_user_can( 'edit_pages' );
                },
            )
        );
    }

    /**
     * Check if a post is excluded from sitemaps.
     *
     * @param int $post_id Post ID.
     * @return bool True if excluded.
     */
    public static function is_post_excluded( $post_id ) {
        return (bool) get_post_meta( $post_id, self::META_KEY, true );
    }

    /**
     * Get all excluded post IDs.
     *
     * @param string $post_type Post type (post, page, or 'any').
     * @return array Array of post IDs.
     */
    public static function get_excluded_posts( $post_type = 'any' ) {
        $args = array(
            'post_type'      => ( 'any' === $post_type ? 'any' : $post_type ),
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'   => self::META_KEY,
                    'value' => '1',
                ),
            ),
            'no_found_rows'  => true,
        );

        $query = new \WP_Query( $args );

        if ( empty( $query->posts ) ) {
            return array();
        }

        return array_map( 'intval', $query->posts );
    }

    /**
 * Check if a post is excluded from sitemaps.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
public static function is_excluded( $post_id ) {
    return (bool) get_post_meta( (int) $post_id, self::META_KEY, true );
}

}
