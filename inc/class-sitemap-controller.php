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

		// Root sitemap index (canonical)
		add_rewrite_rule(
			'^sitemap\.xml$',
			'index.php?easy_sitemap_type=sitemap-index',
			'top'
		);

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
			'^' . $slug . '/pages\.xml$',
			'index.php?easy_sitemap_type=pages',
			'top'
		);

		add_rewrite_rule(
			'^' . $slug . '/categories\.xml$',
			'index.php?easy_sitemap_type=categories',
			'top'
		);

		add_rewrite_rule(
			'^' . $slug . '/general\.xml$',
			'index.php?easy_sitemap_type=general',
			'top'
		);

		add_rewrite_rule(
			'^' . $slug . '/news\.xml$',
			'index.php?easy_sitemap_type=news',
			'top'
		);

		add_rewrite_rule(
			'^' . $slug . '/posts\-([0-9]{4})\-([0-9]{2})\.xml$',
			'index.php?easy_sitemap_type=posts&easy_sitemap_year=$matches[1]&easy_sitemap_month=$matches[2]',
			'top'
		);

		add_rewrite_rule(
			'^' . $slug . '/category\-([0-9]+)\.xml$',
			'index.php?easy_sitemap_type=category&easy_sitemap_cat=$matches[1]',
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
		$vars[] = 'easy_sitemap_generic';
		return $vars;
	}

	/**
	 * Fallback handler for requests when rewrite rules are not active/flushed.
	 * This allows serving known sitemap endpoints by URL path.
	 *
	 * Note: This is intentionally conservative to avoid intercepting other routes.
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

		// Canonical root endpoint: /sitemap.xml
		if ( '/sitemap.xml' === $req_path ) {
			self::record_hit_stat( 'sitemap-index' );
			$this->serve_sitemap( 'sitemap-index' );
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
			$type = 'posts';
			$_GET['easy_sitemap_year']  = $m[1];
			$_GET['easy_sitemap_month'] = $m[2];
			self::record_hit_stat( 'posts' );
			$this->serve_sitemap( $type );
		}

		// category-ID.xml
		if ( preg_match( '/^category\-([0-9]+)\.xml$/', $leaf, $m ) ) {
			$type = 'category';
			$_GET['easy_sitemap_cat'] = $m[1];
			self::record_hit_stat( 'category' );
			$this->serve_sitemap( $type );
		}

		// generic.xml
		if ( preg_match( '/^([a-z0-9_-]+)\.xml$/', $leaf, $m ) ) {
			$type = 'generic';
			$_GET['easy_sitemap_generic'] = $m[1];
			self::record_hit_stat( 'generic' );
			$this->serve_sitemap( $type );
		}
	}

	public function handle_sitemap_request() {
		$type = get_query_var( 'easy_sitemap_type' );
		if ( empty( $type ) ) {
			return;
		}

		self::record_hit_stat( $type );

		$this->serve_sitemap( $type );
	}

	private function serve_sitemap( $type ) {
		$renderer = new XML_Renderer();
		$renderer->render( $type );
		exit;
	}

	/**
	 * Best-effort in-memory hit stats (not persisted).
	 */
	public static function record_hit_stat( $key ) {
		// intentionally minimal; can be expanded later
	}

}
