<?php
/**
 * XML rendering for sitemaps
 *
 * @package EasyXMLSitemap
 */

namespace EasyXMLSitemap;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * XML_Renderer class - generates sitemap XML output
 */
class XML_Renderer {

    /**
     * Generate XML header
     *
     * @param string $type Type of sitemap (standard, news, or index)
     * @return string XML header
     */
    public static function get_xml_header( $type = 'standard' ) {
        $xsl_url = plugins_url( 'sitemap.xsl', EASY_XML_SITEMAP_FILE );
        $generator_url = 'https://wordpress.andremoura.com';
        $version = EASY_XML_SITEMAP_VERSION;
        $generated_on = current_time( 'mysql' );
        
        $header = "<?xml version='1.0' encoding='UTF-8'?>";
        $header .= "<?xml-stylesheet type='text/xsl' href='" . esc_url( $xsl_url ) . "'?>\n";
        $header .= "<!-- sitemap-generator-url='" . esc_url( $generator_url ) . "' sitemap-generator-version='" . esc_attr( $version ) . "' -->\n";
        $header .= "<!-- generated-on='" . esc_attr( $generated_on ) . "' -->\n";
        
        if ( 'news' === $type ) {
            $header .= "<urlset xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' ";
            $header .= "xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd' ";
            $header .= "xmlns='http://www.sitemaps.org/schemas/sitemap/0.9' ";
            $header .= "xmlns:news='http://www.google.com/schemas/sitemap-news/0.9'>\n";
        } elseif ( 'index' === $type ) {
            $header .= "<sitemapindex xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' ";
            $header .= "xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd' ";
            $header .= "xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";
        } else {
            $header .= "<urlset xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' ";
            $header .= "xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd' ";
            $header .= "xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";
        }
        
        return $header;
    }

    /**
     * Generate XML footer
     *
     * @param string $type Type of sitemap (standard or index)
     * @return string XML footer
     */
    public static function get_xml_footer( $type = 'standard' ) {
        if ( 'index' === $type ) {
            return '</sitemapindex>';
        }
        return '</urlset>';
    }

    /**
     * Render a standard URL entry
     *
     * @param string $url      The URL
     * @param string $lastmod  Last modification date (ISO 8601 format)
     * @param string $priority Priority (0.0 to 1.0)
     * @return string XML for single URL entry
     */
    public static function render_url( $url, $lastmod = '', $priority = '0.5' ) {
        $xml = "\t<url>\n";
        $xml .= "\t\t<loc>" . esc_url( $url ) . "</loc>\n";
        
        if ( ! empty( $lastmod ) ) {
            $xml .= "\t\t<lastmod>" . esc_html( $lastmod ) . "</lastmod>\n";
        }
        
        if ( ! empty( $priority ) ) {
            $xml .= "\t\t<priority>" . esc_html( $priority ) . "</priority>\n";
        }
        
        $xml .= "\t</url>\n";
        
        return $xml;
    }

    /**
     * Render a sitemap entry for sitemap index
     *
     * @param string $url     The sitemap URL
     * @param string $lastmod Last modification date (ISO 8601 format)
     * @return string XML for single sitemap entry
     */
    public static function render_sitemap_entry( $url, $lastmod = '' ) {
        $xml = "\t<sitemap>\n";
        $xml .= "\t\t<loc>" . esc_url( $url ) . "</loc>\n";
        
        if ( ! empty( $lastmod ) ) {
            $xml .= "\t\t<lastmod>" . esc_html( $lastmod ) . "</lastmod>\n";
        }
        
        $xml .= "\t</sitemap>\n";
        
        return $xml;
    }

    /**
     * Render a Google News URL entry
     *
     * @param string $url       The URL
     * @param array  $news_data News-specific data
     * @return string XML for single news URL entry
     */
    public static function render_news_url( $url, $news_data ) {
        $xml = "\t<url>\n";
        $xml .= "\t\t<loc>" . esc_url( $url ) . "</loc>\n";
        
        // News element
        $xml .= "\t\t<news:news>\n";
        
        // Publication
        $xml .= "\t\t\t<news:publication>\n";
        $xml .= "\t\t\t\t<news:name>" . esc_html( $news_data['publication_name'] ) . "</news:name>\n";
        $xml .= "\t\t\t\t<news:language>" . esc_html( $news_data['language'] ) . "</news:language>\n";
        $xml .= "\t\t\t</news:publication>\n";
        
        // Publication date
        if ( ! empty( $news_data['publication_date'] ) ) {
            $xml .= "\t\t\t<news:publication_date>" . esc_html( $news_data['publication_date'] ) . "</news:publication_date>\n";
        }
        
        // Title
        if ( ! empty( $news_data['title'] ) ) {
            $xml .= "\t\t\t<news:title>" . esc_html( $news_data['title'] ) . "</news:title>\n";
        }
        
        // Genres (optional)
        if ( ! empty( $news_data['genres'] ) ) {
            $xml .= "\t\t\t<news:genres>" . esc_html( $news_data['genres'] ) . "</news:genres>\n";
        }
        
        // Keywords (optional)
        if ( ! empty( $news_data['keywords'] ) ) {
            $xml .= "\t\t\t<news:keywords>" . esc_html( $news_data['keywords'] ) . "</news:keywords>\n";
        }
        
        $xml .= "\t\t</news:news>\n";
        $xml .= "\t</url>\n";
        
        return $xml;
    }

    /**
     * Generate sitemap index
     *
     * @return string Complete XML sitemap index
     */
    public static function generate_sitemap_index() {
        $xml = self::get_xml_header( 'index' );
        
        $settings = get_option( 'easy_xml_sitemap_settings', array() );
        
        // Get last modification time
        $lastmod = gmdate( 'c' );
        
        // Add enabled sitemaps to index
        $sitemap_types = array(
            'posts-index' => 'enable_posts',
            'pages'       => 'enable_pages',
            'tags'        => 'enable_tags',
            'categories'  => 'enable_categories',
            'general'     => 'enable_general',
            'news'        => 'enable_news',
        );
        
        foreach ( $sitemap_types as $type => $setting_key ) {
            $enabled = isset( $settings[ $setting_key ] ) ? $settings[ $setting_key ] : true;
            
            if ( $enabled ) {
                $url = Sitemap_Controller::get_sitemap_url( $type );
                $xml .= self::render_sitemap_entry( $url, $lastmod );
            }
        }
        
        $xml .= self::get_xml_footer( 'index' );
        
        return $xml;
    }

    /**
     * Generate posts sitemap index (organized by date or category)
     *
     * @return string Complete XML sitemap for posts
     */
    public static function generate_posts_index() {
        $settings = get_option( 'easy_xml_sitemap_settings', array() );
        $organization = isset( $settings['posts_organization'] ) ? $settings['posts_organization'] : 'single';
        
        // If single organization, return simple sitemap
        if ( 'single' === $organization ) {
            return self::generate_posts_sitemap();
        }
        
        // Otherwise, generate an index
        $xml = self::get_xml_header( 'index' );
        
        if ( 'date' === $organization ) {
            // Get all months/years with posts
            global $wpdb;
            
            $dates = $wpdb->get_results(
                "SELECT DISTINCT YEAR(post_date) as year, MONTH(post_date) as month, MAX(post_modified) as lastmod
                FROM {$wpdb->posts}
                WHERE post_type = 'post'
                AND post_status = 'publish'
                GROUP BY YEAR(post_date), MONTH(post_date)
                ORDER BY year DESC, month DESC"
            );
            
            foreach ( $dates as $date ) {
                $year = str_pad( $date->year, 4, '0', STR_PAD_LEFT );
                $month = str_pad( $date->month, 2, '0', STR_PAD_LEFT );
                
                $url = home_url( '/easy-sitemap/posts-' . $year . '-' . $month . '.xml' );
                $lastmod = self::format_lastmod( $date->lastmod );
                
                $xml .= self::render_sitemap_entry( $url, $lastmod );
            }
            
        } elseif ( 'category' === $organization ) {
            // Get all categories with posts
            $categories = get_categories( array(
                'hide_empty' => true,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ) );
            
            foreach ( $categories as $category ) {
                // Use category slug directly (WordPress ensures it's URL-safe)
                $category_slug = $category->slug;
                
                // Build URL: /easy-sitemap/posts-{slug}.xml (not posts-category-{slug})
                $url = home_url( '/easy-sitemap/posts-' . $category_slug . '.xml' );
                $lastmod = gmdate( 'c' );
                
                $xml .= self::render_sitemap_entry( $url, $lastmod );
            }
        }
        
        $xml .= self::get_xml_footer( 'index' );
        
        return $xml;
    }

    /**
     * Generate posts sitemap (single file, all posts)
     *
     * @return string Complete XML sitemap for posts
     */
    public static function generate_posts_sitemap() {
        $xml = self::get_xml_header();
        
        $args = array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_easy_xml_sitemap_exclude',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_easy_xml_sitemap_exclude',
                    'value'   => '1',
                    'compare' => '!=',
                ),
            ),
        );
        
        $posts = get_posts( $args );
        
        foreach ( $posts as $post ) {
            $url      = get_permalink( $post->ID );
            $lastmod  = self::format_lastmod( $post->post_modified_gmt );
            $priority = '0.6';
            
            $xml .= self::render_url( $url, $lastmod, $priority );
        }
        
        $xml .= self::get_xml_footer();
        
        return $xml;
    }

    /**
     * Generate posts sitemap for a specific month/year
     *
     * @param string $year  Year (YYYY)
     * @param string $month Month (MM)
     * @return string Complete XML sitemap for posts in that date
     */
    public static function generate_posts_by_date( $year, $month ) {
        $xml = self::get_xml_header();
        
        $args = array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'date_query'     => array(
                array(
                    'year'  => intval( $year ),
                    'month' => intval( $month ),
                ),
            ),
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_easy_xml_sitemap_exclude',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_easy_xml_sitemap_exclude',
                    'value'   => '1',
                    'compare' => '!=',
                ),
            ),
        );
        
        $posts = get_posts( $args );
        
        foreach ( $posts as $post ) {
            $url      = get_permalink( $post->ID );
            $lastmod  = self::format_lastmod( $post->post_modified_gmt );
            $priority = '0.6';
            
            $xml .= self::render_url( $url, $lastmod, $priority );
        }
        
        $xml .= self::get_xml_footer();
        
        return $xml;
    }

    /**
     * Generate posts sitemap for a specific category
     *
     * @param string $cat_slug Category slug
     * @return string Complete XML sitemap for posts in that category
     */
    public static function generate_posts_by_category( $cat_slug ) {
        $xml = self::get_xml_header();
        
        // Get category by slug
        $category = get_category_by_slug( $cat_slug );
        
        if ( ! $category ) {
            return $xml . self::get_xml_footer();
        }
        
        $args = array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'cat'            => $category->term_id,
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_easy_xml_sitemap_exclude',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_easy_xml_sitemap_exclude',
                    'value'   => '1',
                    'compare' => '!=',
                ),
            ),
        );
        
        $posts = get_posts( $args );
        
        foreach ( $posts as $post ) {
            $url      = get_permalink( $post->ID );
            $lastmod  = self::format_lastmod( $post->post_modified_gmt );
            $priority = '0.6';
            
            $xml .= self::render_url( $url, $lastmod, $priority );
        }
        
        $xml .= self::get_xml_footer();
        
        return $xml;
    }

    /**
     * Generate pages sitemap
     *
     * @return string Complete XML sitemap for pages
     */
    public static function generate_pages_sitemap() {
        $xml = self::get_xml_header();
        
        $args = array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_easy_xml_sitemap_exclude',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_easy_xml_sitemap_exclude',
                    'value'   => '1',
                    'compare' => '!=',
                ),
            ),
        );
        
        $pages = get_posts( $args );
        
        foreach ( $pages as $page ) {
            $url      = get_permalink( $page->ID );
            $lastmod  = self::format_lastmod( $page->post_modified_gmt );
            $priority = '0.8';
            
            $xml .= self::render_url( $url, $lastmod, $priority );
        }
        
        $xml .= self::get_xml_footer();
        
        return $xml;
    }

    /**
     * Generate tags sitemap
     *
     * @return string Complete XML sitemap for tags
     */
    public static function generate_tags_sitemap() {
        $xml = self::get_xml_header();
        
        $tags = get_tags( array(
            'hide_empty' => true,
        ) );
        
        foreach ( $tags as $tag ) {
            $url      = get_tag_link( $tag->term_id );
            $priority = '0.4';
            
            $xml .= self::render_url( $url, '', $priority );
        }
        
        $xml .= self::get_xml_footer();
        
        return $xml;
    }

    /**
     * Generate categories sitemap
     *
     * @return string Complete XML sitemap for categories
     */
    public static function generate_categories_sitemap() {
        $xml = self::get_xml_header();
        
        $categories = get_categories( array(
            'hide_empty' => true,
        ) );
        
        foreach ( $categories as $category ) {
            $url      = get_category_link( $category->term_id );
            $priority = '0.5';
            
            $xml .= self::render_url( $url, '', $priority );
        }
        
        $xml .= self::get_xml_footer();
        
        return $xml;
    }

    /**
     * Generate general sitemap (all URLs)
     *
     * @return string Complete XML sitemap containing all URLs
     */
    public static function generate_general_sitemap() {
        $xml = self::get_xml_header();
        
        // Add homepage
        $xml .= self::render_url( home_url( '/' ), '', '1.0' );
        
        // Add posts
        $posts_args = array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_easy_xml_sitemap_exclude',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_easy_xml_sitemap_exclude',
                    'value'   => '1',
                    'compare' => '!=',
                ),
            ),
        );
        
        $posts = get_posts( $posts_args );
        
        foreach ( $posts as $post ) {
            $url      = get_permalink( $post->ID );
            $lastmod  = self::format_lastmod( $post->post_modified_gmt );
            $priority = '0.6';
            
            $xml .= self::render_url( $url, $lastmod, $priority );
        }
        
        // Add pages
        $pages_args = array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_easy_xml_sitemap_exclude',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_easy_xml_sitemap_exclude',
                    'value'   => '1',
                    'compare' => '!=',
                ),
            ),
        );
        
        $pages = get_posts( $pages_args );
        
        foreach ( $pages as $page ) {
            $url      = get_permalink( $page->ID );
            $lastmod  = self::format_lastmod( $page->post_modified_gmt );
            $priority = '0.8';
            
            $xml .= self::render_url( $url, $lastmod, $priority );
        }
        
        // Add categories
        $categories = get_categories( array(
            'hide_empty' => true,
        ) );
        
        foreach ( $categories as $category ) {
            $url      = get_category_link( $category->term_id );
            $priority = '0.5';
            
            $xml .= self::render_url( $url, '', $priority );
        }
        
        // Add tags
        $tags = get_tags( array(
            'hide_empty' => true,
        ) );
        
        foreach ( $tags as $tag ) {
            $url      = get_tag_link( $tag->term_id );
            $priority = '0.4';
            
            $xml .= self::render_url( $url, '', $priority );
        }
        
        $xml .= self::get_xml_footer();
        
        return $xml;
    }

    /**
     * Generate Google News sitemap
     *
     * @return string Complete XML sitemap for Google News
     */
    public static function generate_news_sitemap() {
        $xml = self::get_xml_header( 'news' );
        
        // News sitemap should only include posts from last 2 days
        $args = array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 1000,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'date_query'     => array(
                array(
                    'after' => '2 days ago',
                ),
            ),
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_easy_xml_sitemap_exclude',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_easy_xml_sitemap_exclude',
                    'value'   => '1',
                    'compare' => '!=',
                ),
            ),
        );
        
        $posts = get_posts( $args );
        
        foreach ( $posts as $post ) {
            $url = get_permalink( $post->ID );
            
            // Prepare news data
            $news_data = array(
                'publication_name' => get_bloginfo( 'name' ),
                'language'         => self::get_language_code(),
                'publication_date' => self::format_news_date( $post->post_date_gmt ),
                'title'            => get_the_title( $post->ID ),
            );
            
            // Get categories as genres (optional)
            $categories = get_the_category( $post->ID );
            if ( ! empty( $categories ) ) {
                $news_data['genres'] = 'Blog';
            }
            
            // Get tags as keywords (optional)
            $tags = get_the_tags( $post->ID );
            if ( ! empty( $tags ) ) {
                $keywords = array();
                foreach ( $tags as $tag ) {
                    $keywords[] = $tag->name;
                }
                $news_data['keywords'] = implode( ', ', array_slice( $keywords, 0, 10 ) );
            }
            
            $xml .= self::render_news_url( $url, $news_data );
        }
        
        $xml .= self::get_xml_footer();
        
        return $xml;
    }

    /**
     * Format last modified date for sitemap
     *
     * @param string $date_gmt Date in GMT
     * @return string Formatted date in ISO 8601
     */
    private static function format_lastmod( $date_gmt ) {
        if ( empty( $date_gmt ) || '0000-00-00 00:00:00' === $date_gmt ) {
            return '';
        }
        
        return gmdate( 'c', strtotime( $date_gmt ) );
    }

    /**
     * Format publication date for Google News
     *
     * @param string $date_gmt Date in GMT
     * @return string Formatted date in ISO 8601
     */
    private static function format_news_date( $date_gmt ) {
        if ( empty( $date_gmt ) || '0000-00-00 00:00:00' === $date_gmt ) {
            return gmdate( 'c' );
        }
        
        return gmdate( 'c', strtotime( $date_gmt ) );
    }

    /**
     * Get language code for Google News
     *
     * @return string Language code (e.g., 'en', 'es')
     */
    private static function get_language_code() {
        $locale = get_locale();
        
        // Extract language code from locale (e.g., 'en_US' -> 'en')
        $language = substr( $locale, 0, 2 );
        
        return $language;
    }
}