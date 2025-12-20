<?php
/**
 * XML rendering for sitemaps
 *
 * @package EasyXMLSitemap
 */

namespace EasyXMLSitemap;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class XML_Renderer {

    /**
     * Keeps track of last generated count for stats.
     *
     * @var int
     */
    private static $last_count = 0;

    public static function get_last_count() {
        return (int) self::$last_count;
    }

    private static function set_last_count( $n ) {
        self::$last_count = (int) $n;
    }

    public static function get_xml_header( $type = 'standard', $with_images = false, $with_videos = false ) {
        $xsl_url        = plugins_url( 'sitemap.xsl', EASY_XML_SITEMAP_FILE );
        $generator_url  = 'https://wordpress.andremoura.com';
        $version        = EASY_XML_SITEMAP_VERSION;
        $generated_on   = current_time( 'mysql' );

        $header  = "<?xml version='1.0' encoding='UTF-8'?>";
        $header .= "<?xml-stylesheet type='text/xsl' href='" . esc_url( $xsl_url ) . "'?>\n";
        $header .= "<!-- sitemap-generator-url='" . esc_url( $generator_url ) . "' sitemap-generator-version='" . esc_attr( $version ) . "' -->\n";
        $header .= "<!-- generated-on='" . esc_attr( $generated_on ) . "' -->\n";

        if ( 'index' === $type ) {
            $header .= "<sitemapindex xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' ";
            $header .= "xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd' ";
            $header .= "xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";
            return $header;
        }

        if ( 'news' === $type ) {
            $header .= "<urlset xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' ";
            $header .= "xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd' ";
            $header .= "xmlns='http://www.sitemaps.org/schemas/sitemap/0.9' ";
            $header .= "xmlns:news='http://www.google.com/schemas/sitemap-news/0.9'>\n";
            return $header;
        }

        // Standard urlset with optional namespaces.
        $header .= "<urlset xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' ";
        $header .= "xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd' ";
        $header .= "xmlns='http://www.sitemaps.org/schemas/sitemap/0.9' ";

        if ( $with_images ) {
            $header .= "xmlns:image='http://www.google.com/schemas/sitemap-image/1.1' ";
        }
        if ( $with_videos ) {
            $header .= "xmlns:video='http://www.google.com/schemas/sitemap-video/1.1' ";
        }

        $header .= ">\n";
        return $header;
    }

    public static function get_xml_footer( $type = 'standard' ) {
        return ( 'index' === $type ) ? '</sitemapindex>' : '</urlset>';
    }

    public static function render_sitemap_entry( $url, $lastmod = '' ) {
        $xml  = "\t<sitemap>\n";
        $xml .= "\t\t<loc>" . esc_url( $url ) . "</loc>\n";
        if ( ! empty( $lastmod ) ) {
            $xml .= "\t\t<lastmod>" . esc_html( $lastmod ) . "</lastmod>\n";
        }
        $xml .= "\t</sitemap>\n";
        return $xml;
    }

    /**
     * Standard URL entry with optional image/video blocks (conservative schema).
     */
    public static function render_url( $url, $lastmod = '', $priority = '0.5', $images = array(), $videos = array() ) {
        $xml  = "\t<url>\n";
        $xml .= "\t\t<loc>" . esc_url( $url ) . "</loc>\n";

        if ( ! empty( $lastmod ) ) {
            $xml .= "\t\t<lastmod>" . esc_html( $lastmod ) . "</lastmod>\n";
        }

        if ( ! empty( $priority ) ) {
            $xml .= "\t\t<priority>" . esc_html( $priority ) . "</priority>\n";
        }

        // Images (simple: only loc).
        foreach ( $images as $img_url ) {
            $xml .= "\t\t<image:image>\n";
            $xml .= "\t\t\t<image:loc>" . esc_url( $img_url ) . "</image:loc>\n";
            $xml .= "\t\t</image:image>\n";
        }

        // Videos (conservative: include only if we have required minimums).
        foreach ( $videos as $video ) {
            if ( empty( $video['thumbnail_loc'] ) || empty( $video['title'] ) || empty( $video['description'] ) ) {
                continue;
            }
            $xml .= "\t\t<video:video>\n";
            $xml .= "\t\t\t<video:thumbnail_loc>" . esc_url( $video['thumbnail_loc'] ) . "</video:thumbnail_loc>\n";
            $xml .= "\t\t\t<video:title>" . esc_html( $video['title'] ) . "</video:title>\n";
            $xml .= "\t\t\t<video:description>" . esc_html( $video['description'] ) . "</video:description>\n";

            if ( ! empty( $video['content_loc'] ) ) {
                $xml .= "\t\t\t<video:content_loc>" . esc_url( $video['content_loc'] ) . "</video:content_loc>\n";
            } elseif ( ! empty( $video['player_loc'] ) ) {
                $xml .= "\t\t\t<video:player_loc>" . esc_url( $video['player_loc'] ) . "</video:player_loc>\n";
            }

            if ( ! empty( $video['duration'] ) ) {
                $xml .= "\t\t\t<video:duration>" . (int) $video['duration'] . "</video:duration>\n";
            }

            $xml .= "\t\t</video:video>\n";
        }

        $xml .= "\t</url>\n";
        return $xml;
    }

    public static function render_news_url( $url, $news_data ) {
        $xml  = "\t<url>\n";
        $xml .= "\t\t<loc>" . esc_url( $url ) . "</loc>\n";
        $xml .= "\t\t<news:news>\n";
        $xml .= "\t\t\t<news:publication>\n";
        $xml .= "\t\t\t\t<news:name>" . esc_html( $news_data['publication_name'] ) . "</news:name>\n";
        $xml .= "\t\t\t\t<news:language>" . esc_html( $news_data['language'] ) . "</news:language>\n";
        $xml .= "\t\t\t</news:publication>\n";

        if ( ! empty( $news_data['publication_date'] ) ) {
            $xml .= "\t\t\t<news:publication_date>" . esc_html( $news_data['publication_date'] ) . "</news:publication_date>\n";
        }
        if ( ! empty( $news_data['title'] ) ) {
            $xml .= "\t\t\t<news:title>" . esc_html( $news_data['title'] ) . "</news:title>\n";
        }
        if ( ! empty( $news_data['genres'] ) ) {
            $xml .= "\t\t\t<news:genres>" . esc_html( $news_data['genres'] ) . "</news:genres>\n";
        }
        if ( ! empty( $news_data['keywords'] ) ) {
            $xml .= "\t\t\t<news:keywords>" . esc_html( $news_data['keywords'] ) . "</news:keywords>\n";
        }

        $xml .= "\t\t</news:news>\n";
        $xml .= "\t</url>\n";
        return $xml;
    }

    private static function format_lastmod( $date ) {
        if ( empty( $date ) ) {
            return gmdate( 'c' );
        }
        $timestamp = strtotime( $date );
        if ( ! $timestamp ) {
            return gmdate( 'c' );
        }
        return gmdate( 'c', $timestamp );
    }

    private static function get_settings() {
        return get_option( 'easy_xml_sitemap_settings', array() );
    }

    private static function include_images_enabled() {
        $s = self::get_settings();
        return ! empty( $s['include_images'] );
    }

    private static function include_videos_enabled() {
        $s = self::get_settings();
        return ! empty( $s['include_videos'] );
    }

    /**
     * Basic image extraction: featured + <img src="..."> in post_content.
     */
    private static function get_images_for_post( $post_id, $post_content = '' ) {
        $images = array();

        if ( has_post_thumbnail( $post_id ) ) {
            $thumb = wp_get_attachment_url( get_post_thumbnail_id( $post_id ) );
            if ( $thumb ) {
                $images[] = $thumb;
            }
        }

        if ( ! empty( $post_content ) ) {
            if ( preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\']/i', $post_content, $m ) ) {
                foreach ( $m[1] as $src ) {
                    $images[] = $src;
                }
            }
        }

        $images = array_unique( array_filter( $images ) );

        // Keep it small (avoid giant output).
        return array_slice( $images, 0, 10 );
    }

    /**
     * Conservative video detection:
     * - YouTube/Vimeo embed detection (thumbnail derived)
     * - self-hosted <video src> or core/video block (thumbnail fallback: featured image)
     */
    private static function get_videos_for_post( $post ) {
        $videos = array();
        $title  = get_the_title( $post );
        $desc   = wp_strip_all_tags( $post->post_excerpt ? $post->post_excerpt : wp_trim_words( $post->post_content, 30 ) );

        $content = (string) $post->post_content;

        // YouTube
        if ( preg_match_all( '#(youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{6,})#', $content, $m ) ) {
            foreach ( $m[2] as $id ) {
                $videos[] = array(
                    'thumbnail_loc' => 'https://img.youtube.com/vi/' . $id . '/hqdefault.jpg',
                    'title'         => $title,
                    'description'   => $desc,
                    'player_loc'    => 'https://www.youtube.com/watch?v=' . $id,
                );
            }
        }

        // Vimeo
        if ( preg_match_all( '#vimeo\.com/([0-9]{6,})#', $content, $m2 ) ) {
            foreach ( $m2[1] as $id ) {
                // Vimeo thumbnails require API; we avoid invalid schema by using featured image fallback.
                $thumb = '';
                if ( has_post_thumbnail( $post->ID ) ) {
                    $thumb = wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) );
                }
                if ( $thumb ) {
                    $videos[] = array(
                        'thumbnail_loc' => $thumb,
                        'title'         => $title,
                        'description'   => $desc,
                        'player_loc'    => 'https://vimeo.com/' . $id,
                    );
                }
            }
        }

        // Self-hosted video src in HTML
        if ( preg_match_all( '#<video[^>]+src=["\']([^"\']+)["\']#i', $content, $m3 ) ) {
            foreach ( $m3[1] as $src ) {
                $thumb = '';
                if ( has_post_thumbnail( $post->ID ) ) {
                    $thumb = wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) );
                }
                if ( $thumb ) {
                    $videos[] = array(
                        'thumbnail_loc' => $thumb,
                        'title'         => $title,
                        'description'   => $desc,
                        'content_loc'   => $src,
                    );
                }
            }
        }

        // Limit.
        return array_slice( $videos, 0, 3 );
    }

    /**
     * ===== SITEMAP INDEX =====
     */
    public static function generate_sitemap_index() {
        $settings = self::get_settings();
        $xml      = self::get_xml_header( 'index' );
        $lastmod  = gmdate( 'c' );

        $count_entries = 0;

        // Built-in sitemaps.
        $base = array(
            'posts-index' => 'enable_posts',
            'pages'       => 'enable_pages',
            'tags'        => 'enable_tags',
            'categories'  => 'enable_categories',
            'general'     => 'enable_general',
            'news'        => 'enable_news',
        );

        foreach ( $base as $type => $key ) {
            $enabled = isset( $settings[ $key ] ) ? (bool) $settings[ $key ] : true;
            if ( $enabled ) {
                $xml .= self::render_sitemap_entry( Sitemap_Controller::get_sitemap_url( $type ), $lastmod );
                $count_entries++;
            }
        }

        // CPT sitemaps.
        $post_types = self::get_enabled_post_types();
        foreach ( $post_types as $pt ) {
            // Keep legacy posts/pages listed above; add CPT endpoints for everything enabled.
            if ( in_array( $pt, array( 'post', 'page' ), true ) ) {
                continue;
            }
            $xml .= self::render_sitemap_entry( home_url( '/easy-sitemap/' . $pt . '.xml' ), $lastmod );
            $count_entries++;
        }

        $xml .= self::get_xml_footer( 'index' );

        self::set_last_count( $count_entries );
        return $xml;
    }

    private static function get_enabled_post_types() {
        $settings = self::get_settings();

        if ( isset( $settings['post_types'] ) && is_array( $settings['post_types'] ) ) {
            $enabled = array();
            foreach ( $settings['post_types'] as $pt => $on ) {
                $pt = sanitize_key( (string) $pt );
                if ( $on && $pt && 'attachment' !== $pt ) {
                    $obj = get_post_type_object( $pt );
                    if ( $obj && ! empty( $obj->public ) ) {
                        $enabled[] = $pt;
                    }
                }
            }
            return $enabled;
        }

        // Fallback.
        return array( 'post', 'page' );
    }

    /**
     * ===== POSTS (legacy) =====
     */
    public static function generate_posts_index() {
        $settings      = self::get_settings();
        $organization  = isset( $settings['posts_organization'] ) ? $settings['posts_organization'] : 'single';

        if ( 'single' === $organization ) {
            $xml = self::generate_posts_sitemap();
            // count already set there
            return $xml;
        }

        $xml = self::get_xml_header( 'index' );
        $count_entries = 0;

        if ( 'date' === $organization ) {
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
                $year   = str_pad( $date->year, 4, '0', STR_PAD_LEFT );
                $month  = str_pad( $date->month, 2, '0', STR_PAD_LEFT );
                $url    = home_url( '/easy-sitemap/posts-' . $year . '-' . $month . '.xml' );
                $lastmod = self::format_lastmod( $date->lastmod );
                $xml .= self::render_sitemap_entry( $url, $lastmod );
                $count_entries++;
            }
        } elseif ( 'category' === $organization ) {
            $categories = get_categories(
                array(
                    'hide_empty' => true,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                )
            );

            foreach ( $categories as $category ) {
                $url = home_url( '/easy-sitemap/posts-' . $category->slug . '.xml' );
                $xml .= self::render_sitemap_entry( $url, gmdate( 'c' ) );
                $count_entries++;
            }
        } else {
            // Unknown => fallback single.
            $xml = self::generate_posts_sitemap();
            return $xml;
        }

        $xml .= self::get_xml_footer( 'index' );
        self::set_last_count( $count_entries );
        return $xml;
    }

    public static function generate_posts_sitemap() {
        return self::generate_post_type_sitemap( 'post' );
    }

    public static function generate_posts_by_date( $year, $month ) {
        $year  = absint( $year );
        $month = absint( $month );

        $settings = self::get_settings();
        $with_images = self::include_images_enabled();
        $with_videos = self::include_videos_enabled();

        $xml  = self::get_xml_header( 'standard', $with_images, $with_videos );
        $count = 0;

        $q = new \WP_Query(
            array(
                'post_type'              => 'post',
                'post_status'            => 'publish',
                'posts_per_page'         => 5000,
                'date_query'             => array(
                    array(
                        'year'  => $year,
                        'month' => $month,
                    ),
                ),
                'orderby'                => 'modified',
                'order'                  => 'DESC',
                'no_found_rows'           => true,
                'update_post_meta_cache'  => false,
                'update_post_term_cache'  => false,
            )
        );

        if ( $q->have_posts() ) {
            foreach ( $q->posts as $post ) {
                if ( Post_Meta::is_excluded( $post->ID ) ) {
                    continue;
                }
                $url     = get_permalink( $post );
                $lastmod = self::format_lastmod( $post->post_modified_gmt ? $post->post_modified_gmt : $post->post_modified );

                $images = array();
                $videos = array();

                if ( $with_images ) {
                    $images = self::get_images_for_post( $post->ID, $post->post_content );
                }
                if ( $with_videos ) {
                    $videos = self::get_videos_for_post( $post );
                }

                $xml .= self::render_url( $url, $lastmod, '0.6', $images, $videos );
                $count++;
            }
        }

        $xml .= self::get_xml_footer( 'standard' );
        self::set_last_count( $count );
        return $xml;
    }

    public static function generate_posts_by_category( $cat_slug ) {
        $cat_slug = sanitize_title( $cat_slug );
        $cat      = get_category_by_slug( $cat_slug );
        if ( ! $cat ) {
            self::set_last_count( 0 );
            return self::get_xml_header() . self::get_xml_footer();
        }

        $with_images = self::include_images_enabled();
        $with_videos = self::include_videos_enabled();

        $xml  = self::get_xml_header( 'standard', $with_images, $with_videos );
        $count = 0;

        $q = new \WP_Query(
            array(
                'post_type'              => 'post',
                'post_status'            => 'publish',
                'posts_per_page'         => 5000,
                'cat'                    => (int) $cat->term_id,
                'orderby'                => 'modified',
                'order'                  => 'DESC',
                'no_found_rows'           => true,
                'update_post_meta_cache'  => false,
                'update_post_term_cache'  => false,
            )
        );

        if ( $q->have_posts() ) {
            foreach ( $q->posts as $post ) {
                if ( Post_Meta::is_excluded( $post->ID ) ) {
                    continue;
                }
                $url     = get_permalink( $post );
                $lastmod = self::format_lastmod( $post->post_modified_gmt ? $post->post_modified_gmt : $post->post_modified );

                $images = array();
                $videos = array();

                if ( $with_images ) {
                    $images = self::get_images_for_post( $post->ID, $post->post_content );
                }
                if ( $with_videos ) {
                    $videos = self::get_videos_for_post( $post );
                }

                $xml .= self::render_url( $url, $lastmod, '0.6', $images, $videos );
                $count++;
            }
        }

        $xml .= self::get_xml_footer( 'standard' );
        self::set_last_count( $count );
        return $xml;
    }

    /**
     * ===== CPTs =====
     */
    public static function generate_post_type_index( $post_type ) {
        $post_type = sanitize_key( $post_type );
        // For now, index is a thin wrapper (single file). Kept for forward scalability.
        $xml = self::get_xml_header( 'index' );
        $lastmod = gmdate( 'c' );
        $url = home_url( '/easy-sitemap/' . $post_type . '.xml' );
        $xml .= self::render_sitemap_entry( $url, $lastmod );
        $xml .= self::get_xml_footer( 'index' );

        self::set_last_count( 1 );
        return $xml;
    }

    public static function generate_post_type_sitemap( $post_type ) {
        $post_type = sanitize_key( $post_type );

        $with_images = self::include_images_enabled();
        $with_videos = self::include_videos_enabled();

        $xml  = self::get_xml_header( 'standard', $with_images, $with_videos );
        $count = 0;

        $q = new \WP_Query(
            array(
                'post_type'              => $post_type,
                'post_status'            => 'publish',
                'posts_per_page'         => 5000,
                'orderby'                => 'modified',
                'order'                  => 'DESC',
                'no_found_rows'           => true,
                'update_post_meta_cache'  => false,
                'update_post_term_cache'  => false,
            )
        );

        if ( $q->have_posts() ) {
            foreach ( $q->posts as $post ) {
                if ( Post_Meta::is_excluded( $post->ID ) ) {
                    continue;
                }
                $url     = get_permalink( $post );
                $lastmod = self::format_lastmod( $post->post_modified_gmt ? $post->post_modified_gmt : $post->post_modified );

                $images = array();
                $videos = array();

                if ( $with_images ) {
                    $images = self::get_images_for_post( $post->ID, $post->post_content );
                }
                if ( $with_videos ) {
                    $videos = self::get_videos_for_post( $post );
                }

                $xml .= self::render_url( $url, $lastmod, '0.5', $images, $videos );
                $count++;
            }
        }

        $xml .= self::get_xml_footer( 'standard' );
        self::set_last_count( $count );
        return $xml;
    }

    /**
     * ===== PAGES / TAXONOMIES / GENERAL / NEWS (legacy behavior preserved) =====
     */
    public static function generate_pages_sitemap() {
        return self::generate_post_type_sitemap( 'page' );
    }

    public static function generate_tags_sitemap() {
        $xml = self::get_xml_header( 'standard', false, false );
        $count = 0;

        $tags = get_terms(
            array(
                'taxonomy'   => 'post_tag',
                'hide_empty' => true,
            )
        );

        if ( ! is_wp_error( $tags ) ) {
            foreach ( $tags as $tag ) {
                $xml .= self::render_url( get_term_link( $tag ), gmdate( 'c' ), '0.3' );
                $count++;
            }
        }

        $xml .= self::get_xml_footer( 'standard' );
        self::set_last_count( $count );
        return $xml;
    }

    public static function generate_categories_sitemap() {
        $xml = self::get_xml_header( 'standard', false, false );
        $count = 0;

        $cats = get_terms(
            array(
                'taxonomy'   => 'category',
                'hide_empty' => true,
            )
        );

        if ( ! is_wp_error( $cats ) ) {
            foreach ( $cats as $cat ) {
                $xml .= self::render_url( get_term_link( $cat ), gmdate( 'c' ), '0.3' );
                $count++;
            }
        }

        $xml .= self::get_xml_footer( 'standard' );
        self::set_last_count( $count );
        return $xml;
    }

    public static function generate_general_sitemap() {
        $xml = self::get_xml_header( 'standard', false, false );
        $count = 0;

        // Homepage
        $xml .= self::render_url( home_url( '/' ), gmdate( 'c' ), '1.0' );
        $count++;

        // Posts + Pages only (legacy general). CPTs remain separate.
        $xml .= self::render_url( get_post_type_archive_link( 'post' ), gmdate( 'c' ), '0.6' );

        $xml .= self::get_xml_footer( 'standard' );
        self::set_last_count( $count );
        return $xml;
    }

    public static function generate_news_sitemap() {
        $xml = self::get_xml_header( 'news' );
        $count = 0;

        $since = gmdate( 'Y-m-d H:i:s', time() - ( 2 * DAY_IN_SECONDS ) );

        $q = new \WP_Query(
            array(
                'post_type'              => 'post',
                'post_status'            => 'publish',
                'posts_per_page'         => 1000,
                'date_query'             => array(
                    array(
                        'after'     => $since,
                        'inclusive' => true,
                        'column'    => 'post_date_gmt',
                    ),
                ),
                'orderby'                => 'date',
                'order'                  => 'DESC',
                'no_found_rows'           => true,
                'update_post_meta_cache'  => false,
                'update_post_term_cache'  => true,
            )
        );

        $publication_name = get_bloginfo( 'name' );
        $language         = get_bloginfo( 'language' );
        $language         = $language ? substr( $language, 0, 2 ) : 'en';

        if ( $q->have_posts() ) {
            foreach ( $q->posts as $post ) {
                if ( Post_Meta::is_excluded( $post->ID ) ) {
                    continue;
                }

                $tags = get_the_tags( $post->ID );
                $keywords = array();
                if ( $tags ) {
                    foreach ( $tags as $t ) {
                        $keywords[] = $t->name;
                        if ( count( $keywords ) >= 10 ) {
                            break;
                        }
                    }
                }

                $news_data = array(
                    'publication_name' => $publication_name,
                    'language'         => $language,
                    'publication_date' => gmdate( 'c', strtotime( $post->post_date_gmt ? $post->post_date_gmt : $post->post_date ) ),
                    'title'            => get_the_title( $post ),
                    'genres'           => 'Blog',
                    'keywords'         => implode( ', ', $keywords ),
                );

                $xml .= self::render_news_url( get_permalink( $post ), $news_data );
                $count++;
            }
        }

        $xml .= self::get_xml_footer( 'news' );
        self::set_last_count( $count );
        return $xml;
    }
}
