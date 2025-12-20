<?php
/**
 * Sitemap controller - handles requests and routing
 *
 * @package EasyXMLSitemap
 */

namespace EasyXMLSitemap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sitemap_Controller {

	private static $instance = null;

	const SITEMAP_SLUG = 'easy-sitemap';
	const OPTION_NAME  = 'easy_xml_sitemap_settings';
	const STATS_NAME   = 'easy_xml_sitemap_stats';

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Register rewrites early
		add_action( 'init', array( $this, 'register_rewrite_rules' ), 1 );

		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );

		/**
		 * Fallback handler by path:
		 * Even if rewrite rules don't match, this will serve the XML for known endpoints.
		 */
		add_action( 'template_redirect', array( $this, 'handle_direct_path_requests' ), 0 );

		// Normal handler via query vars (when rewrites match)
		add_action( 'template_redirect', array( $this, 'handle_sitemap_request' ), 1 );
	}

	public function register_rewrite_rules() {
		$slug = self::SITEMAP_SLUG;

		add_rewrite_rule(
			'^' . $slug . '/sitemap\.xml$',
			'index.php?easy_sitemap_type=sitemap-index',
			'top'
		);

		add_rewrite_rule(
			'^' . $slug . '/posts-index\.xml$',
			'index.php?easy_sitemap_type=posts-index',
			'top'
		);

		add_rewrite_rule(
			'^' . $slug . '/posts-([0-9]{4})-([0-9]{2})\.xml$',
			'index.php?easy_sitemap_type=posts-date&easy_sitemap_year=$matches[1]&easy_sitemap_month=$matches[2]',
			'top'
		);

		add_rewrite_rule(
			'^' . $slug . '/posts-([a-z0-9-]+)\.xml$',
			'index.php?easy_sitemap_type=posts-category&easy_sitemap_cat=$matches[1]',
			'top'
		);

		add_rewrite_rule(
			'^' . $slug . '/([a-z0-9_-]+)-index\.xml$',
			'index.php?easy_sitemap_type=posttype-index&easy_sitemap_post_type=$matches[1]',
			'top'
		);

		// Generic must be bottom
		add_rewrite_rule(
			'^' . $slug . '/([a-z0-9_-]+)\.xml$',
			'index.php?easy_sitemap_type=generic&easy_sitemap_generic=$matches[1]',
			'bottom'
		);
	}

	public function add_query_vars( $vars ) {
		$vars[] = 'easy_sitemap_type';
		$vars[] = 'easy_sitemap_year';
		$vars[] = 'easy_sitemap_month';
		$vars[] = 'easy_sitemap_cat';
		$vars[] = 'easy_sitemap_post_type';
		$vars[] = 'easy_sitemap_generic';
		return $vars;
	}

	/**
	 * Fallback: serve sitemap by matching REQUEST_URI path.
	 * This bypasses the rewrite system entirely.
	 */
	public function handle_direct_path_requests() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		$req_path = wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		if ( ! $req_path ) {
			return;
		}

		// If WP is in a subdirectory, home_url() includes that path.
		$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$home_path = $home_path ? rtrim( $home_path, '/' ) : '';

		// Normalize: remove home path prefix if present.
		if ( $home_path && 0 === strpos( $req_path, $home_path ) ) {
			$req_path = substr( $req_path, strlen( $home_path ) );
			if ( '' === $req_path ) {
				$req_path = '/';
			}
		}

		$base = '/' . self::SITEMAP_SLUG . '/';

		if ( 0 !== strpos( $req_path, $base ) ) {
			return;
		}

		$leaf = substr( $req_path, strlen( $base ) ); // e.g. sitemap.xml
		if ( empty( $leaf ) ) {
			return;
		}

		// Known fixed endpoints
		if ( 'sitemap.xml' === $leaf ) {
			self::record_hit_stat( 'sitemap-index' );
			$this->serve_sitemap( 'sitemap-index' );
		}
		if ( 'posts-index.xml' === $leaf ) {
			self::record_hit_stat( 'posts-index' );
			$this->serve_sitemap( 'posts-index' );
		}
		if ( 'pages.xml' === $leaf ) {
			self::record_hit_stat( 'pages' );
			$this->serve_sitemap( 'pages' );
		}
		if ( 'tags.xml' === $leaf ) {
			self::record_hit_stat( 'tags' );
			$this->serve_sitemap( 'tags' );
		}
		if ( 'categories.xml' === $leaf ) {
			self::record_hit_stat( 'categories' );
			$this->serve_sitemap( 'categories' );
		}
		if ( 'general.xml' === $leaf ) {
			self::record_hit_stat( 'general' );
			$this->serve_sitemap( 'general' );
		}
		if ( 'news.xml' === $leaf ) {
			self::record_hit_stat( 'news' );
			$this->serve_sitemap( 'news' );
		}

		// posts-YYYY-MM.xml
		if ( preg_match( '/^posts\-([0-9]{4})\-([0-9]{2})\.xml$/', $leaf, $m ) ) {
			$year  = (int) $m[1];
			$month = (int) $m[2];
			if ( $this->validate_year_month( $year, $month ) ) {
				self::record_hit_stat( 'posts-date' );
				$this->serve_sitemap( 'posts-date', array( 'year' => $year, 'month' => $month ) );
			}
			$this->send_404();
		}

		// posts-{category}.xml
		if ( preg_match( '/^posts\-([a-z0-9\-]+)\.xml$/', $leaf, $m ) ) {
			$cat = sanitize_title( $m[1] );
			if ( $cat && get_category_by_slug( $cat ) ) {
				self::record_hit_stat( 'posts-category' );
				$this->serve_sitemap( 'posts-category', array( 'cat' => $cat ) );
			}
			$this->send_404();
		}

		// {posttype}-index.xml
		if ( preg_match( '/^([a-z0-9\_]+)\-index\.xml$/', $leaf, $m ) ) {
			$pt = sanitize_key( $m[1] );
			if ( $pt && $this->is_valid_public_post_type( $pt ) && $this->is_post_type_enabled( $pt ) ) {
				self::record_hit_stat( 'posttype-index' );
				$this->serve_sitemap( 'posttype-index', array( 'post_type' => $pt ) );
			}
			$this->send_404();
		}

		// {posttype}.xml or other generic leaf
		if ( preg_match( '/^([a-z0-9\_]+)\.xml$/', $leaf, $m ) ) {
			$generic = sanitize_key( $m[1] );

			$legacy = array( 'pages', 'tags', 'categories', 'general', 'news' );
			if ( in_array( $generic, $legacy, true ) ) {
				// already handled above
				return;
			}

			if ( $generic && $this->is_valid_public_post_type( $generic ) && $this->is_post_type_enabled( $generic ) ) {
				self::record_hit_stat( 'posttype' );
				$this->serve_sitemap( 'posttype', array( 'post_type' => $generic ) );
			}

			// Unknown generic
			$this->send_404();
		}

		// If it starts with /easy-sitemap/ but doesn't match anything, 404.
		$this->send_404();
	}

	public function handle_sitemap_request() {
		$type = get_query_var( 'easy_sitemap_type', '' );
		if ( empty( $type ) ) {
			return;
		}

		self::record_hit_stat( $type );

		switch ( $type ) {
			case 'sitemap-index':
				$this->serve_sitemap( 'sitemap-index' );
				break;

			case 'posts-index':
				$this->serve_sitemap( 'posts-index' );
				break;

			case 'posts-date':
				$year  = get_query_var( 'easy_sitemap_year', '' );
				$month = get_query_var( 'easy_sitemap_month', '' );
				if ( ! $this->validate_year_month( $year, $month ) ) {
					$this->send_404();
				}
				$this->serve_sitemap( 'posts-date', array( 'year' => (int) $year, 'month' => (int) $month ) );
				break;

			case 'posts-category':
				$slug = sanitize_title( (string) get_query_var( 'easy_sitemap_cat', '' ) );
				if ( empty( $slug ) || ! get_category_by_slug( $slug ) ) {
					$this->send_404();
				}
				$this->serve_sitemap( 'posts-category', array( 'cat' => $slug ) );
				break;

			case 'posttype-index':
				$post_type = sanitize_key( (string) get_query_var( 'easy_sitemap_post_type', '' ) );
				if ( empty( $post_type ) || ! $this->is_valid_public_post_type( $post_type ) || ! $this->is_post_type_enabled( $post_type ) ) {
					$this->send_404();
				}
				$this->serve_sitemap( 'posttype-index', array( 'post_type' => $post_type ) );
				break;

			case 'generic':
				$generic = sanitize_key( (string) get_query_var( 'easy_sitemap_generic', '' ) );
				if ( empty( $generic ) ) {
					$this->send_404();
				}

				$legacy = array( 'pages', 'tags', 'categories', 'general', 'news' );
				if ( in_array( $generic, $legacy, true ) ) {
					if ( ! $this->is_sitemap_enabled( $generic ) ) {
						$this->send_404();
					}
					$this->serve_sitemap( $generic );
					break;
				}

				if ( $this->is_valid_public_post_type( $generic ) ) {
					if ( ! $this->is_post_type_enabled( $generic ) ) {
						$this->send_404();
					}
					$this->serve_sitemap( 'posttype', array( 'post_type' => $generic ) );
					break;
				}

				$this->send_404();
				break;

			default:
				$this->send_404();
		}
	}

	private function validate_year_month( $year, $month ) {
		$y = (int) $year;
		$m = (int) $month;
		if ( $y < 1970 || $y > 2100 ) {
			return false;
		}
		if ( $m < 1 || $m > 12 ) {
			return false;
		}
		return true;
	}

	private function serve_sitemap( $type, $args = array() ) {
		$cache_key = $type;

		if ( 'posts-date' === $type && isset( $args['year'], $args['month'] ) ) {
			$cache_key .= '-' . (int) $args['year'] . '-' . (int) $args['month'];
		} elseif ( 'posts-category' === $type && isset( $args['cat'] ) ) {
			$cache_key .= '-' . sanitize_key( (string) $args['cat'] );
		} elseif ( 'posttype-index' === $type && isset( $args['post_type'] ) ) {
			$cache_key .= '-' . sanitize_key( (string) $args['post_type'] );
		} elseif ( 'posttype' === $type && isset( $args['post_type'] ) ) {
			$cache_key .= '-' . sanitize_key( (string) $args['post_type'] );
		}

		$xml = Cache::get( $cache_key );

		if ( false === $xml ) {
			$start = microtime( true );

			$xml = $this->generate_sitemap_xml( $type, $args );

			$duration_ms = (int) round( ( microtime( true ) - $start ) * 1000 );

			if ( ! empty( $xml ) ) {
				Cache::set( $cache_key, $xml );
				self::record_generation_stat( (int) XML_Renderer::get_last_count(), $duration_ms );
			}
		}

		$this->send_xml_headers();
		echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	private function generate_sitemap_xml( $type, $args = array() ) {
		switch ( $type ) {
			case 'sitemap-index':
				return XML_Renderer::generate_sitemap_index();
			case 'posts-index':
				return XML_Renderer::generate_posts_index();
			case 'posts-date':
				return XML_Renderer::generate_posts_by_date( (int) $args['year'], (int) $args['month'] );
			case 'posts-category':
				return XML_Renderer::generate_posts_by_category( (string) $args['cat'] );
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
			case 'posttype-index':
				return XML_Renderer::generate_post_type_index( (string) $args['post_type'] );
			case 'posttype':
				return XML_Renderer::generate_post_type_sitemap( (string) $args['post_type'] );
			default:
				return '';
		}
	}

	private function is_sitemap_enabled( $sitemap_type ) {
		$settings = get_option( self::OPTION_NAME, array() );
		$key      = 'enable_' . $sitemap_type;
		return isset( $settings[ $key ] ) ? (bool) $settings[ $key ] : true;
	}

	private function is_valid_public_post_type( $post_type ) {
		$obj = get_post_type_object( $post_type );
		return ( $obj && ! empty( $obj->public ) && 'attachment' !== $post_type );
	}

	private function is_post_type_enabled( $post_type ) {
		$settings = get_option( self::OPTION_NAME, array() );
		if ( isset( $settings['post_types'] ) && is_array( $settings['post_types'] ) ) {
			return ! empty( $settings['post_types'][ $post_type ] );
		}
		// legacy fallback
		if ( 'post' === $post_type ) {
			return isset( $settings['enable_posts'] ) ? (bool) $settings['enable_posts'] : true;
		}
		if ( 'page' === $post_type ) {
			return isset( $settings['enable_pages'] ) ? (bool) $settings['enable_pages'] : true;
		}
		return false;
	}

	private function send_xml_headers() {
		if ( headers_sent() ) {
			return;
		}
		status_header( 200 );
		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, follow', true );

		$cache_duration = $this->get_cache_duration();
		header( 'Cache-Control: max-age=' . $cache_duration );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $cache_duration ) . ' GMT' );
	}

	private function get_cache_duration() {
		$settings = get_option( self::OPTION_NAME, array() );
		return isset( $settings['cache_duration'] ) ? absint( $settings['cache_duration'] ) : 3600;
	}

	private function send_404() {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		$template = get_query_template( '404' );
		if ( $template ) {
			include $template;
		} else {
			echo '404';
		}
		exit;
	}

	public static function record_hit_stat( $type ) {
		$stats = get_option( self::STATS_NAME, array() );
		$stats['hits_total'] = isset( $stats['hits_total'] ) ? (int) $stats['hits_total'] + 1 : 1;

		if ( ! isset( $stats['hits_by_type'] ) || ! is_array( $stats['hits_by_type'] ) ) {
			$stats['hits_by_type'] = array();
		}
		$stats['hits_by_type'][ $type ] = isset( $stats['hits_by_type'][ $type ] ) ? (int) $stats['hits_by_type'][ $type ] + 1 : 1;

		update_option( self::STATS_NAME, $stats );
	}

	public static function record_generation_stat( $total_urls, $duration_ms ) {
		$stats = get_option( self::STATS_NAME, array() );
		$stats['last_generated']  = current_time( 'mysql' );
		$stats['last_total_urls'] = (int) $total_urls;
		$stats['last_gen_time']   = (int) $duration_ms;
		update_option( self::STATS_NAME, $stats );
	}

	public static function record_ping_stat( $engine, $status ) {
		$stats = get_option( self::STATS_NAME, array() );
		$stats['last_ping']        = current_time( 'mysql' );
		$stats['last_ping_engine'] = sanitize_text_field( (string) $engine );
		$stats['last_ping_status'] = sanitize_text_field( (string) $status );
		update_option( self::STATS_NAME, $stats );
	}

	/**
 * Build a sitemap URL for a given type.
 *
 * @param string $type Sitemap type slug (e.g. 'posts-index', 'pages', 'news').
 * @return string
 */
public static function get_sitemap_url( $type ) {
	$type = sanitize_key( (string) $type );
	return home_url( '/' . self::SITEMAP_SLUG . '/' . $type . '.xml' );
}

}
