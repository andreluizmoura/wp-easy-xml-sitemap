<?php
/**
 * Admin settings page and UI
 *
 * @package EasyXMLSitemap
 */

namespace EasyXMLSitemap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_Settings {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_menu() {
		add_options_page(
			__( 'Easy XML Sitemap', 'easy-xml-sitemap' ),
			__( 'Easy XML Sitemap', 'easy-xml-sitemap' ),
			'manage_options',
			'easy-xml-sitemap',
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting( 'easy_xml_sitemap', Easy_XML_Sitemap::OPTION_NAME, array( $this, 'sanitize' ) );
	}

	public function sanitize( $input ) {
		$defaults = array(
			'enabled'           => true,
			'add_to_robots'     => true,
			'disable_native'    => true,
			'cache_ttl_minutes' => 60,

			'include_images'    => true,
			'include_videos'    => false,

			'lastmod_key'       => '',
			'changefreq_key'    => '',
			'priority_key'      => '',

			'post_types'        => array(
				'post' => true,
				'page' => true,
			),

			'auto_ping'         => true,
			'ping_google'       => true,
			'ping_bing'         => true,
			'ping_debounce_min' => 5,
		);

		$input = is_array( $input ) ? $input : array();
		$out   = wp_parse_args( $input, $defaults );

		$out['enabled']           = ! empty( $out['enabled'] );
		$out['add_to_robots']     = ! empty( $out['add_to_robots'] );
		$out['disable_native']    = ! empty( $out['disable_native'] );
		$out['cache_ttl_minutes'] = absint( $out['cache_ttl_minutes'] );
		if ( $out['cache_ttl_minutes'] < 1 ) {
			$out['cache_ttl_minutes'] = 1;
		}

		$out['include_images'] = ! empty( $out['include_images'] );
		$out['include_videos'] = ! empty( $out['include_videos'] );

		$out['lastmod_key']    = sanitize_text_field( $out['lastmod_key'] );
		$out['changefreq_key'] = sanitize_text_field( $out['changefreq_key'] );
		$out['priority_key']   = sanitize_text_field( $out['priority_key'] );

		$out['auto_ping'] = ! empty( $out['auto_ping'] );
		$out['ping_google'] = ! empty( $out['ping_google'] );
		$out['ping_bing']   = ! empty( $out['ping_bing'] );

		$out['ping_debounce_min'] = absint( $out['ping_debounce_min'] );
		if ( $out['ping_debounce_min'] < 1 ) {
			$out['ping_debounce_min'] = 1;
		}

		// Post types
		$out['post_types'] = is_array( $out['post_types'] ) ? $out['post_types'] : array();
		foreach ( $out['post_types'] as $k => $v ) {
			$out['post_types'][ $k ] = (bool) $v;
		}

		return $out;
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = get_option( Easy_XML_Sitemap::OPTION_NAME, array() );
		$settings = is_array( $settings ) ? $settings : array();

		$tabs = array(
			'general'    => __( 'General', 'easy-xml-sitemap' ),
			'post_types' => __( 'Post Types', 'easy-xml-sitemap' ),
			'media'      => __( 'Media', 'easy-xml-sitemap' ),
			'ping'       => __( 'Ping', 'easy-xml-sitemap' ),
			'advanced'   => __( 'Advanced', 'easy-xml-sitemap' ),
			'quicklinks' => __( 'Quick Links', 'easy-xml-sitemap' ),
		);

		$active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
		if ( ! isset( $tabs[ $active ] ) ) {
			$active = 'general';
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Easy XML Sitemap', 'easy-xml-sitemap' ) . '</h1>';

		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $k => $label ) {
			$url = admin_url( 'options-general.php?page=easy-xml-sitemap&tab=' . $k );
			$cls = ( $k === $active ) ? 'nav-tab nav-tab-active' : 'nav-tab';
			echo '<a class="' . esc_attr( $cls ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</h2>';

		echo '<form method="post" action="options.php">';
		settings_fields( 'easy_xml_sitemap' );

		switch ( $active ) {
			case 'post_types':
				$this->render_tab_post_types( $settings );
				break;
			case 'media':
				$this->render_tab_media( $settings );
				break;
			case 'ping':
				$this->render_tab_ping( $settings );
				break;
			case 'advanced':
				$this->render_tab_advanced( $settings );
				break;
			case 'quicklinks':
				$this->render_tab_quicklinks( $settings );
				break;
			case 'general':
			default:
				$this->render_tab_general( $settings );
				break;
		}

		echo '<p><input type="submit" class="button-primary" value="' . esc_attr__( 'Save Changes', 'easy-xml-sitemap' ) . '"></p>';
		echo '</form>';

		echo '</div>';
	}

	private function render_checkbox( $key, $label, $desc, $settings ) {
		$val = ! empty( $settings[ $key ] );
		echo '<label style="display:block;margin:10px 0;">';
		echo '<input type="checkbox" name="' . esc_attr( Easy_XML_Sitemap::OPTION_NAME ) . '[' . esc_attr( $key ) . ']" value="1" ' . checked( $val, true, false ) . ' /> ';
		echo '<strong>' . esc_html( $label ) . '</strong>';
		echo '</label>';
		if ( $desc ) {
			echo '<p class="description" style="margin:-6px 0 14px 22px;">' . esc_html( $desc ) . '</p>';
		}
	}

	private function render_number( $key, $label, $desc, $settings, $min = 1, $max = 999999 ) {
		$val = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
		echo '<label style="display:block;margin:10px 0;">';
		echo '<strong>' . esc_html( $label ) . '</strong><br>';
		echo '<input type="number" min="' . esc_attr( $min ) . '" max="' . esc_attr( $max ) . '" name="' . esc_attr( Easy_XML_Sitemap::OPTION_NAME ) . '[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '" style="width:120px;">';
		echo '</label>';
		if ( $desc ) {
			echo '<p class="description" style="margin:-6px 0 14px 0;">' . esc_html( $desc ) . '</p>';
		}
	}

	private function render_text( $key, $label, $desc, $settings ) {
		$val = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
		echo '<label style="display:block;margin:10px 0;">';
		echo '<strong>' . esc_html( $label ) . '</strong><br>';
		echo '<input type="text" name="' . esc_attr( Easy_XML_Sitemap::OPTION_NAME ) . '[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '" style="width:420px;max-width:90%;">';
		echo '</label>';
		if ( $desc ) {
			echo '<p class="description" style="margin:-6px 0 14px 0;">' . esc_html( $desc ) . '</p>';
		}
	}

	private function render_tab_general( $settings ) {
		echo '<h2>' . esc_html__( 'General', 'easy-xml-sitemap' ) . '</h2>';

		$this->render_checkbox(
			'enabled',
			__( 'Enable Sitemap', 'easy-xml-sitemap' ),
			__( 'If disabled, the plugin will not output any XML.', 'easy-xml-sitemap' ),
			$settings
		);

		$this->render_checkbox(
			'add_to_robots',
			__( 'Add sitemap to robots.txt', 'easy-xml-sitemap' ),
			__( 'Adds a "Sitemap:" directive to your robots.txt output.', 'easy-xml-sitemap' ),
			$settings
		);

		$this->render_number(
			'cache_ttl_minutes',
			__( 'Cache TTL (minutes)', 'easy-xml-sitemap' ),
			__( 'How long to cache generated XML before regenerating.', 'easy-xml-sitemap' ),
			$settings,
			1,
			43200
		);
	}

	private function render_tab_post_types( $settings ) {
		echo '<h2>' . esc_html__( 'Post Types', 'easy-xml-sitemap' ) . '</h2>';

		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$enabled    = isset( $settings['post_types'] ) && is_array( $settings['post_types'] ) ? $settings['post_types'] : array();

		echo '<p class="description" style="max-width:900px;">' . esc_html__(
			'Select which public post types should be included. Posts get an index and monthly archives; other post types get a single sitemap.',
			'easy-xml-sitemap'
		) . '</p>';

		echo '<table class="widefat striped" style="max-width:900px;margin-top:12px;">';
		echo '<thead><tr><th>' . esc_html__( 'Enable', 'easy-xml-sitemap' ) . '</th><th>' . esc_html__( 'Post Type', 'easy-xml-sitemap' ) . '</th><th>' . esc_html__( 'Label', 'easy-xml-sitemap' ) . '</th></tr></thead><tbody>';

		foreach ( $post_types as $pt => $obj ) {
			$val = isset( $enabled[ $pt ] ) ? (bool) $enabled[ $pt ] : false;

			echo '<tr>';
			echo '<td style="width:120px;">';
			echo '<input type="checkbox" name="' . esc_attr( Easy_XML_Sitemap::OPTION_NAME ) . '[post_types][' . esc_attr( $pt ) . ']" value="1" ' . checked( $val, true, false ) . ' />';
			echo '</td>';
			echo '<td><code>' . esc_html( $pt ) . '</code></td>';
			echo '<td>' . esc_html( $obj->labels->singular_name ) . '</td>';
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
			'Enable optional media tags. These can increase sitemap size; only enable what you need.',
			'easy-xml-sitemap'
		) . '</p>';

		$this->render_checkbox(
			'include_images',
			__( 'Include Images', 'easy-xml-sitemap' ),
			__( 'Add image sitemap tags when images are detected in post content.', 'easy-xml-sitemap' ),
			$settings
		);

		$this->render_checkbox(
			'include_videos',
			__( 'Include Videos', 'easy-xml-sitemap' ),
			__( 'Add video sitemap tags when a reliable video and thumbnail can be detected.', 'easy-xml-sitemap' ),
			$settings
		);
	}

	private function render_tab_ping( $settings ) {
		echo '<h2>' . esc_html__( 'Ping', 'easy-xml-sitemap' ) . '</h2>';

		echo '<p class="description" style="max-width:900px;">' . esc_html__(
			'Automatically notify search engines when content is published.',
			'easy-xml-sitemap'
		) . '</p>';

		$this->render_checkbox(
			'auto_ping',
			__( 'Auto Ping', 'easy-xml-sitemap' ),
			__( 'When enabled, the plugin will ping Google/Bing after new content is published (debounced).', 'easy-xml-sitemap' ),
			$settings
		);

		$this->render_checkbox(
			'ping_google',
			__( 'Ping Google', 'easy-xml-sitemap' ),
			__( 'Include Google ping target.', 'easy-xml-sitemap' ),
			$settings
		);

		$this->render_checkbox(
			'ping_bing',
			__( 'Ping Bing', 'easy-xml-sitemap' ),
			__( 'Include Bing ping target.', 'easy-xml-sitemap' ),
			$settings
		);

		$this->render_number(
			'ping_debounce_min',
			__( 'Debounce (minutes)', 'easy-xml-sitemap' ),
			__( 'Wait this many minutes after publishing before pinging (to avoid multiple pings in a burst).', 'easy-xml-sitemap' ),
			$settings,
			1,
			240
		);
	}

	private function render_tab_advanced( $settings ) {
		echo '<h2>' . esc_html__( 'Advanced', 'easy-xml-sitemap' ) . '</h2>';

		echo '<p class="description" style="max-width:900px;">' . esc_html__(
			'Advanced options for integration and metadata.',
			'easy-xml-sitemap'
		) . '</p>';

		$this->render_text(
			'lastmod_key',
			__( 'Custom "lastmod" meta key', 'easy-xml-sitemap' ),
			__( 'If set, will read this post meta key to determine last modified date (ISO 8601). Leave blank to use WP post_modified_gmt.', 'easy-xml-sitemap' ),
			$settings
		);

		$this->render_text(
			'changefreq_key',
			__( 'Custom "changefreq" meta key', 'easy-xml-sitemap' ),
			__( 'Optional meta key for changefreq. Leave blank for none.', 'easy-xml-sitemap' ),
			$settings
		);

		$this->render_text(
			'priority_key',
			__( 'Custom "priority" meta key', 'easy-xml-sitemap' ),
			__( 'Optional meta key for priority. Leave blank for none.', 'easy-xml-sitemap' ),
			$settings
		);
	}

	private function render_tab_quicklinks( $settings ) {
		echo '<h2>' . esc_html__( 'Quick Links', 'easy-xml-sitemap' ) . '</h2>';

		$sitemap_index_url = home_url( '/sitemap.xml' );
		$robots_url        = home_url( '/robots.txt' );

		echo '<div style="max-width:1000px;margin:12px 0;padding:12px 14px;border:1px solid #ccd0d4;background:#fff;border-radius:6px;">';
		echo '<p style="margin:0 0 10px 0;">' . esc_html__(
			'Use these links to quickly verify your sitemap output.',
			'easy-xml-sitemap'
		) . '</p>';

		echo '<ul style="margin:0 0 0 18px;">';
		echo '<li><strong>' . esc_html__( 'Sitemap Index', 'easy-xml-sitemap' ) . ':</strong> <a href="' . esc_url( $sitemap_index_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $sitemap_index_url ) . '</a></li>';
		echo '<li><strong>' . esc_html__( 'robots.txt', 'easy-xml-sitemap' ) . ':</strong> <a href="' . esc_url( $robots_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $robots_url ) . '</a></li>';
		echo '</ul>';
		echo '</div>';

		echo '<p class="description" style="max-width:1000px;">' . esc_html__(
			'If you use another SEO plugin, make sure only one sitemap is referenced in robots.txt and submitted to search engines.',
			'easy-xml-sitemap'
		) . '</p>';
	}
}