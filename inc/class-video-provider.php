<?php

class Easy_XML_Sitemap_Video_Provider {

    public static function has_video( $post ) {
        return has_block( 'core/video', $post->post_content );
    }
}
