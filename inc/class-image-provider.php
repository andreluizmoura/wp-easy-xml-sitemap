<?php

class Easy_XML_Sitemap_Image_Provider {

    public static function get_images_for_post( $post_id ) {
        $images = [];

        if ( has_post_thumbnail( $post_id ) ) {
            $images[] = wp_get_attachment_url( get_post_thumbnail_id( $post_id ) );
        }

        return array_unique( array_filter( $images ) );
    }
}
